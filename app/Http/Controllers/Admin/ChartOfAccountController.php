<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChartOfAccountRequest;
use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = ChartOfAccount::with('parent')
            ->orderBy('code', 'asc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['data' => $accounts]);
        }

        $parentAccounts = ChartOfAccount::whereNull('parent_id')->get();
        return view('admin.coa.index', compact('accounts', 'parentAccounts'));
    }

    public function store(StoreChartOfAccountRequest $request)
    {
        $coa = ChartOfAccount::create($request->validated());

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Akun COA berhasil dibuat', 'data' => $coa], 201);
        }

        return redirect()->route('admin.chart-of-accounts.index')
            ->with('success', 'Akun COA berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $chartOfAccount = ChartOfAccount::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|unique:chart_of_accounts,code,' . $chartOfAccount->id,
            'name' => 'required|string|max:255',
            'type' => 'required|string',
            'normal_balance' => 'required|in:debit,credit',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ]);

        $chartOfAccount->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Akun COA berhasil diperbarui', 'data' => $chartOfAccount]);
        }

        return redirect()->route('admin.chart-of-accounts.index')
            ->with('success', 'Akun COA berhasil diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        // Mencari data COA secara pasti berdasarkan ID
        $chartOfAccount = ChartOfAccount::findOrFail($id);
        $chartOfAccount->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Akun COA berhasil dihapus']);
        }

        return redirect()->route('admin.chart-of-accounts.index')
            ->with('success', 'Akun COA berhasil dihapus.');
    }
}
