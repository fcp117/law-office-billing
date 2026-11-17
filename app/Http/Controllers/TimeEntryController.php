<?php

namespace App\Http\Controllers;

use App\Models\LawyerRate;
use App\Models\Matter;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimeEntryController extends Controller
{
    public function store(Request $request, Matter$matter)
    {
        if ($matter->status === 'Completed') {
            return redirect()->back()->with('error', 'This matter is closed. No further time entries can be logged.');
        }

        $validated =$request->validate([
            'user_id' => 'nullable|exists:users,id',
            'start_time' => 'required|date',
            'hours' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'billable_id' => 'nullable|integer',
            'billable_type' => 'nullable|string|in:App\Models\Task,App\Models\Event',
        ]);

        $lawyerId = (int) ($validated['user_id'] ?? $request->user()->id);
        $materialDate =$validated['start_time'];
        $hours = round((float)$validated['hours'], 2);
        $currency =$matter->currency ?? 'PHP';

        $hourlyRate =$this->resolveHourlyRate($lawyerId,$materialDate, $currency);$amount = round($hours * $hourlyRate, 2);

        $matter->timeEntries()->create([
            'user_id' => $lawyerId,
            'description' => $validated['description'],
            'start_time' => $materialDate,
            'hours' => $hours,
            'hourly_rate' => $hourlyRate,
            'amount' => $amount,
            'billable_id' => $validated['billable_id'] ?? null,
            'billable_type' => $validated['billable_type'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Time entry logged.');
    }

    public function update(Request $request, TimeEntry$timeEntry)
    {
        if ($timeEntry->invoice_id !== null) {
            return redirect()->back()->with('error', 'This time entry has already been billed and cannot be modified.');
        }

        if ($timeEntry->matter->status === 'Completed') {
            return redirect()->back()->with('error', 'This matter is closed. Time entries can no longer be edited.');
        }

        $validated =$request->validate([
            'user_id' => 'nullable|exists:users,id',
            'start_time' => 'required|date',
            'hours' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'billable_id' => 'nullable|integer',
            'billable_type' => 'nullable|string|in:App\Models\Task,App\Models\Event',
        ]);

        $lawyerId = (int) ($validated['user_id'] ?? $timeEntry->user_id);
        $materialDate =$validated['start_time'];
        $hours = round((float)$validated['hours'], 2);
        $currency =$timeEntry->matter->currency ?? 'PHP';

        // Re-evaluate temporal rate in case start_time or user_id changed
        $hourlyRate =$this->resolveHourlyRate($lawyerId,$materialDate, $currency);$amount = round($hours * $hourlyRate, 2);

        $timeEntry->update([
            'user_id' => $lawyerId,
            'description' => $validated['description'],
            'start_time' => $materialDate,
            'hours' => $hours,
            'hourly_rate' => $hourlyRate,
            'amount' => $amount,
            'billable_id' => $validated['billable_id'] ?? null,
            'billable_type' => $validated['billable_type'] ?? null,
            'modified_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Time entry updated.');
    }

    public function destroy(TimeEntry $timeEntry)
    {
        if ($timeEntry->invoice_id !== null) {
            return redirect()->back()->with('error', 'This time entry has already been billed and cannot be deleted.');
        }

        if ($timeEntry->matter->status === 'Completed') {
            return redirect()->back()->with('error', 'This matter is closed. Time entries cannot be deleted.');
        }

        $timeEntry->delete();

        return redirect()->back()->with('success', 'Time entry deleted.');
    }

    /**
     * Fetch the lawyer's effective hourly rate in the matter's currency on the material date.
     */
    private function resolveHourlyRate(int $lawyerId, string $materialDate, string$currency): float
    {
        $hourlyRate = LawyerRate::getRateFor($lawyerId, $materialDate,$currency);

        if ($hourlyRate === null) {
            throw ValidationException::withMessages([
                'start_time' => "No {$currency} hourly rate is configured for the selected lawyer on or before this date.",
            ]);
        }

        return $hourlyRate;
    }
}