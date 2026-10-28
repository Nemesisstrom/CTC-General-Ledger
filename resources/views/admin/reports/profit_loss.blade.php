@extends('layouts.app')

@section('title', 'Laporan Laba Rugi')

@section('styles')
    <style>
        /* Hilangkan overflow scrollbar di layar web agar seperti dokumen murni */
        .report-page,
        .document-sheet,
        .report-table-wrapper {
            overflow: visible !important;
            max-width: 100% !important;
        }

        /* CSS KHUSUS DOKUMEN CETAK (PRINT VIEW) */
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm;
            }

            .app-sidebar,
            .app-topbar,
            .app-sidebar-backdrop,
            footer,
            .no-print {
                display: none !important;
            }

            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                overflow: visible !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .app-shell, main, #app-main, .report-page {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                overflow: visible !important;
            }

            .document-sheet {
                padding: 0 !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }

            .report-section-block {
                page-break-inside: avoid !important;
                margin-bottom: 20px !important;
            }

            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 8.5pt !important;
                table-layout: fixed !important;
            }

            th, td {
                border: 1px solid #94a3b8 !important;
                padding: 5px 6px !important;
                vertical-align: middle !important;
                word-wrap: break-word !important;
            }

            th {
                background-color: #f1f5f9 !important;
                color: #0f172a !important;
                font-weight: 700 !important;
            }

            .print-signature {
                display: flex !important;
                page-break-inside: avoid !important;
            }
        }
    </style>
@endsection

@section('content')
    <div class="feature-page report-page mx-auto max-w-4xl space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Toolbar Filter (No Print) -->
        <div class="no-print bg-white p-4 rounded-xl shadow-sm flex flex-col lg:flex-row lg:items-end justify-between gap-4 border border-gray-100">
            <form action="{{ route('admin.reports.profit-loss') }}" method="GET" class="flex flex-wrap sm:flex-nowrap items-end gap-3 w-full lg:w-auto">
                <div class="w-1/2 sm:w-auto">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $reportData['start_date'] }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="w-1/2 sm:w-auto">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $reportData['end_date'] }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <button type="submit" class="btn-press w-full sm:w-auto whitespace-nowrap bg-[#176B45] hover:bg-[#104B35] text-white font-medium text-sm px-4 py-2 rounded-lg transition shadow-sm">
                        Filter
                    </button>
                </div>
            </form>

            <div class="flex items-center gap-2 shrink-0">
                <button type="button" onclick="window.print()" class="btn-press whitespace-nowrap bg-[#176B45] hover:bg-[#104B35] text-white font-medium text-sm px-4 py-2 rounded-lg transition shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-print"></i> Cetak Laporan
                </button>
                <a href="{{ route('dashboard') }}" class="btn-press whitespace-nowrap bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm px-4 py-2 rounded-lg transition">
                    Kembali
                </a>
            </div>
        </div>

        <!-- Document Sheet Utama -->
        <div class="document-sheet bg-white p-6 sm:p-8 rounded-xl shadow-sm space-y-6 border border-gray-100">

            <!-- Header Dokumen (Flexbox: Judul Center, Tanggal Dibuat Pojok Kanan) -->
            <div class="flex flex-col sm:flex-row items-center sm:items-start justify-between border-b pb-4 gap-2">
                <!-- Spacer Kiri agar Judul Center Presisi -->
                <div class="hidden sm:block w-36"></div>

                <!-- Header Center -->
                <div class="text-center flex-1">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 uppercase tracking-tight">LAPORAN LABA RUGI</h1>
                    <p class="text-xs font-semibold text-[#176B45] uppercase tracking-wide mt-0.5">PROFIT & LOSS STATEMENT</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Periode: {{ \Carbon\Carbon::parse($reportData['start_date'])->translatedFormat('d F Y') }}
                        s/d {{ \Carbon\Carbon::parse($reportData['end_date'])->translatedFormat('d F Y') }}
                    </p>
                </div>

                <!-- Tanggal Dibuat (Pojok Kanan Top) -->
                <div class="text-center sm:text-right text-xs shrink-0 w-auto sm:w-36">
                    <span class="text-gray-500 font-medium block">Tanggal Dibuat:</span>
                    <span class="font-bold text-gray-800 mt-0.5 block">
                        {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
                    </span>
                </div>
            </div>

            <!-- BLOK KATEGORI PER KELOMPOK AKUN -->
            <div class="space-y-8">

                <!-- 1. PENDAPATAN (REVENUE) -->
                <div class="report-section-block space-y-2 mb-6">
                    <div class="bg-[#E8F4EC] border border-gray-300 py-2 px-3 rounded-t-lg font-bold text-[#104B35] text-xs text-center uppercase tracking-wide">
                        1. PENDAPATAN / REVENUE
                    </div>
                    <div class="report-table-wrapper">
                        <table class="w-full text-left border-collapse text-xs table-fixed">
                            <thead>
                                <tr class="border-b border-gray-400 text-[10px] font-semibold text-gray-700 uppercase bg-gray-100">
                                    <th class="py-2 px-2 text-center w-[20%]">KODE AKUN</th>
                                    <th class="py-2 px-3 text-left w-[50%]">NAMA AKUN</th>
                                    <th class="py-2 px-2 text-center w-[30%]">JUMLAH (RP)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($reportData['revenues'] as $rev)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-2 px-2 text-center font-mono text-gray-600 whitespace-nowrap">{{ $rev->code }}</td>
                                        <td class="py-2 px-3 text-left text-gray-800 font-medium">{{ $rev->name }}</td>
                                        <td class="py-2 px-2 text-center font-mono tabular-nums whitespace-nowrap">
                                            Rp {{ number_format($rev->period_balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-2.5 px-3 text-center text-gray-400 italic">
                                            Tidak ada data pendapatan pada periode ini.
                                        </td>
                                    </tr>
                                @endforelse
                                <tr class="border-t-2 border-gray-400 font-bold bg-gray-50 text-gray-900">
                                    <td colspan="2" class="py-2 px-3 text-center uppercase text-[10px]">TOTAL PENDAPATAN:</td>
                                    <td class="py-2 px-2 text-center font-mono tabular-nums text-emerald-700 whitespace-nowrap">
                                        Rp {{ number_format($reportData['total_revenue'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. BEBAN OPERASIONAL (EXPENSES) -->
                <div class="report-section-block space-y-2 mb-6">
                    <div class="bg-[#E8F4EC] border border-gray-300 py-2 px-3 rounded-t-lg font-bold text-[#104B35] text-xs text-center uppercase tracking-wide">
                        2. BEBAN OPERASIONAL / EXPENSES
                    </div>
                    <div class="report-table-wrapper">
                        <table class="w-full text-left border-collapse text-xs table-fixed">
                            <thead>
                                <tr class="border-b border-gray-400 text-[10px] font-semibold text-gray-700 uppercase bg-gray-100">
                                    <th class="py-2 px-2 text-center w-[20%]">KODE AKUN</th>
                                    <th class="py-2 px-3 text-left w-[50%]">NAMA AKUN</th>
                                    <th class="py-2 px-2 text-center w-[30%]">JUMLAH (RP)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($reportData['expenses'] as $exp)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-2 px-2 text-center font-mono text-gray-600 whitespace-nowrap">{{ $exp->code }}</td>
                                        <td class="py-2 px-3 text-left text-gray-800 font-medium">{{ $exp->name }}</td>
                                        <td class="py-2 px-2 text-center font-mono tabular-nums whitespace-nowrap">
                                            Rp {{ number_format($exp->period_balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-2.5 px-3 text-center text-gray-400 italic">
                                            Tidak ada data beban pada periode ini.
                                        </td>
                                    </tr>
                                @endforelse
                                <tr class="border-t-2 border-gray-400 font-bold bg-gray-50 text-gray-900">
                                    <td colspan="2" class="py-2 px-3 text-center uppercase text-[10px]">TOTAL BEBAN OPERASIONAL:</td>
                                    <td class="py-2 px-2 text-center font-mono tabular-nums text-red-700 whitespace-nowrap">
                                        Rp {{ number_format($reportData['total_expense'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. HASIL LABA / RUGI BERSIH -->
                <div class="report-section-block">
                    <table class="w-full text-left border-collapse text-xs table-fixed">
                        <tbody>
                            <tr class="border-2 font-bold text-xs {{ $reportData['net_profit'] >= 0 ? 'bg-emerald-50/80 border-emerald-600 text-emerald-900' : 'bg-red-50/80 border-red-600 text-red-900' }}">
                                <td class="py-3 px-3 text-center uppercase tracking-wide w-[70%]">
                                    {{ $reportData['net_profit'] >= 0 ? 'LABA BERSIH (NET PROFIT):' : 'RUGI BERSIH (NET LOSS):' }}
                                </td>
                                <td class="py-3 px-2 text-center font-mono tabular-nums text-sm whitespace-nowrap w-[30%]">
                                    Rp {{ number_format($reportData['net_profit'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- Footer Tanda Tangan Cetak -->
            <div class="print-signature hidden justify-between pt-10 text-center text-xs">
                <div class="w-56">
                    <p class="mb-14">Dibuat Oleh,</p>
                    <p class="font-bold underline">{{ auth()->user()->name ?? 'Staff Akuntansi' }}</p>
                    <p class="text-[10px] text-gray-500 mt-0.5">Staff Keuangan / Akuntan</p>
                </div>
                <div class="w-56">
                    <p class="mb-14">Disetujui Oleh,</p>
                    <p class="font-bold underline">( .................................... )</p>
                    <p class="text-[10px] text-gray-500 mt-0.5">Manager Keuangan / Direktur</p>
                </div>
            </div>

        </div>
    </div>
@endsection