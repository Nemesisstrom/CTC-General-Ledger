<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JournalController extends Controller
{
    public function index()
    {
        return response()->json(Journal::with('details.account')->orderBy('journal_date', 'desc')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'journal_date' => 'required|date',
            'description' => 'required|string',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.debit' => 'numeric',
            'items.*.credit' => 'numeric'
        ]);

        // Cek Balance Debit & Kredit
        $totalDebit = collect($request->items)->sum('debit');
        $totalCredit = collect($request->items)->sum('credit');

        if ($totalDebit !== $totalCredit) {
            return response()->json(['message' => 'Jurnal tidak seimbang (Total Debit & Kredit harus sama)'], 422);
        }

        DB::beginTransaction();
        try {
            $journal = Journal::create([
                'journal_number' => 'JRN-' . time(),
                'journal_date' => $request->journal_date,
                'description' => $request->description,
                'status' => 'posted',
                'created_by' => auth()->id()
            ]);

            foreach ($request->items as $item) {
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $item['account_id'],
                    'tax_id' => $item['tax_id'] ?? null,
                    'debit' => $item['debit'] ?? 0,
                    'credit' => $item['credit'] ?? 0,
                    'memo' => $item['memo'] ?? null
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Jurnal umum berhasil disimpan', 'data' => $journal->load('details')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Reversal Jurnal (Pembatalan Transaksi)
    public function reverse($id)
    {
        $original = Journal::with('details')->findOrFail($id);

        if ($original->status === 'reversed') {
            return response()->json(['message' => 'Jurnal ini sudah pernah dibatalkan'], 422);
        }

        DB::beginTransaction();
        try {
            $reversal = Journal::create([
                'journal_number' => 'REV-' . $original->journal_number,
                'journal_date' => now()->toDateString(),
                'description' => 'Reversal dari ' . $original->journal_number,
                'status' => 'posted',
                'created_by' => auth()->id()
            ]);

            // Dibalik: Debit jadi Kredit, Kredit jadi Debit
            foreach ($original->details as $detail) {
                JournalDetail::create([
                    'journal_id' => $reversal->id,
                    'account_id' => $detail->account_id,
                    'debit' => $detail->credit,
                    'credit' => $detail->debit,
                    'memo' => 'Reversal entry'
                ]);
            }

            $original->update(['status' => 'reversed']);

            DB::commit();
            return response()->json(['message' => 'Jurnal berhasil dibatalkan (reversed)', 'reversal' => $reversal]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
