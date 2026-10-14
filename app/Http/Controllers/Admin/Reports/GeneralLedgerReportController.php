<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use App\Exports\GeneralLedgerExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class GeneralLedgerReportController extends Controller
{
    public function index(Request $request)
    {
        $accounts = ChartOfAccount::where('is_active', true)
            ->orderBy('code', 'asc')
            ->get();

        $reportData = $this->generateReportData($request);

        if ($request->wantsJson()) {
            return response()->json(['data' => $reportData]);
        }

        return view('admin.reports.general_ledger', compact('accounts', 'reportData'));
    }

    /**
     * Membangun Struktur Data Laporan Buku Besar (General Ledger)
     */
    private function generateReportData(Request $request): array
    {
        $accountId = $request->input('account_id');
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-t'));

        $selectedAccount = null;
        $openingBalance  = 0;
        $ledgerEntries   = collect();
        $totalDebit      = 0;
        $totalCredit     = 0;

        if ($accountId) {
            $selectedAccount = ChartOfAccount::findOrFail($accountId);

            // 1. Hitung Saldo Awal (Opening Balance) sebelum $startDate
            $totalsPrev = JournalDetail::where(function($q) use ($accountId) {
                    $q->where('chart_of_account_id', $accountId)
                      ->orWhere('account_id', $accountId);
                })
                ->whereHas('journal', function ($q) use ($startDate) {
                    $q->where('journal_date', '<', $startDate)
                      ->where('status', 'posted');
                })
                ->selectRaw('SUM(debit) as prev_debit, SUM(credit) as prev_credit')
                ->first();

            $prevDebit  = $totalsPrev->prev_debit ?? 0;
            $prevCredit = $totalsPrev->prev_credit ?? 0;

            $normalBalance = strtolower($selectedAccount->normal_balance ?? ($selectedAccount->type === 'asset' || $selectedAccount->type === 'expense' ? 'debit' : 'credit'));

            if ($normalBalance === 'debit') {
                $openingBalance = $prevDebit - $prevCredit;
            } else {
                $openingBalance = $prevCredit - $prevDebit;
            }

            // 2. Ambil Transaksi Jurnal dalam Rentang Periode
            $ledgerEntries = JournalDetail::with('journal')
                ->where(function($q) use ($accountId) {
                    $q->where('chart_of_account_id', $accountId)
                      ->orWhere('account_id', $accountId);
                })
                ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('journal_date', [$startDate, $endDate])
                      ->where('status', 'posted');
                })
                ->get()
                ->sortBy(fn ($entry) => $entry->journal->journal_date ?? $entry->created_at);

            // 3. Hitung Running Balance untuk Setiap Transaksi
            $currentRunningBalance = $openingBalance;
            $ledgerEntries->transform(function ($entry) use ($normalBalance, &$currentRunningBalance) {
                if ($normalBalance === 'debit') {
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

        return [
            'account_id'       => $accountId,
            'selected_account' => $selectedAccount,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'opening_balance'  => $openingBalance,
            'ledger_entries'   => $ledgerEntries,
            'total_debit'      => $totalDebit,
            'total_credit'     => $totalCredit,
            'ending_balance'   => $selectedAccount
                ? (($selectedAccount->normal_balance ?? 'debit') === 'debit'
                    ? ($openingBalance + $totalDebit - $totalCredit)
                    : ($openingBalance + $totalCredit - $totalDebit))
                : 0,
        ];
    }

    public function exportPdf(Request $request)
    {
        $reportData = $this->generateReportData($request);

        if (!$reportData['selected_account']) {
            return redirect()->back()->with('error', 'Silakan pilih akun terlebih dahulu untuk mengunduh laporan PDF.');
        }

        $pdf = Pdf::loadView('admin.reports.pdf.general_ledger', compact('reportData'));
        return $pdf->download('Laporan_Buku_Besar_' . $reportData['selected_account']->code . '_' . date('Ymd') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $accountId = $request->input('account_id');
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-t'));

        return Excel::download(
            new GeneralLedgerExport($accountId, $startDate, $endDate),
            'Laporan_Buku_Besar_' . date('Ymd') . '.xlsx'
        );
    }
}