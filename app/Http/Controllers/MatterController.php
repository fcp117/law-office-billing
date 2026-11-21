<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Matter;
use App\Models\Client;
use App\Models\User;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class MatterController extends Controller
{
    public const ASSIGNABLE_LAWYER_ROLES = ['Partner', 'Associate'];
    
    public function show($id)
    {
        $user = auth()->user();

        // Determine the base query: Partners see all matters, others only see assigned matters
        $query = $user->role === 'Partner' ? Matter::query() : $user->matters();

        // Eager load everything needed for the single case view
        $matter = $query->with([
            'client',
            'users',
            'events',
            'tasks',
            'timeEntries' => function ($query) {
                $query->orderBy('start_time', 'asc');
            },
            'timeEntries.user',
            'timeEntries.billable',
            'invoices.payments',
        ])->findOrFail($id);

        $matter->invoices->transform(function ($invoice) {
            $invoice->total_payments = $invoice->payments->sum('amount');
            $invoice->balance_due = max(0, $invoice->total_amount - $invoice->total_payments);
            return $invoice;
        });

        $matter->total_unpaid_amount = $matter->invoices->sum('balance_due');

        $clients = Client::orderBy('name', 'asc')->get();

        // Only pass assignable lawyers when the logged-in user is a Partner
        $lawyers = $user->role === 'Partner'
            ? User::whereIn('role', self::ASSIGNABLE_LAWYER_ROLES)
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'email', 'role'])
            : [];

        return Inertia::render('Matter/Show', [
            'matter' => $matter,
            'clients' => $clients,
            'lawyers' => $lawyers,
        ]);
    }

    public function assignLawyer(Request $request, Matter $matter)
    {
        abort_unless($request->user()->role === 'Partner', 403, 'Only Partners can assign lawyers to cases.');

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereIn('role', self::ASSIGNABLE_LAWYER_ROLES),
            ],
        ], [
            'user_id.exists' => 'Only Partners and Associates can be assigned to a matter.',
        ]);

        $lawyer = User::findOrFail($validated['user_id']);

        if ($matter->users()->where('users.id', $lawyer->id)->exists()) {
            return redirect()->back()->with('error', "{$lawyer->name} is already assigned to this matter.");
        }

        $matter->users()->attach($lawyer->id, [
            'assignment_role' => $lawyer->role,
        ]);

        return redirect()->back()->with('success', "{$lawyer->name} assigned to matter.");
    }

    public function removeLawyer(Request $request, Matter $matter, User $user)
    {
        abort_unless($request->user()->role === 'Partner', 403, 'Only Partners can remove lawyers from cases.');

        if ($matter->users()->count() <= 1) {
            return redirect()->back()->with('error', 'At least one handling lawyer must remain assigned to the matter.');
        }

        $matter->users()->detach($user->id);

        return redirect()->back()->with('success', "{$user->name} removed from matter.");
    }

    public function store(Request $request)
    {
        // 1. Validate the incoming data from the Vue modal
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'authority' => 'nullable|string|max:255',
            'status' => 'required|string|in:Active,Pending,On Hold,Completed',
            'billing_type' => 'required|string|in:hourly,fixed_rate,contingency,administrative',
            'currency' => 'required|string|in:PHP,USD',
            'fixed_fee_amount' => 'nullable|numeric|min:0',
            'contingency_percentage' => 'nullable|numeric|min:0',
            'settlement_judgment' => 'nullable|numeric|min:0',
        ]);

        // 2. Create the new Matter in the database
        $matter = Matter::create($validated);

        $user = $request->user();

        $matter->users()->attach($user->id, [
            'assignment_role' => $user->role,
        ]);

        // 3. Send the user back so Inertia can refresh the page automatically
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
        })->values();

        // Update the relation on the matter model
        $matter->setRelation('invoices', $filteredInvoices);

        // Calculate the total unpaid amount for this matter based only on remaining active invoices
        $matter->total_unpaid_amount = $filteredInvoices->sum('balance_due');

        return Inertia::render('Matter/StatementOfAccount', [
            'matter' => $matter,
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
        })->values();

        // Update the relation on the matter model
        $matter->setRelation('invoices', $filteredInvoices);

        // Calculate the total unpaid amount for this matter based only on remaining active invoices
        $matter->total_unpaid_amount = $filteredInvoices->sum('balance_due');

        $pdf = Pdf::loadView('matters.statementofaccount-pdf', compact('matter'));

        return $pdf->download('Statement-of-Account-' . $matter->id . '.pdf');
    }

    public function update(Request $request, Matter $matter)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'authority' => 'nullable|string|max:255',
            'status' => 'required|string|in:Active,Pending,On Hold,Completed',
            'billing_type' => 'required|string|in:hourly,fixed_rate,contingency,administrative',
            'currency' => 'required|string|in:PHP,USD',
            'fixed_fee_amount' => 'nullable|numeric|min:0',
            'contingency_percentage' => 'nullable|numeric|min:0',
            'settlement_judgment' => 'nullable|numeric|min:0',
        ]);

        $matter->update($validated);

        return redirect()->back()->with('success', 'Matter updated successfully.');
    }

    public function destroy(Matter $matter)
    {
        $matter->delete();

        // Redirect to dashboard since the current Matter page will no longer exist
        return redirect()->route('dashboard')->with('success', 'Matter deleted successfully.');
    }
}