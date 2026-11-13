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

        // Kunci periode jika tahun rekonsiliasi sudah ditutup buku
        if ($this->isPeriodClosed($statementDate)) {
            return back()
                ->with('error', $this->closedPeriodMessage($statementDate))
                ->withInput();
        }

        // Cegah duplikasi rekonsiliasi untuk akun bank dan tanggal rekening koran yang sama
        $alreadyExists = BankReconciliation::where('account_id', $validated['account_id'])
            ->whereDate('statement_date', $statementDate)
            ->exists();

        if ($alreadyExists) {
            return back()
                ->with('error', 'Akun kas/bank tersebut sudah memiliki data rekonsiliasi pada tanggal rekening koran yang sama.')
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // Hitung saldo buku (Book Balance) langsung via SQL SUM hanya dari jurnal berstatus 'posted'
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

            BankReconciliation::create([
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

            return redirect()
                ->route('admin.bank-reconciliations.index')
                ->with('success', 'Data rekonsiliasi bank berhasil disimpan.');

        } catch (Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal memproses rekonsiliasi bank: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        $reconciliation = BankReconciliation::findOrFail($id);

        if ($this->isPeriodClosed($reconciliation->statement_date)) {
            return back()->with('error', $this->closedPeriodMessage($reconciliation->statement_date));
        }

        $validated = $request->validate([
            'statement_balance' => 'required|numeric',
            'notes'             => 'nullable|string|max:500',
            'status'            => 'nullable|in:reconciled,unreconciled',
        ]);

        // Hitung ulang book balance terkini sampai tanggal statement
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

        return redirect()
            ->route('admin.bank-reconciliations.index')
            ->with('success', 'Rekonsiliasi bank berhasil diperbarui dan disesuaikan dengan saldo buku terkini.');
    }

    public function destroy($id)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya Administrator yang memiliki otoritas menghapus data rekonsiliasi bank.');
        }

        $reconciliation = BankReconciliation::findOrFail($id);

        if ($this->isPeriodClosed($reconciliation->statement_date)) {
            return back()->with('error', $this->closedPeriodMessage($reconciliation->statement_date));
        }

        $reconciliation->delete();

        return redirect()
            ->route('admin.bank-reconciliations.index')
            ->with('success', 'Data rekonsiliasi bank berhasil dihapus.');
    }
}
