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
            'settlement_judgment' => 'nullable|numeric'
        ]);

        // 1. Enforce "Ripe for Billing" rules
        if (in_array($matter->billing_type, ['fixed_rate', 'contingency']) && strtolower($matter->status) !== 'completed') {
            return redirect()->back()->with('error', 'Fixed Rate and Contingency matters can only be billed once marked as Completed.');
        }

        // Check if a fixed_rate or contingency matter was already billed to prevent double-billing
        if (in_array($matter->billing_type, ['fixed_rate', 'contingency']) && $matter->invoices()->exists()) {
            return redirect()->back()->with('error', 'This ' . str_replace('_', ' ', $matter->billing_type) . ' matter has already been billed.');
        }

        // Get unbilled time entries to attach to the invoice (so they are cleared from the queue)
        $unbilledEntries = $matter->timeEntries()->whereNull('invoice_id')->get();
        $subtotalFees = 0;

        // 2. Calculate Subtotal based on Matter Billing Type
        if ($matter->billing_type === 'fixed_rate') {
            // Bill the flat fee defined on the Matter
            $subtotalFees = $matter->fixed_fee_amount ?? 0;
            
        } elseif ($matter->billing_type === 'contingency') {
            // Bill the contingency percentage against the final settlement judgment
            // Ensure settlement_judgment and contingency_percentage are provided either in request or database
            $settlement = $request->settlement_judgment ?? $matter->settlement_judgment ?? 0;
            $percentage = $matter->contingency_percentage ?? 30; // Default to 30% if undefined
            
            $subtotalFees = $settlement * ($percentage / 100);
            
        } elseif ($matter->billing_type === 'hourly') {
            // Sum up the active time entries for hourly matters
            if ($unbilledEntries->isEmpty()) {
                return redirect()->back()->with('error', 'No unbilled time entries found for this hourly matter.');
            }
            $subtotalFees = $unbilledEntries->sum('amount');
            
        } elseif ($matter->billing_type === 'administrative') {
            // Administrative matters do not generate revenue
            $subtotalFees = 0;
        }

        // 3. Finalize Calculations
        $vatAmount = $subtotalFees * 0.12; // 12% VAT
        $totalAmount = $subtotalFees + $vatAmount;
        
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);

        // 4. Create the Invoice and Link Entries
        DB::transaction(function () use ($request, $matter, $unbilledEntries, $totalAmount, $invoiceNumber) {
            
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

            // Clear all unbilled entries from the queue by associating them with this invoice
            foreach ($unbilledEntries as $entry) {
                $entry->update([
                    'invoice_id' => $invoice->id,
                    'modified_by' => $request->user()->id,
                ]);
            }
            
            // If contingency, update the Matter with the final settlement amount used
            if ($matter->billing_type === 'contingency' && $request->filled('settlement_judgment')) {
                $matter->update([
                    'settlement_judgment' => $request->settlement_judgment
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