<?php

namespace App\Exports;

use App\Models\JournalDetail;
use App\Models\ChartOfAccount;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class GeneralLedgerExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $accountId;
    protected $startDate;
    protected $endDate;

    public function __construct($accountId, $startDate, $endDate)
    {
        $this->accountId = $accountId;
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function collection()
    {
        return JournalDetail::with('journal')
            ->where('account_id', $this->accountId)
            ->whereHas('journal', function ($q) {
                $q->whereBetween('journal_date', [$this->startDate, $this->endDate])
                  ->where('status', 'posted');
            })
            ->get()
            ->sortBy(fn($entry) => $entry->journal->journal_date);
    }

    public function headings(): array
    {
        return ['Tanggal Jurnal', 'No. Jurnal', 'Keterangan / Memo', 'Debit (Rp)', 'Kredit (Rp)'];
    }

    public function map($row): array
    {
        return [
            $row->journal->journal_date,
            $row->journal->journal_number,
            $row->memo ?? $row->journal->description,
            $row->debit,
            $row->credit,
        ];
    }

    public function title(): string
    {
        return 'Buku Besar';
    }
}