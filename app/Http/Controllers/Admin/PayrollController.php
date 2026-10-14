<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayrollRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', date('Y-m'));

        $payrolls = Payroll::with(['user', 'items', 'journal'])
            ->where('period', $period)
            ->orderBy('id', 'desc')
            ->get();

        $employees = User::where('is_active', true)->get();

        // Akun Beban Gaji, Kas/Bank, & Kewajiban untuk dropdown posting
        $expenseAccounts   = ChartOfAccount::where('type', 'expense')->where('is_active', true)->get();
        $cashAccounts      = ChartOfAccount::where('type', 'asset')->where('is_active', true)->get();
        $liabilityAccounts = ChartOfAccount::where('type', 'liability')->where('is_active', true)->get();

        if ($request->wantsJson()) {
            return response()->json(['data' => $payrolls]);
        }

        return view('admin.payroll.index', compact(
            'payrolls', 
            'employees', 
            'period', 
            'expenseAccounts', 
            'cashAccounts', 
            'liabilityAccounts'
        ));
    }

    public function store(StorePayrollRequest $request)
    {
        $user = User::findOrFail($request->user_id);
        $basicSalary = $user->base_salary ?? 0;

        // Cek jika payroll karyawan untuk periode tersebut sudah ada
        $existing = Payroll::where('user_id', $user->id)->where('period', $request->period)->first();
        if ($existing) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gaji karyawan untuk periode ini sudah pernah diproses.'], 422);
            }
            return redirect()->back()->with('error', 'Gaji karyawan untuk periode ' . $request->period . ' sudah pernah diproses.')->withInput();
        }

        // 1. Kalkulasi Total Tunjangan & Potongan
        $totalAllowance = collect($request->allowances)->sum('amount');
        $totalDeduction = collect($request->deductions)->sum('amount');

        // 2. Kalkulasi Otomatis PPh 21
        $grossIncome = $basicSalary + $totalAllowance;
        $pph21Amount = $this->calculatePPh21($grossIncome);

        $netSalary = $grossIncome - ($totalDeduction + $pph21Amount);

        DB::beginTransaction();
        try {
            // 3. Simpan Header Payroll
            $payroll = Payroll::create([
                'user_id'         => $user->id,
                'period'          => $request->period,
                'basic_salary'    => $basicSalary,
                'total_allowance' => $totalAllowance,
                'total_deduction' => $totalDeduction,
                'pph21_amount'    => $pph21Amount,
                'net_salary'      => $netSalary,
                'status'          => 'approved',
                'created_by'      => auth()->id(),
            ]);

            // 4. Simpan Detail Komponen Tunjangan & Potongan
            if ($request->allowances) {
                foreach ($request->allowances as $item) {
                    if (!empty($item['name']) && $item['amount'] > 0) {
                        PayrollItem::create([
                            'payroll_id' => $payroll->id,
                            'name'       => $item['name'],
                            'type'       => 'allowance',
                            'amount'     => $item['amount'],
                        ]);
                    }
                }
            }

            if ($request->deductions) {
                foreach ($request->deductions as $item) {
                    if (!empty($item['name']) && $item['amount'] > 0) {
                        PayrollItem::create([
                            'payroll_id' => $payroll->id,
                            'name'       => $item['name'],
                            'type'       => 'deduction',
                            'amount'     => $item['amount'],
                        ]);
                    }
                }
            }

            // 5. Auto Posting ke Jurnal Umum Keuangan
            $taxPph21 = Tax::where('type', 'PPh21')->first();
            $pphAccountId = $request->pph_account_id ?? ($taxPph21 ? $taxPph21->account_id : $request->cash_account_id);

            $journal = Journal::create([
                'journal_number'   => 'JRN-PAY-' . $payroll->period . '-' . sprintf('%03d', $user->id),
                'journal_date'     => now()->toDateString(),
                'reference_number' => 'PAY-' . $payroll->id,
                'description'      => 'Penggajian ' . $user->name . ' (Periode ' . $payroll->period . ')',
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            $payroll->update(['journal_id' => $journal->id]);

            // Debit: Beban Gaji Kotor (Gross Income)
            JournalDetail::create([
                'journal_id'          => $journal->id,
                'chart_of_account_id' => $request->salary_expense_account_id,
                'debit'               => $grossIncome,
                'credit'              => 0,
                'memo'                => 'Beban Gaji Kotor ' . $user->name,
            ]);

            // Kredit: Potongan PPh 21
            if ($pph21Amount > 0) {
                JournalDetail::create([
                    'journal_id'          => $journal->id,
                    'chart_of_account_id' => $pphAccountId,
                    'tax_id'              => $taxPph21?->id,
                    'debit'               => 0,
                    'credit'              => $pph21Amount,
                    'memo'                => 'Utang / Potongan PPh 21 Gaji',
                ]);
            }

            // Kredit: Potongan Lainnya (Jika Ada)
            if ($totalDeduction > 0 && $request->deduction_account_id) {
                JournalDetail::create([
                    'journal_id'          => $journal->id,
                    'chart_of_account_id' => $request->deduction_account_id,
                    'debit'               => 0,
                    'credit'              => $totalDeduction,
                    'memo'                => 'Potongan Gaji Non-Pajak ' . $user->name,
                ]);
            }

            // Kredit: Kas/Bank (Gaji Bersih Diterima Karyawan)
            $cashCreditAmount = ($totalDeduction > 0 && !$request->deduction_account_id) 
                ? ($netSalary + $totalDeduction) 
                : $netSalary;

            JournalDetail::create([
                'journal_id'          => $journal->id,
                'chart_of_account_id' => $request->cash_account_id,
                'debit'               => 0,
                'credit'              => $cashCreditAmount,
                'memo'                => 'Pembayaran Gaji Bersih ' . $user->name,
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Penggajian berhasil diproses & di-jurnal',
                    'data'    => $payroll->load('items')
                ], 201);
            }

            return redirect()
                ->route('admin.payrolls.index', ['period' => $request->period])
                ->with('success', 'Gaji karyawan ' . $user->name . ' berhasil diproses dan di-jurnal.');

        } catch (Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Gagal memproses gaji: ' . $e->getMessage()], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Gagal memproses gaji: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Logika Kalkulasi PPh 21 Sederhana (Aturan Progresif / TER)
     */
    private function calculatePPh21(float $grossIncome): float
    {
        if ($grossIncome > 5000000) {
            return ($grossIncome - 5000000) * 0.05;
        }
        return 0;
    }
}