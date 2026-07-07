<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Matter;
use Inertia\Inertia;

class MatterListController extends Controller
{
    public function index()
    {
    $matter = Matter::with(['client'])->orderBy('client_id', 'asc')->get();

    return Inertia::render('MatterList', ['matters' => $matter]);
    }
}
