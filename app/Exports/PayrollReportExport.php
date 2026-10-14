<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class PayrollReportExport implements FromCollection
{
    public function collection(): Collection
    {
        //
    }
}
