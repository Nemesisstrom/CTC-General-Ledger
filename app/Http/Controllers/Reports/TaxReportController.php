<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\JournalDetail;
use Illuminate\Http\Request;

class TaxReportController extends Controller
{
    public function index()
    {
        // Rekapitulasi Pembukuan Pajak dari Jurnal
        $taxReport = JournalDetail::whereNotNull('tax_id')
            ->with('tax')
            ->get();

        return response()->json(['tax_recap' => $taxReport]);
    }
}
