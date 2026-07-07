<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'contact_person',
        'contact_person_email',
        'start_date',
        'retainer_amount',
        'partner_in_charge',
    ];

    protected $casts = [
        'start_date' => 'date',
    ];

    public function matter()
    {
        return $this->hasMany(Matter::class);
    }

    use HasFactory;
}
