<?php

namespace Database\Seeders;

use App\Models\Matter;
use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        // Get all matters AND preload their assigned users
        $matters = Matter::with('users')->get();

        foreach ($matters as $matter) {
            // Create 2 events per matter
            for ($i = 1; $i <= 2; $i++) {
                $billingType = fake()->randomElement(['hourly', 'fixed_rate', 'administrative']);
                $fixedFeeAmount = $billingType === 'fixed_rate' ? fake()->randomFloat(2, 10000, 100000) : null;
            
                $event = Event::create([
                    'matter_id' => $matter->id,
                    'title' => fake()->randomElement(['Court Hearing', 'Client Meeting', 'Mediation', 'Deposition']),
                    'scheduled_at' => fake()->dateTimeBetween('now', '+3 months'),
                    'location' => fake()->randomElement(['RTC Branch 1', 'Conference Room A', 'Zoom', 'SEC Main Office']),
                    'status' => 'Scheduled',
                    'billing_type' => $billingType,
                    'fixed_fee_amount' => $fixedFeeAmount,
                ]);

                // THE CONSTRAINT: Grab 1 or 2 random users ONLY from this specific matter's team
                $assignedLawyers = $matter->users->random(rand(1, 2))->pluck('id');
                
                // Attach them to the pivot table
                $event->users()->attach($assignedLawyers);
            }
        }
    }
}