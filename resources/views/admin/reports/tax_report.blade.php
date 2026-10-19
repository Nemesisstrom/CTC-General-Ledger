<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekapitulasi Pajak</title>
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

        <!-- Toolbar Filter & Ekspor (No Print) -->
        <div class="no-print bg-white p-4 rounded-xl shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
            <form action="{{ route('admin.reports.tax') }}" method="GET" class="flex items-center gap-3 flex-wrap">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Filter Skema Pajak</label>
                    <select name="tax_id" class="px-3 py-1.5 border rounded-lg text-sm outline-none bg-white">
                        <option value="">-- Semua Skema Pajak --</option>
                        @foreach($taxes as $t)
                            <option value="{{ $t->id }}" {{ ($reportData['tax_id'] ?? '') == $t->id ? 'selected' : '' }}>
                                {{ $t->code }} - {{ $t->name }} ({{ $t->rate }}%)
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $reportData['start_date'] ?? '' }}" class="px-3 py-1.5 border rounded-lg text-sm outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $reportData['end_date'] ?? '' }}" class="px-3 py-1.5 border rounded-lg text-sm outline-none">
                </div>

                <div class="self-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-1.5 rounded-lg transition font-medium">
                        Tampilkan
                    </button>
                </div>
            </form>

            <div class="flex items-center gap-2 self-end flex-wrap">
                <!-- Export PDF -->
                <a href="{{ route('admin.reports.tax.pdf', request()->query()) }}" class="bg-red-600 hover:bg-red-700 text-white text-sm px-3 py-1.5 rounded-lg transition font-medium flex items-center gap-1">
                    📄 Export PDF
                </a>

                <!-- Export Excel -->
                <a href="{{ route('admin.reports.tax.excel', request()->query()) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-3 py-1.5 rounded-lg transition font-medium flex items-center gap-1">
                    📊 Export Excel
                </a>

                <!-- Print Browser -->
                <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white text-sm px-3 py-1.5 rounded-lg transition font-medium flex items-center gap-1">
                    🖨️ Cetak
                </button>

                <!-- Kembali ke Dashboard -->
                <a href="{{ route('dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm px-3 py-1.5 rounded-lg transition font-medium">
                    Kembali
                </a>
            </div>
        </div>

        <!-- Document Sheet -->
        <div class="bg-white p-8 rounded-xl shadow-sm space-y-6">

            <!-- Header Dokumen -->
            <div class="text-center border-b pb-4">
                <h1 class="text-2xl font-bold text-gray-900 uppercase">Laporan Rekapitulasi Pajak (PPN & PPh)</h1>
                <p class="text-xs text-gray-500 mt-1">
                    Periode: {{ isset($reportData['start_date']) && $reportData['start_date'] ? \Carbon\Carbon::parse($reportData['start_date'])->translatedFormat('d F Y') : 'Semua Periode' }}
                    {{ isset($reportData['end_date']) && $reportData['end_date'] ? 's/d ' . \Carbon\Carbon::parse($reportData['end_date'])->translatedFormat('d F Y') : '' }}
                </p>
            </div>

            <!-- Table Data Rekapitulasi Pajak -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-800 text-xs font-semibold text-gray-700 uppercase bg-gray-50">
                            <th class="py-3 px-3">Tanggal</th>
                            <th class="py-3 px-3">No. Jurnal</th>
                            <th class="py-3 px-3">Skema Pajak</th>
                            <th class="py-3 px-3">Akun COA</th>
                            <th class="py-3 px-3">Keterangan / Memo</th>
                            <th class="py-3 px-3 text-right">Nilai Pajak (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($reportData['tax_entries'] ?? [] as $entry)
                            @php
                                $taxValue = $entry->credit > 0 ? $entry->credit : $entry->debit;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="py-2.5 px-3">{{ $entry->journal->journal_date ?? '-' }}</td>
                                <td class="py-2.5 px-3 font-mono text-xs font-semibold">{{ $entry->journal->journal_number ?? '-' }}</td>
                                <td class="py-2.5 px-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-100 text-purple-800">
                                        {{ $entry->tax->code ?? 'PAJAK' }} ({{ $entry->tax->rate ?? 0 }}%)
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-xs text-gray-600">
                                    <strong>{{ $entry->account->code ?? '-' }}</strong> - {{ $entry->account->name ?? '-' }}
                                </td>
                                <td class="py-2.5 px-3 text-gray-600">{{ $entry->memo ?? $entry->journal->description ?? '-' }}</td>
                                <td class="py-2.5 px-3 text-right font-semibold text-purple-700">
                                    Rp {{ number_format($taxValue, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-gray-400 italic">Tidak ada rekapitulasi transaksi pajak pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-800 font-bold bg-gray-50 text-gray-900">
                            <td colspan="5" class="py-3 px-3 text-right uppercase">Total Akumulasi Pajak Terpotong / Terpungut:</td>
                            <td class="py-3 px-3 text-right text-purple-800 bg-purple-50">
                                Rp {{ number_format($reportData['total_tax_amount'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Footer Tanda Tangan (Print View) -->
            <div class="hidden print:flex justify-between pt-12 text-center text-sm">
                <div>
                    <p class="mb-12">Dibuat Oleh,</p>
                    <p class="font-bold underline">{{ auth()->user()->name ?? 'Staff Akuntansi' }}</p>
                    <p class="text-xs text-gray-500">Staff Perpajakan / Akuntan</p>
                </div>
                <div>
                    <p class="mb-12">Disetujui Oleh,</p>
                    <p class="font-bold underline">( .................................... )</p>
                    <p class="text-xs text-gray-500">Manager Keuangan & Pajak</p>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
