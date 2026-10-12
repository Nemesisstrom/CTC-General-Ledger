<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'period' => 'required|string', // Contoh '2026-03'
            'allowances' => 'array',
            'deductions' => 'array',
            'salary_expense_account_id' => 'required|exists:chart_of_accounts,id',
            'cash_account_id' => 'required|exists:chart_of_accounts,id'
        ]);

        $user = User::findOrFail($request->user_id);
        $basicSalary = $user->base_salary;

        // Hitung Tambahan Tunjangan
        $totalAllowance = collect($request->allowances)->sum('amount');

        // Hitung Potongan Lain
        $totalDeduction = collect($request->deductions)->sum('amount');

        // Otomatisasi PPh 21
        $grossIncome = $basicSalary + $totalAllowance;
        $pph21Amount = $this->calculatePPh21($grossIncome);

        $netSalary = $grossIncome - ($totalDeduction + $pph21Amount);

        DB::beginTransaction();
        try {
            // 1. Buat Payroll
            $payroll = Payroll::create([
                'user_id' => $user->id,
                'period' => $request->period,
                'basic_salary' => $basicSalary,
                'total_allowance' => $totalAllowance,
                'total_deduction' => $totalDeduction,
                'pph21_amount' => $pph21Amount,
                'net_salary' => $netSalary,
                'status' => 'approved',
                'created_by' => auth()->id()
            ]);

            // 2. Simpan Item Detail
            if ($request->allowances) {
                foreach ($request->allowances as $item) {
                    PayrollItem::create(['payroll_id' => $payroll->id, 'name' => $item['name'], 'type' => 'allowance', 'amount' => $item['amount']]);
                }
            }
            if ($request->deductions) {
                foreach ($request->deductions as $item) {
                    PayrollItem::create(['payroll_id' => $payroll->id, 'name' => $item['name'], 'type' => 'deduction', 'amount' => $item['amount']]);
                }
            }

            // 3. Auto Posting ke Jurnal Umum Keuangan
            $taxPph21 = Tax::where('type', 'PPh21')->first();
            $pphAccountId = $taxPph21 ? $taxPph21->account_id : $request->cash_account_id;

            $journal = Journal::create([
                'journal_number' => 'PAY-' . $payroll->period . '-' . $user->id,
                'journal_date' => now()->toDateString(),
                'description' => 'Penggajian ' . $user->name . ' Periode ' . $payroll->period,
                'status' => 'posted',
                'created_by' => auth()->id()
            ]);

            $payroll->update(['journal_id' => $journal->id]);

            // Debit: Beban Gaji (Gaji Kotor)
            JournalDetail::create(['journal_id' => $journal->id, 'account_id' => $request->salary_expense_account_id, 'debit' => $grossIncome, 'credit' => 0]);

            // Kredit: Utang/Potongan PPh 21
            if ($pph21Amount > 0) {
                JournalDetail::create(['journal_id' => $journal->id, 'account_id' => $pphAccountId, 'debit' => 0, 'credit' => $pph21Amount]);
            }

            // Kredit: Kas/Bank (Gaji Bersih Diterima Karyawan)
            JournalDetail::create(['journal_id' => $journal->id, 'account_id' => $request->cash_account_id, 'debit' => 0, 'credit' => $netSalary]);

            DB::commit();
            return response()->json(['message' => 'Penggajian berhasil diproses & otomatisasi PPh21 diposting ke Keuangan', 'data' => $payroll]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function calculatePPh21($grossIncome)
    {
        // Formula Asumsi Sederhana PPh 21 Progresif / TER
        if ($grossIncome > 5000000) {
            return ($grossIncome - 5000000) * 0.05; // 5% dari kelebihan di atas 5 Juta
        }
        return 0;
    }
}
