@extends('layouts.app')

@section('title', 'Laporan Laba Rugi')

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
    <div class="report-page mx-auto max-w-4xl space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Toolbar Filter & Action (No Print) -->
        <div class="no-print bg-white p-4 rounded-xl shadow-sm flex flex-col sm:flex-row justify-between items-center gap-4">
            <form action="{{ route('admin.reports.profit-loss') }}" method="GET" class="flex items-center gap-2 flex-wrap">
                <span class="text-sm font-medium text-gray-700">Periode:</span>
                <input type="date" name="start_date" value="{{ $reportData['start_date'] }}" class="px-3 py-1.5 border rounded-lg text-sm outline-none">
                <span class="text-sm text-gray-500">s/d</span>
                <input type="date" name="end_date" value="{{ $reportData['end_date'] }}" class="px-3 py-1.5 border rounded-lg text-sm outline-none">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-1.5 rounded-lg transition">Filter</button>
            </form>

            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white text-sm px-4 py-1.5 rounded-lg transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6v-8z"/></svg>
                    Cetak PDF
                </button>
                <a href="{{ route('dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm px-4 py-1.5 rounded-lg transition">
                    Kembali
                </a>
            </div>
        </div>

        <!-- Document Paper -->
        <div class="bg-white p-8 rounded-xl shadow-sm space-y-6">

            <!-- Header Dokumen -->
            <div class="text-center border-b pb-4">
                <h1 class="text-2xl font-bold text-gray-900 uppercase">Laporan Laba Rugi</h1>
                <p class="text-sm text-gray-500">
                    Periode: {{ \Carbon\Carbon::parse($reportData['start_date'])->translatedFormat('d F Y') }}
                    s/d {{ \Carbon\Carbon::parse($reportData['end_date'])->translatedFormat('d F Y') }}
                </p>
            </div>

            <!-- BAGIAN PENDAPATAN -->
            <div class="space-y-3">
                <h2 class="text-md font-bold text-gray-800 uppercase border-b pb-1">1. Pendapatan (Revenue)</h2>
                <div class="max-w-full overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody>
                        @forelse($reportData['revenues'] as $rev)
                            <tr class="border-b border-gray-100">
                                <td class="py-2 text-gray-600"><span class="font-mono text-xs text-gray-400 mr-2">{{ $rev->code }}</span>{{ $rev->name }}</td>
                                <td class="py-2 text-right font-medium">Rp {{ number_format($rev->period_balance, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-2 text-center text-gray-400 italic">Tidak ada pendapatan pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                <div class="flex justify-between items-center font-bold text-gray-900 pt-2 border-t text-sm bg-gray-50 p-2 rounded">
                    <span>TOTAL PENDAPATAN</span>
                    <span>Rp {{ number_format($reportData['total_revenue'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- BAGIAN BEBAN & BIAYA -->
            <div class="space-y-3">
                <h2 class="text-md font-bold text-gray-800 uppercase border-b pb-1">2. Beban & Biaya (Expenses)</h2>
                <div class="max-w-full overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody>
                        @forelse($reportData['expenses'] as $exp)
                            <tr class="border-b border-gray-100">
                                <td class="py-2 text-gray-600"><span class="font-mono text-xs text-gray-400 mr-2">{{ $exp->code }}</span>{{ $exp->name }}</td>
                                <td class="py-2 text-right font-medium">Rp {{ number_format($exp->period_balance, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="py-2 text-center text-gray-400 italic">Tidak ada beban pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                <div class="flex justify-between items-center font-bold text-gray-900 pt-2 border-t text-sm bg-gray-50 p-2 rounded">
                    <span>TOTAL BEBAN</span>
                    <span>Rp {{ number_format($reportData['total_expense'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- HASIL LABA / RUGI BERSIH -->
            <div class="flex justify-between items-center font-bold text-lg p-4 rounded-xl border-2 {{ $reportData['net_profit'] >= 0 ? 'bg-green-50 border-green-500 text-green-800' : 'bg-red-50 border-red-500 text-red-800' }}">
                <span>{{ $reportData['net_profit'] >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}</span>
                <span>Rp {{ number_format($reportData['net_profit'], 0, ',', '.') }}</span>
            </div>

            <!-- Signature Section (Print Only) -->
            <div class="hidden print:flex justify-between pt-12 text-center text-sm">
                <div>
                    <p class="mb-12">Dibuat Oleh,</p>
                    <p class="font-bold underline">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">Staff Keuangan / Akuntan</p>
                </div>
                <div>
                    <p class="mb-12">Disetujui Oleh,</p>
                    <p class="font-bold underline">( .................................... )</p>
                    <p class="text-xs text-gray-500">Direktur Utama</p>
                </div>
            </div>

        </div>
    </div>
@endsection
