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
        abort_unless($request->user()->role === 'Partner', 403, 'Only Partners can generate invoices.');

        $request->validate([
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'remarks' => 'nullable|string',
            'settlement_judgment' => 'nullable|numeric',
        ]);

        $billingType = strtolower($matter->billing_type);
        $status = strtolower($matter->status);

        // 1. Enforce "Ripe for Billing" rules
        if (in_array($billingType, ['fixed_rate', 'contingency']) && $status !== 'completed') {
            return back()->withErrors(['message' => 'Fixed Rate and Contingency matters can only be billed once marked as "Completed".']);
        }

        if (in_array($billingType, ['fixed_rate', 'contingency']) && $matter->invoices()->count() > 0) {
            return back()->withErrors(['message' => 'This matter has already been billed.']);
        }

        $subtotalFees = 0;
        $tierKey = 'standard';
        $billingPercentage = 100.00;

        // 2. Calculate Subtotal based on Matter Billing Type
        if ($billingType === 'fixed_rate') {
            $subtotalFees = (float) $matter->fixed_fee_amount;

            if ($subtotalFees <= 0) {
                return back()->withErrors(['message' => 'Cannot bill: Fixed fee amount is zero or not set.']);
            }
        } elseif ($billingType === 'contingency') {
            $settlement = (float) ($request->settlement_judgment ?? $matter->settlement_judgment ?? 0);
            $percentage = (float) ($matter->contingency_percentage ?? 0);

            if ($settlement <= 0) {
                return back()->withErrors(['message' => 'Cannot bill: A Settlement or Judgment amount is required.']);
            }
            if ($percentage <= 0) {
                return back()->withErrors(['message' => 'Cannot bill: Contingency percentage is not set.']);
            }

            $subtotalFees = $settlement * ($percentage / 100);
        } elseif ($billingType === 'hourly') {
            $unbilledEntries = $matter->timeEntries()->whereNull('invoice_id')->get();
            if ($unbilledEntries->isEmpty()) {
                return back()->withErrors(['message' => 'Cannot bill: No unbilled time entries found.']);
            }

            if ($unbilledEntries->contains(fn ($entry) => $entry->approved_hours === null)) {
                return back()->withErrors(['message' => 'Cannot bill: All unbilled time entries must have Partner-approved hours before generating an invoice.']);
            }

            $tierKey = $matter->billing_tier ?? 'standard';
            $billingPercentage = (float) ($matter->billing_percentage ?? 100.00);

            $grossFees = $unbilledEntries->sum(
                fn ($entry) => round((float) $entry->approved_hours * (float) $entry->hourly_rate, 2)
            );
            $subtotalFees = $matter->applyBillingTier($grossFees);
        } else {
            return back()->withErrors(['message' => 'Administrative matters cannot be billed.']);
        }

        // 3. Finalize Calculations (12% VAT on Net Tier-Adjusted Subtotal)
        $vatAmount = round($subtotalFees * 0.12, 2);
        $totalAmount = round($subtotalFees + $vatAmount, 2);

        $invoiceNumber = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);

        // 4. Create the Invoice and Link Entries
        DB::transaction(function () use ($request, $matter, $totalAmount, $invoiceNumber, $billingType, $tierKey, $billingPercentage) {
            $invoice = Invoice::create([
                'matter_id' => $matter->id,
                'invoice_number' => $invoiceNumber,
                'billing_tier' => $tierKey,
                'billing_percentage' => $billingPercentage,
                'issue_date' => Carbon::parse($request->issue_date),
                'due_date' => Carbon::parse($request->due_date),
                'total_amount' => $totalAmount,
                'status' => 'Sent',
                'remarks' => $request->remarks,
                'created_by' => $request->user()->id,
            ]);

            $matter->timeEntries()->whereNull('invoice_id')->update([
                'invoice_id' => $invoice->id,
                'modified_by' => $request->user()->id,
            ]);

            if ($billingType === 'contingency' && $request->filled('settlement_judgment')) {
                $matter->update([
                    'settlement_judgment' => $request->settlement_judgment,
                ]);
            }
        });

        return back()->with('success', 'Invoice generated successfully.');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['matter.client', 'timeEntries.user', 'timeEntries.billable', 'creator', 'client', 'items.beneficiary']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load(['matter.client', 'timeEntries.user', 'timeEntries.billable', 'creator', 'client', 'items.beneficiary']);

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));

        return $pdf->download('Invoice-' . $invoice->invoice_number . '.pdf');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return back()->with('success', 'Invoice deleted successfully.');
    }
}