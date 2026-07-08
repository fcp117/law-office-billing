<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\TimeEntry;
use App\Models\Client;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // 1. Calculate Monthly KPIs
        $myTimeThisMonth = TimeEntry::where('user_id', $user->id)
            ->whereMonth('start_time', $currentMonth)
            ->whereYear('start_time', $currentYear);

        $totalHours = (clone $myTimeThisMonth)->sum('hours');
        $totalBilled = (clone $myTimeThisMonth)->whereNotNull('invoice_id')->sum('amount');
        $totalUnbilled = (clone $myTimeThisMonth)->whereNull('invoice_id')->sum('amount');

        // 2. Fetch Action Items (Eager load the Matter so the UI can display the case name)
        $events = $user->events()
            ->with('matter:id,name') // Only grab the ID and Name to save memory
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at', 'asc')
            //->take(5) // Limit to next 5 upcoming
            ->get();

        $tasks = $user->tasks()
            ->with('matter:id,name')
            ->where('status', 'Pending')
            ->orderBy('deadline', 'asc')
            //->take(5)
            ->get();

        // 3. Fetch Active Matters
        $matters = $user->matters()
            ->where('status', 'Active')
            ->orderBy('created_at', 'desc')
            ->get();

        // 4. Fetch Clients (for the "Add Matter" button)
        $clients = \App\Models\Client::orderBy('name', 'asc')->get();

        return Inertia::render('Dashboard', [
            'kpis' => [
                'hours' => $totalHours,
                'billed' => $totalBilled,
                'unbilled' => $totalUnbilled
            ],
            'events' => $events,
            'tasks' => $tasks,
            'matters' => $matters,
            'clients' => $clients
        ]);
    }
}