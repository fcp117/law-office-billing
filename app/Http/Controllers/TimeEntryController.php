<?php

namespace App\Http\Controllers;

use App\Models\LawyerRate;
use App\Models\Matter;
use App\Models\TimeEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TimeEntryController extends Controller
{
    public function store(Request $request, Matter $matter)
    {
        if ($matter->status === 'Completed') {
            return redirect()->back()->with('error', 'This matter is closed. No further time entries can be logged.');
        }

        $isPartner = $request->user()->role === 'Partner';

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'start_time' => 'required|date',
            'hours' => 'required|numeric|min:0.01',
            'approved_hours' => 'nullable|numeric|min:0',
            'description' => 'required|string',
            'billable_id' => 'nullable|integer',
            'billable_type' => 'nullable|string|in:App\Models\Task,App\Models\Event',
        ]);

        $lawyerId = (int) ($validated['user_id'] ?? $request->user()->id);
        $materialDate = $validated['start_time'];
        $hours = round((float) $validated['hours'], 2);

        $approvedHours = ($isPartner && array_key_exists('approved_hours', $validated) && $validated['approved_hours'] !== null && $validated['approved_hours'] !== '')
            ? round((float) $validated['approved_hours'], 2)
            : null;

        $currency = $matter->currency ?? 'PHP';
        $hourlyRate = $this->resolveHourlyRate($lawyerId, $materialDate, $currency);

        $effectiveHours = $approvedHours !== null ? $approvedHours : $hours;
        $amount = round($effectiveHours * $hourlyRate, 2);

        $matter->timeEntries()->create([
            'user_id' => $lawyerId,
            'description' => $validated['description'],
            'start_time' => $materialDate,
            'hours' => $hours,
            'approved_hours' => $approvedHours,
            'hourly_rate' => $hourlyRate,
            'amount' => $amount,
            'billable_id' => $validated['billable_id'] ?? null,
            'billable_type' => $validated['billable_type'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Time entry logged.');
    }

    public function update(Request $request, TimeEntry $timeEntry)
    {
        if ($timeEntry->invoice_id !== null) {
            return redirect()->back()->with('error', 'This time entry has already been billed and cannot be modified.');
        }

        if ($timeEntry->matter->status === 'Completed') {
            return redirect()->back()->with('error', 'This matter is closed. Time entries can no longer be edited.');
        }

        $user = $request->user();
        $isPartner = $user->role === 'Partner';
        $isOwner = $user->id === $timeEntry->user_id;

        abort_unless($isPartner || $isOwner, 403, 'You are not authorized to edit this time entry.');

        if (!$isPartner && $timeEntry->approved_hours !== null) {
            return redirect()->back()->with('error', 'This time entry has already been approved by a Partner and can no longer be modified.');
        }

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'start_time' => 'required|date',
            'hours' => 'required|numeric|min:0.01',
            'approved_hours' => 'nullable|numeric|min:0',
            'description' => 'required|string',
            'billable_id' => 'nullable|integer',
            'billable_type' => 'nullable|string|in:App\Models\Task,App\Models\Event',
        ]);

        $lawyerId = (int) ($validated['user_id'] ?? $timeEntry->user_id);
        $materialDate = $validated['start_time'];
        $hours = round((float) $validated['hours'], 2);

        $approvedHours = $timeEntry->approved_hours;
        if ($isPartner) {
            $approvedHours = (array_key_exists('approved_hours', $validated) && $validated['approved_hours'] !== null && $validated['approved_hours'] !== '')
                ? round((float) $validated['approved_hours'], 2)
                : null;
        }

        $currency = $timeEntry->matter->currency ?? 'PHP';
        $hourlyRate = $this->resolveHourlyRate($lawyerId, $materialDate, $currency);

        $effectiveHours = $approvedHours !== null ? $approvedHours : $hours;
        $amount = round($effectiveHours * $hourlyRate, 2);

        $timeEntry->update([
            'user_id' => $lawyerId,
            'description' => $validated['description'],
            'start_time' => $materialDate,
            'hours' => $hours,
            'approved_hours' => $approvedHours,
            'hourly_rate' => $hourlyRate,
            'amount' => $amount,
            'billable_id' => $validated['billable_id'] ?? null,
            'billable_type' => $validated['billable_type'] ?? null,
            'modified_by' => $user->id,
        ]);

        return redirect()->back()->with('success', 'Time entry updated.');
    }

    /**
     * Inline Partner approval / adjustment of approved_hours.
     */
    public function approve(Request $request, TimeEntry $timeEntry)
    {
        abort_unless($request->user()->role === 'Partner', 403, 'Only Partners can approve hours.');

        if ($timeEntry->invoice_id !== null) {
            return redirect()->back()->with('error', 'This time entry has already been billed and cannot be modified.');
        }

        if ($timeEntry->matter->status === 'Completed') {
            return redirect()->back()->with('error', 'This matter is closed. Time entries can no longer be edited.');
        }

        $validated = $request->validate([
            'approved_hours' => 'nullable|numeric|min:0',
        ]);

        $approvedHours = ($validated['approved_hours'] !== null && $validated['approved_hours'] !== '')
            ? round((float) $validated['approved_hours'], 2)
            : null;

        $effectiveHours = $approvedHours !== null ? $approvedHours : (float) $timeEntry->hours;
        $amount = round($effectiveHours * (float) $timeEntry->hourly_rate, 2);

        $timeEntry->update([
            'approved_hours' => $approvedHours,
            'amount' => $amount,
            'modified_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', $approvedHours !== null ? 'Approved hours saved.' : 'Approval cleared.');
    }

    public function destroy(Request $request, TimeEntry $timeEntry)
    {
        if ($timeEntry->invoice_id !== null) {
            return redirect()->back()->with('error', 'This time entry has already been billed and cannot be deleted.');
        }

        if ($timeEntry->matter->status === 'Completed') {
            return redirect()->back()->with('error', 'This matter is closed. Time entries cannot be deleted.');
        }

        $user = $request->user();
        $isPartner = $user->role === 'Partner';
        $isOwner = $user->id === $timeEntry->user_id;

        abort_unless($isPartner || $isOwner, 403, 'You are not authorized to delete this time entry.');

        if (!$isPartner && $timeEntry->approved_hours !== null) {
            return redirect()->back()->with('error', 'This time entry has already been approved by a Partner and cannot be deleted.');
        }

        $timeEntry->delete();

        return redirect()->back()->with('success', 'Time entry deleted.');
    }

    /**
     * Fetch the lawyer's effective hourly rate in the matter's currency on the material date.
     */
    private function resolveHourlyRate(int $lawyerId, string $materialDate, string $currency): float
    {
        $hourlyRate = LawyerRate::getRateFor($lawyerId, $materialDate, $currency);

        if ($hourlyRate === null) {
            throw ValidationException::withMessages([
                'start_time' => "No {$currency} hourly rate is configured for the selected lawyer on or before this date.",
            ]);
        }

        return $hourlyRate;
    }
}