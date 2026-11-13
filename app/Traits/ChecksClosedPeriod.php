<?php

namespace App\Traits;

use App\Models\Journal;

trait ChecksClosedPeriod
{
    /**
     * Cek apakah tahun dari tanggal transaksi sudah di-Tutup Buku (CLS-YYYY-1231).
     */
    protected function isPeriodClosed(string $date): bool
    {
        $year = date('Y', strtotime($date));

        return Journal::where('reference_number', "CLS-{$year}-1231")
            ->orWhere('journal_number', "JRN-CLS-{$year}-1231")
            ->exists();
    }

    /**
     * Pesan standar penolakan jika periode sudah ditutup.
     */
    protected function closedPeriodMessage(string $date): string
    {
        $year = date('Y', strtotime($date));

        return "Periode akuntansi tahun {$year} sudah dikunci (Tutup Buku) dan tidak dapat ditambah, diubah, atau dibatalkan.";
    }
}
