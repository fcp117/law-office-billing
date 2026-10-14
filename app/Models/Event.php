<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'matter_id',
        'title',
        'scheduled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function matter()
    {
        return $this->belongsTo(Matter::class);
    }

    public function users()
    {
        // Allows you to do $event->users or $task->users
        return $this->belongsToMany(User::class)->withTimestamps();
    }
}
