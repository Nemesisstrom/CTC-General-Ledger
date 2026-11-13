<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdjustmentRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Traits\ChecksClosedPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AdjustmentController extends Controller
{
    use ChecksClosedPeriod;

    public function index(Request $request)
    {
        $adjustments = Journal::with(['details.account', 'creator'])
            ->where(function ($q) {
                $q->where('journal_number', 'LIKE', 'ADJ-%')
                  ->orWhere('journal_number', 'LIKE', 'JRN-ADJ-%');
            })
            ->orderBy('journal_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json($adjustments);
        }

        $accounts = ChartOfAccount::where('is_active', true)
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();

        return view('admin.adjustments.index', compact('adjustments', 'accounts'));
    }

    public function store(StoreAdjustmentRequest $request)
    {
        $validated = $request->validated();
        $adjDate = $validated['adjustment_date'];

        if ($this->isPeriodClosed($adjDate)) {
            $msg = $this->closedPeriodMessage($adjDate);
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
        }

        DB::beginTransaction();
        try {
            $ymCode = date('Ym', strtotime($adjDate));
            $lastAdj = Journal::where('journal_number', 'like', "ADJ-{$ymCode}-%")
                ->lockForUpdate()
                ->orderByDesc('journal_number')
                ->first();

            $lastSeq = 0;
            if ($lastAdj) {
                $parts = explode('-', $lastAdj->journal_number);
                $lastSeq = (int) end($parts);
            }

            $adjNumber = 'ADJ-' . $ymCode . '-' . sprintf('%04d', $lastSeq + 1);
            $amount = round((float) $validated['amount'], 2);

            $journal = Journal::create([
                'journal_number'   => $adjNumber,
                'journal_date'     => $adjDate,
                'reference_number' => $validated['reference_number'] ?? $adjNumber,
                'description'      => "[Penyesuaian] " . $validated['description'],
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $validated['debit_account_id'],
                'debit'      => $amount,
                'credit'     => 0,
                'memo'       => $validated['description'],
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $validated['credit_account_id'],
                'debit'      => 0,
                'credit'     => $amount,
                'memo'       => $validated['description'],
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => "Jurnal penyesuaian {$adjNumber} berhasil diposting.",
                    'data'    => $journal->load('details.account'),
                ], 201);
            }

            return redirect()
                ->route('admin.adjustments.index')
                ->with('success', "Jurnal penyesuaian {$adjNumber} berhasil diposting ke Buku Besar.");

        } catch (Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal menyimpan jurnal penyesuaian: ' . $e->getMessage()], 422);
            }
            return back()
                ->with('error', 'Gagal menyimpan jurnal penyesuaian: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Request $request, $id)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya Administrator yang memiliki otoritas menghapus jurnal penyesuaian.');
        }

        DB::beginTransaction();
        try {
            $journal = Journal::findOrFail($id);

            if ($this->isPeriodClosed($journal->journal_date)) {
                $msg = $this->closedPeriodMessage($journal->journal_date);
                if ($request->wantsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return back()->with('error', $msg);
            }

            Log::warning('HARD DELETE JURNAL PENYESUAIAN', [
                'journal_number' => $journal->journal_number,
                'journal_date'   => $journal->journal_date,
                'deleted_by_id'  => auth()->id(),
            ]);

            JournalDetail::where('journal_id', $journal->id)->delete();
            $journal->delete();

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Jurnal penyesuaian berhasil dihapus.']);
            }

            return redirect()
                ->route('admin.adjustments.index')
                ->with('success', 'Jurnal penyesuaian berhasil dihapus.');

        } catch (Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal menghapus jurnal penyesuaian: ' . $e->getMessage()], 422);
            }
            return back()->with('error', 'Gagal menghapus jurnal penyesuaian: ' . $e->getMessage());
        }
    }
}
