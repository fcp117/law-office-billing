<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(?User $user = null, Request $request)
    {
        $billingTypes = ['hourly', 'fixed_rate', 'contingency', 'administrative'];

        $targetUserId = $request->query('user_id') ?? optional($user)->id ?? auth()->id();
        $targetUser = User::findOrFail($targetUserId);

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Fetch targeted user's events, tasks, and assigned matters
        $events = $targetUser->events()->with('matter')->orderBy('scheduled_at', 'asc')->get();
        $tasks = $targetUser->tasks()->with('matter')->orderBy('deadline', 'asc')->get();
        $matters = $targetUser->matters()->with('client')->get();
        $clients = Client::all();

        // Initialize KPI structure
        $kpis = [
            'aggregate' => [
                'hours' => 0.0,
                'billed' => ['PHP' => 0.0, 'USD' => 0.0],
                'unbilled' => ['PHP' => 0.0, 'USD' => 0.0],
            ],
            'by_billing_type' => [],
        ];

        foreach ($billingTypes as $type) {
            $kpis['by_billing_type'][$type] = [
                'hours' => 0.0,
                'billed' => ['PHP' => 0.0, 'USD' => 0.0],
                'unbilled' => ['PHP' => 0.0, 'USD' => 0.0],
            ];
        }

        // -----------------------------------------------------------------
        // 1. TIME ENTRIES THIS MONTH (Hours for all types; $ for Hourly only)
        // -----------------------------------------------------------------
        $entries = TimeEntry::with([
            'matter:id,billing_type,billing_tier,billing_percentage,currency',
            'invoice:id,billing_percentage',
        ])
            ->where('user_id', $targetUser->id)
            ->whereBetween('start_time', [$startOfMonth, $endOfMonth])
            ->get();

        foreach ($entries as $entry) {
            $hours = (float) $entry->hours;
            $type = strtolower($entry->matter->billing_type ?? 'hourly');
            $currency = strtoupper($entry->matter->currency ?? 'PHP');

            if (! isset($kpis['by_billing_type'][$type])) {
                $kpis['by_billing_type'][$type] = [
                    'hours' => 0.0,
                    'billed' => ['PHP' => 0.0, 'USD' => 0.0],
                    'unbilled' => ['PHP' => 0.0, 'USD' => 0.0],
                ];
            }

            if (! in_array($currency, ['PHP', 'USD'], true)) {
                $currency = 'PHP';
            }

            // Track logged hours across all matter types
            $kpis['aggregate']['hours'] += $hours;
            $kpis['by_billing_type'][$type]['hours'] += $hours;

            // Only Hourly matters derive billed/unbilled money from TimeEntry->amount
            if ($type === 'hourly') {
                $effectiveHours = $entry->approved_hours !== null
                    ? (float) $entry->approved_hours
                    : $hours;
                $baseAmount = round($effectiveHours * (float) $entry->hourly_rate, 2);

                $tierPct = $entry->invoice_id !== null
                    ? (float) ($entry->invoice->billing_percentage ?? $entry->matter->billing_percentage ?? 100.00)
                    : (float) ($entry->matter->billing_percentage ?? 100.00);

                $amount = round($baseAmount * ($tierPct / 100), 2);

                if ($entry->invoice_id !== null) {
                    $kpis['aggregate']['billed'][$currency] += $amount;
                    $kpis['by_billing_type']['hourly']['billed'][$currency] += $amount;
                } else {
                    $kpis['aggregate']['unbilled'][$currency] += $amount;
                    $kpis['by_billing_type']['hourly']['unbilled'][$currency] += $amount;
                }
            }
        }

        // -----------------------------------------------------------------
        // 2. FIXED RATE & CONTINGENCY MATTERS (Allocated by Lawyer Effort)
        // -----------------------------------------------------------------
        $nonHourlyMatters = $targetUser->matters()
            ->whereIn('billing_type', ['fixed_rate', 'contingency'])
            ->with([
                'users:id',
                'invoices:id,matter_id,total_amount,issue_date,created_at',
                'timeEntries:id,matter_id,user_id,hours,approved_hours',
            ])
            ->get();

        foreach ($nonHourlyMatters as $matter) {
            $type = strtolower($matter->billing_type);
            $currency = strtoupper($matter->currency ?? 'PHP');
            if (! in_array($currency, ['PHP', 'USD'], true)) {
                $currency = 'PHP';
            }

            // Compute total fee for this matter (pre-VAT, matching hourly fees)
            $matterFee = 0.0;
            if ($type === 'fixed_rate') {
                $matterFee = (float) ($matter->fixed_fee_amount ?? 0);
            } elseif ($type === 'contingency') {
                $settlement = (float) ($matter->settlement_judgment ?? 0);
                $pct = (float) ($matter->contingency_percentage ?? 0);
                $matterFee = round($settlement * ($pct / 100), 2);
            }

            if ($matterFee <= 0) {
                continue;
            }

            // Allocate fee across lawyers pro-rata by logged/approved hours on the matter
            $totalMatterHours = (float) $matter->timeEntries->sum(
                fn ($e) => (float) ($e->approved_hours ?? $e->hours)
            );

            if ($totalMatterHours > 0) {
                $userMatterHours = (float) $matter->timeEntries
                    ->where('user_id', $targetUser->id)
                    ->sum(fn ($e) => (float) ($e->approved_hours ?? $e->hours));

                $shareRatio = $userMatterHours / $totalMatterHours;
            } else {
                // Fallback: equal split among assigned handling lawyers if no time logged yet
                $assignedCount = max(1, $matter->users->count());
                $shareRatio = 1 / $assignedCount;
            }

            $allocatedFee = round($matterFee * $shareRatio, 2);

            if ($matter->invoices->isNotEmpty()) {
                // Count as Billed this month if invoiced during the current month
                $invoicedThisMonth = $matter->invoices->contains(function ($inv) use ($startOfMonth, $endOfMonth) {
                    $date = $inv->issue_date ?? $inv->created_at;
                    return $date && $date->between($startOfMonth, $endOfMonth);
                });

                if ($invoicedThisMonth) {
                    $kpis['aggregate']['billed'][$currency] += $allocatedFee;
                    $kpis['by_billing_type'][$type]['billed'][$currency] += $allocatedFee;
                }
            } else {
                // Unbilled: include Completed matters ready for billing (matching BillingOverviewController)
                if (strtolower($matter->status ?? '') === 'completed') {
                    $kpis['aggregate']['unbilled'][$currency] += $allocatedFee;
                    $kpis['by_billing_type'][$type]['unbilled'][$currency] += $allocatedFee;
                }
            }
        }

        // Round all currency buckets cleanly
        foreach (['PHP', 'USD'] as $cur) {
            $kpis['aggregate']['billed'][$cur] = round($kpis['aggregate']['billed'][$cur], 2);
            $kpis['aggregate']['unbilled'][$cur] = round($kpis['aggregate']['unbilled'][$cur], 2);
            foreach ($billingTypes as $type) {
                $kpis['by_billing_type'][$type]['billed'][$cur] = round($kpis['by_billing_type'][$type]['billed'][$cur], 2);
                $kpis['by_billing_type'][$type]['unbilled'][$cur] = round($kpis['by_billing_type'][$type]['unbilled'][$cur], 2);
            }
        }

        return Inertia::render('Dashboard', [
            'targetUser' => $targetUser,
            'kpis' => $kpis,
            'events' => $events,
            'tasks' => $tasks,
            'matters' => $matters,
            'clients' => $clients,
        ]);
    }
}