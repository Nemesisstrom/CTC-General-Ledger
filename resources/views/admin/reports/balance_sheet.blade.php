<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Neraca Keuangan</title>
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

        <!-- Toolbar Filter & Action (No Print) -->
        <div class="no-print bg-white p-4 rounded-xl shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
            <form action="{{ route('admin.reports.balance-sheet') }}" method="GET" class="flex items-center gap-3">
                <label class="text-sm font-medium text-gray-700">Per Tanggal:</label>
                <input type="date" name="as_of_date" value="{{ $reportData['as_of_date'] }}" class="px-3 py-1.5 border rounded-lg text-sm outline-none">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-1.5 rounded-lg transition">Tampilkan</button>
            </form>

            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white text-sm px-4 py-1.5 rounded-lg transition">
                    🖨️ Cetak / Save PDF
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
                <h1 class="text-2xl font-bold text-gray-900 uppercase">Laporan Posisi Keuangan (Neraca)</h1>
                <p class="text-sm text-gray-500">Per Tanggal {{ \Carbon\Carbon::parse($reportData['as_of_date'])->translatedFormat('d F Y') }}</p>
            </div>

            <!-- Warning Status Balance -->
            @if(!$reportData['is_balanced'])
                <div class="no-print bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm text-center">
                    ⚠️ <strong>Perhatian:</strong> Neraca tidak seimbang! Total Aset tidak sama dengan Total Kewajiban + Ekuitas.
                </div>
            @endif

            <!-- Body Grid: Aset (Kiri) vs Kewajiban & Ekuitas (Kanan) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                <!-- BAGIAN ASET (AKTIVA) -->
                <div class="space-y-4">
                    <h2 class="text-lg font-bold text-gray-800 border-b-2 border-gray-800 pb-1">ASET (AKTIVA)</h2>

                    <table class="w-full text-sm">
                        <tbody>
                            @forelse($reportData['assets'] as $asset)
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 text-gray-600"><span class="font-mono text-xs text-gray-400 mr-2">{{ $asset->code }}</span>{{ $asset->name }}</td>
                                    <td class="py-2 text-right font-medium">Rp {{ number_format($asset->balance, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="py-3 text-center text-gray-400 italic">Tidak ada saldo aset.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="flex justify-between items-center font-bold text-gray-900 pt-4 border-t-2 border-gray-900 bg-gray-50 p-2 rounded">
                        <span>TOTAL ASET</span>
                        <span>Rp {{ number_format($reportData['total_assets'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- BAGIAN KEWAJIBAN & EKUITAS (PASIVA) -->
                <div class="space-y-6">

                    <!-- KEWAJIBAN -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-bold text-gray-800 border-b-2 border-gray-800 pb-1">KEWAJIBAN (LIABILITAS)</h2>
                        <table class="w-full text-sm">
                            <tbody>
                                @forelse($reportData['liabilities'] as $liab)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2 text-gray-600"><span class="font-mono text-xs text-gray-400 mr-2">{{ $liab->code }}</span>{{ $liab->name }}</td>
                                        <td class="py-2 text-right font-medium">Rp {{ number_format($liab->balance, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="py-2 text-center text-gray-400 italic">Tidak ada kewajiban.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="flex justify-between items-center font-semibold text-gray-800 pt-2 border-t text-sm">
                            <span>Total Kewajiban</span>
                            <span>Rp {{ number_format($reportData['total_liabilities'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- EKUITAS -->
                    <div class="space-y-4">
                        <h2 class="text-lg font-bold text-gray-800 border-b-2 border-gray-800 pb-1">EKUITAS (MODAL)</h2>
                        <table class="w-full text-sm">
                            <tbody>
                                @foreach($reportData['equities'] as $equity)
                                    <tr class="border-b border-gray-100">
                                        <td class="py-2 text-gray-600"><span class="font-mono text-xs text-gray-400 mr-2">{{ $equity->code }}</span>{{ $equity->name }}</td>
                                        <td class="py-2 text-right font-medium">Rp {{ number_format($equity->balance, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                                <tr class="border-b border-gray-100">
                                    <td class="py-2 text-gray-600">Laba / (Rugi) Tahun Berjalan</td>
                                    <td class="py-2 text-right font-medium">Rp {{ number_format($reportData['current_earnings'], 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="flex justify-between items-center font-semibold text-gray-800 pt-2 border-t text-sm">
                            <span>Total Ekuitas</span>
                            <span>Rp {{ number_format($reportData['total_equity'], 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- TOTAL PASIVA -->
                    <div class="flex justify-between items-center font-bold text-gray-900 pt-4 border-t-2 border-gray-900 bg-gray-50 p-2 rounded">
                        <span>TOTAL KEWAJIBAN & EKUITAS</span>
                        <span>Rp {{ number_format($reportData['total_liabilities_and_equity'], 0, ',', '.') }}</span>
                    </div>

                </div>

            </div>

            <!-- Footer Tanda Tangan (Print View) -->
            <div class="hidden print:flex justify-between pt-12 text-center text-sm">
                <div>
                    <p class="mb-12">Dibuat Oleh,</p>
                    <p class="font-bold underline">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">Akuntan / Staff Keuangan</p>
                </div>
                <div>
                    <p class="mb-12">Disetujui Oleh,</p>
                    <p class="font-bold underline">( .................................... )</p>
                    <p class="text-xs text-gray-500">Direktur / Manager</p>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
