<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\JournalDetail;
use App\Models\Tax;
use Illuminate\Http\Request;

class TaxReportController extends Controller
{
    public function index(Request $request)
    {
        $taxId     = $request->input('tax_id');
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-t'));

        // Master Data Pajak untuk Filter Dropdown
        $taxes = Tax::where('is_active', true)->get();

        // Query Detail Jurnal yang Mengandung ID Pajak
        $query = JournalDetail::with(['journal', 'tax', 'account'])
            ->whereNotNull('tax_id')
            ->whereHas('journal', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('journal_date', [$startDate, $endDate])
                  ->where('status', 'posted');
            });

        if ($taxId) {
            $query->where('tax_id', $taxId);
        }

        $taxEntries = $query->get()->sortBy(fn ($entry) => $entry->journal->journal_date);

        // Kalkulasi Total Pungutan/Pemotongan Pajak
        $totalTaxAmount = $taxEntries->sum(function ($entry) {
            return $entry->credit > 0 ? $entry->credit : $entry->debit;
        });

        $reportData = [
            'tax_id'           => $taxId,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'tax_entries'      => $taxEntries,
            'total_tax_amount' => $totalTaxAmount,
        ];

        if ($request->wantsJson()) {
            return response()->json(['data' => $reportData]);
        }

        return view('admin.reports.tax_report', compact('taxes', 'reportData'));
    }
}
