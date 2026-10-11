<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Matter;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class MatterController extends Controller
{
    public function show($id)
    {
        $user = auth()->user();
    
        // Determine the base query: Partners see all matters, others only see assigned matters
        $query = $user->role === 'partner' ? Matter::query() : $user->matters();

        // Eager load everything needed for the single case view
        $matter = $query->with([
            'client', 
            'users', 
            'events', 
            'tasks', 
            // Order time entries chronologically by start_time
            'timeEntries' => function ($query) {
                $query->orderBy('start_time', 'asc'); 
            },
            'timeEntries.user', 
            'invoices.payments', 
        ])->findOrFail($id);

        $matter->invoices->transform(function ($invoice) {
            $invoice->total_payments = $invoice->payments->sum('amount');
            $invoice->balance_due = max(0, $invoice->total_amount - $invoice->total_payments);
            return $invoice;
        });

        // Calculate the total unpaid amount for this matter
        $matter->total_unpaid_amount = $matter->invoices->sum('balance_due');

        return Inertia::render('Matter/Show', [
            'matter' => $matter
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validate the incoming data from the Vue modal
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id', // Ensures the client actually exists
            'name' => 'required|string|max:255',
            'authority' => 'nullable|string|max:255',
            'status' => 'required|string|in:Active,Pending,On Hold',
        ]);

        // 2. Create the new Matter in the database
        $matter = Matter::create($validated);

        $user = $request->user();

        $matter->users()->attach($user->id, [
            'assignment_role' => $user->role
        ]);

        // 3. Send the user back so Inertia can refresh the ClientShow page automatically
        return redirect()->back();
    }

    public function statement($id)
    {
        $user = auth()->user();
        $matter = $user->matters()->with(['client', 'invoices.payments'])->findOrFail($id);

        // Transform invoices and filter out fully paid ones (balance_due <= 0)
        $filteredInvoices = $matter->invoices->map(function ($invoice) {
            $invoice->total_payments = $invoice->payments->sum('amount');
            $invoice->balance_due = max(0, $invoice->total_amount - $invoice->total_payments);
            return $invoice;
        })->filter(function ($invoice) {
            return $invoice->balance_due > 0;
        })->values(); // Resets collection keys for clean JSON array serialization

        // Update the relation on the matter model
        $matter->setRelation('invoices', $filteredInvoices);

        // Calculate the total unpaid amount for this matter based only on remaining active invoices
        $matter->total_unpaid_amount = $filteredInvoices->sum('balance_due');

        return Inertia::render('Matter/StatementOfAccount', [
            'matter' => $matter
        ]);
    }

    public function downloadStatementPdf($id)
    {
        $user = auth()->user();
        $matter = $user->matters()->with(['client', 'invoices.payments'])->findOrFail($id);

         // Transform invoices and filter out fully paid ones (balance_due <= 0)
        $filteredInvoices = $matter->invoices->map(function ($invoice) {
            $invoice->total_payments = $invoice->payments->sum('amount');
            $invoice->balance_due = max(0, $invoice->total_amount - $invoice->total_payments);
            return $invoice;
        })->filter(function ($invoice) {
            return $invoice->balance_due > 0;
        })->values(); // Resets collection keys for clean JSON array serialization

        // Update the relation on the matter model
        $matter->setRelation('invoices', $filteredInvoices);

        // Calculate the total unpaid amount for this matter based only on remaining active invoices
        $matter->total_unpaid_amount = $filteredInvoices->sum('balance_due');

        $pdf = Pdf::loadView('matters.statementofaccount-pdf', compact('matter'));

        return $pdf->download('Statement-of-Account-' . $matter->id . '.pdf');
    }
}
