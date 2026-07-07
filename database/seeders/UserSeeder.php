<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. The Partners
        User::create([
            'name' => 'Jane Managing',
            'email' => 'jane.partner@obocc.com',
            'password' => Hash::make('password'),
            'role' => 'Partner',
            'standard_hourly_rate' => 500.00,
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Michael Senior',
            'email' => 'michael.partner@obocc.com',
            'password' => Hash::make('password'),
            'role' => 'Partner',
            'standard_hourly_rate' => 450.00,
            'is_active' => true,
        ]);

        // 2. The Associates
        User::create([
            'name' => 'John Junior',
            'email' => 'john.associate@obocc.com',
            'password' => Hash::make('password'),
            'role' => 'Associate',
            'standard_hourly_rate' => 250.00,
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Alan Ambitious',
            'email' => 'alan.associate@obocc.com',
            'password' => Hash::make('password'),
            'role' => 'Associate',
            'standard_hourly_rate' => 250.00,
            'is_active' => true,
        ]);

        // 3. The Paralegals
        User::create([
            'name' => 'Sarah Support',
            'email' => 'sarah.paralegal@obocc.com',
            'password' => Hash::make('password'),
            'role' => 'Paralegal',
            'standard_hourly_rate' => 120.00,
            'is_active' => true,
        ]);

        User::create([
            'name' => 'David Detail',
            'email' => 'david.paralegal@obocc.com',
            'password' => Hash::make('password'),
            'role' => 'Paralegal',
            'standard_hourly_rate' => 120.00,
            'is_active' => true,
        ]);
    }
}

