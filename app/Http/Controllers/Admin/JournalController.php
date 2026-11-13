<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJournalRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Traits\ChecksClosedPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class JournalController extends Controller
{
    use ChecksClosedPeriod;

    public function index(Request $request)
    {
        $journals = Journal::with(['details.account', 'creator'])
            ->orderBy('journal_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $accounts = ChartOfAccount::where('is_active', true)
            ->whereNotNull('parent_id')
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
            ->where('journal_number', 'NOT LIKE', 'JRN-CLS-%')
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
        $journalDate = $validated['transaction_date'] ?? $validated['journal_date'];

        // Cek apakah tahun jurnal sudah di-Tutup Buku
        if ($this->isPeriodClosed($journalDate)) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $this->closedPeriodMessage($journalDate)], 422);
            }
            return back()
                ->withInput()
                ->with('error', $this->closedPeriodMessage($journalDate));
        }

        DB::beginTransaction();
        try {
            $ymCode = date('Ym', strtotime($journalDate));
            $lastJournal = Journal::where('journal_number', 'like', "JRN-{$ymCode}-%")
                ->lockForUpdate()
                ->orderByDesc('journal_number')
                ->first();

            $lastSequence = 0;
            if ($lastJournal) {
                $parts = explode('-', $lastJournal->journal_number);
                $lastSequence = (int) end($parts);
            }

            $journalNumber = 'JRN-' . $ymCode . '-' . sprintf('%04d', $lastSequence + 1);

            $journal = Journal::create([
                'journal_number'   => $journalNumber,
                'journal_date'     => $journalDate,
                'reference_number' => $validated['reference_number'] ?? null,
                'description'      => $validated['description'] ?? null,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $journal->details()->create([
                    'account_id' => $item['account_id'] ?? $item['chart_of_account_id'],
                    'debit'      => round((float) $item['debit'], 2),
                    'credit'     => round((float) $item['credit'], 2),
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

        // Cek apakah tahun jurnal asli sudah di-Tutup Buku
        if ($this->isPeriodClosed($originalJournal->journal_date)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $this->closedPeriodMessage($originalJournal->journal_date)], 422);
            }
            return redirect()->back()->with('error', $this->closedPeriodMessage($originalJournal->journal_date));
        }

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
                    'debit'      => round((float) $detail->credit, 2),
                    'credit'     => round((float) $detail->debit, 2),
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
