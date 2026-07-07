<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TimeEntry;
use App\Models\Event;
use App\Models\Task;
use App\Models\Matter;
use App\Models\Payment;
use Inertia\Inertia; // Don't forget this!
use Illuminate\Support\Carbon;

class FirmOverviewController extends Controller
{
    public function index(Request $request)
    {
        $currentMonth = \Illuminate\Support\Carbon::now()->month;
        $currentYear = \Illuminate\Support\Carbon::now()->year;
    
        // The ultimate security check
        if ($request->user()->role !== 'Partner') {
            abort(403, 'CONFIDENTIAL: This dashboard is restricted to Partners only.');
        }

        // 1. Calculate Monthly KPIs
        $firmwideTimeThisMonth = TimeEntry::query()
            ->whereMonth('start_time', $currentMonth)
            ->whereYear('start_time', $currentYear);

        $firmwideTimeThisYear = TimeEntry::query()
            ->whereYear('start_time', $currentYear);

        $firmwidePaymentsThisMonth = Payment::query()
            ->whereMonth('payment_date', $currentMonth)
            ->whereYear('payment_date', $currentYear);

        $firmwidePaymentsThisYear = Payment::query()
            ->whereYear('payment_date', $currentYear);

        $totalThisMonth = (clone $firmwideTimeThisMonth)->sum('amount');
        $totalBilledThisMonth = (clone $firmwideTimeThisMonth)->whereNotNull('invoice_id')->sum('amount');
        $totalUnbilledThisMonth = (clone $firmwideTimeThisMonth)->whereNull('invoice_id')->sum('amount');
        $totalPaidThisMonth = (clone $firmwidePaymentsThisMonth)->sum('amount');

        $totalThisYear = (clone $firmwideTimeThisYear)->sum('amount');
        $totalBilledThisYear = (clone $firmwideTimeThisYear)->whereNotNull('invoice_id')->sum('amount');
        $totalUnbilledThisYear = (clone $firmwideTimeThisYear)->whereNull('invoice_id')->sum('amount');
        $totalPaidThisYear = (clone $firmwidePaymentsThisYear)->sum('amount');

        // 2. Fetch Action Items (Eager load the Matter so the UI can display the case name)
        $events = Event::query()
            ->with('matter:id,name') // Only grab the ID and Name to save memory
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at', 'asc')
            ->get();

        $tasks = Task::query()
            ->with('matter:id,name')
            ->where('status', 'Pending')
            ->orderBy('deadline', 'asc')
            ->get();

        // 3. Fetch Active Matters
        $matters = Matter::query()
            ->where('status', 'Active')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('FirmOverview', [
            'kpis' => [
                'total_this_month' => $totalThisMonth,
                'billed_this_month' => $totalBilledThisMonth,
                'unbilled_this_month' => $totalUnbilledThisMonth,
                'paid_this_month' => $totalPaidThisMonth,
                'total_this_year' => $totalThisYear,
                'billed_this_year' => $totalBilledThisYear,
                'unbilled_this_year' => $totalUnbilledThisYear,
                'paid_this_year' => $totalPaidThisYear
            ],
            'events' => $events,
            'tasks' => $tasks,
            'matters' => $matters
        ]);
    }
}
