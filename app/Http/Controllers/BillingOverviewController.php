<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Matter;
use App\Models\Payment;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BillingOverviewController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'Partner', 403, 'Only Partners can access Billing & Collections Overview.');

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfYear = Carbon::now()->startOfYear();

        // -----------------------------------------------------------------
        // 1. UNBILLED WORK IN PROGRESS (BY MATTER)
        // -----------------------------------------------------------------
        $matters = Matter::with([
            'client:id,name',
            'users:id,name,role',
            'timeEntries' => function ($q) {
                $q->whereNull('invoice_id')
                    ->select('id', 'matter_id', 'user_id', 'hours', 'approved_hours', 'hourly_rate', 'amount', 'start_time');
            },
        ])
            ->withCount('invoices')
            ->get();

        $unbilledMatters = [];

        foreach ($matters as $matter) {
            $billingType = strtolower($matter->billing_type ?? 'hourly');
            $status = strtolower($matter->status ?? '');
            $currency = $matter->currency ?? 'PHP';

            $unbilledAmount = 0.0;
            $unapprovedCount = 0;
            $unapprovedAmount = 0.0;
            $totalLoggedHours = 0.0;
            $totalBillableHours = 0.0;
            $oldestEntryDate = null;
            $latestEntryDate = null;
            $readyToBill = false;

            if ($billingType === 'hourly') {
                $entries = $matter->timeEntries;
                if ($entries->isEmpty()) {
                    continue;
                }

                $tierMultiplier = ((float) ($matter->billing_percentage ?? 100.00)) / 100;

                foreach ($entries as $entry) {
                    $logged = (float) $entry->hours;
                    $effectiveHours = $entry->approved_hours !== null ? (float) $entry->approved_hours : $logged;
                    $lineAmount = round($effectiveHours * (float) $entry->hourly_rate * $tierMultiplier, 2);

                    $unbilledAmount += $lineAmount;
                    $totalLoggedHours += $logged;
                    $totalBillableHours += $effectiveHours;

                    if ($entry->approved_hours === null) {
                        $unapprovedCount++;
                        $unapprovedAmount += $lineAmount;
                    }

                    $entryDate = $entry->start_time ? $entry->start_time->toDateString() : null;
                    if ($entryDate) {
                        if ($oldestEntryDate === null || $entryDate < $oldestEntryDate) {
                            $oldestEntryDate = $entryDate;
                        }
                        if ($latestEntryDate === null || $entryDate > $latestEntryDate) {
                            $latestEntryDate = $entryDate;
                        }
                    }
                }

                $readyToBill = $unapprovedCount === 0 && $unbilledAmount > 0;
            } elseif ($billingType === 'fixed_rate') {
                if ($status !== 'completed' || $matter->invoices_count > 0) {
                    continue;
                }
                $unbilledAmount = (float) ($matter->fixed_fee_amount ?? 0);
                if ($unbilledAmount <= 0) {
                    continue;
                }
                $readyToBill = true;
                $oldestEntryDate = $matter->updated_at?->toDateString();
                $latestEntryDate = $oldestEntryDate;
            } elseif ($billingType === 'contingency') {
                if ($status !== 'completed' || $matter->invoices_count > 0) {
                    continue;
                }
                $settlement = (float) ($matter->settlement_judgment ?? 0);
                $pct = (float) ($matter->contingency_percentage ?? 0);
                $unbilledAmount = round($settlement * ($pct / 100), 2);
                if ($unbilledAmount <= 0) {
                    continue;
                }
                $readyToBill = true;
                $oldestEntryDate = $matter->updated_at?->toDateString();
                $latestEntryDate = $oldestEntryDate;
            } else {
                continue; // Administrative matters are non-billable
            }

            $daysStale = $latestEntryDate ? Carbon::parse($latestEntryDate)->diffInDays($today) : 0;

            $unbilledMatters[] = [
                'id' => $matter->id,
                'name' => $matter->name,
                'client_name' => $matter->client?->name ?? 'N/A',
                'billing_type' => $billingType,
                'billing_tier' => $matter->billing_tier ?? 'standard',
                'billing_tier_label' => $matter->billing_tier_label,
                'billing_percentage' => (float) ($matter->billing_percentage ?? 100.00),
                'status' => $matter->status,
                'currency' => $currency,
                'lawyers' => $matter->users->pluck('name')->values(),
                'unbilled_amount' => round($unbilledAmount, 2),
                'unapproved_count' => $unapprovedCount,
                'unapproved_amount' => round($unapprovedAmount, 2),
                'logged_hours' => round($totalLoggedHours, 2),
                'billable_hours' => round($totalBillableHours, 2),
                'oldest_date' => $oldestEntryDate,
                'latest_date' => $latestEntryDate,
                'days_stale' => $daysStale,
                'ready_to_bill' => $readyToBill,
            ];
        }

        // -----------------------------------------------------------------
        // 2. UNPAID INVOICES (ACCOUNTS RECEIVABLE) & AGING
        // -----------------------------------------------------------------
        $invoices = Invoice::with(['matter.client:id,name', 'payments:id,invoice_id,amount,payment_date'])
            ->orderBy('due_date', 'asc')
            ->get();

        $unpaidInvoices = [];
        $matterCollectionsMap = [];

        foreach ($invoices as $invoice) {
            $currency = $invoice->currency ?? $invoice->matter?->currency ?? 'PHP';
            $totalAmount = (float) $invoice->total_amount;
            $paidAmount = (float) $invoice->payments->sum('amount');
            $balanceDue = max(0, round($totalAmount - $paidAmount, 2));

            $matterId = $invoice->matter_id;
            if ($matterId) {
                if (!isset($matterCollectionsMap[$matterId])) {
                    $matterCollectionsMap[$matterId] = [
                        'id' => $matterId,
                        'name' => $invoice->matter?->name ?? 'N/A',
                        //'client_name' => $invoice->matter?->client?->name ?? 'N/A',
                        'client_name' => $invoice->client?->name ?? $invoice->matter?->client?->name ?? 'N/A',
                        'currency' => $currency,
                        'status' => $invoice->matter?->status ?? 'Active',
                        'invoice_count' => 0,
                        'unpaid_invoice_count' => 0,
                        'total_billed' => 0.0,
                        'total_paid' => 0.0,
                        'balance_due' => 0.0,
                        'oldest_unpaid_due_date' => null,
                    ];
                }

                $matterCollectionsMap[$matterId]['invoice_count']++;
                $matterCollectionsMap[$matterId]['total_billed'] += $totalAmount;
                $matterCollectionsMap[$matterId]['total_paid'] += $paidAmount;
                $matterCollectionsMap[$matterId]['balance_due'] += $balanceDue;

                if ($balanceDue > 0) {
                    $matterCollectionsMap[$matterId]['unpaid_invoice_count']++;
                    $dueStr = $invoice->due_date?->toDateString();
                    if ($dueStr && ($matterCollectionsMap[$matterId]['oldest_unpaid_due_date'] === null || $dueStr < $matterCollectionsMap[$matterId]['oldest_unpaid_due_date'])) {
                        $matterCollectionsMap[$matterId]['oldest_unpaid_due_date'] = $dueStr;
                    }
                }
            }

            if ($balanceDue <= 0) {
                continue;
            }

            $daysOverdue = 0;
            if ($invoice->due_date && $invoice->due_date->lt($today)) {
                $daysOverdue = (int) $invoice->due_date->diffInDays($today);
            }

            $agingBucket = match (true) {
                $daysOverdue === 0 => 'Current',
                $daysOverdue <= 30 => '1-30 Days',
                $daysOverdue <= 60 => '31-60 Days',
                $daysOverdue <= 90 => '61-90 Days',
                default => '90+ Days',
            };

            $unpaidInvoices[] = [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'matter_id' => $matterId,
                'matter_name' => $invoice->matter?->name ?? 'N/A',
                'client_name' => $invoice->matter?->client?->name ?? 'N/A',
                'currency' => $currency,
                'status' => $invoice->status,
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'days_overdue' => $daysOverdue,
                'aging_bucket' => $agingBucket,
                'total_amount' => round($totalAmount, 2),
                'total_payments' => round($paidAmount, 2),
                'balance_due' => $balanceDue,
            ];
        }

        // Keep only matters that have an unpaid balance
        $unpaidMatters = array_values(array_filter(
            array_map(function ($row) {
                $row['total_billed'] = round($row['total_billed'], 2);
                $row['total_paid'] = round($row['total_paid'], 2);
                $row['balance_due'] = round($row['balance_due'], 2);
                return $row;
            }, $matterCollectionsMap),
            fn ($row) => $row['balance_due'] > 0
        ));

        // -----------------------------------------------------------------
        // 3. PARTNER APPROVAL QUEUE (UNAPPROVED UNBILLED TIME ENTRIES)
        // -----------------------------------------------------------------
        $pendingApprovals = TimeEntry::with(['user:id,name', 'matter.client:id,name', 'billable'])
            ->whereNull('invoice_id')
            ->whereNull('approved_hours')
            ->whereHas('matter', fn ($q) => $q->whereRaw('LOWER(billing_type) = ?', ['hourly']))
            ->orderBy('start_time', 'asc')
            ->get()
            ->map(function ($entry) {
                $tierPct = (float) ($entry->matter?->billing_percentage ?? 100.00);
                return [
                    'id' => $entry->id,
                    'matter_id' => $entry->matter_id,
                    'matter_name' => $entry->matter?->name ?? 'N/A',
                    'client_name' => $entry->matter?->client?->name ?? 'N/A',
                    'currency' => $entry->matter?->currency ?? 'PHP',
                    'billing_percentage' => $tierPct,
                    'lawyer_name' => $entry->user?->name ?? 'Unknown',
                    'start_time' => $entry->start_time?->toDateString(),
                    'formatted_description' => $entry->formatted_description,
                    'hours' => (float) $entry->hours,
                    'hourly_rate' => (float) $entry->hourly_rate,
                    'amount' => round((float) $entry->hours * (float) $entry->hourly_rate * ($tierPct / 100), 2),
                ];
            })
            ->values();

        // -----------------------------------------------------------------
        // 4. SUMMARY KPIs (SPLIT BY PHP & USD)
        // -----------------------------------------------------------------
        $kpis = [
            'PHP' => $this->buildCurrencyKpis('PHP', $unbilledMatters, $unpaidInvoices, $invoices, $startOfMonth, $startOfYear),
            'USD' => $this->buildCurrencyKpis('USD', $unbilledMatters, $unpaidInvoices, $invoices, $startOfMonth, $startOfYear),
        ];

        return Inertia::render('Billing/Overview', [
            'kpis' => $kpis,
            'unbilledMatters' => $unbilledMatters,
            'unpaidInvoices' => $unpaidInvoices,
            'unpaidMatters' => $unpaidMatters,
            'pendingApprovals' => $pendingApprovals,
        ]);
    }

    private function buildCurrencyKpis(
        string $currency,
        array $unbilledMatters,
        array $unpaidInvoices,
        $allInvoices,
        Carbon $startOfMonth,
        Carbon $startOfYear
    ): array {
        $wipItems = array_filter($unbilledMatters, fn ($m) => $m['currency'] === $currency);
        $arItems = array_filter($unpaidInvoices, fn ($i) => $i['currency'] === $currency);

        $totalWip = array_sum(array_column($wipItems, 'unbilled_amount'));
        $readyToBillWip = array_sum(array_map(
            fn ($m) => $m['ready_to_bill'] ? $m['unbilled_amount'] : 0,
            $wipItems
        ));
        $pendingApprovalWip = array_sum(array_column($wipItems, 'unapproved_amount'));
        $pendingApprovalCount = array_sum(array_column($wipItems, 'unapproved_count'));

        $totalAr = array_sum(array_column($arItems, 'balance_due'));
        $overdueAr = array_sum(array_map(
            fn ($i) => $i['days_overdue'] > 0 ? $i['balance_due'] : 0,
            $arItems
        ));

        // Aging buckets
        $aging = [
            'Current' => 0.0,
            '1-30 Days' => 0.0,
            '31-60 Days' => 0.0,
            '61-90 Days' => 0.0,
            '90+ Days' => 0.0,
        ];
        foreach ($arItems as $inv) {
            $aging[$inv['aging_bucket']] += $inv['balance_due'];
        }

        // Billed & Collected MTD / YTD
        $currencyInvoices = $allInvoices->filter(fn ($inv) => ($inv->matter?->currency ?? 'PHP') === $currency);

        $billedMtd = (float) $currencyInvoices
            ->filter(fn ($inv) => $inv->issue_date && $inv->issue_date->gte($startOfMonth))
            ->sum('total_amount');

        $billedYtd = (float) $currencyInvoices
            ->filter(fn ($inv) => $inv->issue_date && $inv->issue_date->gte($startOfYear))
            ->sum('total_amount');

        $collectedMtd = 0.0;
        $collectedYtd = 0.0;
        foreach ($currencyInvoices as $inv) {
            foreach ($inv->payments as $payment) {
                $pDate = Carbon::parse($payment->payment_date);
                if ($pDate->gte($startOfMonth)) {
                    $collectedMtd += (float) $payment->amount;
                }
                if ($pDate->gte($startOfYear)) {
                    $collectedYtd += (float) $payment->amount;
                }
            }
        }

        // Realization rate (Approved vs Logged hours on approved entries in this currency)
        $approvedStats = TimeEntry::whereNotNull('approved_hours')
            ->whereHas('matter', fn ($q) => $q->where('currency', $currency)->whereRaw('LOWER(billing_type) = ?', ['hourly']))
            ->selectRaw('COALESCE(SUM(hours), 0) as total_logged, COALESCE(SUM(approved_hours), 0) as total_approved')
            ->first();

        $realizationRate = ($approvedStats && (float) $approvedStats->total_logged > 0)
            ? round(((float) $approvedStats->total_approved / (float) $approvedStats->total_logged) * 100, 1)
            : 100.0;

        return [
            'total_wip' => round($totalWip, 2),
            'ready_to_bill_wip' => round($readyToBillWip, 2),
            'pending_approval_wip' => round($pendingApprovalWip, 2),
            'pending_approval_count' => $pendingApprovalCount,
            'unbilled_matters_count' => count($wipItems),
            'total_ar' => round($totalAr, 2),
            'overdue_ar' => round($overdueAr, 2),
            'unpaid_invoices_count' => count($arItems),
            'aging' => array_map(fn ($v) => round($v, 2), $aging),
            'billed_mtd' => round($billedMtd, 2),
            'collected_mtd' => round($collectedMtd, 2),
            'billed_ytd' => round($billedYtd, 2),
            'collected_ytd' => round($collectedYtd, 2),
            'realization_rate' => $realizationRate,
        ];
    }
}