<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdjustmentRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\RecurringExpense;
use App\Traits\ChecksClosedPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustmentController extends Controller
{
    use ChecksClosedPeriod;

    public function index(Request $request)
    {
        $adjustments = Journal::with(['details.account', 'creator'])
            ->where(function ($query) {
                $query->where('journal_number', 'LIKE', 'ADJ-%')
                      ->orWhere('journal_number', 'LIKE', 'JRN-ADJ-%');
            })
            ->latest('journal_date')
            ->latest('id')
            ->paginate(15);

        $accounts = ChartOfAccount::where('is_active', true)
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();

        $expenseAccounts = $accounts->where('type', 'expense')->values();
        $accumulatedAccounts = $accounts->whereIn('type', ['asset', 'liability', 'revenue'])->values();
        $assetAccounts = $accounts->where('type', 'asset')->values();
        $liabilityAccounts = $accounts->where('type', 'liability')->values();
        $revenueAccounts = $accounts->where('type', 'revenue')->values();

        $recurringExpenses = RecurringExpense::with(['debitAccount', 'creditAccount'])
            ->latest()
            ->get();

        return view('admin.adjustments.index', compact(
            'adjustments',
            'accounts',
            'expenseAccounts',
            'accumulatedAccounts',
            'assetAccounts',
            'liabilityAccounts',
            'revenueAccounts',
            'recurringExpenses'
        ));
    }

    public function store(StoreAdjustmentRequest $request)
    {
        $validated = $request->validated();

        $year = date('Y', strtotime($validated['adjustment_date']));
        $isClosed = Journal::where('journal_number', 'LIKE', "JRN-CLS-{$year}%")
            ->where('status', 'posted')
            ->exists();

        if ($isClosed) {
            throw ValidationException::withMessages([
                'adjustment_date' => ["Tahun buku {$year} sudah ditutup dan tidak dapat menerima jurnal penyesuaian baru."],
            ]);
        }

        $journal = DB::transaction(function () use ($validated, $request) {
            $dateStr = date('Ymd', strtotime($validated['adjustment_date']));
            $prefix = "ADJ-{$dateStr}-";
            $lastJournal = Journal::where('journal_number', 'LIKE', "{$prefix}%")
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $sequence = $lastJournal
                ? ((int) substr($lastJournal->journal_number, -4)) + 1
                : 1;

            $journalNumber = $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

            $journal = Journal::create([
                'journal_number'   => $journalNumber,
                'journal_date'     => $validated['adjustment_date'],
                'reference_number' => $validated['reference_number'] ?? $journalNumber,
                'description'      => $validated['description'],
                'status'           => 'posted',
                'created_by'       => $request->user()->id,
            ]);

            $amount = (float) $validated['amount'];

            JournalDetail::create([
                'journal_id'  => $journal->id,
                'account_id'  => $validated['expense_account_id'],
                'debit'       => $amount,
                'credit'      => 0,
                'description' => $validated['description'],
            ]);

            JournalDetail::create([
                'journal_id'  => $journal->id,
                'account_id'  => $validated['accumulated_account_id'],
                'debit'       => 0,
                'credit'      => $amount,
                'description' => $validated['description'],
            ]);

            return $journal;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Jurnal penyesuaian berhasil diposting.',
                'data'    => $journal->load('details.account'),
            ], 201);
        }

        return redirect()
            ->route('admin.adjustments.index')
            ->with('success', 'Jurnal penyesuaian berhasil diposting.');
    }

    public function storeRecurring(Request $request)
    {
        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'debit_account_id'  => ['required', 'exists:chart_of_accounts,id', 'different:credit_account_id'],
            'credit_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'amount'            => ['required', 'numeric', 'min:1'],
            'frequency'         => ['required', 'in:daily,weekly,monthly,yearly'],
            'next_run_date'     => ['required', 'date'],
            'description'       => ['nullable', 'string', 'max:500'],
        ]);

        RecurringExpense::create([
            'name'              => $validated['name'],
            'debit_account_id'  => $validated['debit_account_id'],
            'credit_account_id' => $validated['credit_account_id'],
            'amount'            => $validated['amount'],
            'frequency'         => $validated['frequency'],
            'next_run_date'     => $validated['next_run_date'],
            'description'       => $validated['description'] ?? $validated['name'],
            'is_active'         => true,
            'created_by'        => $request->user()->id,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Jadwal beban berulang berhasil ditambahkan.'], 201);
        }

        return redirect()
            ->route('admin.adjustments.index')
            ->with('success', 'Jadwal beban berulang berhasil ditambahkan.');
    }

    public function toggleRecurring(Request $request, RecurringExpense $recurringExpense)
    {
        $recurringExpense->update([
            'is_active' => ! $recurringExpense->is_active,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Status jadwal beban berulang diperbarui.',
                'data'    => $recurringExpense,
            ]);
        }

        return redirect()
            ->route('admin.adjustments.index')
            ->with('success', 'Status jadwal beban berulang berhasil diperbarui.');
    }
}
