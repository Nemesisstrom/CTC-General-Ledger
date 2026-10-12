<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use Illuminate\Http\Request;

class BalanceSheetReportController extends Controller
{
    public function index(Request $request)
    {
        $asOfDate = $request->input('as_of_date', date('Y-m-d'));

        // 1. Ambil akun Aset (Asset)
        $assets = $this->getAccountGroupBalances('asset', 'debit', $asOfDate);

        // 2. Ambil akun Kewajiban (Liability)
        $liabilities = $this->getAccountGroupBalances('liability', 'credit', $asOfDate);

        // 3. Ambil akun Ekuitas (Equity)
        $equities = $this->getAccountGroupBalances('equity', 'credit', $asOfDate);

        // 4. Hitung Laba/Rugi Berjalan (Current Year Earnings) sampai tanggal Laporan
        $currentEarnings = $this->calculateCurrentEarnings($asOfDate);

        // Total Kalkulasi
        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equities->sum('balance') + $currentEarnings;
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

        $reportData = [
            'as_of_date'                   => $asOfDate,
            'assets'                       => $assets,
            'liabilities'                  => $liabilities,
            'equities'                     => $equities,
            'current_earnings'             => $currentEarnings,
            'total_assets'                 => $totalAssets,
            'total_liabilities'            => $totalLiabilities,
            'total_equity'                 => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'is_balanced'                  => round($totalAssets, 2) === round($totalLiabilitiesAndEquity, 2),
        ];

        if ($request->wantsJson()) {
            return response()->json(['data' => $reportData]);
        }

        return view('admin.reports.balance_sheet', compact('reportData'));
    }

    /**
     * Hitung Saldo Akun Berdasarkan Tipe dan Saldo Normal
     */
    private function getAccountGroupBalances(string $type, string $normalBalance, string $asOfDate)
    {
        return ChartOfAccount::where('type', $type)
            ->where('is_active', true)
            ->get()
            ->map(function ($account) use ($normalBalance, $asOfDate) {
                $debit = JournalDetail::where('account_id', $account->id)
                    ->whereHas('journal', function ($q) use ($asOfDate) {
                        $q->where('journal_date', '<=', $asOfDate)
                          ->where('status', 'posted');
                    })
                    ->sum('debit');

                $credit = JournalDetail::where('account_id', $account->id)
                    ->whereHas('journal', function ($q) use ($asOfDate) {
                        $q->where('journal_date', '<=', $asOfDate)
                          ->where('status', 'posted');
                    })
                    ->sum('credit');

                $balance = ($normalBalance === 'debit') ? ($debit - $credit) : ($credit - $debit);
                $account->balance = $balance;

                return $account;
            })
            ->filter(fn ($account) => $account->balance != 0); // Tampilkan hanya akun bersaldo
    }

    /**
     * Hitung Akumulasi Laba/Rugi Berjalan (Revenue - Expense)
     */
    private function calculateCurrentEarnings(string $asOfDate): float
    {
        $revenueDebit = JournalDetail::whereHas('account', fn ($q) => $q->where('type', 'revenue'))
            ->whereHas('journal', fn ($q) => $q->where('journal_date', '<=', $asOfDate)->where('status', 'posted'))
            ->sum('debit');

        $revenueCredit = JournalDetail::whereHas('account', fn ($q) => $q->where('type', 'revenue'))
            ->whereHas('journal', fn ($q) => $q->where('journal_date', '<=', $asOfDate)->where('status', 'posted'))
            ->sum('credit');

        $totalRevenue = $revenueCredit - $revenueDebit;

        $expenseDebit = JournalDetail::whereHas('account', fn ($q) => $q->where('type', 'expense'))
            ->whereHas('journal', fn ($q) => $q->where('journal_date', '<=', $asOfDate)->where('status', 'posted'))
            ->sum('debit');

        $expenseCredit = JournalDetail::whereHas('account', fn ($q) => $q->where('type', 'expense'))
            ->whereHas('journal', fn ($q) => $q->where('journal_date', '<=', $asOfDate)->where('status', 'posted'))
            ->sum('credit');

        $totalExpense = $expenseDebit - $expenseCredit;

        return $totalRevenue - $totalExpense;
    }
}
