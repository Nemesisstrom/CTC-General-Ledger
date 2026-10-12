<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\JournalDetail;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ringkasan Kas
        $totalCashIn = CashTransaction::where('type', 'in')->sum('amount');
        $totalCashOut = CashTransaction::where('type', 'out')->sum('amount');
        $netCashFlow = $totalCashIn - $totalCashOut;

        // 2. Ringkasan Laba Rugi dari Buku Besar
        $totalRevenue = JournalDetail::whereHas('account', function ($q) {
            $q->where('type', 'revenue');
        })->sum('credit');

        $totalExpense = JournalDetail::whereHas('account', function ($q) {
            $q->where('type', 'expense');
        })->sum('debit');

        $netProfit = $totalRevenue - $totalExpense;

        // 3. Ringkasan Penggajian
        $currentMonthSalary = Payroll::where('period', date('Y-m'))->sum('net_salary');

        // 4. Statistik Tambahan
        $totalUsers = User::count();
        $recentTransactions = CashTransaction::with('creator')
            ->orderBy('transaction_date', 'desc')
            ->take(5)
            ->get();

        $data = [
            'kas_masuk'          => $totalCashIn,
            'kas_keluar'         => $totalCashOut,
            'arus_kas_bersih'    => $netCashFlow,
            'total_pendapatan'   => $totalRevenue,
            'total_beban'        => $totalExpense,
            'laba_rugi_bersih'   => $netProfit,
            'gaji_bulan_ini'     => $currentMonthSalary,
            'total_karyawan'     => $totalUsers,
            'transaksi_terakhir' => $recentTransactions,
        ];

        // Jika request via API (JSON)
        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data'   => $data,
            ]);
        }

        // Tampilan Web Blade UI
        return view('admin.dashboard', compact('data'));
    }
}
