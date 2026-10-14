<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #2d3748;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
            color: #1a202c;
        }
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 13px;
            font-weight: normal;
            color: #4a5568;
        }
        .header p {
            margin: 3px 0 0 0;
            font-size: 11px;
            color: #718096;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 7px 10px;
            text-align: left;
        }
        .table-border th, .table-border td {
            border: 1px solid #cbd5e0;
        }
        .section-header {
            background-color: #edf2f7;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            color: #2d3748;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .subtotal-row {
            font-weight: bold;
            background-color: #f7fafc;
            border-top: 1px solid #cbd5e0;
            border-bottom: 1px solid #cbd5e0;
        }
        .grand-total-row {
            font-weight: bold;
            font-size: 12px;
            background-color: #ebf8ff;
            border-top: 2px solid #2b6cb0;
            border-bottom: 2px solid #2b6cb0;
            color: #2b6cb0;
        }
        .footer-sign {
            margin-top: 40px;
            width: 100%;
            border: none;
        }
        .footer-sign td {
            border: none;
            text-align: center;
            vertical-align: bottom;
            height: 80px;
        }
    </style>
</head>
<body>

    <!-- Header Laporan -->
    <div class="header">
        <h1>Sistem Informasi Keuangan</h1>
        <h2>Laporan Laba Rugi (Profit & Loss Statement)</h2>
        <p>Periode: {{ \Carbon\Carbon::parse($startDate)->translatedFormat('d F Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->translatedFormat('d F Y') }}</p>
    </div>

    @php
        $totalRevenue = 0;
        $totalExpense = 0;
    @endphp

    <table class="table-border">
        <thead>
            <tr style="background-color: #e2e8f0;">
                <th style="width: 15%;">Kode Akun</th>
                <th>Nama Akun</th>
                <th class="text-right" style="width: 25%;">Jumlah (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <!-- SECTION 1: PENDAPATAN (REVENUE) -->
            <tr class="section-header">
                <td colspan="3">PENDAPATAN / REVENUE</td>
            </tr>
            @forelse($revenues as $account)
                @php
                    $balance = $account->getPeriodBalance($startDate, $endDate);
                    $totalRevenue += $balance;
                @endphp
                <tr>
                    <td class="text-center">{{ $account->code }}</td>
                    <td>{{ $account->name }}</td>
                    <td class="text-right">{{ number_format($balance, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="font-style: italic; color: #a0aec0;">Tidak ada data pendapatan untuk periode ini.</td>
                </tr>
            @endforelse
            <tr class="subtotal-row">
                <td colspan="2" class="text-right">TOTAL PENDAPATAN:</td>
                <td class="text-right">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</td>
            </tr>

            <!-- SPACING -->
            <tr><td colspan="3" style="border: none; height: 10px;"></td></tr>

            <!-- SECTION 2: BEBAN / BIAYA (EXPENSES) -->
            <tr class="section-header">
                <td colspan="3">BEBAN OPERASIONAL / EXPENSES</td>
            </tr>
            @forelse($expenses as $account)
                @php
                    $balance = $account->getPeriodBalance($startDate, $endDate);
                    $totalExpense += $balance;
                @endphp
                <tr>
                    <td class="text-center">{{ $account->code }}</td>
                    <td>{{ $account->name }}</td>
                    <td class="text-right">{{ number_format($balance, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center" style="font-style: italic; color: #a0aec0;">Tidak ada data beban untuk periode ini.</td>
                </tr>
            @endforelse
            <tr class="subtotal-row">
                <td colspan="2" class="text-right">TOTAL BEBAN OPERASIONAL:</td>
                <td class="text-right">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td>
            </tr>

            <!-- SPACING -->
            <tr><td colspan="3" style="border: none; height: 15px;"></td></tr>

            <!-- SECTION 3: LABA / RUGI BERSIH (NET PROFIT / LOSS) -->
            @php
                $netProfit = $totalRevenue - $totalExpense;
            @endphp
            <tr class="grand-total-row">
                <td colspan="2" class="text-right">
                    {{ $netProfit >= 0 ? 'LABA BERSIH (NET PROFIT):' : 'RUGI BERSIH (NET LOSS):' }}
                </td>
                <td class="text-right">
                    Rp {{ number_format($netProfit, 0, ',', '.') }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Tanda Tangan Dokumen -->
    <table class="footer-sign">
        <tr>
            <td style="width: 50%;">
                <p>Dibuat Oleh,</p>
                <br><br><br>
                <p><strong>({{ auth()->user()->name ?? 'Staff Akuntansi' }})</strong></p>
                <p style="font-size: 10px; color: #718096;">Staff Akuntansi & Keuangan</p>
            </td>
            <td style="width: 50%;">
                <p>Disetujui Oleh,</p>
                <br><br><br>
                <p><strong>( .................................... )</strong></p>
                <p style="font-size: 10px; color: #718096;">Manager Keuangan / Direktur</p>
            </td>
        </tr>
    </table>

</body>
</html>