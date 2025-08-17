<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void {
        // Regions
        \App\Models\Region::firstOrCreate(['postal_code'=>'4000'],['name'=>'Brisbane']);
        // Positions
        \App\Models\Position::firstOrCreate(['name'=>'Chairperson'],['description'=>'AGM Chair']);
        // Admin user
        \App\Models\User::firstOrCreate(
            ['email'=>'superadmin@example.com'],
            ['name'=>'Super Admin', 'password_hash'=>bcrypt('ChangeMe!123'), 'role'=>'SuperAdmin']
        );
    }
}
