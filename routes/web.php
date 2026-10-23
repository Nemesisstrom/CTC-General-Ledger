<?php

use Illuminate\Support\Facades\Route;

// Auth Controllers
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ChartOfAccountController;
use App\Http\Controllers\Admin\CashTransactionController;
use App\Http\Controllers\Admin\JournalController;
use App\Http\Controllers\Admin\BankReconciliationController;
use App\Http\Controllers\Admin\AdjustmentController;
use App\Http\Controllers\Admin\TaxController;
use App\Http\Controllers\Admin\PayrollController;

// Admin Report Controllers
use App\Http\Controllers\Admin\Reports\GeneralLedgerReportController;
use App\Http\Controllers\Admin\Reports\ProfitLossReportController;
use App\Http\Controllers\Admin\Reports\BalanceSheetReportController;
use App\Http\Controllers\Admin\Reports\TaxReportController;
use App\Http\Controllers\Admin\Reports\PayrollReportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root Redirect ke Halaman Login
Route::get('/', function () {
    return redirect()->route('login');
});

// ==========================================
// Guest Routes (Autentikasi & Reset Password)
// ==========================================
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'loginWeb'])->name('login.post');

    // Registrasi
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'registerWeb'])->name('register.post');

    // Lupa & Reset Password
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkWeb'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPasswordWeb'])->name('password.update');
});

// ==========================================
// Authenticated Routes (Akses Pengguna Terautentikasi)
// ==========================================
Route::middleware('auth')->group(function () {

    // Dashboard Utama (Di luar prefix /admin, nama route: dashboard)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['verified', 'role:admin|akuntan|staff'])
        ->name('dashboard');

    // Logout
    Route::post('/logout', [AuthController::class, 'logoutWeb'])->name('logout');

    // Verifikasi Email
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');

    // ==========================================
    // Panel Admin & Operasional (Prefix: /admin)
    // ==========================================
    Route::prefix('admin')->name('admin.')->group(function () {

        // ------------------------------------------
        // Khusus Role Admin (Manajemen User, COA, & Akses Hapus)
        // ------------------------------------------
        Route::middleware('role:admin')->group(function () {
            Route::resource('users', UserController::class);
            Route::resource('chart-of-accounts', ChartOfAccountController::class);

            // Hak Akses Penghapusan Permanen (Strictly Admin Only)
            Route::delete('adjustments/{id}', [AdjustmentController::class, 'destroy'])->name('adjustments.destroy');
            Route::delete('cash-transactions/{id}', [CashTransactionController::class, 'destroy'])->name('cash-transactions.destroy');
            Route::delete('bank-reconciliations/{id}', [BankReconciliationController::class, 'destroy'])->name('bank-reconciliations.destroy');
            Route::delete('payrolls/{id}', [PayrollController::class, 'destroy'])->name('payrolls.destroy');
        });

        // ------------------------------------------
        // Modul Jurnal Umum & Reversal (Admin & Akuntan)
        // ------------------------------------------
        // Modul Jurnal Umum (Hanya Tampil & Posting)
        Route::middleware('permission:manage-journals')->group(function () {
            Route::get('/journals', [JournalController::class, 'index'])->name('journals.index');
            Route::post('/journals', [JournalController::class, 'store'])->name('journals.store');
        });

        // Modul Khusus Jurnal Pembalikan (Reversal Ledger)
        Route::middleware('permission:reverse-journals')->group(function () {
            Route::get('/journals/reversals', [JournalController::class, 'reversalIndex'])->name('journals.reversals.index');
            Route::post('/journals/{id}/reverse', [JournalController::class, 'reverse'])->name('journals.reverse');
        });

        // ------------------------------------------
        // Modul Transaksi Operasional
        // ------------------------------------------
        Route::middleware('permission:manage-cash-transactions')->group(function () {
            Route::resource('cash-transactions', CashTransactionController::class)->only(['index', 'store']);
        });

        Route::middleware('permission:manage-bank-reconciliations')->group(function () {
            Route::resource('bank-reconciliations', BankReconciliationController::class)->only(['index', 'store', 'update']);
        });

        // Modul Jurnal Penyesuaian & Reversal
        Route::middleware('permission:manage-adjustments')->group(function () {
            Route::get('adjustments', [AdjustmentController::class, 'index'])->name('adjustments.index');
            Route::post('adjustments', [AdjustmentController::class, 'store'])->name('adjustments.store');
            Route::post('adjustments/{id}/reverse', [AdjustmentController::class, 'reverse'])->name('adjustments.reverse');
        });

        // Modul Pajak & Penggajian
        Route::middleware('permission:process-taxes')->group(function () {
            Route::get('/taxes', [TaxController::class, 'index'])->name('taxes.index');
            Route::post('/taxes/process', [TaxController::class, 'processTaxTransaction'])->name('taxes.process');
        });

        Route::middleware('permission:manage-payrolls')->group(function () {
            Route::resource('payrolls', PayrollController::class)->only(['index', 'store']);
        });

        // ==========================================
        // Sub-modul Laporan (Prefix: /admin/reports)
        // ==========================================
        Route::prefix('reports')->name('reports.')->group(function () {

            // 1. Buku Besar (General Ledger)
            Route::middleware('permission:view-reports-general-ledger')->group(function () {
                Route::get('/general-ledger', [GeneralLedgerReportController::class, 'index'])->name('general-ledger');
                Route::get('/general-ledger/pdf', [GeneralLedgerReportController::class, 'exportPdf'])->name('general-ledger.pdf');
                Route::get('/general-ledger/excel', [GeneralLedgerReportController::class, 'exportExcel'])->name('general-ledger.excel');
            });

            // 2. Laba Rugi (Profit & Loss)
            Route::middleware('permission:view-reports-profit-loss')->group(function () {
                Route::get('/profit-loss', [ProfitLossReportController::class, 'index'])->name('profit-loss');
                Route::get('/profit-loss/pdf', [ProfitLossReportController::class, 'exportPdf'])->name('profit-loss.pdf');
                Route::get('/profit-loss/excel', [ProfitLossReportController::class, 'exportExcel'])->name('profit-loss.excel');
            });

            // 3. Neraca (Balance Sheet)
            Route::middleware('permission:view-reports-balance-sheet')->group(function () {
                Route::get('/balance-sheet', [BalanceSheetReportController::class, 'index'])->name('balance-sheet');
                Route::get('/balance-sheet/pdf', [BalanceSheetReportController::class, 'exportPdf'])->name('balance-sheet.pdf');
                Route::get('/balance-sheet/excel', [BalanceSheetReportController::class, 'exportExcel'])->name('balance-sheet.excel');
            });

            // 4. Laporan Pajak (Tax Report)
            Route::middleware('permission:view-reports-tax')->group(function () {
                Route::get('/tax', [TaxReportController::class, 'index'])->name('tax');
                Route::get('/tax/pdf', [TaxReportController::class, 'exportPdf'])->name('tax.pdf');
                Route::get('/tax/excel', [TaxReportController::class, 'exportExcel'])->name('tax.excel');
            });

            // 5. Laporan Penggajian (Payroll Report)
            Route::middleware('permission:view-reports-payroll')->group(function () {
                Route::get('/payroll', [PayrollReportController::class, 'index'])->name('payroll');
                Route::get('/payroll/pdf', [PayrollReportController::class, 'exportPdf'])->name('payroll.pdf');
                Route::get('/payroll/excel', [PayrollReportController::class, 'exportExcel'])->name('payroll.excel');
            });

        }); // End Reports Prefix

    }); // End Admin Prefix

}); // End Auth Middleware
