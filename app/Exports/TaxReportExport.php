<?php

namespace App\Exports;

use App\Models\JournalDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TaxReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $startDate;
    protected $endDate;
    protected $rowNumber = 0;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * mengambil koleksi detail jurnal yang memiliki rincian pajak
     */
    public function collection()
    {
        $query = JournalDetail::with(['journal', 'account', 'tax'])
            ->whereNotNull('tax_id');

        if ($this->startDate && $this->endDate) {
            $query->whereHas('journal', function ($q) {
                $q->whereBetween('journal_date', [$this->startDate, $this->endDate]);
            });
        }

        return $query->get();
    }

    /**
     * Judul Sheet Excel
     */
    public function title(): string
    {
        return 'Laporan Pajak';
    }

    /**
     * Header Kolom Excel
     */
    public function headings(): array
    {
        return [
            'No.',
            'Tanggal Transaksi',
            'No. Jurnal',
            'Jenis Pajak',
            'Kode Akun',
            'Nama Akun',
            'Keterangan / Memo',
            'Nilai Pajak (Rp)',
        ];
    }

    /**
     * Mapping data per baris
     */
    public function map($detail): array
    {
        $this->rowNumber++;

        // Menentukan nilai pajak berdasarkan nominal Debit atau Kredit
        $taxAmount = $detail->debit > 0 ? $detail->debit : $detail->credit;

        return [
            $this->rowNumber,
            $detail->journal->journal_date ?? '-',
            $detail->journal->journal_number ?? '-',
            $detail->tax->name ?? 'Pajak',
            $detail->account->code ?? '-',
            $detail->account->name ?? '-',
            $detail->memo ?? $detail->journal->description ?? '-',
            $taxAmount,
        ];
    }

    /**
     * Styling tampilan Excel (Bold Header)
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Baris 1 (Heading) dibuat Bold
            1 => ['font' => ['bold' => true]],
        ];
    }
}