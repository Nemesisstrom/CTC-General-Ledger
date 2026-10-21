<?php

namespace App\Exports;

use App\Models\JournalDetail;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GeneralLedgerExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $accountId;
    protected $startDate;
    protected $endDate;

    public function __construct($accountId = null, $startDate = null, $endDate = null)
    {
        $this->accountId = $accountId;
        $this->startDate = $startDate ?? date('Y-m-01');
        $this->endDate   = $endDate ?? date('Y-m-t');
    }

    public function collection(): Enumerable
    {
        $query = JournalDetail::with('journal');

        if ($this->accountId) {
            $query->where('account_id', $this->accountId);
        }

        return $query->whereHas('journal', function ($q) {
                $q->whereBetween('journal_date', [$this->startDate, $this->endDate])
                  ->where('status', 'posted');
            })
            ->get()
            ->sortBy(fn($entry) => $entry->journal->journal_date ?? $entry->created_at)
            ->values();
    }

    public function headings(): array
    {
        return ['Tanggal Jurnal', 'No. Jurnal', 'Keterangan / Memo', 'Debit (Rp)', 'Kredit (Rp)'];
    }

    public function map($row): array
    {
        return [
            $row->journal->journal_date ?? '-',
            $row->journal->journal_number ?? '-',
            $row->memo ?? $row->journal->description ?? '-',
            $row->debit,
            $row->credit,
        ];
    }

    public function title(): string
    {
        return 'Buku Besar';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
