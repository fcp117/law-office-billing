<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class LawyerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $lawyers = User::query()
            ->with('currentRate')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('role', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('name', 'asc')
            ->get();

        return inertia('Lawyers/Index', [
            'lawyers' => $lawyers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['Partner', 'Admin']), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::in(['Partner', 'Associate', 'Paralegal', 'Admin'])],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Lawyer account created successfully.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'Partner', 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(['Partner', 'Associate', 'Paralegal', 'Admin'])],
            'standard_hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($request->user()->id === $user->id) {
            if (array_key_exists('is_active', $validated) && ! $validated['is_active']) {
                return back()->with('error', 'You cannot deactivate your own Partner account.');
            }

            if ($validated['role'] !== 'Partner') {
                return back()->with('error', 'You cannot demote your own Partner role.');
            }
        }

        $user->update($validated);

        return redirect()->back()->with('success', 'Lawyer details updated successfully.');
    }

    public function toggleActive(Request $request, User $user)
    {
        abort_unless($request->user()->role === 'Partner', 403, 'Only Partners can activate or deactivate accounts.');

        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot deactivate your own Partner account.');
        }

        $newStatus = ! (bool) $user->is_active;

        $user->update([
            'is_active' => $newStatus,
        ]);

        $statusLabel = $newStatus ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "{$user->name}'s account has been {$statusLabel}.");
    }
}