<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Matter;
use App\Models\RecurringArrangement;
use App\Models\User;
use App\Services\SpecialBillingGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SpecialBillingController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'Partner', 403, 'Only Partners can access Special Billing.');

        $arrangements = RecurringArrangement::with(['client:id,name', 'matter:id,name', 'beneficiary:id,name,role'])
            ->orderByRaw("FIELD(status, 'Active', 'Paused', 'Ended')")
            ->orderBy('client_id')
            ->get();

        $invoices = Invoice::with([
            'client:id,name',
            'matter.client:id,name',
            'items.beneficiary:id,name',
            'items.arrangement:id,type',
            'payments:id,invoice_id,amount,payment_date',
        ])
            ->where('billing_source', 'special_billing')
            ->where('status', '!=', 'Superseded')
            ->orderByRaw("FIELD(status, 'Queued', 'Sent', 'Paid')")
            ->orderBy('issue_date', 'desc')
            ->get()
            ->map(function ($inv) {
                $paid = (float) $inv->payments->sum('amount');
                $total = (float) $inv->total_amount;
                $paidRatio = $total > 0 ? min(1, $paid / $total) : 0;

                $items = $inv->items->map(function ($item) {
                    $type = $item->arrangement?->type ?? $this->inferItemType($item->description);
                    $gross = round((float) $item->amount + (float) $item->vat_amount, 2);

                    return [
                        'id'                      => $item->id,
                        'type'                    => $type,
                        'type_label'              => RecurringArrangement::TYPES[$type] ?? 'Special Fee',
                        'description'             => $item->description,
                        'beneficiary_name'        => $item->beneficiary?->name,
                        'amount'                  => (float) $item->amount,
                        'vat_amount'              => (float) $item->vat_amount,
                        'gross_amount'            => $gross,
                        'beneficiary_remitted_at' => $item->beneficiary_remitted_at?->toDateString(),
                    ];
                });

                return [
                    'id'              => $inv->id,
                    'invoice_number'  => $inv->invoice_number,
                    'client_name'     => $inv->client?->name ?? $inv->matter?->client?->name ?? 'N/A',
                    'matter_name'     => $inv->matter?->name,
                    'currency'        => $inv->currency ?? 'PHP',
                    'period_key'      => $inv->period_key,
                    'issue_date'      => $inv->issue_date?->toDateString(),
                    'due_date'        => $inv->due_date?->toDateString(),
                    'status'          => $inv->status,
                    'total_amount'    => $total,
                    'total_payments'  => $paid,
                    'balance_due'     => max(0, round($total - $paid, 2)),
                    'unpaid_ratio'    => round(1 - $paidRatio, 6),
                    'types'           => $items->pluck('type')->unique()->values(),
                    'items'           => $items,
                ];
            });

        $clients = Client::orderBy('name')->get(['id', 'name']);
        $matters = Matter::orderBy('name')->get(['id', 'client_id', 'name']);
        $lawyers = User::whereIn('role', ['Partner', 'Associate'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return Inertia::render('SpecialBilling/Index', [
            'arrangements' => $arrangements,
            'invoices'     => $invoices,
            'clients'      => $clients,
            'matters'      => $matters,
            'lawyers'      => $lawyers,
        ]);
    }

    private function inferItemType(?string $description): string
    {
        $desc = strtolower((string) $description);
        if (str_contains($desc, 'director')) {
            return 'directors_fee';
        }
        if (str_contains($desc, 'secondment')) {
            return 'secondment';
        }
        return 'retainer';
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->role === 'Partner', 403);

        $validated = $this->validateArrangement($request);
        $validated['created_by'] = $request->user()->id;

        RecurringArrangement::create($validated);

        return back()->with('success', 'Recurring arrangement saved.');
    }

    public function update(Request $request, RecurringArrangement $arrangement)
    {
        abort_unless($request->user()->role === 'Partner', 403);

        $validated = $this->validateArrangement($request);
        $validated['modified_by'] = $request->user()->id;

        $arrangement->update($validated);

        return back()->with('success', 'Recurring arrangement updated.');
    }

    public function destroy(Request $request, RecurringArrangement $arrangement)
    {
        abort_unless($request->user()->role === 'Partner', 403);

        $arrangement->delete();

        return back()->with('success', 'Recurring arrangement removed.');
    }

    public function generateQueue(Request $request, SpecialBillingGenerator $generator)
    {
        abort_unless($request->user()->role === 'Partner', 403);

        $request->validate([
            'target_month' => 'required|date_format:Y-m',
        ]);

        $target = Carbon::createFromFormat('Y-m', $request->target_month)->startOfMonth();
        $result = $generator->generateForDate($target, $request->user()->id);

        return back()->with(
            'success',
            "Generated {$result['created_invoices']} queued invoice(s) ({$result['created_items']} item(s)) for {$target->format('F Y')}."
        );
    }

    public function approveAndSend(Request $request, Invoice $invoice)
    {
        abort_unless($request->user()->role === 'Partner', 403);

        if ($invoice->status !== 'Queued') {
            return back()->withErrors(['message' => 'Only Queued invoices can be approved and marked Sent.']);
        }

        $invoice->update([
            'status'     => 'Sent',
            'issue_date' => Carbon::today(),
            'due_date'   => Carbon::today()->addDays(30),
        ]);

        return back()->with('success', "Invoice {$invoice->invoice_number} approved and marked as Sent.");
    }

    public function remitBeneficiary(Request $request, InvoiceItem $item)
    {
        abort_unless($request->user()->role === 'Partner', 403);

        $item->update([
            'beneficiary_remitted_at' => $item->beneficiary_remitted_at ? null : now(),
        ]);

        return back()->with('success', 'Beneficiary remittance status updated.');
    }

    private function validateArrangement(Request $request): array
    {
        return $request->validate([
            'client_id'           => 'required|exists:clients,id',
            'matter_id'           => 'nullable|exists:matters,id',
            'type'                => ['required', Rule::in(array_keys(RecurringArrangement::TYPES))],
            'beneficiary_user_id' => [
                Rule::requiredIf(fn () => in_array($request->type, ['secondment', 'directors_fee'], true)),
                'nullable',
                'exists:users,id',
            ],
            'description'         => 'required|string|max:255',
            'amount'              => 'required|numeric|min:0.01',
            'currency'            => 'required|in:PHP,USD',
            'is_vatable'          => 'required|boolean',
            'billing_cycle'       => ['required', Rule::in(array_keys(RecurringArrangement::CYCLES))],
            'billing_day'         => 'required|integer|min:1|max:28',
            'grouping_mode'       => 'required|in:grouped,individual',
            'start_date'          => 'required|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'status'              => 'required|in:Active,Paused,Ended',
        ]);
    }
}