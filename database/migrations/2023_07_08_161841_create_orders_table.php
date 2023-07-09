<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->enum('trade_side', ['BUY','SELL']);
            $table->enum('set_currency', ['UAH','USD','EUR']);  // currency, that order-owner wants to sell or buy
            $table->integer('set_amount')->unsigned();          // amount of money, that order-owner wants to sell or buy
            $table->enum('get_currency', ['UAH','USD','EUR']);
            $table->integer('get_amount')->unsigned();
            $table->enum('order_status', ['OPENED','FILLED','CANCELED']);
            $table->integer('apply_user_id')->unsigned()->nullable()->default(null);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
