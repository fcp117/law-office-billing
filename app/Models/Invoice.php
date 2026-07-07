<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'matter_id', 'invoice_number', 'issue_date', 'due_date', 
        'total_amount', 'status', 'remarks', 'created_by', 'modified_by'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
    ];

    public function matter() { return $this->belongsTo(Matter::class); }
    public function timeEntries() { return $this->hasMany(TimeEntry::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function modifier() { return $this->belongsTo(User::class, 'modified_by'); }
    public function payments() { return $this->hasMany(Payment::class); }
}
