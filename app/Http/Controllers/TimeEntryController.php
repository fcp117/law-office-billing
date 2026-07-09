<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Matter;
use App\Models\TimeEntry;
use Carbon\Carbon;

class TimeEntryController extends Controller
{
    public function store(Request $request, Matter $matter)
    {
        $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'description' => 'required|string',
        ]);

        // 1. Calculate precise hours on the backend for security
        $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);
        $hours = $start->diffInMinutes($end) / 60;

        // 2. Define the rate (In a real app, you might fetch this from the User or Matter model)
        $hourlyRate = 2500.00; // Php 2,500/hr
        $amount = $hours * $hourlyRate;

        // 3. Create the Time Entry
        $matter->timeEntries()->create([
            'user_id' => $request->user()->id,
            'description' => $request->description,
            'start_time' => $start,
            'end_time' => $end,
            'hours' => round($hours, 2),
            'hourly_rate' => $hourlyRate,
            'amount' => round($amount, 2),
            'created_by' => $request->user()->id,
            
            // Fulfilling the polymorphic requirement by associating it with the Matter itself
            'billable_id' => $matter->id,
            'billable_type' => Matter::class,
        ]);

        return redirect()->back();
    }

    public function update(Request $request, TimeEntry $timeEntry)
    {
        $request->validate([
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'description' => 'required|string',
        ]);

        $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);
        $hours = $start->diffInMinutes($end) / 60;
        
        $hourlyRate = $timeEntry->hourly_rate; // Keep original rate
        $amount = $hours * $hourlyRate;

        $timeEntry->update([
            'description' => $request->description,
            'start_time' => $start,
            'end_time' => $end,
            'hours' => round($hours, 2),
            'amount' => round($amount, 2),
            'modified_by' => $request->user()->id,
        ]);

        return redirect()->back();
    }

    public function destroy(TimeEntry $timeEntry)
    {
        $timeEntry->delete();
        return redirect()->back();
    }
}
