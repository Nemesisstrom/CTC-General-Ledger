<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekapitulasi Penggajian Karyawan</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0; text-transform: uppercase; font-size: 16px; }
        .header p { margin: 3px 0; color: #555; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f2f2f2; text-transform: uppercase; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .footer-sign { margin-top: 40px; width: 100%; border: none; }
        .footer-sign td { border: none; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Laporan Rekapitulasi Penggajian Karyawan</h2>
        <p>Periode: {{ \Carbon\Carbon::createFromFormat('Y-m', $summary['period'])->translatedFormat('F Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 30px;">No</th>
                <th>Nama Karyawan</th>
                <th class="text-right">Gaji Pokok</th>
                <th class="text-right">Tunjangan</th>
                <th class="text-right">Potongan Lain</th>
                <th class="text-right">PPh 21</th>
                <th class="text-right">Gaji Bersih</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payrolls as $index => $payroll)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $payroll->user->name ?? '-' }}</strong></td>
                    <td class="text-right">Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($payroll->total_allowance, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</td>
                    <td class="text-right" style="color: #c00;">Rp {{ number_format($payroll->pph21_amount, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="font-style: italic;">Tidak ada data penggajian untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f9f9f9;">
                <td colspan="2" class="text-right">TOTAL ({{ $summary['total_employees'] }} Karyawan):</td>
                <td class="text-right">Rp {{ number_format($summary['total_basic_salary'], 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($summary['total_allowance'], 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($summary['total_deduction'], 0, ',', '.') }}</td>
                <td class="text-right" style="color: #c00;">Rp {{ number_format($summary['total_pph21'], 0, ',', '.') }}</td>
                <td class="text-right" style="background-color: #e6f0fa;">Rp {{ number_format($summary['total_net_salary'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="footer-sign">
        <tr>
            <td>
                <p>Dibuat Oleh,</p>
                <br><br><br>
                <p><strong>({{ auth()->user()->name }})</strong></p>
                <p>Staff Payroll / HRD</p>
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