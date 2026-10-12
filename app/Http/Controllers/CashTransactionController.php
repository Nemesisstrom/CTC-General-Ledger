<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashTransactionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'transaction_date' => 'required|date',
            'type' => 'required|in:in,out',
            'account_id' => 'required|exists:chart_of_accounts,id', // Akun Kas/Bank
            'opposite_account_id' => 'required|exists:chart_of_accounts,id', // Akun Lawan
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // 1. Simpan Transaksi Kas
            $trx = CashTransaction::create([
                'transaction_number' => 'TRX-' . time(),
                'transaction_date' => $request->transaction_date,
                'type' => $request->type,
                'account_id' => $request->account_id,
                'amount' => $request->amount,
                'description' => $request->description,
                'created_by' => auth()->id()
            ]);

            // 2. Auto Post Jurnal Umum
            $journal = Journal::create([
                'journal_number' => 'JRN-TRX-' . $trx->id,
                'journal_date' => $request->transaction_date,
                'description' => 'Auto Transaction: ' . $request->description,
                'status' => 'posted',
                'created_by' => auth()->id()
            ]);

            if ($request->type === 'in') {
                // Kas Masuk: Kas (Debit), Opp_Account (Kredit)
                JournalDetail::create(['journal_id' => $journal->id, 'account_id' => $request->account_id, 'debit' => $request->amount, 'credit' => 0]);
                JournalDetail::create(['journal_id' => $journal->id, 'account_id' => $request->opposite_account_id, 'debit' => 0, 'credit' => $request->amount]);
            } else {
                // Kas Keluar: Opp_Account (Debit), Kas (Kredit)
                JournalDetail::create(['journal_id' => $journal->id, 'account_id' => $request->opposite_account_id, 'debit' => $request->amount, 'credit' => 0]);
                JournalDetail::create(['journal_id' => $journal->id, 'account_id' => $request->account_id, 'debit' => 0, 'credit' => $request->amount]);
            }

            DB::commit();
            return response()->json(['message' => 'Transaksi kas berhasil disimpan & di-jurnal', 'data' => $trx]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
