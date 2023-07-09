<?php

namespace App\Http\Controllers;
use App\Models\Order;
use App\Http\Resources\OrderResource;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    private $fee = 0.02;
    private $feeCoeff = 1.02;
    
    public function index(Request $request)
    {
        // This method returns collection of all opened orders, except auth-user orders
        
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'numeric'], // It is considered to be an auth-user in the future, but at present the method recieves a 'user_id' parameter
        ]);
    
        if ($validator->fails()) {
            return ['message' => 'You did not pass all the attributes or passed them incorrectly'];
        }

        $orders = Order::whereNot('user_id', $request->user_id)
            ->where('order_status','OPENED')
            ->with('user:id,name')
            ->get();

        return OrderResource::collection($orders);
    }

    public function store(Request $request)
    {
        
        // This method creates a new order
        
        $validator = Validator::make($request->all(), [
            'trade_side' => ['required',Rule::in(['BUY', 'SELL']),],
            'set_currency' => ['required',Rule::in(['UAH','USD','EUR']),],
			'set_amount' => ['required', 'numeric', 'between:10,10000'], 
            'get_currency' => ['required',Rule::in(['UAH','USD','EUR']),],
            'get_amount' => ['required', 'numeric', 'between:10,10000'],
        ]);
    
        if ($validator->fails()) {
            return ['message' => 'You did not pass all the attributes or passed them incorrectly'];
        }

        $user = User::with('wallets')->find($request->user_id); // get all user`s wallets
        
        $isset_wallet_amount = false; // flag if needed wallet exists and if it is enouph money to create an order 
        foreach($user->wallets as $wallet) {
           if($wallet->currency == ($request->trade_side == 'SELL' ? $request->set_currency : $request->get_currency)) {
                if($wallet->amount-$wallet->blocked >= ($request->trade_side == 'SELL' ? $request->set_amount : $request->get_amount)) $isset_wallet_amount = true; // set flag if enouph money to create an order 
                $wallet_id_for_update = $wallet->id; // save wallet id for further updating
            }
        }
        if($isset_wallet_amount == false) return ['message' => 'you do not have enough funds to create an order'];
        
        try {
            DB::transaction(function () use ($request, $wallet_id_for_update) {
                // creating an order
                $order = Order::create([
                    'user_id' => $request->user_id,
                    'trade_side' => $request->trade_side,
                    'set_currency' => $request->set_currency,
                    'set_amount' => $request->set_amount,
                    'get_currency' => $request->get_currency,
                    'get_amount' => $request->get_amount,
                    'order_status' => 'OPENED',
                ]);

                // block amount of money in the user`s wallet
                Wallet::where('id', $wallet_id_for_update)
                    ->update(['blocked' => DB::raw('blocked + ' . ($request->trade_side == 'SELL' ? $request->set_amount : $request->get_amount))]);
            });  
            return ['message' => 'Order successfully created'];
        } catch (QueryException $e) {
            return ['message' => 'Order hasn`t been created due to error:'  . $e->getMessage()];
        }

    }

    public function apply(Request $request)
    {
        
        // This method handels applying of the order
        
        $validator = Validator::make($request->all(), [  // recieving of order id and user id of the user, that applies the order (auth-user in the future)
            'order_id' => ['required', 'numeric'],
            'user_id' => ['required', 'numeric'],
        ]);
    
        if ($validator->fails()) {
            return ['message' => 'You did not pass all the attributes or passed them incorrectly'];
        }
        
        // this query gets order fields with order-owner particular wallet amount and with order-applier particular wallet amount
        $order = Order::leftJoin('wallets AS order_wallet', function ($join) {
            $join->on('order_wallet.user_id', '=', 'orders.user_id')
                ->where(function ($query) {
                    $query->where(function ($subquery) {
                        $subquery->where('orders.trade_side', '=', 'SELL')              // if order direction is 'SELL' we get order-owner wallet amount of currency, that owner want`s to sell
                        ->on('order_wallet.currency', '=', 'orders.set_currency');
                    })->orWhere(function ($subquery) {
                        $subquery->where('orders.trade_side', '=', 'BUY')               // and vice versa
                        ->on('order_wallet.currency', '=', 'orders.get_currency');
                    });
                });
        })
        ->leftJoin('wallets AS apply_wallet', function ($join) use ($request) {
            $join->on('apply_wallet.user_id', '=', DB::raw($request->user_id))
                ->where(function ($query) {
                    $query->where(function ($subquery) {
                        $subquery->where('orders.trade_side', '=', 'SELL')              // if order direction is 'SELL' we get order-applier wallet amount of currency, that owner needs to pay
                        ->on('apply_wallet.currency', '=', 'orders.get_currency');
                    })->orWhere(function ($subquery) {
                        $subquery->where('orders.trade_side', '=', 'BUY')               // and vice versa
                        ->on('apply_wallet.currency', '=', 'orders.set_currency');
                    });
                });
        })
        ->select('orders.*', 'order_wallet.id AS order_wallet_id', 'order_wallet.amount AS current_order_amount',
            'apply_wallet.id AS apply_wallet_id', 'apply_wallet.amount AS current_apply_amount')
        ->find($request->order_id);

        if(!$order||$order->order_status!='OPENED') return ['This order doesn`t exist!'];
        elseif($order->current_order_amount ===null||$order->current_apply_amount ===null) return ['Some error has occurred!'];
        else {
            if(($order->trade_side == 'SELL'&&$order->current_order_amount >= $order->set_amount&&$order->current_apply_amount >= $order->get_amount*$this->feeCoeff)   // we check if order-owner and order-applier have enouph funds to complete a transaction, including fee
                ||($order->trade_side == 'BUY'&&$order->current_order_amount >= $order->get_amount*$this->feeCoeff&&$order->current_apply_amount >= $order->set_amount)
            ) 
            {
                try {
                    DB::transaction(function () use ($order, $request) {
                        
                        // update order owner wallets
                        Wallet::where('user_id', $order->user_id)
                            ->where('currency', $order->trade_side == 'SELL' ? $order->set_currency : $order->get_currency)
                            ->update(['amount' => $order->current_order_amount - ($order->trade_side == 'SELL' ? $order->set_amount : $order->get_amount*$this->feeCoeff),'blocked' => DB::raw('blocked - ' . ($order->trade_side == 'SELL' ? $order->set_amount : $order->get_amount))]);
                        Wallet::where('user_id', $order->user_id)
                            ->where('currency', $order->trade_side == 'SELL' ? $order->get_currency : $order->set_currency)
                            ->update(['amount' => DB::raw('amount + ' . ($order->trade_side == 'SELL' ? $order->get_amount : $order->set_amount))]);
                        
                        // update apllyer wallets
                        Wallet::where('user_id', $request->user_id)
                            ->where('currency', $order->trade_side == 'SELL' ? $order->get_currency : $order->set_currency)
                            ->update(['amount' => $order->current_apply_amount - ($order->trade_side == 'SELL' ? $order->get_amount*$this->feeCoeff : $order->set_amount)]);
                        Wallet::where('user_id', $request->user_id)
                            ->where('currency', $order->trade_side == 'SELL' ? $order->set_currency : $order->get_currency)
                            ->update(['amount' => DB::raw('amount + ' . ($order->trade_side == 'SELL' ? $order->set_amount : $order->get_amount))]);
                        
                        // update order status
                        Order::where('id', $request->order_id)->update(['order_status' => 'FILLED','apply_user_id' => $request->user_id]);
                    });
                    return ['message' => 'Transaction completed successfully'];
                } catch (QueryException $e) {
                    return ['message' => 'Trade failed due to error:'  . $e->getMessage()];
                }
            }
            else return ['message' => 'You don`t have enough money to fulfill the order!'];
        }
    }

    public function getFeeSum(Request $request) {
        
        // This method returns the amount of commissions for filled orders for a given date period
        
        $validator = Validator::make($request->all(), [
            'user_id' => ['required', 'numeric'],
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
        ]);
    
        if ($validator->fails()) {
            return ['message' => 'You did not pass all the attributes or passed them incorrectly'];
        }
        
        $date_to = Carbon::parse($request->date_to)->addDay(); // we need to add one day, because the data won`t be returned for today

        $orders = Order::where(function ($query) use ($request) { // get all filled orders, where user paied fee
            $query->where('trade_side', 'SELL')
                ->where('apply_user_id', $request->user_id);
            })
            ->orWhere(function ($query) use ($request) {
                $query->where('trade_side', 'BUY')
                    ->where('user_id', $request->user_id);
            })
            ->get();
        
        $UAH = 0;
        $USD = 0;
        $EUR = 0;

        if($orders) {
            foreach($orders as $order) {
                if($order->trade_side =='SELL') ${$order->get_currency} += $order->get_amount*$this->fee; // Buyer pays fee only from buying currency, so we plus buying currency fee to the particular variable
                if($order->trade_side =='BUY') ${$order->set_currency} += $order->set_amount*$this->fee;
            }
        }
        $data = [
            ['currency' => 'UAH', 'amount' => $UAH],
            ['currency' => 'USD', 'amount' => $USD],
            ['currency' => 'EUR', 'amount' => $EUR]
        ];
        return json_encode($data);
    }
}
