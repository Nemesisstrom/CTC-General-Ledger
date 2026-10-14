<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->latest()->get();
        $roles = Role::all();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|min:6',
            'role'        => 'required|string|exists:roles,name',
            'base_salary' => 'nullable|numeric|min:0',
        ]);

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => $request->role,
            'base_salary' => $request->base_salary ?? 0,
            'is_active'   => true,
        ]);

        // Assign Role Spatie
        $user->assignRole($request->role);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'        => 'required|string|exists:roles,name',
            'base_salary' => 'nullable|numeric|min:0',
            'is_active'   => 'required|boolean',
        ]);

        $userData = [
            'name'        => $request->name,
            'email'       => $request->email,
            'role'        => $request->role,
            'base_salary' => $request->base_salary ?? $user->base_salary,
            'is_active'   => $request->is_active,
        ];

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6']);
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        // Sync Spatie Role
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}