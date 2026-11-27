<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matter extends Model
{
    public const CURRENCIES = ['PHP', 'USD'];

    public const BILLING_TIERS = [
        'priority'  => ['label' => 'Priority (120%)', 'percentage' => 120.00],
        'standard'  => ['label' => 'Standard (100%)', 'percentage' => 100.00],
        'select'    => ['label' => 'Select (80%)', 'percentage' => 80.00],
        'preferred' => ['label' => 'Preferred (70%)', 'percentage' => 70.00],
    ];

    protected $fillable = [
        'name',
        'description',
        'practice_area',
        'type',
        'client_id',
        'authority',
        'party_represented',
        'party_represented_role',
        'party_adverse',
        'party_adverse_role',
        'status',
        'start_date',
        'closing_date',
        'remarks',
        'billing_type',
        'billing_tier',
        'billing_percentage',
        'currency',
        'contingency_percentage',
        'settlement_judgment',
        'fixed_fee_amount',
        'created_by',
        'modified_by',
    ];

    protected $appends = [
        'currency_symbol',
        'billing_tier_label',
    ];

    protected $casts = [
        'start_date' => 'date',
        'closing_date' => 'date',
        'billing_percentage' => 'decimal:2',
        'contingency_percentage' => 'decimal:2',
        'settlement_judgment' => 'decimal:2',
        'fixed_fee_amount' => 'decimal:2',
    ];

    public function getCurrencySymbolAttribute(): string
    {
        return ($this->currency ?? 'PHP') === 'PHP' ? '₱' : '$';
    }

    public function getBillingTierLabelAttribute(): string
    {
        $tier = $this->billing_tier ?? 'standard';
        return self::BILLING_TIERS[$tier]['label']
            ?? ('Custom (' . number_format((float) ($this->billing_percentage ?? 100), 0) . '%)');
    }

    public function getBillingTierNameAttribute(): string
    {
        $tier = $this->billing_tier ?? 'standard';
        return match ($tier) {
            'priority'  => 'Priority',
            'select'    => 'Select',
            'preferred' => 'Preferred',
            default     => 'Standard',
        };
    }
    
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
                    ->withPivot('assignment_role')
                    ->withTimestamps();
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function getTotalUnpaidAmountAttribute()
    {
        return $this->invoices->sum(function ($invoice) {
            $totalPaid = $invoice->payments->sum('amount');
            return max(0, $invoice->total_amount - $totalPaid);
        });
    }

    public function applyBillingTier(float $amount): float
    {
        if (strtolower($this->billing_type ?? '') !== 'hourly') {
            return round($amount, 2);
        }

        $percentage = (float) ($this->billing_percentage ?? 100.00);
        return round($amount * ($percentage / 100), 2);
    }
}