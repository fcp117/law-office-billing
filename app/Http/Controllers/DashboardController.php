<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TimeEntry;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(?User $user = null, Request $request)
    {
        $billingTypes = ['hourly', 'fixed_rate', 'contingency', 'administrative'];
        // Fallback to the authenticated user if no user is specified in the route
        $targetUserId = $request->query('user_id') ?? optional($user)->id ?? auth()->id();
        $targetUser = User::findOrFail($targetUserId);

        // Fetch targeted user's data
        $events = $targetUser->events()->with('matter')->orderBy('scheduled_at', 'asc')->get();
        $tasks = $targetUser->tasks()->with('matter')->orderBy('deadline', 'asc')->get();
        
        // Matters assigned to this user
        $matters = $targetUser->matters()->with('client')->get();
        $clients = Client::all();

        // Calculate KPIs for the target user using TimeEntry's perspective
        // Eager load 'matter' to determine billing_type
        $timeEntries = TimeEntry::with(['matter', 'invoice'])
        ->where('user_id', $targetUser->id)
        ->where(function ($query) {
            $query->whereHas('invoice', function ($q) {
                // Filter by Invoice creation date
                $q->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
            })
            ->orWhere(function ($q) {
                // Keep unbilled entries logged in the current month
                $q->whereNull('invoice_id')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
            });
        })
        ->get();

        $hourlyRate = $targetUser->standard_hourly_rate ?? 0;
        
        // Initialize KPI structure
        $kpis = [
            'aggregate' => [
                'hours' => 0,
                'billed' => ['PHP' => 0, 'USD' => 0],
                'unbilled' => ['PHP' => 0, 'USD' => 0],
            ],
            'by_billing_type' => [],
        ];

        foreach ($billingTypes as $type) {
            $kpis['by_billing_type'][$type] = [
                'hours' => 0,
                'billed' => ['PHP' => 0, 'USD' => 0],
                'unbilled' => ['PHP' => 0, 'USD' => 0],
            ];
        }

        $entries = \App\Models\TimeEntry::with('matter:id,billing_type,currency')
                ->where('user_id', $targetUser->id)
                ->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])
                ->get();

        // Process each time entry to populate aggregate and billing-type specific totals
        foreach ($entries as $entry) {
            $hours = (float) $entry->hours;
            $amount = (float) $entry->amount;
            $type = strtolower($entry->matter->billing_type ?? 'hourly');
            $currency = strtoupper($entry->matter->currency ?? 'PHP');

            if (!isset($kpis['by_billing_type'][$type])) {
                $kpis['by_billing_type'][$type] = [
                    'hours' => 0,
                    'billed' => ['PHP' => 0, 'USD' => 0],
                    'unbilled' => ['PHP' => 0, 'USD' => 0],
                ];
            }

            if (!in_array($currency, ['PHP', 'USD'], true)) {
                $currency = 'PHP';
            }

            $kpis['aggregate']['hours'] += $hours;
            $kpis['by_billing_type'][$type]['hours'] += $hours;

            if ($entry->invoice_id !== null) {
                $kpis['aggregate']['billed'][$currency] += $amount;
                $kpis['by_billing_type'][$type]['billed'][$currency] += $amount;
            } else {
                $kpis['aggregate']['unbilled'][$currency] += $amount;
                $kpis['by_billing_type'][$type]['unbilled'][$currency] += $amount;
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