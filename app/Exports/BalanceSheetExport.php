<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class BalanceSheetExport implements FromCollection
{
    public function collection(): Collection
    {
        //
    }
}
