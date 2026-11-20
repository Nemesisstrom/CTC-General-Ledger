<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use App\Models\User;

// Auth Controllers
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;

// Profile Controller
use App\Http\Controllers\ProfileController;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ChartOfAccountController;
use App\Http\Controllers\Admin\CashTransactionController;
use App\Http\Controllers\Admin\JournalController;
use App\Http\Controllers\Admin\BankReconciliationController;
use App\Http\Controllers\Admin\AdjustmentController;
use App\Http\Controllers\Admin\ClosingController;
use App\Http\Controllers\Admin\TaxController;
use App\Http\Controllers\Admin\PayrollController;

// Admin Report Controllers
use App\Http\Controllers\Admin\Reports\GeneralLedgerReportController;
use App\Http\Controllers\Admin\Reports\ProfitLossReportController;
use App\Http\Controllers\Admin\Reports\BalanceSheetReportController;
use App\Http\Controllers\Admin\Reports\TaxReportController;
use App\Http\Controllers\Admin\Reports\PayrollReportController;

// Root Redirect ke Halaman Login
Route::get('/', function () {
    return redirect()->route('login');
});

// ==========================================
// Guest Routes (Autentikasi & Reset Password)
// ==========================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'loginWeb'])->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'registerWeb'])->name('register.post');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkWeb'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPasswordWeb'])->name('password.update');
});

// ==========================================
// Verifikasi Link Email (Bisa Diklik Langsung dari Kotak Masuk)
// ==========================================
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);

    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403, 'Link verifikasi tidak valid.');
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    return redirect()->route('login')
        ->with('status', 'Email berhasil diverifikasi! Silakan login menggunakan akun Anda.');
})->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

// ==========================================
// Authenticated Routes (Pengguna Terautentikasi)
// ==========================================
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logoutWeb'])->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');

    // ==========================================
    // Wajib Sudah Verifikasi Email ('verified')
    // ==========================================
    Route::middleware('verified')->group(function () {

        // Dashboard Utama
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('role:admin|akuntan|staff')
            ->name('dashboard');

        // Profil Pengguna
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

        // Panel Admin & Operasional (Prefix: /admin)
        Route::prefix('admin')->name('admin.')->group(function () {

            // Khusus Role Admin
            Route::middleware('role:admin')->group(function () {
                Route::resource('users', UserController::class);
                Route::resource('branches', BranchController::class)->except(['create', 'edit', 'show']);
                Route::resource('chart-of-accounts', ChartOfAccountController::class);

                Route::post('closing-year-end', [ClosingController::class, 'store'])->name('closing.year-end');

                Route::delete('cash-transactions/{id}', [CashTransactionController::class, 'destroy'])->name('cash-transactions.destroy');
                Route::delete('bank-reconciliations/{id}', [BankReconciliationController::class, 'destroy'])->name('bank-reconciliations.destroy');
                Route::delete('payrolls/{id}', [PayrollController::class, 'destroy'])->name('payrolls.destroy');
            });

            // Ganti Cabang Aktif (Admin & Akuntan)
            Route::middleware('role:admin|akuntan')->group(function () {
                Route::post('switch-branch', function (Request $request) {
                    if ($request->filled('branch_id')) {
                        $request->validate(['branch_id' => 'exists:branches,id']);
                        session(['active_branch_id' => $request->branch_id]);
                    } else {
                        session()->forget('active_branch_id');
                    }
                    return redirect()->back()->with('success', 'Filter cabang aktif berhasil diperbarui.');
                })->name('switch-branch');
            });

            // Modul Jurnal Umum & Reversal
            Route::middleware('permission:manage-journals')->group(function () {
                Route::get('/journals', [JournalController::class, 'index'])->name('journals.index');
                Route::post('/journals', [JournalController::class, 'store'])->name('journals.store');
            });

            Route::middleware('permission:reverse-journals')->group(function () {
                Route::get('/journals/reversals', [JournalController::class, 'reversalIndex'])->name('journals.reversals.index');
                Route::post('/journals/{id}/reverse', [JournalController::class, 'reverse'])->name('journals.reverse');
            });

            // Modul Transaksi Operasional
            Route::middleware('permission:manage-cash-transactions')->group(function () {
                Route::resource('cash-transactions', CashTransactionController::class)->only(['index', 'store']);
            });

            Route::middleware('permission:manage-bank-reconciliations')->group(function () {
                Route::resource('bank-reconciliations', BankReconciliationController::class)->only(['index', 'store', 'update']);
            });

            // Modul Jurnal Penyesuaian
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

            Route::middleware('role:admin')->group(function () {
                Route::post('/taxes/master', [TaxController::class, 'store'])->name('taxes.store');
                Route::put('/taxes/master/{tax}', [TaxController::class, 'update'])->name('taxes.update');
                Route::delete('/taxes/master/{tax}', [TaxController::class, 'destroy'])->name('taxes.destroy');
            });

            Route::middleware('permission:manage-payrolls')->group(function () {
                Route::resource('payrolls', PayrollController::class)->only(['index', 'store']);
            });

            // Sub-modul Laporan
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::middleware('permission:view-reports-general-ledger')->group(function () {
                    Route::get('/general-ledger', [GeneralLedgerReportController::class, 'index'])->name('general-ledger');
                    Route::get('/general-ledger/pdf', [GeneralLedgerReportController::class, 'exportPdf'])->name('general-ledger.pdf');
                    Route::get('/general-ledger/excel', [GeneralLedgerReportController::class, 'exportExcel'])->name('general-ledger.excel');
                });

                Route::middleware('permission:view-reports-profit-loss')->group(function () {
                    Route::get('/profit-loss', [ProfitLossReportController::class, 'index'])->name('profit-loss');
                    Route::get('/profit-loss/pdf', [ProfitLossReportController::class, 'exportPdf'])->name('profit-loss.pdf');
                    Route::get('/profit-loss/excel', [ProfitLossReportController::class, 'exportExcel'])->name('profit-loss.excel');
                });

                Route::middleware('permission:view-reports-balance-sheet')->group(function () {
                    Route::get('/balance-sheet', [BalanceSheetReportController::class, 'index'])->name('balance-sheet');
                    Route::get('/balance-sheet/pdf', [BalanceSheetReportController::class, 'exportPdf'])->name('balance-sheet.pdf');
                    Route::get('/balance-sheet/excel', [BalanceSheetReportController::class, 'exportExcel'])->name('balance-sheet.excel');
                });

                Route::middleware('permission:view-reports-tax')->group(function () {
                    Route::get('/tax', [TaxReportController::class, 'index'])->name('tax');
                    Route::get('/tax/pdf', [TaxReportController::class, 'exportPdf'])->name('tax.pdf');
                    Route::get('/tax/excel', [TaxReportController::class, 'exportExcel'])->name('tax.excel');
                });

                Route::middleware('permission:view-reports-payroll')->group(function () {
                    Route::get('/payroll', [PayrollReportController::class, 'index'])->name('payroll');
                    Route::get('/payroll/pdf', [PayrollReportController::class, 'exportPdf'])->name('payroll.pdf');
                    Route::get('/payroll/excel', [PayrollReportController::class, 'exportExcel'])->name('payroll.excel');
                });
            });

        }); // End Admin Prefix

    }); // End Verified Group

}); // End Auth Group
