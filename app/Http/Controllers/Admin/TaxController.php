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
            ->where(function ($q) {
                $q->where('journal_number', 'LIKE', 'TAX-%')
                  ->orWhere('journal_number', 'LIKE', 'JRN-TAX-%');
            })
            ->orderBy('journal_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json([
                'taxes'        => $taxes,
                'tax_journals' => $taxJournals,
            ]);
        }

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

        $tax = Tax::create([
            'name'        => $validated['name'],
            'code'        => strtoupper($validated['code']),
            'rate'        => round((float) $validated['rate'], 2),
            'type'        => $validated['type'],
            'account_id'  => $validated['account_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Master tarif pajak berhasil ditambahkan.', 'data' => $tax], 201);
        }

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

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Master tarif pajak berhasil diperbarui.', 'data' => $tax]);
        }

        return redirect()
            ->route('admin.taxes.index')
            ->with('success', 'Master tarif pajak berhasil diperbarui.');
    }

    public function destroy(Request $request, Tax $tax)
    {
        $tax->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Master tarif pajak berhasil dihapus.']);
        }

        return redirect()
            ->route('admin.taxes.index')
            ->with('success', 'Master tarif pajak berhasil dihapus.');
    }

    public function processTaxTransaction(StoreTaxTransactionRequest $request)
    {
        $validated = $request->validated();
        $trxDate = $validated['transaction_date'];

        if ($this->isPeriodClosed($trxDate)) {
            $msg = $this->closedPeriodMessage($trxDate);
            if ($request->wantsJson()) {
                return response()->json(['message' => $msg], 422);
            }
            return back()->with('error', $msg)->withInput();
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

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => "Transaksi pajak {$taxRefNumber} berhasil diposting ke jurnal.",
                    'data'    => $journal->load('details.account'),
                ], 201);
            }

            return redirect()
                ->route('admin.taxes.index')
                ->with('success', "Transaksi pajak {$taxRefNumber} sebesar Rp " . number_format($taxAmount, 0, ',', '.') . " berhasil diposting ke jurnal.");

        } catch (Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Gagal memproses transaksi pajak: ' . $e->getMessage()], 422);
            }
            return back()
                ->with('error', 'Gagal memproses transaksi pajak: ' . $e->getMessage())
                ->withInput();
        }
    }
}
