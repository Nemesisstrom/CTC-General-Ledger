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
        $journals = Journal::with(['details.chartOfAccount', 'creator'])
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

    /**
     * Menyimpan data transaksi jurnal baru.
     */
    public function store(StoreJournalRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            // Generasi nomor jurnal unik
            $nextId = (Journal::max('id') ?? 0) + 1;
            $journalNumber = 'JRN-' . date('Ym') . '-' . sprintf('%04d', $nextId);

            // 1. Simpan Header Jurnal
            $journal = Journal::create([
                'journal_number'   => $journalNumber,
                'journal_date'     => $validated['transaction_date'] ?? $validated['journal_date'],
                'reference_number' => $validated['reference_number'],
                'description'      => $validated['description'] ?? null,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            // 2. Simpan Detail Jurnal
            foreach ($validated['items'] as $item) {
                $journal->details()->create([
                    'chart_of_account_id' => $item['chart_of_account_id'] ?? $item['account_id'],
                    'debit'               => $item['debit'],
                    'credit'              => $item['credit'],
                    'memo'                => $item['memo'] ?? null,
                ]);
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Transaksi jurnal berhasil disimpan.',
                    'data'    => $journal->load('details')
                ], 201);
            }

            return redirect()
                ->route('admin.journals.index')
                ->with('success', 'Transaksi jurnal ' . $journalNumber . ' berhasil disimpan.');

        } catch (Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Gagal menyimpan jurnal: ' . $e->getMessage()], 500);
            }

            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan jurnal: ' . $e->getMessage());
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
                'journal_date'     => now()->toDateString(),
                'reference_number' => $originalJournal->journal_number,
                'description'      => '[REVERSAL/PEMBATALAN] ' . $originalJournal->description,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            // Pembalikan Otomatis: Debit jadi Kredit, Kredit jadi Debit
            foreach ($originalJournal->details as $detail) {
                JournalDetail::create([
                    'journal_id'          => $reversalJournal->id,
                    'chart_of_account_id' => $detail->chart_of_account_id ?? $detail->account_id,
                    'debit'               => $detail->credit, // Tukar Posisi
                    'credit'              => $detail->debit,  // Tukar Posisi
                    'memo'                => 'Pembalikan otomatis dari ' . $originalJournal->journal_number,
                ]);
            }

            // Update status jurnal asli
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