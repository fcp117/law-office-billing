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
        //Fetch the user
        $user = auth()->user();
    
        // Eager load everything needed for the single case view
        $matter = $user->matters()->with([
            'client', 
            'users', // The legal team assigned
            'events', 
            'tasks', 
            'timeEntries.user', // Eager load the user inside the time entry to see WHO logged it
            'invoices.payments', // Eager load payments for each invoice
        ])->findOrFail($id);

        $matter->invoices->transform(function ($invoice) {
            $invoice->total_payments = $invoice->payments->sum('amount');
            $invoice->balance_due = max(0, $invoice->total_amount - $invoice->total_payments);
            return $invoice;
        });

        //dd($matter);

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

        $matter->invoices->transform(function ($invoice) {
            $invoice->total_payments = $invoice->payments->sum('amount');
            $invoice->balance_due = max(0, $invoice->total_amount - $invoice->total_payments);
            return $invoice;
        });

        // Calculate the total unpaid amount for this matter
        $matter->total_unpaid_amount = $matter->invoices->sum('balance_due');

        return Inertia::render('Matter/StatementOfAccount', [
            'matter' => $matter
        ]);
    }

    public function downloadStatementPdf($id)
    {
        $user = auth()->user();
        $matter = $user->matters()->with(['client', 'invoices.payments'])->findOrFail($id);

        $matter->invoices->transform(function ($invoice) {
            $invoice->total_payments = $invoice->payments->sum('amount');
            $invoice->balance_due = max(0, $invoice->total_amount - $invoice->total_payments);
            return $invoice;
        });

        $pdf = Pdf::loadView('matters.statementofaccount-pdf', compact('matter'));

        return $pdf->download('Statement-of-Account-' . $matter->id . '.pdf');
    }
}
