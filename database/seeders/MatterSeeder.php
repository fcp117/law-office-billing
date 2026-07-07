<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Matter;
use App\Models\User;
use Illuminate\Database\Seeder;

class MatterSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::all();
        
        // 1. Separate the users into their specific role buckets
        $partners = User::where('role', 'Partner')->get();
        $associates = User::where('role', 'Associate')->get();
        $paralegals = User::where('role', 'Paralegal')->get();

        // Safety check
        if ($clients->isEmpty() || $partners->isEmpty() || $associates->isEmpty() || $paralegals->isEmpty()) {
            $this->command->warn('Ensure you have seeded Clients and all User roles before running this.');
            return;
        }

        foreach ($clients as $client) {
            for ($i = 1; $i <= 2; $i++) {
                
                // 2. Create the Matter (same as before)
                $matter = Matter::create([
                    'name' => 'Matter 0' . $i . ' - ' . $client->name,
                    'description' => fake()->paragraph(),
                    'practice_area' => fake()->randomElement(['Corporate', 'Litigation', 'Labor', 'Intellectual Property']),
                    'type' => fake()->randomElement(['Retainer', 'Project', 'Case']),
                    
                    'client_id' => $client->id,
                    'authority' => fake()->randomElement(['RTC', 'MTC', 'SEC']),
                    
                    'party_represented' => $client->name,
                    'party_represented_role' => fake()->randomElement(['Plaintiff', 'Defendant', 'Applicant', 'Oppositor']),
                    'party_adverse' => fake()->company(),
                    'party_adverse_role' => fake()->randomElement(['Defendant', 'Plaintiff', 'Respondent', 'Intervenor']),
                    
                    'status' => 'Active',
                    'start_date' => fake()->dateTimeBetween('-3 years', '-1 year'),
                    'closing_date' => null, 
                    'remarks' => fake()->sentence(),
                    
                    // Any random user can be the creator of the record
                    'created_by' => User::inRandomOrder()->first()->id, 
                    'modified_by' => null,
                ]);

                // 3. THE PIVOT TABLE SEEDING
                // Grab one random user from each role bucket
                $assignedPartner = $partners->random();
                $assignedAssociate = $associates->random();
                $assignedParalegal = $paralegals->random();

                // Attach all three to the matter at once, injecting their exact role into the pivot table
                $matter->users()->attach([
                    $assignedPartner->id => ['assignment_role' => $assignedPartner->role],
                    $assignedAssociate->id => ['assignment_role' => $assignedAssociate->role],
                    $assignedParalegal->id => ['assignment_role' => $assignedParalegal->role],
                ]);
            }
        }
    }
}