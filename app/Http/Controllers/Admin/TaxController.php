<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaxTransactionRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class TaxController extends Controller
{
    public function index(Request $request)
    {
        $taxes = Tax::with('account')->orderBy('type')->get();
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code', 'asc')->get();
        $cashAccounts = ChartOfAccount::where('type', 'asset')->where('is_active', true)->orderBy('code', 'asc')->get();

        if ($request->wantsJson()) {
            return response()->json(['data' => $taxes]);
        }

        return view('admin.tax.index', compact('taxes', 'accounts', 'cashAccounts'));
    }

    /**
     * Tambah Master Skema Pajak Baru (Admin Only)
     */
    public function store(Request $request)
    {
        $request->validate([
            'code'        => 'required|string|max:20|unique:taxes,code',
            'name'        => 'required|string|max:255',
            'rate'        => 'required|numeric|min:0|max:100',
            'type'        => 'required|in:PPN,PPh21,PPh23,PPh_Final',
            'account_id'  => 'required|exists:chart_of_accounts,id',
            'description' => 'nullable|string',
        ]);

        Tax::create([
            'code'        => strtoupper($request->code),
            'name'        => $request->name,
            'rate'        => $request->rate,
            'type'        => $request->type,
            'account_id'  => $request->account_id,
            'description' => $request->description,
            'is_active'   => true,
        ]);

        return redirect()->route('admin.taxes.index')->with('success', 'Skema pajak baru berhasil ditambahkan.');
    }

    /**
     * Update Master Skema Pajak (Admin Only)
     */
    public function update(Request $request, Tax $tax)
    {
        $request->validate([
            'code'        => 'required|string|max:20|unique:taxes,code,' . $tax->id,
            'name'        => 'required|string|max:255',
            'rate'        => 'required|numeric|min:0|max:100',
            'type'        => 'required|in:PPN,PPh21,PPh23,PPh_Final',
            'account_id'  => 'required|exists:chart_of_accounts,id',
            'is_active'   => 'required|boolean',
            'description' => 'nullable|string',
        ]);

        $tax->update([
            'code'        => strtoupper($request->code),
            'name'        => $request->name,
            'rate'        => $request->rate,
            'type'        => $request->type,
            'account_id'  => $request->account_id,
            'is_active'   => $request->is_active,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.taxes.index')->with('success', 'Skema pajak berhasil diperbarui.');
    }

    /**
     * Hapus Master Skema Pajak (Admin Only)
     */
    public function destroy(Tax $tax)
    {
        $tax->delete();
        return redirect()->route('admin.taxes.index')->with('success', 'Skema pajak berhasil dihapus.');
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
            $nextId = (Journal::max('id') ?? 0) + 1;
            $journalNumber = 'TAX-' . date('Ym') . '-' . sprintf('%04d', $nextId);

            $journal = Journal::create([
                'journal_number'   => $journalNumber,
                'journal_date'     => $request->transaction_date,
                'reference_number' => 'TAX-' . $tax->code,
                'description'      => '[Otomatisasi Pajak ' . $tax->name . '] ' . ($request->description ?? 'Transaksi Terkait Pajak'),
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            if ($request->transaction_type === 'sales') {
                $totalCash = $subtotal + $taxAmount;

                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $request->cash_account_id,
                    'debit'      => $totalCash,
                    'credit'     => 0,
                    'memo'       => 'Penerimaan Kas (Termasuk ' . $tax->name . ')',
                ]);

                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $request->revenue_expense_id,
                    'debit'      => 0,
                    'credit'     => $subtotal,
                    'memo'       => 'Pendapatan Bersih DPP',
                ]);

                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $tax->account_id,
                    'tax_id'     => $tax->id,
                    'debit'      => 0,
                    'credit'     => $taxAmount,
                    'memo'       => 'Kewajiban ' . $tax->name,
                ]);
            } else {
                $isWithholding = str_contains(strtoupper($tax->type ?? ''), 'PPH');

                if ($isWithholding) {
                    $totalCash = $subtotal - $taxAmount;

                    JournalDetail::create([
                        'journal_id' => $journal->id,
                        'account_id' => $request->revenue_expense_id,
                        'debit'      => $subtotal,
                        'credit'     => 0,
                        'memo'       => 'Beban Operasional DPP',
                    ]);

                    JournalDetail::create([
                        'journal_id' => $journal->id,
                        'account_id' => $tax->account_id,
                        'tax_id'     => $tax->id,
                        'debit'      => 0,
                        'credit'     => $taxAmount,
                        'memo'       => 'Potongan ' . $tax->name,
                    ]);

                    JournalDetail::create([
                        'journal_id' => $journal->id,
                        'account_id' => $request->cash_account_id,
                        'debit'      => 0,
                        'credit'     => $totalCash,
                        'memo'       => 'Pembayaran Kas setelah Potongan Pajak',
                    ]);
                } else {
                    $totalCash = $subtotal + $taxAmount;

                    JournalDetail::create([
                        'journal_id' => $journal->id,
                        'account_id' => $request->revenue_expense_id,
                        'debit'      => $subtotal,
                        'credit'     => 0,
                        'memo'       => 'Beban Operasional / Pembelian DPP',
                    ]);

                    JournalDetail::create([
                        'journal_id' => $journal->id,
                        'account_id' => $tax->account_id,
                        'tax_id'     => $tax->id,
                        'debit'      => $taxAmount,
                        'credit'     => 0,
                        'memo'       => 'PPN Masukan / ' . $tax->name,
                    ]);

                    JournalDetail::create([
                        'journal_id' => $journal->id,
                        'account_id' => $request->cash_account_id,
                        'debit'      => 0,
                        'credit'     => $totalCash,
                        'memo'       => 'Pengeluaran Kas Pembelian (Termasuk Pajak)',
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.taxes.index')
                ->with('success', 'Transaksi pajak ' . $tax->name . ' sebesar Rp ' . number_format($taxAmount, 0, ',', '.') . ' berhasil dihitung & di-jurnal.');

        } catch (Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Gagal memproses transaksi pajak: ' . $e->getMessage())
                ->withInput();
        }
    }
}
