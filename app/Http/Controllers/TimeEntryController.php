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
        // Block new time entries if the matter is finalized
        if ($matter->status === 'Completed') {
            return redirect()->back()->with('error', 'This matter is closed. No further time entries can be logged.');
        }
    
        $request->validate([
            'start_time' => 'nullable|date',
            /* 'end_time' => 'nullable|date|after:start_time', */
            'hours' => 'required|numeric',
            'description' => 'required|string',
            'billable_id' => 'nullable|integer',
            'billable_type' => 'nullable|string|in:App\Models\Task,App\Models\Event',
        ]);

        // 1. Calculate precise hours on the backend for security
        /* $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);
        $hours = $start->diffInMinutes($end) / 60; */

        $hours = (float) $request->hours;
        $date = $request->start_time;

        // 2. Define the rate (In a real app, you might fetch this from the User or Matter model)
        $hourlyRate = 2500.00; // Php 2,500/hr
        $amount = $hours * $hourlyRate;

        // 3. Create the Time Entry
        $matter->timeEntries()->create([
            'user_id' => $request->user()->id,
            'description' => $request->description,
            'start_time' => $date,
            /* 'end_time' => $end, */
            'hours' => $hours,
            'hourly_rate' => $hourlyRate,
            'amount' => $amount,
            'billable_id' => $request->billable_id,
            'billable_type' => $request->billable_type,
        ]);

        return redirect()->back();
    }

    public function update(Request $request, TimeEntry $timeEntry)
    {
        // Block new time entries if the matter is finalized
        if ($matter->status === 'Completed') {
            return redirect()->back()->with('error', 'This matter is closed. No further time entries can be logged.');
        }
    
        $request->validate([
            'user_id'       => $request->user()->id,
            'start_time' => 'required|date',
            /*'end_time' => 'required|date|after:start_time', */
            'hours' => 'required|numeric',
            'description' => 'required|string',
            'billable_id' => 'nullable|integer',
            'billable_type' => 'nullable|string|in:App\Models\Task,App\Models\Event',
        ]);

       /*  $start = Carbon::parse($request->start_time);
        $end = Carbon::parse($request->end_time);
        $hours = $start->diffInMinutes($end) / 60; */

        $hours = (float) $request->hours;
        
        $hourlyRate = $timeEntry->hourly_rate; // Keep original rate
        $amount = $hours * $hourlyRate;

        $timeEntry->update([
            'description' => $request->description,
            /* 'start_time' => $start,
            'end_time' => $end, */
            'hours' => round($hours, 2),
            'amount' => round($amount, 2),
            'billable_id' => $request->billable_id,
            'billable_type' => $request->billable_type,
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
