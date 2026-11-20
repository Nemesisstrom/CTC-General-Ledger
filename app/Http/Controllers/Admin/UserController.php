<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with(['roles', 'branch'])
            ->orderBy('id')
            ->paginate(15);

        $roles = Role::orderBy('id')->get();
        $branches = Branch::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'    => ['required', Password::defaults()],
            'role'        => ['required', 'exists:roles,name'],
            'branch_id'   => ['nullable', 'exists:branches,id'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $user = User::create([
            'name'              => $validated['name'],
            'email'             => $validated['email'],
            'password'          => Hash::make($validated['password']),
            'branch_id'         => $validated['branch_id'] ?? null,
            'email_verified_at' => now(),
        ]);

        $user->forceFill([
            'base_salary' => $validated['base_salary'] ?? 0,
            'is_active'   => $request->boolean('is_active', true),
        ])->save();

        $user->assignSystemRole($validated['role']);

        return redirect()->route('admin.users.index')->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password'    => ['nullable', Password::defaults()],
            'role'        => ['required', 'exists:roles,name'],
            'branch_id'   => ['nullable', 'exists:branches,id'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        // Cegah admin menurunkan role-nya sendiri
        if ($user->id === auth()->id() && $validated['role'] !== 'admin') {
            return back()->withErrors(['role' => 'Anda tidak dapat menurunkan role akun Anda sendiri.']);
        }

        // Cegah admin menonaktifkan akunnya sendiri
        $isActive = $request->boolean('is_active', true);
        if ($user->id === auth()->id() && ! $isActive) {
            return back()->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.']);
        }

        // Cegah sistem kehilangan seluruh akun admin aktif
        if ($user->role === 'admin' && ($validated['role'] !== 'admin' || ! $isActive)) {
            $otherActiveAdmins = User::where('role', 'admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveAdmins === 0) {
                return back()->withErrors(['role' => 'Minimal harus ada satu akun Administrator aktif di dalam sistem.']);
            }
        }

        $user->update([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'branch_id' => $validated['branch_id'] ?? null,
        ]);

        if (! empty($validated['password'])) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        $user->forceFill([
            'base_salary' => $validated['base_salary'] ?? $user->base_salary,
            'is_active'   => $isActive,
        ])->save();

        $user->assignSystemRole($validated['role']);

        return redirect()->route('admin.users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['user' => 'Anda tidak dapat menghapus akun Anda sendiri.']);
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->withErrors(['user' => 'Akun Administrator terakhir tidak dapat dihapus.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
