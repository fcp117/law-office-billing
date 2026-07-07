<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_person_email' => 'nullable|email|max:255',
            'start_date' =>  'nullable|date',
            'retainer_amount' => 'nullable|numeric|min:0',
            'partner_in_charge' => 'nullable|string|max:255',
        ]);

        $client = Client::create($validated);

        return redirect()->back()->with('message', 'Client created successfully!');
    }

    // Add this inside your ClientController
    public function show(Client $client)
    {
        // Fetch the matters that belong to this client
        $matters = $client->matter()->orderBy('created_at', 'desc')->get();

        // Return a new Vue view (we will need to build ClientShow.vue next!)
        return Inertia::render('ClientShow', [
            'client' => $client,
            'matters' => $matters
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'address' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_person_email' => 'nullable|email|max:255',
            'retainer_amount' => 'nullable|numeric|min:0',
            'partner_in_charge' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
        ]);

        $client->update($validated);

        return redirect()->back();
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->back();
    }

}


