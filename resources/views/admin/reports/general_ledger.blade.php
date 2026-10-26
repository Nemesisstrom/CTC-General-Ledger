@extends('layouts.app')

@section('title', 'Laporan Buku Besar (General Ledger)')

@section('styles')
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .report-page { max-width: none !important; margin: 0 !important; padding: 0 !important; }
            .shadow-sm { box-shadow: none !important; }
        }
    </style>
@endsection

@section('content')
    <div class="report-page mx-auto max-w-6xl space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Toolbar Filter (No Print) -->
        <div class="no-print bg-white p-4 rounded-xl shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
            <form action="{{ route('admin.reports.general-ledger') }}" method="GET" class="flex items-center gap-3 flex-wrap">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Pilih Akun COA</label>
                    <select name="account_id" required class="px-3 py-1.5 border rounded-lg text-sm outline-none">
                        <option value="">-- Pilih Akun --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ ($reportData['account_id'] ?? null) == $acc->id ? 'selected' : '' }}>
                                {{ $acc->code }} - {{ $acc->name }} ({{ strtoupper($acc->normal_balance) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $reportData['start_date'] ?? date('Y-m-01') }}" class="px-3 py-1.5 border rounded-lg text-sm outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $reportData['end_date'] ?? date('Y-m-t') }}" class="px-3 py-1.5 border rounded-lg text-sm outline-none">
                </div>

                <div class="self-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-1.5 rounded-lg transition">Tampilkan</button>
                </div>
            </form>

            <div class="flex items-center gap-2 self-end">
                @if(!empty($reportData['selected_account']))
                    <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white text-sm px-4 py-1.5 rounded-lg transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/></svg>
                        Cetak PDF
                    </button>
                @endif
                <a href="{{ route('dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm px-4 py-1.5 rounded-lg transition">
                    Kembali
                </a>
            </div>
        </div>

        <!-- Document Sheet -->
        @if(!empty($reportData['selected_account']))
            <div class="bg-white p-8 rounded-xl shadow-sm space-y-6">

                <!-- Header Dokumen -->
                <div class="text-center border-b pb-4">
                    <h1 class="text-2xl font-bold text-gray-900 uppercase">Laporan Buku Besar</h1>
                    <p class="text-md font-semibold text-blue-600 mt-1">
                        {{ $reportData['selected_account']->code }} - {{ $reportData['selected_account']->name }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        Periode: {{ \Carbon\Carbon::parse($reportData['start_date'])->translatedFormat('d F Y') }}
                        s/d {{ \Carbon\Carbon::parse($reportData['end_date'])->translatedFormat('d F Y') }}
                    </p>
                </div>

                <!-- Table Data Buku Besar -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b-2 border-gray-800 text-xs font-semibold text-gray-700 uppercase bg-gray-50">
                                <th class="py-3 px-3">Tanggal</th>
                                <th class="py-3 px-3">No. Jurnal</th>
                                <th class="py-3 px-3">Keterangan / Memo</th>
                                <th class="py-3 px-3 text-right">Debit</th>
                                <th class="py-3 px-3 text-right">Kredit</th>
                                <th class="py-3 px-3 text-right">Saldo Running</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <!-- Row Saldo Awal -->
                            <tr class="bg-gray-50 font-semibold">
                                <td class="py-2.5 px-3">{{ date('d/m/Y', strtotime($reportData['start_date'])) }}</td>
                                <td class="py-2.5 px-3">-</td>
                                <td class="py-2.5 px-3">SALDO AWAL PERIODE</td>
                                <td class="py-2.5 px-3 text-right">-</td>
                                <td class="py-2.5 px-3 text-right">-</td>
                                <td class="py-2.5 px-3 text-right">Rp {{ number_format($reportData['opening_balance'] ?? 0, 0, ',', '.') }}</td>
                            </tr>

                            <!-- Detail Transaksi (DIPERBAIKI: Menggunakan $reportData['entries']) -->
                            @forelse($reportData['entries'] as $entry)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-2.5 px-3">{{ $entry->journal->journal_date ? date('d/m/Y', strtotime($entry->journal->journal_date)) : '-' }}</td>
                                    <td class="py-2.5 px-3 font-mono text-xs">{{ $entry->journal->journal_number ?? '-' }}</td>
                                    <td class="py-2.5 px-3 text-gray-600">{{ $entry->memo ?? $entry->journal->description ?? '-' }}</td>
                                    <td class="py-2.5 px-3 text-right">
                                        {{ $entry->debit > 0 ? 'Rp ' . number_format($entry->debit, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right">
                                        {{ $entry->credit > 0 ? 'Rp ' . number_format($entry->credit, 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-medium">
                                        Rp {{ number_format($entry->running_balance ?? 0, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center text-gray-400 italic">Tidak ada transaksi jurnal pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-800 font-bold bg-gray-50 text-gray-900">
                                <td colspan="3" class="py-3 px-3 text-right uppercase">Total Mutasi & Saldo Akhir:</td>
                                <td class="py-3 px-3 text-right text-green-700">
                                    Rp {{ number_format($reportData['entries']->sum('debit') ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-3 text-right text-red-700">
                                    Rp {{ number_format($reportData['entries']->sum('credit') ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="py-3 px-3 text-right text-blue-700 bg-blue-50">
                                    Rp {{ number_format($reportData['ending_balance'] ?? 0, 0, ',', '.') }}
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
                        <p class="text-xs text-gray-500">Staff Keuangan / Akuntan</p>
                    </div>
                    <div>
                        <p class="mb-12">Disetujui Oleh,</p>
                        <p class="font-bold underline">( .................................... )</p>
                        <p class="text-xs text-gray-500">Manager Keuangan</p>
                    </div>
                </div>

            </div>
        @else
            <div class="bg-white p-12 rounded-xl shadow-sm text-center text-gray-500">
                <p class="text-lg">Silakan pilih <strong>Akun COA</strong> terlebih dahulu untuk melihat Laporan Buku Besar.</p>
            </div>
        @endif

    </div>
@endsection
