<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
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

// Root redirect ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth Routes (Tamu / Belum Login)
Route::middleware('guest')->group(function () {
    // Form & Proses Login
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'loginWeb'])->name('login.post');

    // Form & Proses Registrasi
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'registerWeb'])->name('register.post');

    // Fitur Lupa & Reset Password
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkWeb'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPasswordWeb'])->name('password.update');
});

// Authenticated Routes (Sudah Login)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logoutWeb'])->name('logout');

    // Route Verifikasi Email
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->middleware(['throttle:6,1'])->name('verification.send');

    // Dashboard UI (Akses Role: Admin & Akuntan)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('auth', 'role:admin|akuntan')//'verified'
        ->name('dashboard');

    // Khusus Admin
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('chart-of-accounts', ChartOfAccountController::class);
    });

    // Modul Transaksi & Jurnal
    Route::middleware('permission:manage-journals')->group(function () {
        Route::get('/journals', [JournalController::class, 'index'])->name('journals.index');
        Route::post('/journals', [JournalController::class, 'store'])->name('journals.store');
    });

    Route::post('/journals/{id}/reverse', [JournalController::class, 'reverse'])
        ->middleware('permission:reverse-journals')
        ->name('journals.reverse');

    // Modul Kas, Rekonsiliasi, Penyesuaian, Pajak, & Payroll (Menggunakan Resource yang bersih)
    Route::middleware('permission:manage-cash-transactions')->group(function () {
        Route::resource('cash-transactions', CashTransactionController::class)->only(['index', 'store']);
    });

    Route::middleware('permission:manage-bank-reconciliations')->group(function () {
        Route::resource('bank-reconciliations', BankReconciliationController::class)->only(['index', 'store']);
    });

    Route::middleware('permission:manage-adjustments')->group(function () {
        Route::resource('adjustments', AdjustmentController::class)->only(['index', 'store']);
    });

    Route::middleware('permission:process-taxes')->group(function () {
        Route::get('/taxes', [TaxController::class, 'index'])->name('taxes.index');
        Route::post('/taxes/process', [TaxController::class, 'processTaxTransaction'])->name('taxes.process');
    });

    Route::middleware('permission:manage-payrolls')->group(function () {
        Route::resource('payrolls', PayrollController::class)->only(['index', 'store']);
    });

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
