<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ChartOfAccountController;
use App\Http\Controllers\Reports\BalanceSheetReportController;
use App\Http\Controllers\Reports\ProfitLossReportController;
use App\Http\Controllers\Reports\GeneralLedgerReportController;

// Auth Guest API
Route::post('/v1/register', [AuthController::class, 'registerApi']);
Route::post('/v1/login', [AuthController::class, 'loginApi']);

// Authenticated API (Sanctum)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/logout', [AuthController::class, 'logoutApi']);

    // Dashboard JSON Endpoint (Role: Admin & Akuntan)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:admin|akuntan');

    // Chart Of Account API (Admin Only)
    Route::middleware('role:admin')->group(function () {
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
    });
});
