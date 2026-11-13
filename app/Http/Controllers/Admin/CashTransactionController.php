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

        // 2. Dropdown AKUN KAS / BANK UTAMA (hanya akun turunan kode kepala 1-11)
        $cashAccounts = ChartOfAccount::where('is_active', true)
            ->whereNotNull('parent_id')
            ->where('code', 'like', '1-11%')
            ->orderBy('code')
            ->get();

        // 3. Dropdown AKUN LAWAN (hanya akun turunan selain 1-11%)
        $contraAccounts = ChartOfAccount::where('is_active', true)
            ->whereNotNull('parent_id')
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

    public function destroy($id)
    {
        // Lapisan 1: Pastikan hanya role admin yang memiliki otoritas hard delete
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya Administrator yang memiliki otoritas menghapus transaksi secara permanen.');
        }

        DB::beginTransaction();
        try {
            $cashTransaction = CashTransaction::findOrFail($id);

            // Lapisan 2: Kunci penghapusan jika tahun transaksi sudah di-Tutup Buku
            $trxYear = date('Y', strtotime($cashTransaction->transaction_date));
            if (Journal::where('reference_number', "CLS-{$trxYear}-1231")->exists()) {
                return back()->with('error', "Transaksi tahun {$trxYear} sudah dikunci (Tutup Buku) dan tidak dapat dihapus. Gunakan menu Jurnal Pembalikan.");
            }

            // Cari jurnal otomatis yang diposting dari transaksi kas ini
            $journal = Journal::where('reference_number', $cashTransaction->transaction_number)
                ->orWhere('journal_number', 'JRN-' . $cashTransaction->transaction_number)
                ->first();

            if ($journal) {
                JournalDetail::where('journal_id', $journal->id)->delete();
                $journal->delete();
            }

            $cashTransaction->delete();

            DB::commit();

            return redirect()
                ->route('admin.cash-transactions.index')
                ->with('success', 'Transaksi kas beserta jurnal terkait berhasil dihapus permanen.');
        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus transaksi kas: ' . $e->getMessage());
        }
    }
}
