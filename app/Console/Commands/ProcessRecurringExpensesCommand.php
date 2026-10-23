<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\RecurringExpense;
use Illuminate\Support\Facades\DB;
use Exception;

class ProcessRecurringExpensesCommand extends Command
{
    protected $signature = 'app:process-recurring-expenses';
    protected $description = 'Memposting beban rutin bulanan (listrik, internet, air) ke jurnal umum secara otomatis';

    public function handle()
    {
        $expenses = RecurringExpense::where('is_active', true)->get();

        if ($expenses->isEmpty()) {
            $this->info('Tidak ada beban rutin aktif untuk diproses.');
            return 0;
        }

        $count = 0;
        foreach ($expenses as $expense) {
            DB::beginTransaction();
            try {
                $nextId = (Journal::max('id') ?? 0) + 1;
                $journalNumber = 'JRN-AUTO-' . date('Ym') . '-' . sprintf('%04d', $nextId);

                $journal = Journal::create([
                    'journal_number'   => $journalNumber,
                    'journal_date'     => now()->toDateString(),
                    'reference_number' => 'AUTO-' . sprintf('%03d', $expense->id),
                    'description'      => '[Beban Rutin Otomatis] ' . $expense->description,
                    'status'           => 'posted',
                    'created_by'       => 1, // ID System / Admin
                ]);

                // Debit: Rekening Beban
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $expense->expense_account_id,
                    'debit'      => $expense->amount,
                    'credit'     => 0,
                    'memo'       => 'Beban Rutin Otomatis Periode ' . date('F Y'),
                ]);

                // Kredit: Rekening Kas / Utang
                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $expense->cash_account_id,
                    'debit'      => 0,
                    'credit'     => $expense->amount,
                    'memo'       => 'Pembayaran Beban Rutin ' . date('F Y'),
                ]);

                DB::commit();
                $count++;
            } catch (Exception $e) {
                DB::rollBack();
                $this->error('Gagal memproses beban ID ' . $expense->id . ': ' . $e->getMessage());
            }
        }

        $this->info("Berhasil memposting {$count} beban rutin ke jurnal umum.");
        return 0;
    }
}
