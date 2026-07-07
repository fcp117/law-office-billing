<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Matter;
use App\Models\Task;

class TaskController extends Controller
{
    public function store(Request $request, Matter $matter)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'deadline' => 'required|date',
            'status' => 'required|string|in:Pending,Completed',
        ]);

        // Automatically attaches this new task to the correct matter
        $matter->tasks()->create($validated);

        return redirect()->back();
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'deadline' => 'required|date',
            'status' => 'required|string|in:Pending,Completed',
        ]);

        $task->update($validated);

        return redirect()->back();
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->back();
    }
}
