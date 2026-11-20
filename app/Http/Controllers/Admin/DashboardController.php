<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashTransaction;
use App\Models\Journal;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $selectedMonth = (int) $request->input('month', now()->month);
        $selectedYear  = (int) $request->input('year', now()->year);

        $startOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();

        // 1. Kartu Ringkasan (Dihitung terpisah khusus bulan & tahun terpilih)
        $kasMasuk = (float) CashTransaction::where('type', 'in')
            ->whereBetween('transaction_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->sum('amount');

        $kasKeluar = (float) CashTransaction::where('type', 'out')
            ->whereBetween('transaction_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->sum('amount');

        $labaRugiBersih = $kasMasuk - $kasKeluar;

        // Penyesuaian otomatis nama kolom tanggal & nominal pada tabel payrolls
        $payrollQuery = Payroll::query();
        $payrollAmountCol = Schema::hasColumn('payrolls', 'net_salary')
            ? 'net_salary'
            : (Schema::hasColumn('payrolls', 'total_amount') ? 'total_amount' : 'amount');

        if (Schema::hasColumn('payrolls', 'payment_date')) {
            $payrollQuery->whereBetween('payment_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);
        } elseif (Schema::hasColumn('payrolls', 'payroll_date')) {
            $payrollQuery->whereBetween('payroll_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()]);
        } elseif (Schema::hasColumn('payrolls', 'month') && Schema::hasColumn('payrolls', 'year')) {
            $payrollQuery->where('month', $selectedMonth)->where('year', $selectedYear);
        } elseif (Schema::hasColumn('payrolls', 'period')) {
            $payrollQuery->where('period', $startOfMonth->format('Y-m'));
        } else {
            $payrollQuery->whereBetween('created_at', [$startOfMonth->startOfDay(), $endOfMonth->endOfDay()]);
        }

        $gajiBulanIni = (float) $payrollQuery->sum($payrollAmountCol);

        // 2. Aktivitas Terbaru (Difilter pada bulan terpilih)
        $transaksiTerakhir = CashTransaction::whereBetween('transaction_date', [
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString(),
            ])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $pembalikanTerakhir = Journal::where('journal_number', 'like', 'REV-%')
            ->whereBetween('journal_date', [
                $startOfMonth->toDateString(),
                $endOfMonth->toDateString(),
            ])
            ->orderByDesc('journal_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        // 3. Data Grafik 6 Bulan Terakhir (mundur dari bulan yang dipilih)
        $monthlyLabels = [];
        $incomeData    = [];
        $expenseData   = [];

        for ($i = 5; $i >= 0; $i--) {
            $cursor = $startOfMonth->copy()->subMonths($i);
            $monthlyLabels[] = $cursor->translatedFormat('M Y');

            $incomeData[] = (float) CashTransaction::where('type', 'in')
                ->whereYear('transaction_date', $cursor->year)
                ->whereMonth('transaction_date', $cursor->month)
                ->sum('amount');

            $expenseData[] = (float) CashTransaction::where('type', 'out')
                ->whereYear('transaction_date', $cursor->year)
                ->whereMonth('transaction_date', $cursor->month)
                ->sum('amount');
        }

        // Daftar tahun tersedia (kompatibel untuk MySQL & SQLite)
        $availableYears = CashTransaction::pluck('transaction_date')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->year)
            ->unique()
            ->sortDesc()
            ->values();

        if ($availableYears->isEmpty()) {
            $availableYears = collect([now()->year]);
        }

        $data = [
            'kas_masuk'           => $kasMasuk,
            'kas_keluar'          => $kasKeluar,
            'laba_rugi_bersih'    => $labaRugiBersih,
            'gaji_bulan_ini'      => $gajiBulanIni,
            'transaksi_terakhir'  => $transaksiTerakhir,
            'pembalikan_terakhir' => $pembalikanTerakhir,
        ];

        return view('admin.dashboard', compact(
            'data',
            'selectedMonth',
            'selectedYear',
            'availableYears',
            'monthlyLabels',
            'incomeData',
            'expenseData'
        ));
    }
}
