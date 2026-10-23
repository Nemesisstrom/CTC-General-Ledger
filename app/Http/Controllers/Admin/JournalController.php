<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJournalRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        // Menggunakan Eager Loading untuk mencegah N+1 Problem[cite: 2]
        $journals = Journal::with(['details.account', 'creator'])
            ->latest('journal_date')
            ->paginate(15);

        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();

        if ($request->wantsJson()) {
            return response()->json(['data' => $journals]);
        }

        return view('admin.journals.index', compact('journals', 'accounts'));
    }

    public function store(StoreJournalRequest $request)
    {
        DB::beginTransaction();
        try {
            $nextId = (Journal::max('id') ?? 0) + 1;
            $journalNumber = 'JRN-' . date('Ym') . '-' . sprintf('%04d', $nextId);

            // 1. Simpan Header Jurnal
            $journal = Journal::create([
                'journal_number'   => $journalNumber,
                'journal_date'     => $request->journal_date ?? $request->transaction_date,
                'reference_number' => $request->reference_number,
                'description'      => $request->description,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            // 2. Simpan Detail Jurnal (Debet & Kredit)
            $items = $request->items ?? $request->details ?? [];
            foreach ($items as $item) {
                JournalDetail::create([
                    'journal_id'          => $journal->id,
                    'chart_of_account_id' => $item['account_id'] ?? $item['chart_of_account_id'],
                    'debit'               => $item['debit'] ?? 0,
                    'credit'              => $item['credit'] ?? 0,
                    'memo'                => $item['memo'] ?? $request->description,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('admin.journals.index')
                ->with('success', 'Jurnal ' . $journalNumber . ' berhasil disimpan dan diposting.');

        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Gagal memproses jurnal: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function reverse(Request $request, $id)
    {
        $originalJournal = Journal::with('details')->findOrFail($id);

        if ($originalJournal->status === 'reversed') {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Jurnal ini sudah pernah dibatalkan sebelumnya.'], 422);
            }
            return redirect()->back()->with('error', 'Jurnal ini sudah pernah dibatalkan.');
        }

        DB::beginTransaction();
        try {
            $reversalNumber = 'REV-' . $originalJournal->journal_number;

            $reversalJournal = Journal::create([
                'journal_number'   => $reversalNumber,
                'journal_date'     => now()->toDateString(),
                'reference_number' => $originalJournal->journal_number,
                'description'      => '[REVERSAL/PEMBATALAN] ' . $originalJournal->description,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            foreach ($originalJournal->details as $detail) {
                JournalDetail::create([
                    'journal_id'          => $reversalJournal->id,
                    'chart_of_account_id' => $detail->chart_of_account_id ?? $detail->account_id,
                    'debit'               => $detail->credit,
                    'credit'              => $detail->debit,
                    'memo'                => 'Pembalikan otomatis dari ' . $originalJournal->journal_number,
                ]);
            }

            $originalJournal->update(['status' => 'reversed']);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message'  => 'Jurnal berhasil dibatalkan (reversed)',
                    'reversal' => $reversalJournal->load('details')
                ]);
            }

            return redirect()
                ->route('admin.journals.index')
                ->with('success', 'Jurnal ' . $originalJournal->journal_number . ' berhasil dibatalkan dengan Reversal Entry: ' . $reversalNumber);

        } catch (Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Gagal membalikkan jurnal: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Gagal membalikkan jurnal: ' . $e->getMessage());
        }
    }
}
