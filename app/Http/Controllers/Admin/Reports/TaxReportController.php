<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\JournalDetail;
use App\Models\Tax;
use App\Exports\TaxReportExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class TaxReportController extends Controller
{
    public function index(Request $request)
    {
        $taxes = Tax::where('is_active', true)->get();
        $reportData = $this->generateReportData($request);

        if ($request->wantsJson()) {
            return response()->json(['data' => $reportData]);
        }

        return view('admin.reports.tax_report', compact('taxes', 'reportData'));
    }

    /**
     * Membangun Struktur Data Laporan Pajak
     */
    private function generateReportData(Request $request): array
    {
        $taxId     = $request->input('tax_id');
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-t'));

        $selectedTax = $taxId ? Tax::find($taxId) : null;

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

        $taxEntries = $query->get()->sortBy(fn ($entry) => $entry->journal->journal_date ?? $entry->created_at);

        // Kalkulasi Total Pungutan/Pemotongan Pajak
        $totalTaxAmount = $taxEntries->sum(function ($entry) {
            return $entry->credit > 0 ? $entry->credit : $entry->debit;
        });

        return [
            'tax_id'           => $taxId,
            'selected_tax'     => $selectedTax,
            'start_date'       => $startDate,
            'end_date'         => $endDate,
            'tax_entries'      => $taxEntries,
            'total_tax_amount' => $totalTaxAmount,
        ];
    }

    public function exportPdf(Request $request)
    {
        $reportData = $this->generateReportData($request);

        if ($reportData['tax_entries']->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'Tidak ditemukan data transaksi pajak pada periode tersebut untuk diekspor.');
        }

        $pdf = Pdf::loadView('admin.reports.pdf.tax_report', compact('reportData'));
        return $pdf->download('Laporan_Rekapitulasi_Pajak_' . date('Ymd') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $taxId     = $request->input('tax_id');
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate   = $request->input('end_date', date('Y-m-t'));

        return Excel::download(
            new TaxReportExport($taxId, $startDate, $endDate),
            'Laporan_Rekapitulasi_Pajak_' . date('Ymd') . '.xlsx'
        );
    }
}