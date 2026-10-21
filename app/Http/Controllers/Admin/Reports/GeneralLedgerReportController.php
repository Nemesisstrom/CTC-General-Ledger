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
        $accounts   = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        $reportData = $this->generateReportData($request, $accounts);

        if ($request->wantsJson()) {
            return response()->json(['data' => $reportData]);
        }

        return view('admin.reports.general_ledger', compact('accounts', 'reportData'));
    }

    private function generateReportData(Request $request, $accounts): array
    {
        $accountId = $request->input('account_id', $accounts->first()?->id);
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-t'));

        $selectedAccount = $accountId ? ChartOfAccount::find($accountId) : null;
        $journalEntries  = collect();
        $openingBalance  = 0;
        $endingBalance   = 0;

        if ($selectedAccount) {
            // 1. Hitung Saldo Awal (Saldo sebelum tanggal mulai) - Menggunakan account_id
            $prevTotals = JournalDetail::where('account_id', $selectedAccount->id)
                ->whereHas('journal', function ($q) use ($startDate) {
                    $q->where('journal_date', '<', $startDate)
                      ->where('status', 'posted');
                })
                ->selectRaw('SUM(debit) as prev_debit, SUM(credit) as prev_credit')
                ->first();

            $prevDebit  = $prevTotals->prev_debit ?? 0;
            $prevCredit = $prevTotals->prev_credit ?? 0;

            $openingBalance = $selectedAccount->normal_balance === 'debit'
                ? ($prevDebit - $prevCredit)
                : ($prevCredit - $prevDebit);

            // 2. Ambil Transaksi pada Periode Pilihan - Menggunakan account_id
            $journalEntries = JournalDetail::with('journal')
                ->where('account_id', $selectedAccount->id)
                ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('journal_date', [$startDate, $endDate])
                      ->where('status', 'posted');
                })
                ->get()
                ->sortBy(fn ($entry) => $entry->journal->journal_date ?? $entry->created_at)
                ->values();

            // 3. Hitung Running Balance
            $currentBalance = $openingBalance;
            foreach ($journalEntries as $entry) {
                if ($selectedAccount->normal_balance === 'debit') {
                    $currentBalance += ($entry->debit - $entry->credit);
                } else {
                    $currentBalance += ($entry->credit - $entry->debit);
                }
                $entry->running_balance = $currentBalance;
            }

            $endingBalance = $currentBalance;
        }

        return [
            'account_id'       => $accountId,
            'selected_account' => $selectedAccount,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'opening_balance'  => $openingBalance,
            'ending_balance'   => $endingBalance,
            'entries'          => $journalEntries,
        ];
    }

    public function exportPdf(Request $request)
    {
        $accounts   = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        $reportData = $this->generateReportData($request, $accounts);

        if (!$reportData['selected_account']) {
            return redirect()->back()->with('error', 'Silakan pilih akun buku besar terlebih dahulu.');
        }

        $pdf = Pdf::loadView('admin.reports.pdf.general_ledger', compact('reportData'));
        return $pdf->download('Laporan_Buku_Besar_' . ($reportData['selected_account']->code ?? 'ALL') . '_' . date('Ymd_His') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $accounts   = ChartOfAccount::where('is_active', true)->orderBy('code')->get();
        $reportData = $this->generateReportData($request, $accounts);

        if (!$reportData['selected_account']) {
            return redirect()->back()->with('error', 'Silakan pilih akun buku besar terlebih dahulu.');
        }

        return Excel::download(
            new GeneralLedgerExport($reportData['account_id'], $reportData['start_date'], $reportData['end_date']),
            'Laporan_Buku_Besar_' . ($reportData['selected_account']->code ?? 'ALL') . '_' . date('Ymd_His') . '.xlsx'
        );
    }
}
