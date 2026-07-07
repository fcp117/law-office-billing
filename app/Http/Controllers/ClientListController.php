<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Matter;
use App\Models\Client;
use Inertia\Inertia;

class ClientListController extends Controller
{
    public function index()
    {
    $client = Client::query()->orderBy('name', 'asc')->get();

    //dd ($client);

    return Inertia::render('ClientList', ['clients' => $client]);
    }
}
