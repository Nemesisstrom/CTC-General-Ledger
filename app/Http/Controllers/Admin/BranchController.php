<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::withCount('users')->latest()->paginate(10);
        return view('admin.branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:branches,code',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:25',
            'address' => 'nullable|string',
        ]);

        Branch::create($validated);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Cabang baru berhasil ditambahkan.');
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:branches,code,' . $branch->id,
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:25',
            'address' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $branch->update($validated);

        return redirect()->route('admin.branches.index')
            ->with('success', 'Data cabang berhasil diperbarui.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->users()->exists() || $branch->journals()->exists()) {
            return redirect()->back()
                ->with('error', 'Cabang tidak dapat dihapus karena masih memiliki data user atau transaksi.');
        }

        if (session('active_branch_id') == $branch->id) {
            session()->forget('active_branch_id');
        }

        $branch->delete();

        return redirect()->route('admin.branches.index')
            ->with('success', 'Cabang berhasil dihapus.');
    }
}
