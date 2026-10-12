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

        return redirect()->route('chart-of-accounts.index')->with('success', 'Akun COA berhasil ditambahkan.');
    }

    public function update(StoreChartOfAccountRequest $request, ChartOfAccount $chartOfAccount)
    {
        $chartOfAccount->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Akun COA berhasil diperbarui', 'data' => $chartOfAccount]);
        }

        return redirect()->route('chart-of-accounts.index')->with('success', 'Akun COA berhasil diperbarui.');
    }

    public function destroy(Request $request, ChartOfAccount $chartOfAccount)
    {
        $chartOfAccount->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Akun COA berhasil dihapus']);
        }

        return redirect()->route('chart-of-accounts.index')->with('success', 'Akun COA berhasil dihapus.');
    }
}
