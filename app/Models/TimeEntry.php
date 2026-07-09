<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    protected $fillable = [
        'user_id', 'matter_id', 'description', 'start_time', 'end_time', 
        'hours', 'hourly_rate', 'amount', 'billable_id', 'billable_type', 'invoice_id', 'created_by', 'modified_by'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    // The Polymorphic magic
    public function billable() { return $this->morphTo(); }
    
    public function user() { return $this->belongsTo(User::class); }
    public function matter() { return $this->belongsTo(Matter::class); }
    public function invoice() { return $this->belongsTo(Invoice::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function modifier() { return $this->belongsTo(User::class, 'modified_by'); }
}
