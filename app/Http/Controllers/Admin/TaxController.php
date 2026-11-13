<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaxTransactionRequest;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Tax;
use App\Traits\ChecksClosedPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class TaxController extends Controller
{
    use ChecksClosedPeriod;

    public function index(Request $request)
    {
        $taxes = Tax::orderBy('name')->get();

        $taxJournals = Journal::with(['details.account', 'creator'])
            ->where('journal_number', 'LIKE', 'TAX-%')
            ->orWhere('journal_number', 'LIKE', 'JRN-TAX-%')
            ->orderBy('journal_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $accounts = ChartOfAccount::where('is_active', true)
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();

        return view('admin.tax.index', compact('taxes', 'taxJournals', 'accounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:30|unique:taxes,code',
            'rate'        => 'required|numeric|min:0|max:100',
            'type'        => 'required|in:ppn_in,ppn_out,pph,other',
            'account_id'  => 'nullable|exists:chart_of_accounts,id',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        Tax::create([
            'name'        => $validated['name'],
            'code'        => strtoupper($validated['code']),
            'rate'        => round((float) $validated['rate'], 2),
            'type'        => $validated['type'],
            'account_id'  => $validated['account_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.taxes.index')
            ->with('success', 'Master tarif pajak berhasil ditambahkan.');
    }

    public function update(Request $request, Tax $tax)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:30|unique:taxes,code,' . $tax->id,
            'rate'        => 'required|numeric|min:0|max:100',
            'type'        => 'required|in:ppn_in,ppn_out,pph,other',
            'account_id'  => 'nullable|exists:chart_of_accounts,id',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'boolean',
        ]);

        $tax->update([
            'name'        => $validated['name'],
            'code'        => strtoupper($validated['code']),
            'rate'        => round((float) $validated['rate'], 2),
            'type'        => $validated['type'],
            'account_id'  => $validated['account_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.taxes.index')
            ->with('success', 'Master tarif pajak berhasil diperbarui.');
    }

    public function destroy(Tax $tax)
    {
        $tax->delete();

        return redirect()
            ->route('admin.taxes.index')
            ->with('success', 'Master tarif pajak berhasil dihapus.');
    }

    public function processTaxTransaction(StoreTaxTransactionRequest $request)
    {
        $validated = $request->validated();
        $trxDate = $validated['transaction_date'];

        // Kunci periode jika tahun pajak sudah ditutup buku
        if ($this->isPeriodClosed($trxDate)) {
            return back()
                ->with('error', $this->closedPeriodMessage($trxDate))
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $tax = Tax::findOrFail($validated['tax_id']);
            $baseAmount = round((float) $validated['base_amount'], 2);
            $taxAmount  = round($baseAmount * ((float) $tax->rate / 100), 2);

            if ($taxAmount <= 0) {
                throw new Exception('Nominal pajak terhitung harus lebih besar dari Rp 0.');
            }

            $ymCode = date('Ym', strtotime($trxDate));
            $lastTaxJrn = Journal::where('journal_number', 'like', "JRN-TAX-{$ymCode}-%")
                ->lockForUpdate()
                ->orderByDesc('journal_number')
                ->first();

            $lastSeq = 0;
            if ($lastTaxJrn) {
                $parts = explode('-', $lastTaxJrn->journal_number);
                $lastSeq = (int) end($parts);
            }

            $taxRefNumber = 'TAX-' . $ymCode . '-' . sprintf('%04d', $lastSeq + 1);

            $journal = Journal::create([
                'journal_number'   => 'JRN-' . $taxRefNumber,
                'journal_date'     => $trxDate,
                'reference_number' => $taxRefNumber,
                'description'      => "[Pajak - {$tax->name} ({$tax->rate}%)] " . ($validated['description'] ?? 'Pencatatan Pajak'),
                'status'           => 'posted',
                'created_by'       => auth()->id(),
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $validated['debit_account_id'],
                'debit'      => $taxAmount,
                'credit'     => 0,
                'memo'       => "Pajak {$tax->name} (DPP: Rp " . number_format($baseAmount, 0, ',', '.') . ")",
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'account_id' => $validated['credit_account_id'],
                'debit'      => 0,
                'credit'     => $taxAmount,
                'memo'       => "Pajak {$tax->name} (DPP: Rp " . number_format($baseAmount, 0, ',', '.') . ")",
            ]);

            DB::commit();

            return redirect()
                ->route('admin.taxes.index')
                ->with('success', "Transaksi pajak {$taxRefNumber} sebesar Rp " . number_format($taxAmount, 0, ',', '.') . " berhasil diposting ke jurnal.");

        } catch (Exception $e) {
            DB::rollBack();
            return back()
                ->with('error', 'Gagal memproses transaksi pajak: ' . $e->getMessage())
                ->withInput();
        }
    }
}
