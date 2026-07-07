<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Matter;
use App\Models\Invoice;
use App\Models\TimeEntry;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run()
    {
        // Find matters that actually have time entries
        $matters = \App\Models\Matter::has('timeEntries')->get();
        
        // Create a counter for our invoice numbers
        $invoiceCount = 1;

        foreach ($matters as $matter) {
            // Grab some unbilled time entries for this matter
            $unbilledEntries = $matter->timeEntries()->whereNull('invoice_id')->get();

            if ($unbilledEntries->count() > 0) {
                // Calculate the total amount
                $totalAmount = $unbilledEntries->sum('amount');
                
                // Simulate generating this invoice sometime last month
                $issueDate = \Carbon\Carbon::now()->subDays(rand(15, 45));

                // Create the Invoice WITH the missing invoice_number
                $invoice = \App\Models\Invoice::create([
                    'invoice_number' => 'INV-' . str_pad($invoiceCount, 4, '0', STR_PAD_LEFT),
                    'matter_id' => $matter->id,
                    'total_amount' => $totalAmount,
                    'issue_date' => $issueDate,
                    'status' => 'Sent', 
                    'due_date' => (clone $issueDate)->addDays(30)
                ]);

                // Update the Time Entries to link them to this new Invoice
                \App\Models\TimeEntry::whereIn('id', $unbilledEntries->pluck('id'))
                    ->update(['invoice_id' => $invoice->id]);
                    
                // Increment the counter for the next invoice
                $invoiceCount++;
            }
        }
    }
}
