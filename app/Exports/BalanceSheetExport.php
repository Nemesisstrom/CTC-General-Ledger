<?php

namespace App\Exports;

use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class BalanceSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected $asOfDate;

    public function __construct($asOfDate)
    {
        $this->asOfDate = $asOfDate;
    }

    public function collection()
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

            return [
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
            $row['code'],
            $row['name'],
            $row['type'],
            $row['normal_balance'],
            $row['balance'],
        ];
    }

    public function title(): string
    {
        return 'Laporan Neraca';
    }
}