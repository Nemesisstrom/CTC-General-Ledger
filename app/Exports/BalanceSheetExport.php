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

class BalanceSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $asOfDate;

    public function __construct($asOfDate = null)
    {
        $this->asOfDate = $asOfDate ?? date('Y-m-d');
    }

    public function collection(): Enumerable
    {
        $accounts = ChartOfAccount::whereIn('type', ['asset', 'liability', 'equity'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return $accounts->map(function ($account) {
            $debit = JournalDetail::where('account_id', $account->id)
                ->whereHas('journal', function ($q) {
                    $q->where('journal_date', '<=', $this->asOfDate)
                      ->where('status', 'posted');
                })->sum('debit');

            $credit = JournalDetail::where('account_id', $account->id)
                ->whereHas('journal', function ($q) {
                    $q->where('journal_date', '<=', $this->asOfDate)
                      ->where('status', 'posted');
                })->sum('credit');

            $balance = $account->normal_balance === 'debit'
                ? ($debit - $credit)
                : ($credit - $debit);

            return (object) [
                'code'           => $account->code,
                'name'           => $account->name,
                'type'           => strtoupper($account->type),
                'normal_balance' => strtoupper($account->normal_balance),
                'balance'        => $balance,
            ];
        });
    }

    public function headings(): array
    {
        return ['Kode Akun', 'Nama Akun', 'Kategori', 'Saldo Normal', 'Saldo per Tanggal (Rp)'];
    }

    public function map($row): array
    {
        return [
            $row->code,
            $row->name,
            $row->type,
            $row->normal_balance,
            $row->balance,
        ];
    }

    public function title(): string
    {
        return 'Laporan Neraca';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
