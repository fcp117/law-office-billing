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
        $timeEntries = TimeEntry::with('matter')
            ->where('user_id', $targetUser->id)
            ->whereMonth('created_at', now()->month)
            ->get();

        $hourlyRate = $targetUser->standard_hourly_rate ?? 0;
        
        // Initialize KPI structure
        $kpis = [
            'aggregate' => [
                'hours' => 0,
                'billed' => 0,
                'unbilled' => 0,
            ],
            'by_billing_type' => []
        ];

        // Process each time entry to populate aggregate and billing-type specific totals
        foreach ($timeEntries as $entry) {
            $billingType = $entry->matter->billing_type ?? 'Unspecified';

            // Initialize the specific billing type array if it doesn't exist yet
            if (!isset($kpis['by_billing_type'][$billingType])) {
                $kpis['by_billing_type'][$billingType] = [
                    'hours' => 0,
                    'billed' => 0,
                    'unbilled' => 0,
                ];
            }

            $amount = $entry->hours * $hourlyRate;

            // Add to aggregate totals
            $kpis['aggregate']['hours'] += $entry->hours;

            // Add to billing type specific totals
            $kpis['by_billing_type'][$billingType]['hours'] += $entry->hours;

            if ($entry->is_billed) {
                $kpis['aggregate']['billed'] += $amount;
                $kpis['by_billing_type'][$billingType]['billed'] += $amount;
            } else {
                $kpis['aggregate']['unbilled'] += $amount;
                $kpis['by_billing_type'][$billingType]['unbilled'] += $amount;
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