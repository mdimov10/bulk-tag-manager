<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plans')->insert([
            [
                'type' => 'RECURRING',
                'name' => 'Dual Pricing Compliance',
                'price' => 8.99,
                'interval' => 'EVERY_30_DAYS',
                'test' => config('app.env') === 'local',
                'capped_amount' => null,
                'terms' => 'Show dual prices (BGN/EUR) and stay legally compliant during Euro transition.',
                'trial_days' => 3,
                'on_install' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
