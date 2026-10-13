<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Matter;
use App\Models\Event;

class EventController extends Controller
{
    public function store(Request $request, Matter $matter)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date',
            'status' => 'required|string|in:Scheduled,Completed',
            'billing_type' => 'required|string|in:hourly,fixed_rate,administrative',
            'fixed_fee_amount' => 'nullable|numeric|min:0',
        ]);

        $matter->events()->create($validated);
        return redirect()->back();
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date',
            'status' => 'required|string|in:Scheduled,Completed',
            'billing_type' => 'required|string|in:hourly,fixed_rate,administrative',
            'fixed_fee_amount' => 'nullable|numeric|min:0',
        ]);

        $event->update($validated);
        return redirect()->back();
    }

    public function destroy(Event $event)
    {
        $event->delete();

        return redirect()->back();
    }
}