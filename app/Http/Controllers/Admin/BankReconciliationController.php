<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBankReconciliationRequest;
use App\Models\BankReconciliation;
use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use App\Traits\ChecksClosedPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class BankReconciliationController extends Controller
{
    use ChecksClosedPeriod;

    public function index(Request $request)
    {
        $reconciliations = BankReconciliation::with(['account', 'creator'])
            ->orderBy('statement_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json($reconciliations);
        }

        $bankAccounts = ChartOfAccount::where('is_active', true)
            ->whereNotNull('parent_id')
            ->where('code', 'like', '1-11%')
            ->orderBy('code')
            ->get();

        return view('admin.bank_reconciliation.index', compact('reconciliations', 'bankAccounts'));
    }

    public function store(StoreBankReconciliationRequest $request)
    {
        $validated = $request->validated();
        $statementDate = $validated['statement_date'];

        if ($this->isPeriodClosed($statementDate)) {
            $msg = $this->closedPeriodMessage($statementDate);
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        $alreadyExists = BankReconciliation::where('account_id', $validated['account_id'])
            ->whereDate('statement_date', $statementDate)
            ->exists();

        if ($alreadyExists) {
            $msg = 'Akun kas/bank tersebut sudah memiliki data rekonsiliasi pada tanggal rekening koran yang sama.';
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        DB::beginTransaction();
        try {
            $bookTotals = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
                ->where('journal_details.account_id', $validated['account_id'])
                ->where('journals.status', 'posted')
                ->whereDate('journals.journal_date', '<=', $statementDate)
                ->selectRaw('COALESCE(SUM(journal_details.debit), 0) as total_debit, COALESCE(SUM(journal_details.credit), 0) as total_credit')
                ->first();

            $bookBalance = round((float) $bookTotals->total_debit - (float) $bookTotals->total_credit, 2);
            $statementBalance = round((float) $validated['statement_balance'], 2);
            $difference = round($statementBalance - $bookBalance, 2);
            $status = abs($difference) < 0.01 ? 'reconciled' : 'unreconciled';

            $reconciliation = BankReconciliation::create([
                'account_id'        => $validated['account_id'],
                'statement_date'    => $statementDate,
                'statement_balance' => $statementBalance,
                'book_balance'      => $bookBalance,
                'difference'        => $difference,
                'status'            => $status,
                'notes'             => $validated['notes'] ?? null,
                'created_by'        => auth()->id(),
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Data rekonsiliasi bank berhasil disimpan.',
                    'data'    => $reconciliation->load('account'),
                ], 201);
            }

            return redirect()
                ->route('admin.bank-reconciliations.index')
                ->with('success', 'Data rekonsiliasi bank berhasil disimpan.');

        } catch (Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal memproses rekonsiliasi bank: ' . $e->getMessage()], 422);
            }
            return back()
                ->with('error', 'Gagal memproses rekonsiliasi bank: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $reconciliation = BankReconciliation::findOrFail($id);

        if ($this->isPeriodClosed($reconciliation->statement_date)) {
            $msg = $this->closedPeriodMessage($reconciliation->statement_date);
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $validated = $request->validate([
            'statement_balance' => 'required|numeric',
            'notes'             => 'nullable|string|max:500',
            'status'            => 'nullable|in:reconciled,unreconciled',
        ]);

        $bookTotals = JournalDetail::join('journals', 'journal_details.journal_id', '=', 'journals.id')
            ->where('journal_details.account_id', $reconciliation->account_id)
            ->where('journals.status', 'posted')
            ->whereDate('journals.journal_date', '<=', $reconciliation->statement_date)
            ->selectRaw('COALESCE(SUM(journal_details.debit), 0) as total_debit, COALESCE(SUM(journal_details.credit), 0) as total_credit')
            ->first();

        $bookBalance = round((float) $bookTotals->total_debit - (float) $bookTotals->total_credit, 2);
        $statementBalance = round((float) $validated['statement_balance'], 2);
        $difference = round($statementBalance - $bookBalance, 2);
        $status = $validated['status'] ?? (abs($difference) < 0.01 ? 'reconciled' : 'unreconciled');

        $reconciliation->update([
            'statement_balance' => $statementBalance,
            'book_balance'      => $bookBalance,
            'difference'        => $difference,
            'status'            => $status,
            'notes'             => $validated['notes'] ?? $reconciliation->notes,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Rekonsiliasi bank berhasil diperbarui.',
                'data'    => $reconciliation->fresh('account'),
            ]);
        }

        return redirect()
            ->route('admin.bank-reconciliations.index')
            ->with('success', 'Rekonsiliasi bank berhasil diperbarui dan disesuaikan dengan saldo buku terkini.');
    }

    public function destroy(Request $request, $id)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya Administrator yang memiliki otoritas menghapus data rekonsiliasi bank.');
        }

        $reconciliation = BankReconciliation::findOrFail($id);

        if ($this->isPeriodClosed($reconciliation->statement_date)) {
            $msg = $this->closedPeriodMessage($reconciliation->statement_date);
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $reconciliation->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Data rekonsiliasi bank berhasil dihapus.']);
        }

        return redirect()
            ->route('admin.bank-reconciliations.index')
            ->with('success', 'Data rekonsiliasi bank berhasil dihapus.');
    }
}
