<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class ClosingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $year = $request->year;
        $startDate = "{$year}-01-01";
        $closingDate = "{$year}-12-31";
        $referenceNumber = "CLS-{$year}-1231";
        $journalNumber = "JRN-{$referenceNumber}";

        // 1. Cegah tutup buku ganda untuk tahun yang sama
        if (Journal::where('reference_number', $referenceNumber)->orWhere('journal_number', $journalNumber)->exists()) {
            return back()->with('error', "Tutup buku tahun {$year} sudah pernah dijalankan ({$journalNumber}).");
        }

        // 2. Pastikan akun 3-1200 (Laba Ditahan) ada di Master COA
        $retainedEarnings = ChartOfAccount::where('code', '3-1200')->first();
        if (!$retainedEarnings) {
            return back()->with('error', 'Akun 3-1200 (Laba Ditahan) tidak ditemukan di Master COA.');
        }

        // 3. Ambil semua akun detail Pendapatan (revenue) & Beban (expense)
        $nominalAccounts = ChartOfAccount::whereIn('type', ['revenue', 'expense'])
            ->whereNotNull('parent_id')
            ->orderBy('code')
            ->get();

        $closingLines = [];
        $totalRevenue = 0;
        $totalExpense = 0;

        foreach ($nominalAccounts as $account) {
            // Hitung total Debit & Kredit akun ini sepanjang tahun (01 Jan - 31 Des)
            $sums = JournalDetail::where('account_id', $account->id)
                ->whereHas('journal', function ($q) use ($startDate, $closingDate) {
                    $q->where('status', 'posted')
                      ->whereBetween('journal_date', [$startDate, $closingDate]);
                })
                ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
                ->first();

            $debitSum = (float) $sums->total_debit;
            $creditSum = (float) $sums->total_credit;

            if ($account->type === 'revenue') {
                // Saldo normal Pendapatan = Kredit. Penutupannya di-DEBIT.
                $balance = $creditSum - $debitSum;
                if (round($balance, 2) != 0) {
                    $totalRevenue += $balance;
                    $closingLines[] = [
                        'account_id' => $account->id,
                        'debit'      => $balance > 0 ? $balance : 0,
                        'credit'     => $balance < 0 ? abs($balance) : 0,
                        'memo'       => "Tutup buku saldo {$account->code} - {$account->name} ({$year})",
                    ];
                }
            } elseif ($account->type === 'expense') {
                // Saldo normal Beban = Debit. Penutupannya di-KREDIT.
                $balance = $debitSum - $creditSum;
                if (round($balance, 2) != 0) {
                    $totalExpense += $balance;
                    $closingLines[] = [
                        'account_id' => $account->id,
                        'debit'      => $balance < 0 ? abs($balance) : 0,
                        'credit'     => $balance > 0 ? $balance : 0,
                        'memo'       => "Tutup buku saldo {$account->code} - {$account->name} ({$year})",
                    ];
                }
            }
        }

        if (empty($closingLines)) {
            return back()->with('error', "Tidak ditemukan saldo Pendapatan atau Beban pada tahun {$year} yang perlu ditutup.");
        }

        // 4. Hitung Laba / Rugi Bersih ke akun 3-1200 (Laba Ditahan)
        $netIncome = $totalRevenue - $totalExpense;

        if (round($netIncome, 2) != 0) {
            $closingLines[] = [
                'account_id' => $retainedEarnings->id,
                // Laba Bersih (> 0) -> Kredit 3-1200 | Rugi Bersih (< 0) -> Debit 3-1200
                'debit'      => $netIncome < 0 ? abs($netIncome) : 0,
                'credit'     => $netIncome > 0 ? $netIncome : 0,
                'memo'       => ($netIncome >= 0 ? 'Pemindahan Laba Bersih' : 'Pemindahan Rugi Bersih') . " Tahun {$year} ke Laba Ditahan",
            ];
        }

        // 5. Simpan Jurnal Penutup ke Database
        DB::beginTransaction();
        try {
            $journal = Journal::create([
                'journal_number'   => $journalNumber,
                'journal_date'     => $closingDate,
                'reference_number' => $referenceNumber,
                'description'      => "[Tutup Buku] Jurnal Penutup Akhir Tahun {$year}",
                'status'           => 'posted',
                'created_by'       => Auth::id(),
            ]);

            foreach ($closingLines as $line) {
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $line['account_id'],
                    'debit'      => $line['debit'],
                    'credit'     => $line['credit'],
                    'memo'       => $line['memo'],
                ]);
            }

            DB::commit();

            $label = $netIncome >= 0 ? 'Laba Bersih' : 'Rugi Bersih';
            return back()->with(
                'success',
                "Tutup Buku Tahun {$year} berhasil diposting ({$journalNumber})! {$label} sebesar Rp " . number_format(abs($netIncome), 0, ',', '.') . " telah dipindahkan ke akun 3-1200 (Laba Ditahan)."
            );
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses tutup buku: ' . $e->getMessage());
        }
    }
}
