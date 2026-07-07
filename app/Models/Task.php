<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{

    protected $fillable = [
        'matter_id', // Don't forget this one!
        'title',
        'description',
        'deadline',
        'status',
    ];

    protected $casts = [
        'deadline' => 'datetime',
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
