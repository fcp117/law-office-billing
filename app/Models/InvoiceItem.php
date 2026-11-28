<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'recurring_arrangement_id',
        'beneficiary_user_id',
        'period_key',
        'description',
        'amount',
        'vat_amount',
        'beneficiary_remitted_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'beneficiary_remitted_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function arrangement()
    {
        return $this->belongsTo(RecurringArrangement::class, 'recurring_arrangement_id');
    }

    public function beneficiary()
    {
        return $this->belongsTo(User::class, 'beneficiary_user_id');
    }
}