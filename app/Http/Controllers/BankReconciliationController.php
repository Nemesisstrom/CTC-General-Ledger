<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBankReconciliationRequest;
use App\Models\BankReconciliation;
use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use Illuminate\Http\Request;

class BankReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $reconciliations = BankReconciliation::with(['account', 'reconciler'])
            ->orderBy('statement_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        // Hanya mengambil akun bertipe Asset (Kas/Bank)
        $bankAccounts = ChartOfAccount::where('type', 'asset')
            ->where('is_active', true)
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['data' => $reconciliations]);
        }

        return view('admin.bank_reconciliation.index', compact('reconciliations', 'bankAccounts'));
    }

    public function store(StoreBankReconciliationRequest $request)
    {
        $accountId = $request->account_id;
        $statementDate = $request->statement_date;
        $statementBalance = $request->statement_balance;

        // 1. Hitung Saldo Buku (Book Balance) dari Jurnal Umum ter-posting per tanggal statement
        $debitSum = JournalDetail::where('account_id', $accountId)
            ->whereHas('journal', function ($q) use ($statementDate) {
                $q->where('journal_date', '<=', $statementDate)
                  ->where('status', 'posted');
            })
            ->sum('debit');

        $creditSum = JournalDetail::where('account_id', $accountId)
            ->whereHas('journal', function ($q) use ($statementDate) {
                $q->where('journal_date', '<=', $statementDate)
                  ->where('status', 'posted');
            })
            ->sum('credit');

        // Saldo Normal Kas/Bank adalah Debit
        $bookBalance = $debitSum - $creditSum;
        $difference = $statementBalance - $bookBalance;
        $status = (round($difference, 2) == 0) ? 'reconciled' : 'pending';

        $reconciliation = BankReconciliation::create([
            'account_id'        => $accountId,
            'statement_date'    => $statementDate,
            'statement_balance' => $statementBalance,
            'book_balance'      => $bookBalance,
            'difference'        => $difference,
            'status'            => $status,
            'notes'             => $request->notes,
            'reconciled_by'     => auth()->id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Rekonsiliasi bank berhasil diproses',
                'data'    => $reconciliation
            ], 201);
        }

        $message = ($status === 'reconciled')
            ? 'Rekonsiliasi Berhasil! Saldo bank dan pembukuan cocok (MATCH).'
            : 'Rekonsiliasi Disimpan. Terdapat selisih sebesar Rp ' . number_format(abs($difference), 0, ',', '.');

        return redirect()->route('bank-reconciliations.index')->with('success', $message);
    }
}
