<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdjustmentRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class AdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $adjustments = Journal::with(['details.account', 'creator'])
            ->where('journal_number', 'LIKE', 'ADJ-%')
            ->orderBy('journal_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $expenseAccounts = ChartOfAccount::where('type', 'expense')
            ->where('is_active', true)
            ->get();

        $accumulatedAccounts = ChartOfAccount::whereIn('type', ['asset', 'liability', 'equity'])
            ->where('is_active', true)
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['data' => $adjustments]);
        }

        return view('admin.adjustments.index', compact('adjustments', 'expenseAccounts', 'accumulatedAccounts'));
    }

    public function store(StoreAdjustmentRequest $request)
    {
        DB::beginTransaction();
        try {
            $nextId = (Journal::max('id') ?? 0) + 1;
            $journalNumber = 'ADJ-' . date('Ym') . '-' . sprintf('%04d', $nextId);

            $journal = Journal::create([
                'journal_number'   => $journalNumber,
                'journal_date'     => $request->adjustment_date,
                'reference_number' => 'PENYESUAIAN',
                'description'      => '[Jurnal Penyesuaian] ' . $request->description,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $request->expense_account_id,
                'debit'      => $request->amount,
                'credit'     => 0,
                'memo'       => 'Penyesuaian: ' . $request->description,
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $request->accumulated_account_id,
                'debit'      => 0,
                'credit'     => $request->amount,
                'memo'       => 'Penyesuaian: ' . $request->description,
            ]);

            DB::commit();

            return redirect()
                ->route('admin.adjustments.index')
                ->with('success', 'Jurnal penyesuaian ' . $journalNumber . ' berhasil diposting.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal memproses penyesuaian: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Membatalkan / Reversal Jurnal Penyesuaian
     */
    public function reverse($id)
    {
        $original = Journal::with('details')->findOrFail($id);

        if ($original->status === 'reversed') {
            return redirect()->back()->with('error', 'Jurnal ini sudah pernah dibatalkan.');
        }

        DB::beginTransaction();
        try {
            $reversalNumber = 'REV-' . $original->journal_number;

            $reversal = Journal::create([
                'journal_number'   => $reversalNumber,
                'journal_date'     => now()->toDateString(),
                'reference_number' => $original->journal_number,
                'description'      => '[PEMBATALAN] ' . $original->description,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            foreach ($original->details as $detail) {
                JournalDetail::create([
                    'journal_id' => $reversal->id,
                    'account_id' => $detail->account_id,
                    'debit'      => $detail->credit, // Tukar Debit & Kredit
                    'credit'     => $detail->debit,
                    'memo'       => 'Pembalik otomatis ' . $original->journal_number,
                ]);
            }

            $original->update(['status' => 'reversed']);

            DB::commit();

            return redirect()
                ->route('admin.adjustments.index')
                ->with('success', 'Jurnal penyesuaian ' . $original->journal_number . ' berhasil dibatalkan.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal membatalkan jurnal: ' . $e->getMessage());
        }
    }

    /**
     * Hapus Jurnal Penyesuaian (Hanya jika benar-benar dibutuhkan)
     */
    public function destroy($id)
    {
        $journal = Journal::findOrFail($id);

        DB::beginTransaction();
        try {
            // Hapus detail lalu header
            $journal->details()->delete();
            $journal->delete();

            DB::commit();

            return redirect()
                ->route('admin.adjustments.index')
                ->with('success', 'Data jurnal penyesuaian berhasil dihapus.');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus jurnal: ' . $e->getMessage());
        }
    }
}
