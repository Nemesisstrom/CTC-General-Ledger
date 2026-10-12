<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ChartOfAccountController;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\Reports\GeneralLedgerReportController;
use App\Http\Controllers\Reports\ProfitLossReportController;
use App\Http\Controllers\Reports\BalanceSheetReportController;
use App\Http\Controllers\Reports\TaxReportController;
use App\Http\Controllers\Reports\PayrollReportController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'loginWeb'])->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'registerWeb'])->name('register.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logoutWeb'])->name('logout');

    // Dashboard UI (Akses Role: Admin & Akuntan)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:admin|akuntan')
        ->name('dashboard');

    // Khusus Admin
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('chart-of-accounts', ChartOfAccountController::class);
    });

    // Transaksi Kas & Jurnal (Berdasarkan Permission Spatie)
    Route::middleware('permission:manage-cash-transactions')->group(function () {
        Route::post('/cash-transactions', [CashTransactionController::class, 'store']);
    });

    Route::middleware('permission:manage-journals')->group(function () {
        Route::get('/journals', [JournalController::class, 'index']);
        Route::post('/journals', [JournalController::class, 'store']);
    });

    Route::post('/journals/{id}/reverse', [JournalController::class, 'reverse'])
        ->middleware('permission:reverse-journals');

    Route::post('/bank-reconciliations', [BankReconciliationController::class, 'store'])
        ->middleware('permission:manage-bank-reconciliations');

    Route::post('/adjustments', [AdjustmentController::class, 'store'])
        ->middleware('permission:manage-adjustments');

    // Pajak & Payroll
    Route::post('/taxes/process', [TaxController::class, 'processTaxTransaction'])
        ->middleware('permission:process-taxes');

    Route::resource('payrolls', PayrollController::class)
        ->middleware('permission:manage-payrolls');

    // Modul Laporan (Diconsolidasi & Diberi Nama Route)
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/general-ledger', [GeneralLedgerReportController::class, 'index'])
            ->middleware('permission:view-reports-general-ledger')
            ->name('general-ledger');

        Route::get('/profit-loss', [ProfitLossReportController::class, 'index'])
            ->middleware('permission:view-reports-profit-loss')
            ->name('profit-loss');

        Route::get('/balance-sheet', [BalanceSheetReportController::class, 'index'])
            ->middleware('permission:view-reports-balance-sheet')
            ->name('balance-sheet');

        Route::get('/tax', [TaxReportController::class, 'index'])
            ->middleware('permission:view-reports-tax')
            ->name('tax');

        Route::get('/payroll', [PayrollReportController::class, 'index'])
            ->middleware('permission:view-reports-payroll')
            ->name('payroll');
    });
});
