<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekapitulasi Pajak</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0; text-transform: uppercase; }
        .header p { margin: 3px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f2f2f2; text-transform: uppercase; font-size: 10px; }
        .text-right { text-align: right; }
        .footer-sign { margin-top: 40px; width: 100%; border: none; }
        .footer-sign td { border: none; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Rekapitulasi Pajak</h2>
        <p><strong>Skema Filter:</strong> {{ $selectedTax ? $selectedTax->name : 'Semua Skema Pajak' }}</p>
        <p><strong>Periode:</strong> {{ $startDate }} s/d {{ $endDate }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>No. Jurnal</th>
                <th>Skema Pajak</th>
                <th>Akun COA</th>
                <th>Keterangan</th>
                <th class="text-right">Nilai Pajak (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($taxEntries as $entry)
                @php $val = $entry->credit > 0 ? $entry->credit : $entry->debit; @endphp
                <tr>
                    <td>{{ $entry->journal->journal_date }}</td>
                    <td>{{ $entry->journal->journal_number }}</td>
                    <td>{{ $entry->tax->code ?? 'PAJAK' }} ({{ $entry->tax->rate ?? 0 }}%)</td>
                    <td>{{ $entry->account->code }} - {{ $entry->account->name }}</td>
                    <td>{{ $entry->memo ?? $entry->journal->description }}</td>
                    <td class="text-right">Rp {{ number_format($val, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; font-style: italic;">Tidak ada data transaksi pajak pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f0f0f0;">
                <td colspan="5" class="text-right">TOTAL PAJAK TERPOTONG / TERPUNGUT:</td>
                <td class="text-right">Rp {{ number_format($totalTaxAmount, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="footer-sign">
        <tr>
            <td>
                <p>Dibuat Oleh,</p>
                <br><br><br>
                <p><strong>({{ auth()->user()->name }})</strong></p>
                <p>Staff Perpajakan</p>
            </td>
            <td>
                <p>Disetujui Oleh,</p>
                <br><br><br>
                <p><strong>( Manager Keuangan & Pajak )</strong></p>
            </td>
        </tr>
    </table>
</body>
</html>