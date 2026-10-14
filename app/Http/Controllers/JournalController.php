<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJournalRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalController extends Controller
{
    public function index(Request $request)
    {
        $journals = Journal::with(['details.account', 'creator'])
            ->orderBy('journal_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $accounts = ChartOfAccount::where('is_active', true)
            ->orderBy('code', 'asc')
            ->get();

        if ($request->wantsJson()) {
            return response()->json(['data' => $journals]);
        }

        return view('admin.journals.index', compact('journals', 'accounts'));
    }

    public function store(StoreJournalRequest $request)
    {
        DB::beginTransaction();
        try {
            $journalNumber = 'JRN-' . date('Ym') . '-' . sprintf('%04d', Journal::count() + 1);

            $journal = Journal::create([
                'journal_number'   => $journalNumber,
                'journal_date'     => $request->journal_date,
                'reference_number' => $request->reference_number,
                'description'      => $request->description,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            foreach ($request->items as $item) {
                if (($item['debit'] ?? 0) > 0 || ($item['credit'] ?? 0) > 0) {
                    JournalDetail::create([
                        'journal_id' => $journal->id,
                        'account_id' => $item['account_id'],
                        'debit'      => $item['debit'] ?? 0,
                        'credit'     => $item['credit'] ?? 0,
                        'memo'       => $item['memo'] ?? null,
                    ]);
                }
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Jurnal umum berhasil disimpan', 'data' => $journal->load('details')], 201);
            }

            return redirect()->route('journals.index')->with('success', 'Jurnal umum ' . $journalNumber . ' berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Gagal menyimpan jurnal: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Gagal menyimpan jurnal: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Membalikkan Jurnal (Reversal Journal Entry)
     */
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
                'journal_date'     => date('Y-m-d'),
                'reference_number' => $originalJournal->journal_number,
                'description'      => '[REVERSAL/PEMBATALAN] ' . $originalJournal->description,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            // Pembalikan Otomatis: Debit jadi Kredit, Kredit jadi Debit
            foreach ($originalJournal->details as $detail) {
                JournalDetail::create([
                    'journal_id' => $reversalJournal->id,
                    'account_id' => $detail->account_id,
                    'debit'      => $detail->credit, // Tukar Posisi
                    'credit'     => $detail->debit,  // Tukar Posisi
                    'memo'       => 'Pembalikan otomatis dari ' . $originalJournal->journal_number,
                ]);
            }

            // Update status jurnal asli
            $originalJournal->update(['status' => 'reversed']);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json(['message' => 'Jurnal berhasil dibatalkan (reversed)', 'reversal' => $reversalJournal->load('details')]);
            }

            return redirect()->route('journals.index')->with('success', 'Jurnal ' . $originalJournal->journal_number . ' berhasil dibatalkan dengan Reversal Entry: ' . $reversalNumber);
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Gagal membalikkan jurnal: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Gagal membalikkan jurnal: ' . $e->getMessage());
        }
    }
}
