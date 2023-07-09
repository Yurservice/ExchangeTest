<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'trade_side' => $this->trade_side,
            'set_currency' => $this->set_currency,
			'set_amount' => $this->trade_side == 'BUY' ? $this->set_amount*1.02 : $this->set_amount, // return buyer prise with fee
            'get_currency' => $this->get_currency,
            'get_amount' => $this->trade_side == 'SELL' ? $this->get_amount*1.02 : $this->get_amount, // return buyer prise with fee
            'created_at' => Carbon::parse($this->created_at)->format('d.m.Y h:m'),
        ];
    }
}
