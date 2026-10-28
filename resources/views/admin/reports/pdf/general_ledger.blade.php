<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Buku Besar</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 15px; color: #222; }
        .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #176B45; padding-bottom: 8px; }
        .header h2 { margin: 0; text-transform: uppercase; color: #176B45; font-size: 16px; }
        .header p { margin: 3px 0; color: #555; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; }
        th { background-color: #E8F4EC; text-transform: uppercase; font-size: 9px; color: #104B35; font-weight: bold; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .bg-gray { background-color: #f9f9f9; }
        .footer-sign { margin-top: 30px; width: 100%; }
        .footer-sign td { border: none; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Buku Besar</h2>
        <p><strong>Akun:</strong> {{ $selected_account->code }} - {{ $selected_account->name }} ({{ strtoupper($selected_account->normal_balance) }})</p>
        <p><strong>Periode:</strong> {{ \Carbon\Carbon::parse($start_date)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($end_date)->translatedFormat('d F Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Tanggal</th>
                <th style="width: 18%;">No. Jurnal</th>
                <th style="width: 32%;">Keterangan / Memo</th>
                <th class="text-right" style="width: 12.5%;">Debit</th>
                <th class="text-right" style="width: 12.5%;">Kredit</th>
                <th class="text-right" style="width: 13%;">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <!-- Row Saldo Awal -->
            <tr class="bg-gray" style="font-weight: bold;">
                <td>{{ date('d/m/Y', strtotime($start_date)) }}</td>
                <td>-</td>
                <td>SALDO AWAL PERIODE</td>
                <td class="text-right font-mono">-</td>
                <td class="text-right font-mono">-</td>
                <td class="text-right font-mono">Rp {{ number_format($opening_balance ?? 0, 0, ',', '.') }}</td>
            </tr>

            <!-- Detail Transaksi -->
            @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry->journal->journal_date ? date('d/m/Y', strtotime($entry->journal->journal_date)) : '-' }}</td>
                    <td class="font-mono">{{ $entry->journal->journal_number ?? '-' }}</td>
                    <td>{{ $entry->memo ?? $entry->journal->description ?? '-' }}</td>
                    <td class="text-right font-mono">{{ $entry->debit > 0 ? 'Rp ' . number_format($entry->debit, 0, ',', '.') : '-' }}</td>
                    <td class="text-right font-mono">{{ $entry->credit > 0 ? 'Rp ' . number_format($entry->credit, 0, ',', '.') : '-' }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($entry->running_balance ?? 0, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; font-style: italic; color: #888; padding: 10px;">Tidak ada transaksi pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #E8F4EC;">
                <td colspan="3" class="text-right">TOTAL MUTASI & SALDO AKHIR:</td>
                <td class="text-right font-mono" style="color: #176B45;">Rp {{ number_format($entries->sum('debit') ?? 0, 0, ',', '.') }}</td>
                <td class="text-right font-mono" style="color: #c53030;">Rp {{ number_format($entries->sum('credit') ?? 0, 0, ',', '.') }}</td>
                <td class="text-right font-mono" style="color: #2b6cb0;">Rp {{ number_format($ending_balance ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="footer-sign">
        <tr>
            <td style="width: 50%;">
                <p>Dibuat Oleh,</p>
                <br><br><br>
                <p><strong>({{ auth()->user()->name }})</strong></p>
                <p style="color: #666; font-size: 9px;">Staff Keuangan / Akuntan</p>
            </td>
            <td style="width: 50%;">
                <p>Disetujui Oleh,</p>
                <br><br><br>
                <p><strong>( Manager Keuangan )</strong></p>
                <p style="color: #666; font-size: 9px;">Pimpinan</p>
            </td>
        </tr>
    </table>
</body>
</html>