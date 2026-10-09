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
        // 1. Validate incoming invoice details
        $request->validate([
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'remarks' => 'nullable|string',
        ]);

        // 2. Retrieve unbilled time entries (where invoice_id is null)
        $unbilledEntries = $matter->timeEntries()->whereNull('invoice_id')->get();

        if ($unbilledEntries->isEmpty()) {
            return redirect()->back()->with('error', 'No billable time entries found for this matter.');
        }

        // 3. Calculate total from the time entries
        $totalAmount = $unbilledEntries->sum('amount');
        
        // Generate a simple invoice number (you can replace this with a sequence generator)
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);

        // 4. Use a database transaction to ensure data integrity
        DB::transaction(function () use ($request, $matter, $unbilledEntries, $totalAmount, $invoiceNumber) {
            
            // Create the Invoice record
            $invoice = Invoice::create([
                'matter_id' => $matter->id,
                'invoice_number' => $invoiceNumber,
                'issue_date' => Carbon::parse($request->issue_date),
                'due_date' => Carbon::parse($request->due_date),
                'total_amount' => $totalAmount,
                'status' => 'draft', // Default status
                'remarks' => $request->remarks,
                'created_by' => $request->user()->id,
            ]);

            // Associate the unbilled Time Entries with the newly created Invoice
            foreach ($unbilledEntries as $entry) {
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
        // Eager load relationships defined in the models
        $invoice->load(['matter.client', 'timeEntries.user', 'creator']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice
        ]);
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['matter', 'timeEntries', 'creator']);

        // Load a specific Blade view for the PDF layout
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));

        return $pdf->download('Invoice-' . $invoice->invoice_number . '.pdf');
    }
}