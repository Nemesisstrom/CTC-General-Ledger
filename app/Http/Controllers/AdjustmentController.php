<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Http\Request;

class AdjustmentController extends Controller
{
    // Jurnal Penyesuaian & Penyusutan
    public function store(Request $request)
    {
        $request->validate([
            'adjustment_date' => 'required|date',
            'description' => 'required|string',
            'expense_account_id' => 'required|exists:chart_of_accounts,id', // Akun Beban (mis: Beban Penyusutan)
            'accumulated_account_id' => 'required|exists:chart_of_accounts,id', // Akun Akumulasi (mis: Akumulasi Penyusutan)
            'amount' => 'required|numeric|min:1'
        ]);

        $journal = Journal::create([
            'journal_number' => 'ADJ-' . time(),
            'journal_date' => $request->adjustment_date,
            'description' => '[Penyesuaian] ' . $request->description,
            'status' => 'posted',
            'created_by' => auth()->id()
        ]);

        // Beban (Debit)
        JournalDetail::create(['journal_id' => $journal->id, 'account_id' => $request->expense_account_id, 'debit' => $request->amount, 'credit' => 0]);
        // Akumulasi / Kewajiban (Kredit)
        JournalDetail::create(['journal_id' => $journal->id, 'account_id' => $request->accumulated_account_id, 'debit' => 0, 'credit' => $request->amount]);

        return response()->json(['message' => 'Jurnal penyesuaian berhasil dicatat', 'data' => $journal->load('details')]);
    }
}
