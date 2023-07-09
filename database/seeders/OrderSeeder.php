<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        $startDate = '2023-07-01';
        $endDate = '2023-07-07';
        $startDateObj = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);

        DB::table('orders')->insert([
            'user_id' => 1,
            'trade_side' => 'SELL',
            'set_currency' => 'USD',
            'set_amount' => 50,
            'get_currency' => 'UAH',
            'get_amount' => 2000,
            'order_status' => 'OPENED',
            'created_at' => $faker->dateTimeBetween($startDate, $endDate),
            'updated_at' => now(),
        ]);
    }
}
