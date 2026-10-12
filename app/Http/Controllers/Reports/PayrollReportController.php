<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use Illuminate\Http\Request;

class PayrollReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->period ?? date('Y-m');
        $payrolls = Payroll::with(['user', 'items'])->where('period', $period)->get();

        return response()->json([
            'period' => $period,
            'total_disbursed' => $payrolls->sum('net_salary'),
            'total_pph21_collected' => $payrolls->sum('pph21_amount'),
            'payrolls' => $payrolls
        ]);
    }
}
