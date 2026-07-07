<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matter extends Model
{
    protected $fillable = [
        'name', 'description', 'practice_area', 'type', 'client_id',
        'authority', 'party_represented', 'party_represented_role',
        'party_adverse', 'party_adverse_role', 'status', 'start_date',
        'closing_date', 'remarks', 'created_by', 'modified_by'
    ];

    // Ensures these columns are treated as Dates, not just strings
    protected $casts = [
        'start_date' => 'date',
        'closing_date' => 'date',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Audit Trail Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    // The Many-to-Many relationship for assigned lawyers
    public function users()
    {
        return $this->belongsToMany(User::class)
                    ->withPivot('assignment_role')
                    ->withTimestamps();
    }

    // Links to the Events and Tasks architecture
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // 1. The missing TimeEntries relationship
    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }
    
    // 2. The missing Invoices relationship
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}