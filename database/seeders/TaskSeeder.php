<?php

namespace Database\Seeders;

use App\Models\Matter;
use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $matters = Matter::with('users')->get();

        foreach ($matters as $matter) {
            // Create 2 tasks per matter
            for ($i = 1; $i <= 2; $i++) {
                $billingType = fake()->randomElement(['hourly', 'fixed_rate', 'administrative']);
                $fixedFeeAmount = $billingType === 'fixed_rate' ? fake()->randomFloat(2, 5000, 50000) : null;
            
                $task = Task::create([
                    'matter_id' => $matter->id,
                    'title' => fake()->randomElement(['Draft Pleading', 'Review Evidence', 'File Motion', 'Send Demand Letter']),
                    'deadline' => fake()->dateTimeBetween('now', '+1 month'),
                    'status' => 'Pending',
                    'billing_type' => $billingType,
                    'fixed_fee_amount' => $fixedFeeAmount,
                ]);

                // THE CONSTRAINT: Grab 1 or 2 random users ONLY from this specific matter's team
                $assignedLawyers = $matter->users->random(rand(1, 2))->pluck('id');
                
                $task->users()->attach($assignedLawyers);
            }
        }
    }
}