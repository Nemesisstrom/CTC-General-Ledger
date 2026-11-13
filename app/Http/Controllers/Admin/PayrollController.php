<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayrollRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Payroll;
use App\Traits\ChecksClosedPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PayrollController extends Controller
{
    use ChecksClosedPeriod;

    public function index(Request $request)
    {
        $payrolls = Payroll::with(['creator'])
            ->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json($payrolls);
        }

        $expenseAccounts = ChartOfAccount::where('type', 'expense')
            ->where('is_active', true)
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();

        $paymentAccounts = ChartOfAccount::where('is_active', true)
            ->whereNotNull('parent_id')
            ->where('code', 'like', '1-11%')
            ->orderBy('code')
            ->get();

        return view('admin.payroll.index', compact('payrolls', 'expenseAccounts', 'paymentAccounts'));
    }

    public function store(StorePayrollRequest $request)
    {
        $validated = $request->validated();
        $paymentDate = $validated['payment_date'];

        if ($this->isPeriodClosed($paymentDate)) {
            $msg = $this->closedPeriodMessage($paymentDate);
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        DB::beginTransaction();
        try {
            $ymCode = date('Ym', strtotime($paymentDate));
            $lastPayroll = Payroll::where('payroll_number', 'like', "PAY-{$ymCode}-%")
                ->lockForUpdate()
                ->orderByDesc('payroll_number')
                ->first();

            $lastSeq = 0;
            if ($lastPayroll) {
                $parts = explode('-', $lastPayroll->payroll_number);
                $lastSeq = (int) end($parts);
            }

            $payrollNumber = 'PAY-' . $ymCode . '-' . sprintf('%04d', $lastSeq + 1);

            $baseSalary = round((float) ($validated['base_salary'] ?? 0), 2);
            $allowances = round((float) ($validated['allowances'] ?? 0), 2);
            $deductions = round((float) ($validated['deductions'] ?? 0), 2);
            $netSalary  = round(($baseSalary + $allowances) - $deductions, 2);

            if ($netSalary <= 0) {
                throw new Exception('Total gaji bersih (Net Salary) harus lebih besar dari Rp 0.');
            }

            $payroll = Payroll::create([
                'payroll_number' => $payrollNumber,
                'period'         => $validated['period'] ?? date('Y-m', strtotime($paymentDate)),
                'payment_date'   => $paymentDate,
                'employee_name'  => $validated['employee_name'] ?? 'Karyawan',
                'base_salary'    => $baseSalary,
                'allowances'     => $allowances,
                'deductions'     => $deductions,
                'net_salary'     => $netSalary,
                'notes'          => $validated['notes'] ?? null,
                'status'         => 'paid',
                'created_by'     => auth()->id(),
            ]);

            $expenseAccount = !empty($validated['expense_account_id'])
                ? ChartOfAccount::findOrFail($validated['expense_account_id'])
                : ChartOfAccount::where('type', 'expense')->whereNotNull('parent_id')->orderBy('code')->firstOrFail();

            $paymentAccount = !empty($validated['payment_account_id'])
                ? ChartOfAccount::findOrFail($validated['payment_account_id'])
                : ChartOfAccount::where('code', 'like', '1-11%')->whereNotNull('parent_id')->orderBy('code')->firstOrFail();

            $journal = Journal::create([
                'journal_number'   => 'JRN-' . $payrollNumber,
                'journal_date'     => $paymentDate,
                'reference_number' => $payrollNumber,
                'description'      => "[Penggajian] Pembayaran gaji {$payroll->employee_name} ({$payroll->period})",
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $expenseAccount->id,
                'debit'      => $netSalary,
                'credit'     => 0,
                'memo'       => "Beban Gaji - {$payroll->employee_name}",
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $paymentAccount->id,
                'debit'      => 0,
                'credit'     => $netSalary,
                'memo'       => "Pembayaran Gaji via {$paymentAccount->name}",
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => "Data penggajian {$payrollNumber} berhasil disimpan.",
                    'data'    => $payroll,
                ], 201);
            }

            return redirect()
                ->route('admin.payrolls.index')
                ->with('success', "Data penggajian {$payrollNumber} berhasil disimpan dan diposting ke jurnal.");

        } catch (Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal memproses penggajian: ' . $e->getMessage()], 422);
            }
            return back()
                ->with('error', 'Gagal memproses penggajian: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Request $request, $id)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya Administrator yang memiliki otoritas menghapus data penggajian.');
        }

        DB::beginTransaction();
        try {
            $payroll = Payroll::findOrFail($id);

            if ($this->isPeriodClosed($payroll->payment_date)) {
                $msg = $this->closedPeriodMessage($payroll->payment_date);
                if ($request->wantsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }

            Log::warning('HARD DELETE DATA PENGGAJIAN', [
                'payroll_number' => $payroll->payroll_number,
                'payment_date'   => $payroll->payment_date,
                'net_salary'     => $payroll->net_salary,
                'deleted_by_id'  => auth()->id(),
            ]);

            $journal = Journal::where('reference_number', $payroll->payroll_number)
                ->orWhere('journal_number', 'JRN-' . $payroll->payroll_number)
                ->first();

            if ($journal) {
                JournalDetail::where('journal_id', $journal->id)->delete();
                $journal->delete();
            }

            $payroll->delete();

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Data penggajian berhasil dihapus.']);
            }

            return redirect()
                ->route('admin.payrolls.index')
                ->with('success', 'Data penggajian beserta jurnal terkait berhasil dihapus.');
        } catch (Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal menghapus data penggajian: ' . $e->getMessage()], 422);
            }
            return back()->with('error', 'Gagal menghapus data penggajian: ' . $e->getMessage());
        }
    }
}
