<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin
        User::updateOrCreate(
            ['email' => env('SEED_SUPERADMIN_EMAIL', 'superadmin@example.com')],
            [
                'name' => 'Super Admin',
                'password' => Hash::make(env('SEED_SUPERADMIN_PASSWORD', 'ChangeMe!123')),
                'role' => 'SuperAdmin',
            ]
        );

        // Admin
        User::updateOrCreate(
            ['email' => env('SEED_ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => 'Admin',
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'ChangeMe!123')),
                'role' => 'Admin',
            ]
        );
    }
}
