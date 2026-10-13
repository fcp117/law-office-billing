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
            'description' => 'nullable|string',
            'deadline' => 'required|date',
            'status' => 'required|string|in:Pending,Completed',
            'billing_type' => 'required|string|in:hourly,fixed_rate,administrative',
            'fixed_fee_amount' => 'nullable|numeric|min:0',
        ]);

        $matter->tasks()->create($validated);
        return redirect()->back();
    }

    public function update(Request $request, Task $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'required|date',
            'status' => 'required|string|in:Pending,Completed',
            'billing_type' => 'required|string|in:hourly,fixed_rate,administrative',
            'fixed_fee_amount' => 'nullable|numeric|min:0',
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
