<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class UserSeeder extends Seeder
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
            DB::table('users')->insert([
                'name' => $faker->firstName,
                'email' => $faker->unique()->safeEmail,
                'created_at' => $faker->dateTimeBetween($startDate, $endDate),
                'updated_at' => now(),
            ]);
        }
    }
}
