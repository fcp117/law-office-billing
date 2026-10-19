<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Matter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Index', [
            'users' => User::all(),
            'matters' => Matter::all(),
        ]);
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:Admin,Partner,Associate,Paralegal',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return back()->with('success', 'User created successfully.');
    }

    public function assignUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'matter_id' => 'required|exists:matters,id',
        ]);

        $user = User::findOrFail($request->user_id);
        $matter = Matter::findOrFail($request->matter_id);

        // This writes to "2026_06_30_053735_create_matter_user_table.php" 
        // automatically mapping the user's structural role to the assignment_role on the case.
        $matter->users()->syncWithoutDetaching([
            $user->id => ['assignment_role' => $user->role]
        ]);

        return back()->with('success', $user->name . ' assigned to ' . $matter->name . ' as ' . $user->role);
    }
}