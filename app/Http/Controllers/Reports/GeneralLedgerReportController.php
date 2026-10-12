<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use Illuminate\Http\Request;

class GeneralLedgerReportController extends Controller
{
    public function index(Request $request)
    {
        $accountId = $request->input('account_id');
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-t'));

        $accounts = ChartOfAccount::where('is_active', true)
            ->orderBy('code', 'asc')
            ->get();

        $selectedAccount = null;
        $openingBalance  = 0;
        $ledgerEntries   = collect();
        $totalDebit      = 0;
        $totalCredit     = 0;

        if ($accountId) {
            $selectedAccount = ChartOfAccount::findOrFail($accountId);

            // 1. Hitung Saldo Awal (Opening Balance) sebelum $startDate
            $prevDebit = JournalDetail::where('account_id', $accountId)
                ->whereHas('journal', function ($q) use ($startDate) {
                    $q->where('journal_date', '<', $startDate)
                      ->where('status', 'posted');
                })
                ->sum('debit');

            $prevCredit = JournalDetail::where('account_id', $accountId)
                ->whereHas('journal', function ($q) use ($startDate) {
                    $q->where('journal_date', '<', $startDate)
                      ->where('status', 'posted');
                })
                ->sum('credit');

            if ($selectedAccount->normal_balance === 'debit') {
                $openingBalance = $prevDebit - $prevCredit;
            } else {
                $openingBalance = $prevCredit - $prevDebit;
            }

            // 2. Ambil Transaksi Jurnal dalam Rentang Periode
            $ledgerEntries = JournalDetail::with('journal')
                ->where('account_id', $accountId)
                ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('journal_date', [$startDate, $endDate])
                      ->where('status', 'posted');
                })
                ->get()
                ->sortBy(fn ($entry) => $entry->journal->journal_date);

            // 3. Hitung Running Balance untuk Setiap Transaksi
            $currentRunningBalance = $openingBalance;
            $ledgerEntries->transform(function ($entry) use ($selectedAccount, &$currentRunningBalance) {
                if ($selectedAccount->normal_balance === 'debit') {
                    $currentRunningBalance += ($entry->debit - $entry->credit);
                } else {
                    $currentRunningBalance += ($entry->credit - $entry->debit);
                }
                $entry->running_balance = $currentRunningBalance;
                return $entry;
            });

            $totalDebit  = $ledgerEntries->sum('debit');
            $totalCredit = $ledgerEntries->sum('credit');
        }

        $reportData = [
            'account_id'       => $accountId,
            'selected_account' => $selectedAccount,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'opening_balance'  => $openingBalance,
            'ledger_entries'   => $ledgerEntries,
            'total_debit'      => $totalDebit,
            'total_credit'     => $totalCredit,
            'ending_balance'   => $selectedAccount
                ? ($selectedAccount->normal_balance === 'debit'
                    ? ($openingBalance + $totalDebit - $totalCredit)
                    : ($openingBalance + $totalCredit - $totalDebit))
                : 0,
        ];

        if ($request->wantsJson()) {
            return response()->json(['data' => $reportData]);
        }

        return view('admin.reports.general_ledger', compact('accounts', 'reportData'));
    }
}
