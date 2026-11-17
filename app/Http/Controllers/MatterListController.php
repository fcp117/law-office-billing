<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Matter;
use Inertia\Inertia;
use App\Models\Client;

class MatterListController extends Controller
{
    public function index()
    {
    $matter = Matter::with(['client'])->orderBy('client_id', 'asc')->get();

    return Inertia::render('Matters', [
        'matters' => $matter,
        'clients' => Client::orderBy('name', 'asc')->get(['id', 'name']),
        ]);
    }
}
