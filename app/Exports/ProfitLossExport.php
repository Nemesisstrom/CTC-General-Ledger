<?php

namespace App\Exports;

use App\Models\ChartOfAccount;
use App\Models\JournalDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProfitLossExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
    }

    public function collection()
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

            return [
                'code'    => $account->code,
                'name'    => $account->name,
                'type'    => strtoupper($account->type),
                'balance' => $balance,
            ];
        });
    }

    public function headings(): array
    {
        return ['Kode Akun', 'Nama Akun', 'Kategori', 'Total Balance (Rp)'];
    }

    public function map($row): array
    {
        return [
            $row['code'],
            $row['name'],
            $row['type'],
            $row['balance'],
        ];
    }
}