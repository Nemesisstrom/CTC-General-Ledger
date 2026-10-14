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
        // Ambil daftar jurnal penyesuaian (ditandai prefix ADJ)
        $adjustments = Journal::with(['details.account', 'creator'])
            ->where('journal_number', 'LIKE', 'ADJ-%')
            ->orderBy('journal_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        // Akun Beban (Expense) untuk Debit
        $expenseAccounts = ChartOfAccount::where('type', 'expense')
            ->where('is_active', true)
            ->get();

        // Akun Akumulasi Penyusutan / Kewajiban / Aset untuk Kredit
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
            // Generasi nomor jurnal penyesuaian berbasis ID terakhir agar aman dari duplikasi
            $nextId = (Journal::max('id') ?? 0) + 1;
            $journalNumber = 'ADJ-' . date('Ym') . '-' . sprintf('%04d', $nextId);

            // 1. Simpan Header Jurnal Penyesuaian
            $journal = Journal::create([
                'journal_number'   => $journalNumber,
                'journal_date'     => $request->adjustment_date,
                'reference_number' => 'PENYESUAIAN',
                'description'      => '[Jurnal Penyesuaian] ' . $request->description,
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            // 2. Debit: Beban (misal: Beban Penyusutan Peralatan)
            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $request->expense_account_id,
                'debit'      => $request->amount,
                'credit'     => 0,
                'memo'       => 'Penyesuaian: ' . $request->description,
            ]);

            // 3. Kredit: Akumulasi Penyusutan / Kontra Aset / Kewajiban
            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $request->accumulated_account_id,
                'debit'      => 0,
                'credit'     => $request->amount,
                'memo'       => 'Penyesuaian: ' . $request->description,
            ]);

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Jurnal penyesuaian berhasil disimpan dan diposting',
                    'data'    => $journal->load('details'),
                ], 201);
            }

            return redirect()
                ->route('admin.adjustments.index')
                ->with('success', 'Jurnal penyesuaian ' . $journalNumber . ' berhasil diposting.');
        } catch (Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Gagal memproses penyesuaian: ' . $e->getMessage()], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Gagal memproses penyesuaian: ' . $e->getMessage())
                ->withInput();
        }
    }
}