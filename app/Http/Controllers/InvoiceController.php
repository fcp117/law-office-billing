<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Matter;
use App\Models\Invoice;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    /**
     * Generate an invoice for unbilled time entries belonging to a Matter.
     */
    public function store(Request $request, Matter $matter)
    {
        $request->validate([
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'remarks' => 'nullable|string',
        ]);

        $unbilledEntries = $matter->timeEntries()->with('billable')->whereNull('invoice_id')->get();

        if ($unbilledEntries->isEmpty()) {
            return redirect()->back()->with('error', 'No unbilled time entries found for this matter.');
        }

        $hourlyTotal = 0;
        $fixedRateItems = collect();
        $entriesToInvoice = collect(); // Tracks only the time entries that are eligible to be billed right now

        foreach ($unbilledEntries as $entry) {
            $billable = $entry->billable;

            if ($billable && $billable->billing_type === 'fixed_rate') {
                // Only process fixed-rate time entries if the parent Task or Event is completed
                if (strtolower($billable->status) === 'completed') {
                    $key = class_basename($billable) . '-' . $billable->id;
                    
                    if (!$fixedRateItems->has($key)) {
                        $fixedRateItems->put($key, $billable->fixed_fee_amount);
                    }
                    
                    $entriesToInvoice->push($entry);
                }
                // If not completed, it is intentionally skipped and left in the unbilled queue
            } elseif ($billable && $billable->billing_type === 'administrative') {
                // Attach administrative time to the invoice to clear it from the queue, 
                // but it contributes $0.00 to the totals.
                $entriesToInvoice->push($entry);
            } else {
                // Standard hourly billing
                $hourlyTotal += $entry->amount;
                $entriesToInvoice->push($entry);
            }
        }

        if ($entriesToInvoice->isEmpty()) {
            return redirect()->back()->with('error', 'No time entries are ready for invoicing. Fixed-rate tasks must be marked as Completed.');
        }

        $fixedRateTotal = $fixedRateItems->sum();
        $subtotalFees = $hourlyTotal + $fixedRateTotal;

        $vatAmount = $subtotalFees * 0.12;
        $totalAmount = $subtotalFees + $vatAmount;
        
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);

        DB::transaction(function () use ($request, $matter, $entriesToInvoice, $totalAmount, $invoiceNumber) {
            
            $invoice = Invoice::create([
                'matter_id' => $matter->id,
                'invoice_number' => $invoiceNumber,
                'issue_date' => Carbon::parse($request->issue_date),
                'due_date' => Carbon::parse($request->due_date),
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'remarks' => $request->remarks,
                'created_by' => $request->user()->id,
            ]);

            // Update only the eligible entries, leaving incomplete fixed-rate entries null
            foreach ($entriesToInvoice as $entry) {
                $entry->update([
                    'invoice_id' => $invoice->id,
                    'modified_by' => $request->user()->id,
                ]);
            }
        });

        return redirect()->back()->with('success', 'Invoice generated successfully.');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['matter.client', 'timeEntries.user', 'creator']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice
        ]);
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['matter.client', 'timeEntries', 'creator']);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));

        return $pdf->download('Invoice-' . $invoice->invoice_number . '.pdf');
    }
}