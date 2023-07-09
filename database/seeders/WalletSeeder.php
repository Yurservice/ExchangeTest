<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        
        $startDate = '2023-01-01';
        $endDate = '2023-07-07';
        $startDateObj = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);

        for ($i = 0; $i < 2; $i++) {
            if($i == 0) {
                for ($j = 0; $j < 2; $j++) {
                    DB::table('wallets')->insert([
                        'user_id' => 1,
                        'currency' => $j == 0 ? 'UAH' : 'USD',
                        'amount' => $j == 0 ? 500 : 100,
                        'blocked' => $j == 0 ? 0 : 50,
                        'created_at' => $faker->dateTimeBetween($startDate, $endDate),
                        'updated_at' => now(),
                    ]);
                }
            }
            if($i == 1) {
                for ($j = 0; $j < 3; $j++) {
                    DB::table('wallets')->insert([
                        'user_id' => 2,
                        'currency' => $j == 0 ? 'UAH' : ($j == 1 ? 'USD' : 'EUR'),
                        'amount' => $j == 0 ? 2500 : ($j == 1 ? 10 : 400),
                        'created_at' => $faker->dateTimeBetween($startDate, $endDate),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
