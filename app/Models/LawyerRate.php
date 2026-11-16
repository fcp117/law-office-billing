<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LawyerRate extends Model
{
    public const CURRENCIES = ['USD', 'PHP'];

    protected $fillable = [
        'user_id',
        'rank',
        'currency',
        'hourly_rate',
        'effective_from',
        'created_by',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'effective_from' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to find the applicable rate record for a lawyer, currency, and material date.
     */
    public function scopeForLawyerOnDate(
        Builder $query,
        int $userId,
        string|\DateTimeInterface $materialDate,
        string $currency = 'USD'
    ): Builder {
        $date = Carbon::parse($materialDate)->toDateString();

        return $query->where('user_id', $userId)
            ->where('currency', strtoupper($currency))
            ->whereDate('effective_from', '<=', $date)
            ->orderByDesc('effective_from')
            ->orderByDesc('id');
    }

    /**
     * Convenience helper returning the numeric hourly rate (or null if none exists).
     */
    public static function getRateFor(
        int $userId,
        string|\DateTimeInterface $materialDate,
        string $currency = 'USD'
    ): ?float {
        $rate = static::forLawyerOnDate($userId, $materialDate, $currency)->value('hourly_rate');

        return $rate !== null ? (float) $rate : null;
    }
}