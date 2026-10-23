<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBankReconciliationRequest;
use App\Models\BankReconciliation;
use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class BankReconciliationController extends Controller
{
    public function index(Request $request)
    {
        // Mengambil seluruh catatan rekonsiliasi bank beserta relasi akun & reconciler
        $reconciliations = BankReconciliation::with(['account', 'reconciler'])
            ->orderBy('statement_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        // Hanya mengambil akun bertipe Asset (Kas/Bank) yang aktif
        $bankAccounts = ChartOfAccount::where('type', 'asset')
            ->where('is_active', true)
            ->orderBy('code', 'asc')
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

        DB::beginTransaction();
        try {
            // 1. Hitung Total Debit & Kredit dalam 1 Query Aggregation
            $totals = JournalDetail::where('account_id', $accountId)
                ->whereHas('journal', function ($q) use ($statementDate) {
                    $q->where('journal_date', '<=', $statementDate)
                      ->where('status', 'posted');
                })
                ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                ->first();

            $debitSum = $totals->total_debit ?? 0;
            $creditSum = $totals->total_credit ?? 0;

            // Saldo Normal Kas/Bank adalah Debit
            $bookBalance = $debitSum - $creditSum;
            $difference = $statementBalance - $bookBalance;
            $status = (round($difference, 2) == 0) ? 'reconciled' : 'pending';

            // 2. Simpan Rekord Rekonsiliasi Bank
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

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Rekonsiliasi bank berhasil diproses',
                    'data'    => $reconciliation
                ], 201);
            }

            $message = ($status === 'reconciled')
                ? 'Rekonsiliasi Berhasil! Saldo bank dan pembukuan cocok (MATCH).'
                : 'Rekonsiliasi Disimpan. Terdapat selisih sebesar Rp ' . number_format(abs($difference), 0, ',', '.');

            return redirect()
                ->route('admin.bank-reconciliations.index')
                ->with('success', $message);

        } catch (Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Gagal memproses rekonsiliasi: ' . $e->getMessage()], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Gagal memproses rekonsiliasi: ' . $e->getMessage());
        }
    }
}
