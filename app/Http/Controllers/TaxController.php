<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxController extends Controller
{
    public function index()
    {
        return response()->json(Tax::all());
    }

    // Perhitungan otomatis transaksi bertipe Pajak (misal Penjualan dengan PPN 11%)
    public function processTaxTransaction(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'subtotal' => 'required|numeric',
            'tax_id' => 'required|exists:taxes,id',
            'cash_account_id' => 'required|exists:chart_of_accounts,id',
            'revenue_account_id' => 'required|exists:chart_of_accounts,id',
        ]);

        $tax = Tax::findOrFail($request->tax_id);
        $taxAmount = ($request->subtotal * $tax->rate) / 100;
        $totalAmount = $request->subtotal + $taxAmount;

        DB::beginTransaction();
        try {
            $journal = Journal::create([
                'journal_number' => 'TAX-TRX-' . time(),
                'journal_date' => $request->date,
                'description' => 'Transaksi Pajak ' . $tax->name,
                'status' => 'posted',
                'created_by' => auth()->id()
            ]);

            // Debit Kas (Total setelah pajak)
            JournalDetail::create(['journal_id' => $journal->id, 'account_id' => $request->cash_account_id, 'debit' => $totalAmount, 'credit' => 0]);

            // Kredit Pendapatan (Nilai sebelum pajak)
            JournalDetail::create(['journal_id' => $journal->id, 'account_id' => $request->revenue_account_id, 'debit' => 0, 'credit' => $request->subtotal]);

            // Kredit Hutang Pajak / PPN Keluaran
            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $tax->account_id,
                'tax_id' => $tax->id,
                'debit' => 0,
                'credit' => $taxAmount
            ]);

            DB::commit();
            return response()->json(['message' => 'Transaksi pajak berhasil dihitung dan di-jurnal', 'tax_amount' => $taxAmount]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
