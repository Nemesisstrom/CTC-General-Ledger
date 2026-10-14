<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Rekapitulasi Penggajian</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .shadow-sm { box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-6xl mx-auto space-y-6">

        <!-- Toolbar Filter (No Print) -->
        <div class="no-print bg-white p-4 rounded-xl shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
            <form action="{{ route('reports.payroll') }}" method="GET" class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700">Pilih Periode Gaji:</label>
                <input type="month" name="period" value="{{ $reportData['period'] }}" class="px-3 py-1.5 border rounded-lg text-sm outline-none">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-1.5 rounded-lg transition">Tampilkan</button>
            </form>

            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white text-sm px-4 py-1.5 rounded-lg transition">
                    🖨️ Cetak PDF
                </button>
                <a href="{{ route('dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm px-4 py-1.5 rounded-lg transition">
                    Kembali
                </a>
            </div>
        </div>

        <!-- Document Sheet -->
        <div class="bg-white p-8 rounded-xl shadow-sm space-y-6">

            <!-- Header Dokumen -->
            <div class="text-center border-b pb-4">
                <h1 class="text-2xl font-bold text-gray-900 uppercase">Laporan Rekapitulasi Penggajian Karyawan</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Periode: {{ \Carbon\Carbon::createFromFormat('Y-m', $reportData['period'])->translatedFormat('F Y') }}
                </p>
            </div>

            <!-- Table Data Rekapitulasi Gaji -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b-2 border-gray-800 font-semibold text-gray-700 uppercase bg-gray-50">
                            <th class="py-3 px-3">No</th>
                            <th class="py-3 px-3">Nama Karyawan</th>
                            <th class="py-3 px-3 text-right">Gaji Pokok</th>
                            <th class="py-3 px-3 text-right">Total Tunjangan</th>
                            <th class="py-3 px-3 text-right">Potongan Lain</th>
                            <th class="py-3 px-3 text-right text-red-600">PPh 21</th>
                            <th class="py-3 px-3 text-right font-bold">Gaji Bersih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($reportData['payrolls'] as $index => $payroll)
                            <tr class="hover:bg-gray-50">
                                <td class="py-2.5 px-3 font-semibold text-gray-500">{{ $index + 1 }}</td>
                                <td class="py-2.5 px-3 font-semibold text-gray-800">{{ $payroll->user->name }}</td>
                                <td class="py-2.5 px-3 text-right">Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right">Rp {{ number_format($payroll->total_allowance, 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right">Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right text-red-600">Rp {{ number_format($payroll->pph21_amount, 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-blue-700">
                                    Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-gray-400 italic">Tidak ada data penggajian untuk periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-800 font-bold bg-gray-50 text-gray-900">
                            <td colspan="2" class="py-3 px-3 uppercase">Total Pembayaran ({{ $reportData['total_employees'] }} Karyawan):</td>
                            <td class="py-3 px-3 text-right">Rp {{ number_format($reportData['total_basic_salary'], 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right">Rp {{ number_format($reportData['total_allowance'], 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right">Rp {{ number_format($reportData['total_deduction'], 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right text-red-700">Rp {{ number_format($reportData['total_pph21'], 0, ',', '.') }}</td>
                            <td class="py-3 px-3 text-right text-blue-800 bg-blue-50">
                                Rp {{ number_format($reportData['total_net_salary'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer Tanda Tangan (Print View) -->
            <div class="hidden print:flex justify-between pt-12 text-center text-sm">
                <div>
                    <p class="mb-12">Dibuat Oleh,</p>
                    <p class="font-bold underline">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">Staff Payroll / HRD</p>
                </div>
                <div>
                    <p class="mb-12">Disetujui Oleh,</p>
                    <p class="font-bold underline">( .................................... )</p>
                    <p class="text-xs text-gray-500">Manager Keuangan / Direktur</p>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
