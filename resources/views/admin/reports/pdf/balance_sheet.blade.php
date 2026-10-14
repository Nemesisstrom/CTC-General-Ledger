<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Neraca</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0; text-transform: uppercase; }
        .header p { margin: 3px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f2f2f2; text-transform: uppercase; font-size: 10px; }
        .text-right { text-align: right; }
        .section-title { font-weight: bold; background-color: #eaeaea; }
        .footer-sign { margin-top: 40px; width: 100%; border: none; }
        .footer-sign td { border: none; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Neraca (Balance Sheet)</h2>
        <p>Per Tanggal: {{ \Carbon\Carbon::parse($asOfDate)->translatedFormat('d F Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Kode Akun</th>
                <th>Nama Akun</th>
                <th class="text-right">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <!-- AKTIVA / ASSET -->
            <tr class="section-title"><td colspan="3">ASET / AKTIVA</td></tr>
            @foreach($assets as $acc)
                <tr>
                    <td>{{ $acc->code }}</td>
                    <td>{{ $acc->name }}</td>
                    <td class="text-right">Rp {{ number_format($acc->getBalanceAsOf($asOfDate), 0, ',', '.') }}</td>
                </tr>
            @endforeach

            <!-- KEWAJIBAN / LIABILITIES -->
            <tr class="section-title"><td colspan="3">KEWAJIBAN / LIABILITAS</td></tr>
            @foreach($liabilities as $acc)
                <tr>
                    <td>{{ $acc->code }}</td>
                    <td>{{ $acc->name }}</td>
                    <td class="text-right">Rp {{ number_format($acc->getBalanceAsOf($asOfDate), 0, ',', '.') }}</td>
                </tr>
            @endforeach

            <!-- EKUITAS / EQUITY -->
            <tr class="section-title"><td colspan="3">EKUITAS / MODAL</td></tr>
            @foreach($equities as $acc)
                <tr>
                    <td>{{ $acc->code }}</td>
                    <td>{{ $acc->name }}</td>
                    <td class="text-right">Rp {{ number_format($acc->getBalanceAsOf($asOfDate), 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="footer-sign">
        <tr>
            <td>
                <p>Dibuat Oleh,</p>
                <br><br><br>
                <p><strong>({{ auth()->user()->name }})</strong></p>
                <p>Staff Akuntansi</p>
            </td>
            <td>
                <p>Disetujui Oleh,</p>
                <br><br><br>
                <p><strong>( Manager Keuangan )</strong></p>
            </td>
        </tr>
    </table>
</body>
</html>