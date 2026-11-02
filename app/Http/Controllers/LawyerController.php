<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LawyerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $lawyers = User::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('role', 'like', "%{$search}%");
            })
            ->orderBy('name', 'asc')
            ->get();

        return inertia('Lawyers/Index', [
            'lawyers' => $lawyers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(['Partner', 'Associate Attorney','Assistant Attornry', 'Paralegal'])],
            'standard_hourly_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Lawyer details updated successfully.');
    }
}