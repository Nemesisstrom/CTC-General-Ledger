<?php

namespace App\Exports;

use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitLossExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate = null, $endDate = null)
    {
        $this->startDate = $startDate ?? date('Y-m-01');
        $this->endDate   = $endDate ?? date('Y-m-t');
    }

    /**
     * Mengembalikan data akun pendapatan dan beban
     */
    public function collection(): Enumerable
    {
        $accounts = ChartOfAccount::whereIn('type', ['revenue', 'expense'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return $accounts->map(function ($account) {
            $debit = JournalDetail::where('account_id', $account->id)
                ->whereHas('journal', fn($q) => $q->whereBetween('journal_date', [$this->startDate, $this->endDate])->where('status', 'posted'))
                ->sum('debit');

            $credit = JournalDetail::where('account_id', $account->id)
                ->whereHas('journal', fn($q) => $q->whereBetween('journal_date', [$this->startDate, $this->endDate])->where('status', 'posted'))
                ->sum('credit');

            $balance = $account->type === 'revenue' ? ($credit - $debit) : ($debit - $credit);

            return (object) [
                'code'    => $account->code,
                'name'    => $account->name,
                'type'    => strtoupper($account->type),
                'balance' => $balance,
            ];
        });
    }

    /**
     * Judul Sheet Excel
     */
    public function title(): string
    {
        return 'Laba Rugi';
    }

    /**
     * Header Kolom Excel
     */
    public function headings(): array
    {
        return ['Kode Akun', 'Nama Akun', 'Kategori', 'Total Saldo (Rp)'];
    }

    /**
     * Mapping data per baris
     */
    public function map($row): array
    {
        return [
            $row->code,
            $row->name,
            $row->type,
            $row->balance,
        ];
    }

    /**
     * Styling Tampilan (Bold Header)
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
