<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChartOfAccountRequest;
use App\Models\CashTransaction;
use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use Illuminate\Http\Request;

class ChartOfAccountController extends Controller
{
    /**
     * Daftar kode akun sistem inti yang dilarang dihapus permanen.
     */
    private array $protectedSystemCodes = [
        '1-1100', // Kas & Bank (Induk)
        '1-1110', // Kas Kecil / Kas Utama
        '2-1100', // Utang Usaha
        '2-1200', // Utang Pajak
        '2-1300', // Utang Gaji
        '3-1100', // Modal Disetor
        '3-1200', // Laba Ditahan (Retained Earnings)
    ];

    public function index(Request $request)
    {
        $accounts = ChartOfAccount::with('parent')
            ->orderBy('code', 'asc')
            ->get();

        $parentAccounts = ChartOfAccount::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('code', 'asc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['data' => $accounts]);
        }

        return view('admin.coa.index', compact('accounts', 'parentAccounts'));
    }

    public function store(StoreChartOfAccountRequest $request)
    {
        $validated = $request->validated();

        $account = ChartOfAccount::create([
            'code'        => $validated['code'],
            'name'        => $validated['name'],
            'type'        => $validated['type'],
            'parent_id'   => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Akun perkiraan berhasil ditambahkan.',
                'data'    => $account
            ], 201);
        }

        return redirect()
            ->route('admin.chart-of-accounts.index')
            ->with('success', "Akun [{$account->code}] {$account->name} berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $account = ChartOfAccount::findOrFail($id);

        $validated = $request->validate([
            'code'        => 'required|string|max:20|unique:chart_of_accounts,code,' . $account->id,
            'name'        => 'required|string|max:150',
            'type'        => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id'   => 'nullable|exists:chart_of_accounts,id|not_in:' . $account->id,
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        // Cegah perubahan kode atau tipe jika sudah memiliki transaksi jurnal
        $hasTransactions = JournalDetail::where('account_id', $account->id)->exists();
        if ($hasTransactions && ($validated['code'] !== $account->code || $validated['type'] !== $account->type)) {
            return back()
                ->with('error', 'Kode dan tipe akun tidak dapat diubah karena sudah tercatat dalam riwayat jurnal. Anda tetap dapat mengubah nama atau status aktifnya.')
                ->withInput();
        }

        $account->update([
            'code'        => $validated['code'],
            'name'        => $validated['name'],
            'type'        => $validated['type'],
            'parent_id'   => $validated['parent_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.chart-of-accounts.index')
            ->with('success', "Akun [{$account->code}] {$account->name} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $account = ChartOfAccount::findOrFail($id);

        // 1. Cek apakah merupakan akun sistem inti
        if (in_array($account->code, $this->protectedSystemCodes, true)) {
            return back()->with('error', "Akun [{$account->code}] {$account->name} adalah akun sistem inti dan tidak boleh dihapus.");
        }

        // 2. Cek apakah masih memiliki sub-akun (child)
        if (ChartOfAccount::where('parent_id', $account->id)->exists()) {
            return back()->with('error', "Akun [{$account->code}] {$account->name} masih memiliki sub-akun di bawahnya. Hapus atau pindahkan sub-akun terlebih dahulu.");
        }

        // 3. Cek apakah sudah pernah dipakai di Jurnal Umum atau Transaksi Kas
        $usedInJournal = JournalDetail::where('account_id', $account->id)->exists();
        $usedInCash = CashTransaction::where('account_id', $account->id)
            ->orWhere('opposite_account_id', $account->id)
            ->exists();

        if ($usedInJournal || $usedInCash) {
            return back()->with('error', "Akun [{$account->code}] {$account->name} sudah memiliki riwayat transaksi keuangan sehingga tidak dapat dihapus. Silakan nonaktifkan status akunnya saja.");
        }

        $account->delete();

        return redirect()
            ->route('admin.chart-of-accounts.index')
            ->with('success', "Akun [{$account->code}] {$account->name} berhasil dihapus.");
    }
}
