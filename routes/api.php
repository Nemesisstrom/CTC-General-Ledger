<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth Controllers
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ChartOfAccountController;
use App\Http\Controllers\Admin\CashTransactionController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\JournalController;
use App\Http\Controllers\Admin\BankReconciliationController;
use App\Http\Controllers\Admin\AdjustmentController;
use App\Http\Controllers\Admin\TaxController;

// Admin Report Controllers
use App\Http\Controllers\Admin\Reports\BalanceSheetReportController;
use App\Http\Controllers\Admin\Reports\ProfitLossReportController;
use App\Http\Controllers\Admin\Reports\GeneralLedgerReportController;
use App\Http\Controllers\Admin\Reports\TaxReportController;
use App\Http\Controllers\Admin\Reports\PayrollReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ==========================================
// Guest API Routes (v1)
// ==========================================
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('/register', [AuthController::class, 'registerApi'])->name('register');
    Route::post('/login', [AuthController::class, 'loginApi'])->name('login');

    // Forgot & Reset Password API
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkApi'])->name('password.email');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPasswordApi'])->name('password.update');
});

// ==========================================
// Authenticated API Routes (Sanctum v1)
// ==========================================
Route::middleware('auth:sanctum')->prefix('v1')->name('api.v1.')->group(function () {

    // Profile & Logout API
    Route::post('/logout', [AuthController::class, 'logoutApi'])->name('logout');
    Route::get('/me', fn (Request $request) => response()->json(['user' => $request->user()]))->name('me');

    // Dashboard JSON Endpoint (Role: Admin, Akuntan, & Staff)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:admin|akuntan|staff')
        ->name('dashboard');

    // User Management & Chart Of Account API (Admin Only)
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('chart-of-accounts', ChartOfAccountController::class);
    });

    // Reports API Group
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/balance-sheet', [BalanceSheetReportController::class, 'index'])
            ->middleware('permission:view-reports-balance-sheet')
            ->name('balance-sheet');

        Route::get('/profit-loss', [ProfitLossReportController::class, 'index'])
            ->middleware('permission:view-reports-profit-loss')
            ->name('profit-loss');

        Route::get('/general-ledger', [GeneralLedgerReportController::class, 'index'])
            ->middleware('permission:view-reports-general-ledger')
            ->name('general-ledger');

        Route::get('/tax', [TaxReportController::class, 'index'])
            ->middleware('permission:view-reports-tax')
            ->name('tax');

        Route::get('/payroll', [PayrollReportController::class, 'index'])
            ->middleware('permission:view-reports-payroll')
            ->name('payroll');
    });

    // Transaksi Kas & Bank API
    Route::middleware('permission:manage-cash-transactions')->group(function () {
        Route::apiResource('cash-transactions', CashTransactionController::class)->only(['index', 'store']);
    });

    Route::middleware('permission:manage-bank-reconciliations')->group(function () {
        Route::apiResource('bank-reconciliations', BankReconciliationController::class)->only(['index', 'store']);
    });

    // Payroll & Pajak API
    Route::middleware('permission:manage-payrolls')->group(function () {
        Route::apiResource('payrolls', PayrollController::class)->only(['index', 'store']);
    });

    Route::middleware('permission:process-taxes')->group(function () {
        Route::get('/taxes', [TaxController::class, 'index'])->name('taxes.index');
        Route::post('/taxes/process', [TaxController::class, 'processTaxTransaction'])->name('taxes.process');
    });

    // Jurnal & Penyesuaian API
    Route::middleware('permission:manage-journals')->group(function () {
        Route::get('/journals', [JournalController::class, 'index'])->name('journals.index');
        Route::post('/journals', [JournalController::class, 'store'])->name('journals.store');
    });

    Route::post('/journals/{id}/reverse', [JournalController::class, 'reverse'])
        ->middleware('permission:reverse-journals')
        ->name('journals.reverse');

    Route::middleware('permission:manage-adjustments')->group(function () {
        Route::apiResource('adjustments', AdjustmentController::class)->only(['index', 'store']);
    });

});