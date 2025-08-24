<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('positions')->insert([
            [
                'name' => 'President',
                'description' => 'Leads the organization',
                'region_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Vice President',
                'description' => 'Assists the president',
                'region_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Secretary',
                'description' => 'Maintains records',
                'region_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Treasurer',
                'description' => 'Handles finances',
                'region_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
