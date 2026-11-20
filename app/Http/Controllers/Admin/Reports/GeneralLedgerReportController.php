<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Exports\GeneralLedgerExport;
use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class GeneralLedgerReportController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = ChartOfAccount::orderBy('code')->get();
        $reportData = $this->buildReportData($request, $accounts);

        return view('admin.reports.general_ledger', compact('accounts', 'reportData'));
    }

    public function exportPdf(Request $request)
    {
        $accounts = ChartOfAccount::orderBy('code')->get();
        $reportData = $this->buildReportData($request, $accounts);

        $pdf = Pdf::loadView('admin.reports.pdf.general_ledger', compact('accounts', 'reportData'))
            ->setPaper('a4', 'landscape');

        return $pdf->download("buku-besar-{$reportData['start_date']}-sd-{$reportData['end_date']}.pdf");
    }

    public function exportExcel(Request $request)
    {
        $accounts = ChartOfAccount::orderBy('code')->get();
        $reportData = $this->buildReportData($request, $accounts);

        return Excel::download(
            new GeneralLedgerExport($reportData),
            "buku-besar-{$reportData['start_date']}-sd-{$reportData['end_date']}.xlsx"
        );
    }

    private function buildReportData(Request $request, $accounts): array
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $accountId = $request->input('account_id', 'all');

        $targetAccounts = ($accountId === 'all' || empty($accountId))
            ? $accounts
            : $accounts->where('id', (int) $accountId)->values();

        $reportAccounts = $targetAccounts->map(function (ChartOfAccount $acc) use ($startDate, $endDate) {
            $isDebitNormal = strtolower($acc->normal_balance ?? '') === 'debit'
                || in_array($acc->type, ['asset', 'expense'], true);

            // Saldo awal periode (untuk akun neraca dihitung kumulatif sebelum start_date, akun laba rugi mulai dari 0 per periode)
            $openingBalance = 0.0;
            if (in_array($acc->type, ['asset', 'liability', 'equity'], true)) {
                $prior = JournalDetail::where('account_id', $acc->id)
                    ->whereHas('journal', fn ($q) => $q->whereDate('journal_date', '<', $startDate))
                    ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
                    ->first();

                $openingBalance = $isDebitNormal
                    ? (float) ($prior->total_debit - $prior->total_credit)
                    : (float) ($prior->total_credit - $prior->total_debit);
            }

            // Mutasi transaksi hanya pada rentang tanggal periode terpilih
            $rawEntries = JournalDetail::with('journal')
                ->where('account_id', $acc->id)
                ->whereHas('journal', fn ($q) => $q->whereBetween('journal_date', [$startDate, $endDate]))
                ->get()
                ->sortBy(fn ($item) => ($item->journal->journal_date ?? '') . '-' . str_pad((string) ($item->journal->id ?? 0), 8, '0', STR_PAD_LEFT))
                ->values();

            $runningBalance = $openingBalance;

            $entries = $rawEntries->map(function ($detail) use (&$runningBalance, $isDebitNormal) {
                $delta = $isDebitNormal
                    ? ((float) $detail->debit - (float) $detail->credit)
                    : ((float) $detail->credit - (float) $detail->debit);

                $runningBalance += $delta;
                $detail->running_balance = $runningBalance;

                return $detail;
            });

            return [
                'account'         => $acc,
                'entries'         => $entries,
                'opening_balance' => $openingBalance,
                'ending_balance'  => $runningBalance,
            ];
        });

        return [
            'account_id'      => $accountId,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'report_accounts' => $reportAccounts,
        ];
    }
}
