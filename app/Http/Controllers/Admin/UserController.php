<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'branch'])->latest()->get();
        $roles = Role::all();
        $branches = Branch::where('is_active', true)->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'    => ['required', 'string', 'min:6'],
            'role'        => ['required', 'string', 'exists:roles,name'],
            'branch_id'   => ['nullable', 'exists:branches,id'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'role'              => $validated['role'],
            'branch_id'         => $validated['branch_id'] ?? null,
            'base_salary'       => $validated['base_salary'] ?? 0,
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);

        // Assign Role Spatie
        $user->assignRole($validated['role']);

        return redirect()->route('admin.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'        => ['required', 'string', 'exists:roles,name'],
            'branch_id'   => ['nullable', 'exists:branches,id'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'is_active'   => ['required', 'boolean'],
        ]);

        $userData = [
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'role'        => $validated['role'],
            'branch_id'   => $validated['branch_id'] ?? null,
            'base_salary' => $validated['base_salary'] ?? $user->base_salary,
            'is_active'   => $validated['is_active'],
        ];

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:6']);
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        // Sync Spatie Role
        $user->syncRoles([$validated['role']]);

        return redirect()->route('admin.users.index')->with('success', 'Data user berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }
}
