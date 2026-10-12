<?php

namespace App\Http\Controllers;

use App\Models\BankReconciliation;
use App\Models\JournalDetail;
use Illuminate\Http\Request;

class BankReconciliationController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'account_id' => 'required|exists:chart_of_accounts,id',
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric'
        ]);

        // Hitung Saldo Buku dari Jurnal Detail
        $debitSum = JournalDetail::where('account_id', $request->account_id)->sum('debit');
        $creditSum = JournalDetail::where('account_id', $request->account_id)->sum('credit');
        $bookBalance = $debitSum - $creditSum;

        $difference = $request->statement_balance - $bookBalance;

        $reconcile = BankReconciliation::create([
            'account_id' => $request->account_id,
            'statement_date' => $request->statement_date,
            'statement_balance' => $request->statement_balance,
            'book_balance' => $bookBalance,
            'difference' => $difference,
            'status' => ($difference == 0) ? 'reconciled' : 'pending',
            'reconciled_by' => auth()->id()
        ]);

        return response()->json(['message' => 'Proses rekonsiliasi bank tersimpan', 'data' => $reconcile]);
    }
}
