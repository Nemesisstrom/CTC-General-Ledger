<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use App\Exports\ProfitLossExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ProfitLossReportController extends Controller
{
    public function index(Request $request)
    {
        $reportData = $this->generateReportData($request);

        if ($request->wantsJson()) {
            return response()->json(['data' => $reportData]);
        }

        return view('admin.reports.profit_loss', compact('reportData'));
    }

    /**
     * Membangun Struktur Data Laporan Laba Rugi (Profit & Loss)
     */
    private function generateReportData(Request $request): array
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-t'));

        // 1. Ambil Pendapatan (Revenue Accounts)
        $revenues = $this->getAccountPeriodBalances('revenue', 'credit', $startDate, $endDate);

        // 2. Ambil Beban/Biaya (Expense Accounts)
        $expenses = $this->getAccountPeriodBalances('expense', 'debit', $startDate, $endDate);

        // Kalkulasi Total
        $totalRevenue = $revenues->sum('period_balance');
        $totalExpense = $expenses->sum('period_balance');
        $netProfit    = $totalRevenue - $totalExpense;

        return [
            'start_date'    => $startDate,
            'end_date'      => $endDate,
            'revenues'      => $revenues,
            'expenses'      => $expenses,
            'total_revenue' => $totalRevenue,
            'total_expense' => $totalExpense,
            'net_profit'    => $netProfit,
        ];
    }

    /**
     * Hitung Pergerakan Saldo Akun Dalam Periode Tertentu (Mencegah N+1 Query Problem)
     */
    private function getAccountPeriodBalances(string $type, string $normalBalance, string $startDate, string $endDate)
    {
        return ChartOfAccount::where('type', $type)
            ->where('is_active', true)
            ->withSum(['journalDetails as total_debit' => function ($query) use ($startDate, $endDate) {
                $query->whereHas('journal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('journal_date', [$startDate, $endDate])
                      ->where('status', 'posted');
                });
            }], 'debit')
            ->withSum(['journalDetails as total_credit' => function ($query) use ($startDate, $endDate) {
                $query->whereHas('journal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('journal_date', [$startDate, $endDate])
                      ->where('status', 'posted');
                });
            }], 'credit')
            ->get()
            ->map(function ($account) use ($normalBalance) {
                $debit  = $account->total_debit ?? 0;
                $credit = $account->total_credit ?? 0;

                $account->period_balance = ($normalBalance === 'credit') ? ($credit - $debit) : ($debit - $credit);
                return $account;
            })
            ->filter(fn ($account) => $account->period_balance != 0); // Tampilkan hanya akun yang memiliki aktivitas
    }

    public function exportPdf(Request $request)
    {
        $reportData = $this->generateReportData($request);

        $pdf = Pdf::loadView('admin.reports.pdf.profit_loss', compact('reportData'));
        return $pdf->download('Laporan_Laba_Rugi_' . date('Ymd') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-t'));

        return Excel::download(
            new ProfitLossExport($startDate, $endDate),
            'Laporan_Laba_Rugi_' . date('Ymd') . '.xlsx'
        );
    }
}