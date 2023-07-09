<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/* Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
}); */

Route::group(['controller' => OrderController::class], function() {
    Route::get('orders', 'index')->name('orders');      // get all opened orders
    Route::get('store', 'store')->name('order_store');  // crate an order
    Route::get('apply', 'apply')->name('order_apply');  // apply an order
    Route::get('get_fee_sum', 'getFeeSum')->name('fee_sum');  // get fee sum for some period
});