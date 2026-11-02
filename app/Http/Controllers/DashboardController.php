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
        $timeEntries = TimeEntry::where('user_id', $targetUser->id)
            ->whereMonth('created_at', now()->month)
            ->get();

        $totalHours = $timeEntries->sum('hours');
        $hourlyRate = $targetUser->standard_hourly_rate ?? 0;
        
        // KPI computations
        $billedAmount = $timeEntries->where('is_billed', true)->sum(function($entry) use ($hourlyRate) {
            return $entry->hours * $hourlyRate;
        });

        $unbilledAmount = $timeEntries->where('is_billed', false)->sum(function($entry) use ($hourlyRate) {
            return $entry->hours * $hourlyRate;
        });

        return Inertia::render('Dashboard', [
            'targetUser' => $targetUser,
            'kpis' => [
                'hours' => $totalHours,
                'billed' => $billedAmount,
                'unbilled' => $unbilledAmount,
            ],
            'events' => $events,
            'tasks' => $tasks,
            'matters' => $matters,
            'clients' => $clients,
        ]);
    }
}