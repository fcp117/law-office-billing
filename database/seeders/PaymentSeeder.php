<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    public function run()
    {
        // Grab all invoices that aren't drafts
        $invoices = Invoice::where('status', '!=', 'Draft')->get();

        foreach ($invoices as $invoice) {
            // 70% chance the invoice has been paid
            if (rand(1, 100) <= 70) {
                
                // Simulate a payment made a few days after the invoice issue date
                $paymentDate = Carbon::parse($invoice->issue_date)->addDays(rand(2, 14));

                // Simulate a client paying in full
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total_amount,
                    'payment_date' => $paymentDate,
                    'payment_method' => 'Bank Transfer',
                    'reference_number' => 'REF-' . rand(100000, 999999)
                ]);

                // Automatically update the invoice status
                $invoice->update(['status' => 'Paid']);
            }
        }
    }
}