<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $totalCOA = ChartOfAccount::count();
        $totalJournals = Journal::count();

        // Ringkasan Kas
        $totalInflow = CashTransaction::where('type', 'in')->sum('amount');
        $totalOutflow = CashTransaction::where('type', 'out')->sum('amount');
        $cashBalance = $totalInflow - $totalOutflow;

        // Data Grafik: Tren Pendapatan & Beban 6 Bulan Terakhir
        $monthlyLabels = [];
        $incomeData = [];
        $expenseData = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyLabels[] = $month->translatedFormat('M Y');

            $in = CashTransaction::where('type', 'in')
                ->whereYear('transaction_date', $month->year)
                ->whereMonth('transaction_date', $month->month)
                ->sum('amount');

            $out = CashTransaction::where('type', 'out')
                ->whereYear('transaction_date', $month->year)
                ->whereMonth('transaction_date', $month->month)
                ->sum('amount');

            $incomeData[] = (float) $in;
            $expenseData[] = (float) $out;
        }

        // Data Grafik: Saldo Akun Kas & Bank
        $cashAccounts = ChartOfAccount::whereIn('type', ['asset', 'kas'])
            ->where(function ($query) {
                $query->where('name', 'LIKE', '%kas%')
                    ->orWhere('name', 'LIKE', '%bank%');
            })
            ->get();

        $accountNames = [];
        $accountBalances = [];

        foreach ($cashAccounts as $account) {
            $accountNames[] = $account->name;
            // Menghitung estimasi saldo akun kas/bank
            $debit = DB::table('journal_details')->where('account_id', $account->id)->sum('debit');
            $credit = DB::table('journal_details')->where('account_id', $account->id)->sum('credit');
            $accountBalances[] = (float) ($debit - $credit);
        }

        return view('admin.dashboard', compact(
            'totalUsers',
            'totalCOA',
            'totalJournals',
            'cashBalance',
            'totalInflow',
            'totalOutflow',
            'monthlyLabels',
            'incomeData',
            'expenseData',
            'accountNames',
            'accountBalances'
        ));
    }
}
