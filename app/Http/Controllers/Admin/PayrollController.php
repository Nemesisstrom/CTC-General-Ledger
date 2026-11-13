<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayrollRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', now()->format('Y-m'));

        $payrolls = Payroll::with(['user', 'creator', 'journal'])
            ->latest('payment_date')
            ->latest('id')
            ->paginate(15);

        $employees = User::where('is_active', true)
            ->orderBy('name')
            ->get();

        $cashAccounts = ChartOfAccount::where('is_active', true)
            ->where('type', 'asset')
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();

        $expenseAccounts = ChartOfAccount::where('is_active', true)
            ->where('type', 'expense')
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();

        $liabilityAccounts = ChartOfAccount::where('is_active', true)
            ->where('type', 'liability')
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();

        return view('admin.payroll.index', compact(
            'period',
            'payrolls',
            'employees',
            'cashAccounts',
            'expenseAccounts',
            'liabilityAccounts'
        ));
    }

    public function store(StorePayrollRequest $request)
    {
        $validated = $request->validated();

        $paymentDate = $validated['payment_date'] ?? now()->toDateString();
        $year = date('Y', strtotime($paymentDate));

        $isClosed = Journal::where('journal_number', 'LIKE', "JRN-CLS-{$year}%")
            ->where('status', 'posted')
            ->exists();

        if ($isClosed) {
            throw ValidationException::withMessages([
                'payment_date' => ["Tahun buku {$year} sudah ditutup dan tidak dapat memproses penggajian baru."],
            ]);
        }

        $payroll = DB::transaction(function () use ($validated, $request, $paymentDate) {
            $employee = User::findOrFail($validated['user_id']);

            $baseSalary = (float) ($validated['base_salary'] ?? $employee->base_salary ?? 0);
            $allowances = (float) ($validated['allowances'] ?? 0);
            $deductions = (float) ($validated['deductions'] ?? 0);
            $pphAmount  = (float) ($validated['pph21_amount'] ?? $validated['tax_deduction'] ?? 0);

            $grossSalary = $baseSalary + $allowances;
            $netSalary   = max(0, $grossSalary - $deductions - $pphAmount);

            $expenseAccountId = $validated['expense_account_id']
                ?? ChartOfAccount::where('type', 'expense')->whereNotNull('parent_id')->value('id');
            $cashAccountId = $validated['cash_account_id']
                ?? ChartOfAccount::where('type', 'asset')->whereNotNull('parent_id')->value('id');
            $pphAccountId = $validated['pph_account_id']
                ?? ChartOfAccount::where('type', 'liability')->whereNotNull('parent_id')->value('id');

            $dateStr = date('Ymd', strtotime($paymentDate));
            $prefix = "PAY-{$dateStr}-";
            $lastJournal = Journal::where('journal_number', 'LIKE', "{$prefix}%")
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $sequence = $lastJournal
                ? ((int) substr($lastJournal->journal_number, -4)) + 1
                : 1;

            $journalNumber = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $period = $validated['period'] ?? date('Y-m', strtotime($paymentDate));
            $description = $validated['notes'] ?? "Pembayaran Gaji {$employee->name} ({$period})";

            $journal = Journal::create([
                'journal_number'   => $journalNumber,
                'journal_date'     => $paymentDate,
                'reference_number' => $journalNumber,
                'description'      => $description,
                'status'           => 'posted',
                'created_by'       => $request->user()->id,
            ]);

            if ($expenseAccountId && $cashAccountId && ($netSalary + $pphAmount) > 0) {
                JournalDetail::create([
                    'journal_id'  => $journal->id,
                    'account_id'  => $expenseAccountId,
                    'debit'       => $netSalary + $pphAmount,
                    'credit'      => 0,
                    'description' => $description,
                ]);

                if ($netSalary > 0) {
                    JournalDetail::create([
                        'journal_id'  => $journal->id,
                        'account_id'  => $cashAccountId,
                        'debit'       => 0,
                        'credit'      => $netSalary,
                        'description' => $description,
                    ]);
                }

                if ($pphAmount > 0 && $pphAccountId) {
                    JournalDetail::create([
                        'journal_id'  => $journal->id,
                        'account_id'  => $pphAccountId,
                        'debit'       => 0,
                        'credit'      => $pphAmount,
                        'description' => "Potongan PPh 21 - {$employee->name} ({$period})",
                    ]);
                }
            }

            $payroll = Payroll::create([
                'user_id'      => $employee->id,
                'journal_id'   => $journal->id,
                'period'       => $period,
                'payment_date' => $paymentDate,
                'base_salary'  => $baseSalary,
                'allowances'   => $allowances,
                'deductions'   => $deductions + $pphAmount,
                'net_salary'   => $netSalary,
                'status'       => 'paid',
                'notes'        => $validated['notes'] ?? null,
                'created_by'   => $request->user()->id,
            ]);

            if (class_exists(PayrollItem::class) && ! empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'name'       => $item['name'],
                        'type'       => $item['type'],
                        'amount'     => $item['amount'],
                    ]);
                }
            }

            return $payroll;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Penggajian berhasil diproses dan dijurnal.',
                'data'    => $payroll->load(['user', 'journal']),
            ], 201);
        }

        return redirect()
            ->route('admin.payrolls.index')
            ->with('success', 'Penggajian karyawan berhasil diproses dan dijurnal.');
    }
}
