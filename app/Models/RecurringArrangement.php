<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecurringArrangement extends Model
{
    public const TYPES = [
        'retainer'      => 'Retainer Fee',
        'secondment'    => 'Secondment Fee',
        'directors_fee' => "Director's Fee",
    ];

    public const CYCLES = [
        'monthly'     => 'Monthly',
        'quarterly'   => 'Quarterly',
        'semi_annual' => 'Semi-Annual',
        'annual'      => 'Annual',
    ];

    protected $fillable = [
        'client_id',
        'matter_id',
        'type',
        'beneficiary_user_id',
        'description',
        'amount',
        'currency',
        'is_vatable',
        'billing_cycle',
        'billing_day',
        'grouping_mode',
        'start_date',
        'end_date',
        'status',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_vatable' => 'boolean',
        'billing_day' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $appends = [
        'type_label',
        'cycle_label',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function getCycleLabelAttribute(): string
    {
        return self::CYCLES[$this->billing_cycle] ?? ucfirst($this->billing_cycle);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function matter()
    {
        return $this->belongsTo(Matter::class);
    }

    public function beneficiary()
    {
        return $this->belongsTo(User::class, 'beneficiary_user_id');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}