<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaxTransactionRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $taxes = Tax::with('account')
            ->orderBy('name')
            ->get();

        $taxTransactions = Journal::with(['details.account', 'creator'])
            ->where(function ($query) {
                $query->where('journal_number', 'LIKE', 'TAX-%')
                      ->orWhere('journal_number', 'LIKE', 'JRN-TAX-%');
            })
            ->latest('journal_date')
            ->latest('id')
            ->paginate(15);

        $accounts = ChartOfAccount::where('is_active', true)
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();

        $cashAccounts = ChartOfAccount::where('is_active', true)
            ->where('type', 'asset')
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();

        $taxAccounts = ChartOfAccount::where('is_active', true)
            ->whereIn('type', ['liability', 'asset', 'expense'])
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();

        return view('admin.tax.index', compact(
            'taxes',
            'taxTransactions',
            'accounts',
            'cashAccounts',
            'taxAccounts'
        ));
    }

    public function store(StoreTaxTransactionRequest $request)
    {
        $validated = $request->validated();

        $transactionDate = $validated['transaction_date'] ?? $validated['payment_date'] ?? now()->toDateString();
        $year = date('Y', strtotime($transactionDate));

        $isClosed = Journal::where('journal_number', 'LIKE', "JRN-CLS-{$year}%")
            ->where('status', 'posted')
            ->exists();

        if ($isClosed) {
            throw ValidationException::withMessages([
                'transaction_date' => ["Tahun buku {$year} sudah ditutup dan tidak dapat menerima transaksi pajak baru."],
            ]);
        }

        $journal = DB::transaction(function () use ($validated, $request, $transactionDate) {
            $tax = isset($validated['tax_id']) ? Tax::find($validated['tax_id']) : null;
            $taxAccountId = $validated['tax_account_id'] ?? $tax?->account_id;
            $cashAccountId = $validated['cash_account_id'];
            $amount = (float) ($validated['tax_amount'] ?? $validated['amount'] ?? 0);
            $description = $validated['description'] ?? ('Pembayaran / Pencatatan Pajak ' . ($tax?->name ?? ''));

            $dateStr = date('Ymd', strtotime($transactionDate));
            $prefix = "TAX-{$dateStr}-";
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
                'journal_date'     => $transactionDate,
                'reference_number' => $validated['reference_number'] ?? $journalNumber,
                'description'      => $description,
                'status'           => 'posted',
                'created_by'       => $request->user()->id,
            ]);

            JournalDetail::create([
                'journal_id'  => $journal->id,
                'account_id'  => $taxAccountId,
                'debit'       => $amount,
                'credit'      => 0,
                'description' => $description,
            ]);

            JournalDetail::create([
                'journal_id'  => $journal->id,
                'account_id'  => $cashAccountId,
                'debit'       => 0,
                'credit'      => $amount,
                'description' => $description,
            ]);

            return $journal;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Transaksi pajak berhasil dicatat dan diposting ke jurnal.',
                'data'    => $journal->load('details.account'),
            ], 201);
        }

        return redirect()
            ->route('admin.taxes.index')
            ->with('success', 'Transaksi pajak berhasil dicatat dan diposting ke jurnal.');
    }

    public function storeTaxType(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'code'        => ['nullable', 'string', 'max:50', 'unique:taxes,code'],
            'rate'        => ['required', 'numeric', 'min:0', 'max:100'],
            'account_id'  => ['nullable', 'exists:chart_of_accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $tax = Tax::create([
            'name'        => $validated['name'],
            'code'        => $validated['code'] ?? strtoupper(str_replace(' ', '_', $validated['name'])),
            'rate'        => $validated['rate'],
            'account_id'  => $validated['account_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active'   => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Master pajak berhasil ditambahkan.',
                'data'    => $tax,
            ], 201);
        }

        return redirect()
            ->route('admin.taxes.index')
            ->with('success', 'Jenis pajak baru berhasil ditambahkan.');
    }
}
