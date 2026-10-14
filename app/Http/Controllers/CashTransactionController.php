<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCashTransactionRequest;
use App\Models\CashTransaction;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashTransactionController extends Controller
{
    public function index(Request $request)
    {
        $transactions = CashTransaction::with(['account', 'oppositeAccount', 'creator'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        // Akun untuk Dropdown Kas & Bank (Asset)
        $cashAccounts = ChartOfAccount::where('type', 'asset')
            ->where('is_active', true)
            ->get();

        // Akun Lawan
        $allAccounts = ChartOfAccount::where('is_active', true)->get();

        if ($request->wantsJson()) {
            return response()->json(['data' => $transactions]);
        }

        return view('admin.cash.index', compact('transactions', 'cashAccounts', 'allAccounts'));
    }

    public function store(StoreCashTransactionRequest $request)
    {
        DB::beginTransaction();
        try {
            // 1. Generate Nomor Transaksi Unik
            $prefix = $request->type === 'in' ? 'TRX-IN-' : 'TRX-OUT-';
            $transactionNumber = $prefix . date('Ym') . '-' . sprintf('%04d', CashTransaction::count() + 1);

            // 2. Simpan Transaksi Kas
            $trx = CashTransaction::create([
                'transaction_number'  => $transactionNumber,
                'transaction_date'    => $request->transaction_date,
                'type'                => $request->type,
                'account_id'          => $request->account_id,
                'opposite_account_id' => $request->opposite_account_id,
                'amount'              => $request->amount,
                'description'         => $request->description,
                'created_by'          => auth()->id(),
            ]);

            // 3. Auto-Posting ke Jurnal Umum (Integrasi Modul Keuangan)
            $journal = Journal::create([
                'journal_number'   => 'JRN-' . $transactionNumber,
                'journal_date'     => $request->transaction_date,
                'reference_number' => $transactionNumber,
                'description'      => ($request->type === 'in' ? '[Kas Masuk] ' : '[Kas Keluar] ') . ($request->description ?? 'Transaksi Kas'),
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            if ($request->type === 'in') {
                // Kas Masuk: Kas/Bank (Debit), Akun Lawan (Kredit)
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $request->account_id,
                    'debit'      => $request->amount,
                    'credit'     => 0,
                    'memo'       => 'Pemasukan Kas/Bank',
                ]);
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $request->opposite_account_id,
                    'debit'      => 0,
                    'credit'     => $request->amount,
                    'memo'       => 'Penerimaan dari ' . $trx->oppositeAccount->name,
                ]);
            } else {
                // Kas Keluar: Akun Lawan (Debit), Kas/Bank (Kredit)
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $request->opposite_account_id,
                    'debit'      => $request->amount,
                    'credit'     => 0,
                    'memo'       => 'Pengeluaran untuk ' . $trx->oppositeAccount->name,
                ]);
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $request->account_id,
                    'debit'      => 0,
                    'credit'     => $request->amount,
                    'memo'       => 'Pengeluaran Kas/Bank',
                ]);
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Transaksi kas berhasil disimpan & di-jurnal', 'data' => $trx], 201);
            }

            return redirect()->route('cash-transactions.index')->with('success', 'Transaksi kas berhasil dicatat dan diposting ke jurnal.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Gagal memproses transaksi: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Gagal memproses transaksi: ' . $e->getMessage())->withInput();
        }
    }
}
