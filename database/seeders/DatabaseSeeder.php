<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\ClientSeeder;
use Database\Seeders\MatterSeeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'fcpagdanganan.ocbocc@gmail.com')],
            [
                'name' => env('ADMIN_NAME', 'System Admin'),
                'password' => Hash::make(env('ADMIN_PASSWORD', '@Asdf123')),
                'role' => 'Admin',
            ]
        );

        $this->call([
            UserSeeder::class,
            ClientSeeder::class,
            MatterSeeder::class,
            EventSeeder::class,
            TaskSeeder::class,
            TimeEntrySeeder::class,
            InvoiceSeeder::class,
            PaymentSeeder::class,
        ]);
    }
}
