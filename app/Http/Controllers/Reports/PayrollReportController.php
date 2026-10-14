<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use Illuminate\Http\Request;

class PayrollReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', date('Y-m'));

        // Ambil data payroll periode terpilih beserta relasi user dan item tunjangan/potongan
        $payrolls = Payroll::with(['user', 'items'])
            ->where('period', $period)
            ->orderBy('id', 'asc')
            ->get();

        // Kalkulasi Total Akumulasi Periode
        $totalBasicSalary = $payrolls->sum('basic_salary');
        $totalAllowance   = $payrolls->sum('total_allowance');
        $totalDeduction   = $payrolls->sum('total_deduction');
        $totalPph21       = $payrolls->sum('pph21_amount');
        $totalNetSalary   = $payrolls->sum('net_salary');

        $reportData = [
            'period'             => $period,
            'payrolls'           => $payrolls,
            'total_employees'    => $payrolls->count(),
            'total_basic_salary' => $totalBasicSalary,
            'total_allowance'    => $totalAllowance,
            'total_deduction'    => $totalDeduction,
            'total_pph21'        => $totalPph21,
            'total_net_salary'   => $totalNetSalary,
        ];

        if ($request->wantsJson()) {
            return response()->json(['data' => $reportData]);
        }

        return view('admin.reports.payroll_report', compact('reportData'));
    }
}
