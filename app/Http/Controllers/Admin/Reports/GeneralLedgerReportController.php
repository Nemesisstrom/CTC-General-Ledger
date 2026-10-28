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
        // Berikan 'all' sebagai nilai default jika request account_id kosong
        $accountId = $request->input('account_id', 'all'); 
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-t'));

        $reportAccounts = collect();

        // Logika pengumpulan data akun...
        if ($accountId === 'all') {
            foreach ($accounts as $acc) {
                $accountReport = $this->getAccountReportData($acc, $startDate, $endDate);
                $reportAccounts->push($accountReport);
            }
        } elseif ($accountId) {
            $acc = ChartOfAccount::find($accountId);
            if ($acc) {
                $reportAccounts->push($this->getAccountReportData($acc, $startDate, $endDate));
            }
        }

        return [
            'account_id'      => $accountId,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'report_accounts' => $reportAccounts,
        ];
    }   

    private function getAccountReportData($account, $startDate, $endDate): array
    {
        $prevTotals = JournalDetail::where('account_id', $account->id)
            ->whereHas('journal', function ($q) use ($startDate) {
                $q->where('journal_date', '<', $startDate)
                  ->where('status', 'posted');
            })
            ->selectRaw('SUM(debit) as prev_debit, SUM(credit) as prev_credit')
            ->first();

        $prevDebit  = $prevTotals->prev_debit ?? 0;
        $prevCredit = $prevTotals->prev_credit ?? 0;

        $openingBalance = $account->normal_balance === 'debit'
            ? ($prevDebit - $prevCredit)
            : ($prevCredit - $prevDebit);

        $journalEntries = JournalDetail::with('journal')
            ->where('account_id', $account->id)
            ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('journal_date', [$startDate, $endDate])
                  ->where('status', 'posted');
            })
            ->get()
            ->sortBy(fn ($entry) => $entry->journal->journal_date ?? $entry->created_at)
            ->values();

        $currentBalance = $openingBalance;
        foreach ($journalEntries as $entry) {
            if ($account->normal_balance === 'debit') {
                $currentBalance += ($entry->debit - $entry->credit);
            } else {
                $currentBalance += ($entry->credit - $entry->debit);
            }
            $entry->running_balance = $currentBalance;
        }

        return [
            'account'         => $account,
            'opening_balance' => $openingBalance,
            'ending_balance'  => $currentBalance,
            'entries'         => $journalEntries,
        ];
    }
}