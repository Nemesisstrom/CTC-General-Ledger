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

    /**
     * Halaman Khusus Pengelolaan & Eksekusi Jurnal Pembalikan (Reversal)
     */
    public function reversalIndex(Request $request)
    {
        // Ambil jurnal aktif yang dapat di-reverse
        $postedJournals = Journal::with(['details.account', 'creator'])
            ->where('status', 'posted')
            ->where('journal_number', 'NOT LIKE', 'REV-%')
            ->orderBy('journal_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'posted_page');

        // Ambil riwayat jurnal pembalik atau jurnal yang sudah dibatalkan
        $reversedJournals = Journal::with(['details.account', 'creator'])
            ->where(function ($query) {
                $query->where('status', 'reversed')
                      ->orWhere('journal_number', 'LIKE', 'REV-%');
            })
            ->orderBy('journal_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'reversed_page');

        return view('admin.journals.reversals', compact('postedJournals', 'reversedJournals'));
    }

    public function store(StoreJournalRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();
        try {
            $nextId = (Journal::max('id') ?? 0) + 1;
            $journalNumber = 'JRN-' . date('Ym') . '-' . sprintf('%04d', $nextId);

            $journal = Journal::create([
                'journal_number'   => $journalNumber,
                'journal_date'     => $validated['transaction_date'] ?? $validated['journal_date'],
                'reference_number' => $validated['reference_number'] ?? null,
                'description'      => $validated['description'] ?? null,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $journal->details()->create([
                    'account_id' => $item['account_id'] ?? $item['chart_of_account_id'],
                    'debit'      => $item['debit'],
                    'credit'     => $item['credit'],
                    'memo'       => $item['memo'] ?? null,
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
                    'journal_id' => $reversalJournal->id,
                    'account_id' => $detail->account_id ?? $detail->chart_of_account_id,
                    'debit'      => $detail->credit,
                    'credit'     => $detail->debit,
                    'memo'       => 'Pembalikan otomatis dari ' . $originalJournal->journal_number,
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
                ->route('admin.journals.reversals.index')
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
