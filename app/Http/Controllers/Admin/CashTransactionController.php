<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashTransactionRequest;
use App\Models\CashTransaction;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class CashTransactionController extends Controller
{
    public function index(Request $request)
    {
       // 1. Ambil riwayat transaksi kas & bank terbaru beserta relasi akunnya
        $transactions = CashTransaction::with(['account', 'oppositeAccount'])
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(15);

        // 2. Dropdown AKUN KAS / BANK UTAMA (hanya kode kepala 1-11)
        $cashAccounts = ChartOfAccount::where('is_active', true)
            ->whereNotNull('parent_id')
            ->where('code', 'like', '1-11%')
            ->orderBy('code')
            ->get();

        // 3. Dropdown AKUN LAWAN (seluruh akun aktif selain 1-11%, termasuk akun induk)
        $contraAccounts = ChartOfAccount::where('is_active', true)
            ->where('code', 'not like', '1-11%')
            ->orderBy('code')
            ->get();

        return view('admin.cash.index', compact(
            'transactions',
            'cashAccounts',
            'contraAccounts'
        ));
    }

    public function store(StoreCashTransactionRequest $request)
    {
        DB::beginTransaction();
        try {
            // 1. Generate Nomor Transaksi Unik (Aman dari Duplikasi)
            $nextId = (CashTransaction::max('id') ?? 0) + 1;
            $prefix = $request->type === 'in' ? 'TRX-IN-' : 'TRX-OUT-';
            $transactionNumber = $prefix . date('Ym') . '-' . sprintf('%04d', $nextId);

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

            // Ambil nama akun lawan untuk penulisan memo jurnal
            $oppositeAccount = ChartOfAccount::find($request->opposite_account_id);
            $oppositeAccountName = $oppositeAccount ? $oppositeAccount->name : 'Akun Lawan';

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
                    'memo'       => 'Penerimaan dari ' . $oppositeAccountName,
                ]);
            } else {
                // Kas Keluar: Akun Lawan (Debit), Kas/Bank (Kredit)
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $request->opposite_account_id,
                    'debit'      => $request->amount,
                    'credit'     => 0,
                    'memo'       => 'Pengeluaran untuk ' . $oppositeAccountName,
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
                return response()->json([
                    'message' => 'Transaksi kas berhasil disimpan & di-jurnal',
                    'data'    => $trx
                ], 201);
            }

            return redirect()
                ->route('admin.cash-transactions.index')
                ->with('success', 'Transaksi kas berhasil dicatat dan diposting ke jurnal.');

        } catch (Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Gagal memproses transaksi: ' . $e->getMessage()], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Gagal memproses transaksi: ' . $e->getMessage())
                ->withInput();
        }
    }
}
