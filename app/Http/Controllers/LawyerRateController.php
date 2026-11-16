<?php

namespace App\Http\Controllers;

use App\Models\LawyerRate;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LawyerRateController extends Controller
{
    public const RANKS = [
        'Junior Associate',
        'Senior Associate',
        'Junior Partner',
        'Senior Partner',
        'Of Counsel',
    ];

    public const CURRENCIES = [
        'USD',
        'PHP',
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

        $activeRates = [
            'USD' => $rates->first(
                fn ($rate) => $rate->currency === 'USD' && $rate->effective_from->toDateString() <= $today
            ),
            'PHP' => $rates->first(
                fn ($rate) => $rate->currency === 'PHP' && $rate->effective_from->toDateString() <= $today
            ),
        ];

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
            'activeRates' => $activeRates,
            'ranks' => self::RANKS,
            'currencies' => self::CURRENCIES,
            'unbilledCount' => $unbilledCount,
        ]);
    }

    public function store(Request $request, User $user)
    {
        Gate::authorize('create', [LawyerRate::class, $user]);

        $validated = $request->validate([
            'rank' => ['required', 'string', Rule::in(self::RANKS)],
            'currency' => ['required', 'string', Rule::in(self::CURRENCIES)],
            'hourly_rate' => ['required', 'numeric', 'min:0'],
            'effective_from' => [
                'required',
                'date',
                Rule::unique('lawyer_rates', 'effective_from')
                    ->where('user_id', $user->id)
                    ->where('currency', $request->input('currency')),
            ],
            'recalculate_unbilled' => ['boolean'],
        ], [
            'effective_from.unique' => 'A rate entry in this currency already exists for this lawyer on the selected effective date.',
        ]);

        $recalculatedCount = 0;

        DB::transaction(function () use ($request, $user, $validated, &$recalculatedCount) {
            LawyerRate::create([
                'user_id' => $user->id,
                'rank' => $validated['rank'],
                'currency' => $validated['currency'],
                'hourly_rate' => round((float) $validated['hourly_rate'], 2),
                'effective_from' => Carbon::parse($validated['effective_from'])->toDateString(),
                'created_by' => $request->user()->id,
            ]);

            if (!empty($validated['recalculate_unbilled'])) {
                $recalculatedCount = $this->recalculateUnbilledEntries(
                    $user->id,
                    $validated['currency'],
                    $validated['effective_from'],
                    $request->user()->id
                );
            }
        });

        $message = "{$validated['currency']} rate added successfully.";
        if ($recalculatedCount > 0) {
            $message .= " Recalculated {$recalculatedCount} unbilled {$validated['currency']} time " . ($recalculatedCount === 1 ? 'entry.' : 'entries.');
        }

        return redirect()->back()->with('success', $message);
    }

    public function destroy(Request $request, LawyerRate $lawyerRate)
    {
        Gate::authorize('delete', $lawyerRate);

        $userId = $lawyerRate->user_id;
        $currency = $lawyerRate->currency;
        $effectiveFrom = $lawyerRate->effective_from->toDateString();

        // Check if an earlier fallback rate exists in the same currency
        $hasEarlierRate = LawyerRate::where('user_id', $userId)
            ->where('currency', $currency)
            ->where('id', '!=', $lawyerRate->id)
            ->whereDate('effective_from', '<=', $effectiveFrom)
            ->exists();

        $entriesQuery = TimeEntry::where('user_id', $userId)
            ->whereDate('start_time', '>=', $effectiveFrom);

        if (Schema::hasColumn('matters', 'currency')) {
            $entriesQuery->whereHas('matter', fn ($q) => $q->where('currency', $currency));
        }

        if (!$hasEarlierRate && $entriesQuery->exists()) {
            return redirect()->back()->with(
                'error',
                "Cannot delete this {$currency} rate because existing {$currency} time entries on or after its effective date have no earlier fallback rate."
            );
        }

        DB::transaction(function () use ($request, $lawyerRate, $userId, $currency, $effectiveFrom) {
            $lawyerRate->delete();

            // Re-sync any unbilled entries in that currency from that date onward to the fallback rate
            $this->recalculateUnbilledEntries($userId, $currency, $effectiveFrom, $request->user()->id);
        });

        return redirect()->back()->with('success', "{$currency} rate record removed and unbilled entries synced.");
    }

    /**
     * Re-evaluates temporal rates for unbilled time entries matching the given currency on or after $fromDate.
     */
    private function recalculateUnbilledEntries(int $userId, string $currency, string $fromDate, int $modifierId): int
    {
        $query = TimeEntry::where('user_id', $userId)
            ->whereNull('invoice_id')
            ->whereDate('start_time', '>=', Carbon::parse($fromDate)->toDateString());

        // Filter by matter currency once multi-currency matters are enabled; otherwise default to USD
        if (Schema::hasColumn('matters', 'currency')) {
            $query->whereHas('matter', fn ($q) => $q->where('currency', $currency));
        } elseif ($currency !== 'USD') {
            return 0;
        }

        $entries = $query->get();
        $updated = 0;

        foreach ($entries as $entry) {
            $applicableRate = LawyerRate::getRateFor($userId, $entry->start_time, $currency);

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