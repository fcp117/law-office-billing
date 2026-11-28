<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'matter_id',
        'client_id',
        'invoice_number',
        'currency',
        'billing_source',
        'period_key',
        'billing_tier',
        'billing_percentage',
        'issue_date',
        'due_date',
        'total_amount',
        'status',
        'remarks',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'issue_date'         => 'date',
        'due_date'           => 'date',
        'total_amount'       => 'decimal:2',
        'billing_percentage' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function matter()
    {
        return $this->belongsTo(Matter::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}