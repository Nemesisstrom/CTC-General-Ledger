<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaxTransactionRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $taxes = Tax::with('account')->orderBy('type')->get();
        $accounts = ChartOfAccount::where('is_active', true)->get();
        $cashAccounts = ChartOfAccount::where('type', 'asset')->where('is_active', true)->get();

        if ($request->wantsJson()) {
            return response()->json(['data' => $taxes]);
        }

        return view('admin.tax.index', compact('taxes', 'accounts', 'cashAccounts'));
    }

    /**
     * Hitung & Posting Otomatis Transaksi Pajak ke Jurnal Umum
     */
    public function processTaxTransaction(StoreTaxTransactionRequest $request)
    {
        $tax = Tax::findOrFail($request->tax_id);
        $subtotal = $request->subtotal;
        $taxAmount = ($subtotal * $tax->rate) / 100;

        DB::beginTransaction();
        try {
            $journalNumber = 'TAX-' . date('Ym') . '-' . sprintf('%04d', Journal::count() + 1);

            $journal = Journal::create([
                'journal_number'   => $journalNumber,
                'journal_date'     => $request->transaction_date,
                'reference_number' => 'TAX-' . $tax->code,
                'description'      => '[Otomatisasi Pajak ' . $tax->name . '] ' . ($request->description ?? 'Transaksi Terkait Pajak'),
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            if ($request->transaction_type === 'sales') {
                // Penjualan dengan PPN Keluaran
                $totalCash = $subtotal + $taxAmount;

                // 1. Debit: Kas/Bank (Total setelah pajak)
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $request->cash_account_id,
                    'debit'      => $totalCash,
                    'credit'     => 0,
                    'memo'       => 'Penerimaan Kas (Termasuk ' . $tax->name . ')',
                ]);

                // 2. Kredit: Pendapatan (Subtotal DPP)
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $request->revenue_expense_id,
                    'debit'      => 0,
                    'credit'     => $subtotal,
                    'memo'       => 'Pendapatan Bersih DPP',
                ]);

                // 3. Kredit: Hutang Pajak / PPN Keluaran
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $tax->account_id,
                    'tax_id'     => $tax->id,
                    'debit'      => 0,
                    'credit'     => $taxAmount,
                    'memo'       => 'Kewajiban ' . $tax->name,
                ]);
            } else {
                // Pembelian Jasa/Barang dengan Pemotongan PPh 23 atau PPN Masukan
                $totalCash = $subtotal - $taxAmount; // Kas Keluar setelah Potongan PPh 23

                // 1. Debit: Beban Operasional (DPP)
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $request->revenue_expense_id,
                    'debit'      => $subtotal,
                    'credit'     => 0,
                    'memo'       => 'Beban Operasional DPP',
                ]);

                // 2. Kredit: Hutang PPh (Pemotongan Pajak)
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $tax->account_id,
                    'tax_id'     => $tax->id,
                    'debit'      => 0,
                    'credit'     => $taxAmount,
                    'memo'       => 'Potongan ' . $tax->name,
                ]);

                // 3. Kredit: Kas/Bank (Net Payment)
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $request->cash_account_id,
                    'debit'      => 0,
                    'credit'     => $totalCash,
                    'memo'       => 'Pembayaran Kas setelah Potongan Pajak',
                ]);
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'message'    => 'Transaksi pajak berhasil dihitung & diposting ke jurnal',
                    'tax_amount' => $taxAmount,
                    'journal'    => $journal->load('details'),
                ], 201);
            }

            return redirect()->route('taxes.index')->with('success', 'Transaksi pajak ' . $tax->name . ' sebesar Rp ' . number_format($taxAmount, 0, ',', '.') . ' berhasil dihitung & di-jurnal.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->wantsJson()) {
                return response()->json(['error' => 'Gagal memproses pajak: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Gagal memproses transaksi pajak: ' . $e->getMessage())->withInput();
        }
    }
}
