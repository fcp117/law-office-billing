<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Task;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TimeEntrySeeder extends Seeder
{
    public function run(): void
    {
        // Load all Events and Tasks, including their parent matters and assigned users
        $events = Event::with('matter.users')->get();
        $tasks = Task::with('matter.users')->get();

        $this->seedEntriesFor($events, 'Event');
        $this->seedEntriesFor($tasks, 'Task');
    }

    private function seedEntriesFor($items, $type)
    {
        foreach ($items as $item) {
            // Ensure the matter actually has assigned lawyers to pull from
            if ($item->matter->users->isEmpty()) {
                continue;
            }

            // Create exactly 3 time entries per item
            for ($i = 0; $i < 3; $i++) {
                // Pick a random lawyer assigned to this specific case
                $lawyer = $item->matter->users->random();
                
                // Generate realistic timestamps
                $startTime = Carbon::instance(fake()->dateTimeBetween('-2 months', 'now'));
                $hoursLogged = fake()->randomFloat(2, 0.5, 4.0); // Between 30 mins and 4 hours
                $endTime = (clone $startTime)->addMinutes($hoursLogged * 60);

                // Calculate the final billable amount
                $rate = $lawyer->standard_hourly_rate ?? 250.00;
                $amount = round($hoursLogged * $rate, 2);

                TimeEntry::create([
                    'user_id' => $lawyer->id,
                    'matter_id' => $item->matter_id,
                    
                    // Attach polymorphically to the specific Task or Event
                    'billable_id' => $item->id,
                    'billable_type' => $type === 'Event' ? Event::class : Task::class,
                    
                    'description' => fake()->sentence(),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'hours' => $hoursLogged,
                    'hourly_rate' => $rate,
                    'amount' => $amount,
                    
                    // Left null so these entries show up as "Unbilled" in your dashboard
                    'invoice_id' => null, 
                    
                    'created_by' => $lawyer->id,
                    'modified_by' => null,
                ]);
            }
        }
    }
}