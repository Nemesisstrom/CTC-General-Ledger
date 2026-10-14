<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ChartOfAccountController;
use App\Http\Controllers\Reports\BalanceSheetReportController;
use App\Http\Controllers\Reports\ProfitLossReportController;
use App\Http\Controllers\Reports\GeneralLedgerReportController;
use App\Http\Controllers\Reports\TaxReportController;
use App\Http\Controllers\Reports\PayrollReportController;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\TaxController;

// Auth Guest API (v1)
Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'registerApi']);
    Route::post('/login', [AuthController::class, 'loginApi']);

    // Forgot & Reset Password API
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkApi']);
    Route::post('/reset-password', [PasswordResetController::class, 'resetPasswordApi']);
});

// Authenticated API (Sanctum) dengan Prefix 'v1'
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Logout API & User Profile Active
    Route::post('/logout', [AuthController::class, 'logoutApi']);
    Route::get('/me', fn (Request $request) => response()->json(['user' => $request->user()]));

    // Dashboard JSON Endpoint (Role: Admin & Akuntan)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:admin|akuntan');

    // User Management & Chart Of Account API (Admin Only)
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('chart-of-accounts', ChartOfAccountController::class);
    });

    // Reports API Group
    Route::prefix('reports')->group(function () {
        Route::get('/balance-sheet', [BalanceSheetReportController::class, 'index'])
            ->middleware('permission:view-reports-balance-sheet');

        Route::get('/profit-loss', [ProfitLossReportController::class, 'index'])
            ->middleware('permission:view-reports-profit-loss');

        Route::get('/general-ledger', [GeneralLedgerReportController::class, 'index'])
            ->middleware('permission:view-reports-general-ledger');

        Route::get('/tax', [TaxReportController::class, 'index'])
            ->middleware('permission:view-reports-tax');

        Route::get('/payroll', [PayrollReportController::class, 'index'])
            ->middleware('permission:view-reports-payroll');
    });

    // Transaksi Kas & Bank
    Route::middleware('permission:manage-cash-transactions')->group(function () {
        Route::apiResource('cash-transactions', CashTransactionController::class)->only(['index', 'store']);
    });

    Route::middleware('permission:manage-bank-reconciliations')->group(function () {
        Route::apiResource('bank-reconciliations', BankReconciliationController::class)->only(['index', 'store']);
    });

    // Payroll & Pajak
    Route::middleware('permission:manage-payrolls')->group(function () {
        Route::apiResource('payrolls', PayrollController::class)->only(['index', 'store']);
    });

    Route::middleware('permission:process-taxes')->group(function () {
        Route::get('/taxes', [TaxController::class, 'index']);
        Route::post('/taxes/process', [TaxController::class, 'processTaxTransaction']);
    });

    // Jurnal & Penyesuaian
    Route::middleware('permission:manage-journals')->group(function () {
        Route::get('/journals', [JournalController::class, 'index']);
        Route::post('/journals', [JournalController::class, 'store']);
        Route::post('/journals/{id}/reverse', [JournalController::class, 'reverse'])
            ->middleware('permission:reverse-journals');
    });

    Route::middleware('permission:manage-adjustments')->group(function () {
        Route::apiResource('adjustments', AdjustmentController::class)->only(['index', 'store']);
    });
});