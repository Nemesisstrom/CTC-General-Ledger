<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use App\Exports\BalanceSheetExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class BalanceSheetReportController extends Controller
{
    public function index(Request $request)
    {
        $reportData = $this->generateReportData($request);

        if ($request->wantsJson()) {
            return response()->json(['data' => $reportData]);
        }

        return view('admin.reports.balance_sheet', compact('reportData'));
    }

    /**
     * Membangun Struktur Data Laporan Neraca
     */
    private function generateReportData(Request $request): array
    {
        $asOfDate = $request->input('as_of_date', date('Y-m-d'));

        // 1. Ambil akun Aset
        $assets = $this->getAccountGroupBalances('asset', 'debit', $asOfDate);

        // 2. Ambil akun Kewajiban
        $liabilities = $this->getAccountGroupBalances('liability', 'credit', $asOfDate);

        // 3. Ambil akun Ekuitas
        $equities = $this->getAccountGroupBalances('equity', 'credit', $asOfDate);

        // 4. Hitung Laba/Rugi Berjalan (Current Year Earnings)
        $currentEarnings = $this->calculateCurrentEarnings($asOfDate);

        // Total Kalkulasi
        $totalAssets = $assets->sum('balance');
        $totalLiabilities = $liabilities->sum('balance');
        $totalEquity = $equities->sum('balance') + $currentEarnings;
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

        return [
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
    }

    /**
     * Hitung Saldo Akun secara Efisien (Mencegah N+1 Query Problem)
     */
    private function getAccountGroupBalances(string $type, string $normalBalance, string $asOfDate)
    {
        return ChartOfAccount::where('type', $type)
            ->where('is_active', true)
            ->withSum(['journalDetails as total_debit' => function ($query) use ($asOfDate) {
                $query->whereHas('journal', function ($q) use ($asOfDate) {
                    $q->where('journal_date', '<=', $asOfDate)
                      ->where('status', 'posted');
                });
            }], 'debit')
            ->withSum(['journalDetails as total_credit' => function ($query) use ($asOfDate) {
                $query->whereHas('journal', function ($q) use ($asOfDate) {
                    $q->where('journal_date', '<=', $asOfDate)
                      ->where('status', 'posted');
                });
            }], 'credit')
            ->get()
            ->map(function ($account) use ($normalBalance) {
                $debit = $account->total_debit ?? 0;
                $credit = $account->total_credit ?? 0;

                $account->balance = ($normalBalance === 'debit') ? ($debit - $credit) : ($credit - $debit);
                return $account;
            })
            ->filter(fn ($account) => $account->balance != 0);
    }

    /**
     * Hitung Akumulasi Laba/Rugi Berjalan (Revenue - Expense) dalam 2 Query Ringkas
     */
    private function calculateCurrentEarnings(string $asOfDate): float
    {
        // Total Pendapatan (Kredit - Debit)
        $revenue = JournalDetail::whereHas('account', fn ($q) => $q->where('type', 'revenue'))
            ->whereHas('journal', fn ($q) => $q->where('journal_date', '<=', $asOfDate)->where('status', 'posted'))
            ->selectRaw('SUM(credit) - SUM(debit) as total')
            ->value('total') ?? 0;

        // Total Beban (Debit - Kredit)
        $expense = JournalDetail::whereHas('account', fn ($q) => $q->where('type', 'expense'))
            ->whereHas('journal', fn ($q) => $q->where('journal_date', '<=', $asOfDate)->where('status', 'posted'))
            ->selectRaw('SUM(debit) - SUM(credit) as total')
            ->value('total') ?? 0;

        return $revenue - $expense;
    }

    public function exportPdf(Request $request)
    {
        $reportData = $this->generateReportData($request);

        $pdf = Pdf::loadView('admin.reports.pdf.balance_sheet', compact('reportData'));
        return $pdf->download('Laporan_Neraca_Per_' . $reportData['as_of_date'] . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $asOfDate = $request->input('as_of_date', date('Y-m-d'));

        return Excel::download(
            new BalanceSheetExport($asOfDate),
            'Laporan_Neraca_Per_' . $asOfDate . '.xlsx'
        );
    }
}