<?php

namespace App\Http\Controllers;

use App\Models\LawyerRate;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\Gate;

class LawyerRateController extends Controller
{
    public const RANKS = [
        'Junior Associate',
        'Senior Associate',
        'Junior Partner',
        'Senior Partner',
        'Of Counsel',
    ];

    public function index(User $user)
    {
        Gate::authorize('viewAny', [LawyerRate::class, $user]);
        
        $rates = $user->rates()
            ->with('creator:id,name')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();

        $today = now()->toDateString();
        $activeRate = $rates->first(fn ($rate) => $rate->effective_from->toDateString() <= $today);

        $unbilledCount = TimeEntry::where('user_id', $user->id)
            ->whereNull('invoice_id')
            ->count();

        $lawyers = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Users/Rates', [
            'lawyer' => $user->only(['id', 'name', 'email']),
            'lawyers' => $lawyers,
            'rates' => $rates,
            'activeRate' => $activeRate,
            'ranks' => self::RANKS,
            'unbilledCount' => $unbilledCount,
        ]);
    }

    public function store(Request $request, User $user)
    {
        
        Gate::authorize('create', [LawyerRate::class, $user]);

        $validated = $request->validate([
            'rank' => ['required', 'string', Rule::in(self::RANKS)],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'effective_from' => [
                'required',
                'date',
                Rule::unique('lawyer_rates', 'effective_from')->where('user_id', $user->id),
            ],
            'recalculate_unbilled' => ['boolean'],
        ], [
            'effective_from.unique' => 'A rate entry already exists for this lawyer on the selected effective date.',
        ]);

        $recalculatedCount = 0;

        DB::transaction(function () use ($request, $user, $validated, &$recalculatedCount) {
            LawyerRate::create([
                'user_id' => $user->id,
                'rank' => $validated['rank'],
                'hourly_rate' => round((float) $validated['hourly_rate'], 2),
                'effective_from' => Carbon::parse($validated['effective_from'])->toDateString(),
                'created_by' => $request->user()->id,
            ]);

            if (!empty($validated['recalculate_unbilled'])) {
                $recalculatedCount = $this->recalculateUnbilledEntries(
                    $user->id,
                    $validated['effective_from'],
                    $request->user()->id
                );
            }
        });

        $message = 'Lawyer rate added successfully.';
        if ($recalculatedCount > 0) {
            $message .= " Recalculated {$recalculatedCount} unbilled time " . ($recalculatedCount === 1 ? 'entry.' : 'entries.');
        }

        return redirect()->back()->with('success', $message);
    }

    public function destroy(Request $request, LawyerRate $lawyerRate)
    {
        Gate::authorize('delete', $lawyerRate);

        $userId = $lawyerRate->user_id;
        $effectiveFrom = $lawyerRate->effective_from->toDateString();

        // Prevent deleting the sole remaining rate if unbilled entries depend on it
        $hasEarlierRate = LawyerRate::where('user_id', $userId)
            ->where('id', '!=', $lawyerRate->id)
            ->whereDate('effective_from', '<=', $effectiveFrom)
            ->exists();

        $affectedEntriesExist = TimeEntry::where('user_id', $userId)
            ->whereDate('start_time', '>=', $effectiveFrom)
            ->exists();

        if (!$hasEarlierRate && $affectedEntriesExist) {
            return redirect()->back()->with('error', 'Cannot delete this rate because existing time entries on or after its effective date have no earlier fallback rate.');
        }

        DB::transaction(function () use ($request, $lawyerRate, $userId, $effectiveFrom) {
            $lawyerRate->delete();

            // Re-sync any unbilled entries from that date onward to the fallback rate
            $this->recalculateUnbilledEntries($userId, $effectiveFrom, $request->user()->id);
        });

        return redirect()->back()->with('success', 'Rate record removed and unbilled entries synced.');
    }

    /**
     * Re-evaluates temporal rates for all unbilled time entries on or after $fromDate.
     */
    private function recalculateUnbilledEntries(int $userId, string $fromDate, int $modifierId): int
    {
        $entries = TimeEntry::where('user_id', $userId)
            ->whereNull('invoice_id')
            ->whereDate('start_time', '>=', Carbon::parse($fromDate)->toDateString())
            ->get();

        $updated = 0;

        foreach ($entries as $entry) {
            $applicableRate = LawyerRate::getRateFor($userId, $entry->start_time);

            if ($applicableRate !== null && (float) $entry->hourly_rate !== $applicableRate) {
                $entry->update([
                    'hourly_rate' => $applicableRate,
                    'amount' => round((float) $entry->hours * $applicableRate, 2),
                    'modified_by' => $modifierId,
                ]);
                $updated++;
            }
        }

        return $updated;
    }
}