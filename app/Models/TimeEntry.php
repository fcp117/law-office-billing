<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeEntry extends Model
{
    protected $fillable = [
        'user_id',
        'matter_id',
        'description',
        'start_time',
        'end_time',
        'hours',
        'approved_hours',
        'hourly_rate',
        'amount',
        'billable_id',
        'billable_type',
        'invoice_id',
        'created_by',
        'modified_by',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'hours' => 'decimal:2',
        'approved_hours' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    protected $appends = ['billable_hours', 'formatted_description'];

    public function getBillableHoursAttribute(): float
    {
        return (float) ($this->approved_hours ?? $this->hours);
    }

    public function getFormattedDescriptionAttribute(): string
    {
        if (!$this->billable_id || !$this->billable) {
            return (string) $this->description;
        }

        $typeLabel = match ($this->billable_type) {
            Task::class => 'Task',
            Event::class => 'Event',
            default => class_basename($this->billable_type),
        };

        $title = $this->billable->title ?? null;

        if (!$title) {
            return (string) $this->description;
        }

        return "[{$typeLabel}: {$title}] {$this->description}";
    }

    public function billable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function matter()
    {
        return $this->belongsTo(Matter::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifier()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}