<?php

namespace App\Console\Commands;

use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\RecurringExpense;
use App\Traits\ChecksClosedPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessRecurringExpensesCommand extends Command
{
    use ChecksClosedPeriod;

    protected $signature = 'expenses:process-recurring';
    protected $description = 'Memproses beban berulang otomatis yang sudah jatuh tempo ke Jurnal Umum';

    public function handle(): int
    {
        $today = now()->toDateString();

        $dueExpenses = RecurringExpense::where('is_active', true)
            ->whereDate('next_run_date', '<=', $today)
            ->get();

        if ($dueExpenses->isEmpty()) {
            $this->info('Tidak ada beban berulang yang jatuh tempo hari ini.');
            return self::SUCCESS;
        }

        foreach ($dueExpenses as $expense) {
            // Lewati eksekusi jika tanggal jatuh tempo berada di tahun yang sudah Tutup Buku
            if ($this->isPeriodClosed($expense->next_run_date)) {
                $this->warn("Dilewati: {$expense->name} jatuh tempo pada tahun yang sudah ditutup buku ({$expense->next_run_date}).");
                continue;
            }

            DB::beginTransaction();
            try {
                $ymCode = date('Ym', strtotime($expense->next_run_date));
                $lastRec = Journal::where('journal_number', 'like', "JRN-REC-{$ymCode}-%")
                    ->lockForUpdate()
                    ->orderByDesc('journal_number')
                    ->first();

                $lastSeq = 0;
                if ($lastRec) {
                    $parts = explode('-', $lastRec->journal_number);
                    $lastSeq = (int) end($parts);
                }

                $journalNumber = 'JRN-REC-' . $ymCode . '-' . sprintf('%04d', $lastSeq + 1);
                $amount = round((float) $expense->amount, 2);

                $journal = Journal::create([
                    'journal_number'   => $journalNumber,
                    'journal_date'     => $expense->next_run_date,
                    'reference_number' => 'RECURRING-' . $expense->id,
                    'description'      => "[Beban Berulang] {$expense->name}",
                    'status'           => 'posted',
                    'created_by'       => $expense->created_by ?? 1,
                ]);

                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $expense->expense_account_id,
                    'debit'      => $amount,
                    'credit'     => 0,
                    'memo'       => "Beban Berulang: {$expense->name}",
                ]);

                JournalDetail::create([
                    'journal_id' => $journal->id,
                    'account_id' => $expense->payment_account_id,
                    'debit'      => 0,
                    'credit'     => $amount,
                    'memo'       => "Pembayaran Otomatis: {$expense->name}",
                ]);

                // Hitung jadwal berikutnya
                $nextDate = match ($expense->frequency) {
                    'daily'   => date('Y-m-d', strtotime($expense->next_run_date . ' +1 day')),
                    'weekly'  => date('Y-m-d', strtotime($expense->next_run_date . ' +1 week')),
                    'yearly'  => date('Y-m-d', strtotime($expense->next_run_date . ' +1 year')),
                    default   => date('Y-m-d', strtotime($expense->next_run_date . ' +1 month')),
                };

                $expense->update([
                    'last_run_date' => $expense->next_run_date,
                    'next_run_date' => $nextDate,
                ]);

                DB::commit();
                $this->info("Berhasil memposting {$journalNumber} untuk {$expense->name}.");
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("Gagal memproses beban berulang ID {$expense->id}: " . $e->getMessage());
                $this->error("Gagal memproses {$expense->name}: " . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
