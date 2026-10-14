<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Exports\PayrollReportExport;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class PayrollReportController extends Controller
{
    public function index(Request $request)
    {
        $reportData = $this->generateReportData($request);

        if ($request->wantsJson()) {
            return response()->json(['data' => $reportData]);
        }

        return view('admin.reports.payroll_report', compact('reportData'));
    }

    /**
     * Membangun Struktur Data Rekapitulasi Laporan Penggajian
     */
    private function generateReportData(Request $request): array
    {
        $period = $request->input('period', date('Y-m'));

        // Ambil data payroll periode terpilih beserta relasi user dan item tunjangan/potongan
        $payrolls = Payroll::with(['user', 'items'])
            ->where('period', $period)
            ->orderBy('id', 'asc')
            ->get();

        return [
            'period'             => $period,
            'payrolls'           => $payrolls,
            'total_employees'    => $payrolls->count(),
            'total_basic_salary' => $payrolls->sum('basic_salary'),
            'total_allowance'    => $payrolls->sum('total_allowance'),
            'total_deduction'    => $payrolls->sum('total_deduction'),
            'total_pph21'        => $payrolls->sum('pph21_amount'),
            'total_net_salary'   => $payrolls->sum('net_salary'),
        ];
    }

    /**
     * Export Laporan Rekapitulasi Gaji ke PDF
     */
    public function exportPdf(Request $request)
    {
        $reportData = $this->generateReportData($request);

        if ($reportData['payrolls']->isEmpty()) {
            return redirect()
                ->back()
                ->with('error', 'Tidak ditemukan data penggajian pada periode ' . $reportData['period'] . ' untuk diekspor.');
        }

        $pdf = Pdf::loadView('admin.reports.pdf.payroll_report', compact('reportData'))
            ->setPaper('a4', 'landscape'); // Orientasi Lanskap agar kolom muat dengan rapi

        return $pdf->download('Laporan_Rekapitulasi_Gaji_' . $reportData['period'] . '.pdf');
    }

    /**
     * Export Laporan Rekapitulasi Gaji ke Excel
     */
    public function exportExcel(Request $request)
    {
        $period = $request->input('period', date('Y-m'));

        return Excel::download(
            new PayrollReportExport($period),
            'Laporan_Rekapitulasi_Gaji_' . $period . '.xlsx'
        );
    }
}