<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use Illuminate\Http\Request;

class ProfitLossReportController extends Controller
{
    public function index(Request $request)
    {
        // Default periode: Bulan berjalan
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-t'));

        // 1. Ambil Pendapatan (Revenue Accounts)
        $revenues = $this->getAccountPeriodBalances('revenue', 'credit', $startDate, $endDate);

        // 2. Ambil Beban/Biaya (Expense Accounts)
        $expenses = $this->getAccountPeriodBalances('expense', 'debit', $startDate, $endDate);

        // Kalkulasi Total
        $totalRevenue = $revenues->sum('period_balance');
        $totalExpense = $expenses->sum('period_balance');
        $netProfit = $totalRevenue - $totalExpense;

        $reportData = [
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'revenues'      => $revenues,
            'expenses'      => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_profit'    => $netProfit,
        ];

        if ($request->wantsJson()) {
            return response()->json(['data' => $reportData]);
        }

        return view('admin.reports.profit_loss', compact('reportData'));
    }

    /**
     * Hitung Pergerakan Saldo Akun Dalam Periode Tertentu
     */
    private function getAccountPeriodBalances(string $type, string $normalBalance, string $startDate, string $endDate)
    {
        return ChartOfAccount::where('type', $type)
            ->where('is_active', true)
            ->get()
            ->map(function ($account) use ($normalBalance, $startDate, $endDate) {
                $debit = JournalDetail::where('account_id', $account->id)
                    ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('journal_date', [$startDate, $endDate])
                          ->where('status', 'posted');
                    })
                    ->sum('debit');

                $credit = JournalDetail::where('account_id', $account->id)
                    ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('journal_date', [$startDate, $endDate])
                          ->where('status', 'posted');
                    })
                    ->sum('credit');

                $periodBalance = ($normalBalance === 'credit') ? ($credit - $debit) : ($debit - $credit);
                $account->period_balance = $periodBalance;

                return $account;
            })
            ->filter(fn ($account) => $account->period_balance != 0); // Tampilkan hanya akun yang memiliki aktivitas
    }
}
