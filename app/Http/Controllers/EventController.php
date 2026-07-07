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
            'scheduled_at' => 'required|date',
        ]);

        $matter->events()->create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'scheduled_at' => 'required|date',
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