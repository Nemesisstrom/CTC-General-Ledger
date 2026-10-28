@extends('layouts.app')

@section('title', 'Laporan Buku Besar (General Ledger)')

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
                size: A4 landscape;
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

            .report-account-block {
                page-break-inside: avoid !important;
                margin-bottom: 20px !important;
            }

            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 8pt !important;
                table-layout: fixed !important;
            }

            th, td {
                border: 1px solid #94a3b8 !important;
                padding: 4px 5px !important;
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
    <div class="feature-page report-page mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Toolbar Filter (No Print) -->
        <div class="no-print bg-white p-4 rounded-xl shadow-sm flex flex-col lg:flex-row lg:items-end justify-between gap-4 border border-gray-100">
            <form action="{{ route('admin.reports.general-ledger') }}" method="GET" class="flex flex-wrap sm:flex-nowrap items-end gap-3 w-full lg:w-auto">
                <div class="w-full sm:w-auto flex-1 min-w-[240px]">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Pilih Akun COA</label>
                    <select name="account_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="all" {{ ($reportData['account_id'] ?? 'all') == 'all' ? 'selected' : '' }}>-- Semua akun --</option>
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}" {{ ($reportData['account_id'] ?? null) == $acc->id ? 'selected' : '' }}>
                                {{ $acc->code }} - {{ $acc->name }} ({{ strtoupper($acc->normal_balance) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-1/2 sm:w-auto">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $reportData['start_date'] ?? date('Y-m-01') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="w-1/2 sm:w-auto">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $reportData['end_date'] ?? date('Y-m-t') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <button type="submit" class="btn-press w-full sm:w-auto whitespace-nowrap bg-[#176B45] hover:bg-[#104B35] text-white font-medium text-sm px-4 py-2 rounded-lg transition shadow-sm">
                        Tampilkan
                    </button>
                </div>
            </form>

            <div class="flex items-center gap-2 shrink-0">
                @if(($reportData['report_accounts'] ?? collect())->count() > 0)
                    <button type="button" onclick="window.print()" class="btn-press whitespace-nowrap bg-[#176B45] hover:bg-[#104B35] text-white font-medium text-sm px-4 py-2 rounded-lg transition shadow-sm flex items-center gap-2">
                        <i class="fa-solid fa-print"></i> Cetak Laporan
                    </button>
                @endif 
                <a href="{{ route('dashboard') }}" class="btn-press whitespace-nowrap bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium text-sm px-4 py-2 rounded-lg transition">
                    Kembali
                </a>
            </div>
        </div>

        <!-- Document Sheet Utama -->
        @if(($reportData['report_accounts'] ?? collect())->count() > 0)
            <div class="document-sheet bg-white p-6 sm:p-8 rounded-xl shadow-sm space-y-6 border border-gray-100">
                
                <!-- Header Dokumen (Flexbox: Judul Center, Tanggal Dibuat Pas di Pojok Kanan) -->
                <div class="flex flex-col sm:flex-row items-center sm:items-start justify-between border-b pb-4 gap-2">
                    <!-- Spacer kiri agar Judul presisi di tengah pada tampilan desktop -->
                    <div class="hidden sm:block w-36"></div>

                    <!-- Header Center -->
                    <div class="text-center flex-1">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 uppercase tracking-tight">LAPORAN BUKU BESAR</h1>
                        <p class="text-sm font-bold text-[#176B45] mt-1 uppercase">
                            {{ ($reportData['account_id'] ?? 'all') === 'all' ? 'SELURUH AKUN (ALL ACCOUNTS)' : ($reportData['report_accounts']->first()['account']->code ?? '') . ' - ' . ($reportData['report_accounts']->first()['account']->name ?? '') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Periode: {{ \Carbon\Carbon::parse($reportData['start_date'])->translatedFormat('d F Y') }}
                            s/d {{ \Carbon\Carbon::parse($reportData['end_date'])->translatedFormat('d F Y') }}
                        </p>
                    </div>

                    <!-- Tanggal Dibuat (Pojok Kanan Aman Tanpa Terpotong) -->
                    <div class="text-center sm:text-right text-xs shrink-0 w-auto sm:w-36">
                        <span class="text-gray-500 font-medium block">Tanggal Dibuat:</span>
                        <span class="font-bold text-gray-800 mt-0.5 block">
                            {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
                        </span>
                    </div>
                </div>

                <!-- Kontainer Daftar Tabel Per Akun (Bebas Scrollbar) -->
                <div class="space-y-8">
                    @foreach($reportData['report_accounts'] as $item)
                        @php
                            $acc = $item['account'];
                            $entries = $item['entries'];
                            $openingBalance = $item['opening_balance'];
                            $endingBalance = $item['ending_balance'];
                        @endphp

                        <!-- Block Per Akun -->
                        <div class="report-account-block space-y-2 mb-8">
                            
                            <!-- Header Nama Akun (Rata Tengah) -->
                            <div class="bg-[#E8F4EC] border border-gray-300 py-2 px-3 rounded-t-lg font-bold text-[#104B35] text-xs text-center uppercase tracking-wide">
                                AKUN: {{ $acc->code }} - {{ $acc->name }} ({{ strtoupper($acc->normal_balance) }})
                            </div>

                            <!-- Tabel Murni Presisi 100% -->
                            <div class="report-table-wrapper">
                                <table class="w-full text-left border-collapse text-[11px] table-fixed">
                                    <thead>
                                        <tr class="border-b border-gray-400 text-[10px] font-semibold text-gray-700 uppercase bg-gray-100">
                                            <th class="py-2 px-1.5 text-center w-[12%]">TANGGAL</th>
                                            <th class="py-2 px-1.5 text-center w-[20%]">NO. JURNAL</th>
                                            <th class="py-2 px-2 text-left w-[26%]">KETERANGAN / MEMO</th>
                                            <th class="py-2 px-1.5 text-center w-[14%]">DEBIT</th>
                                            <th class="py-2 px-1.5 text-center w-[14%]">KREDIT</th>
                                            <th class="py-2 px-1.5 text-center w-[14%]">SALDO RUNNING</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <!-- Saldo Awal -->
                                        <tr class="bg-gray-50/70 font-semibold text-gray-700">
                                            <td class="py-2 px-1.5 text-center whitespace-nowrap">{{ date('d/m/Y', strtotime($reportData['start_date'])) }}</td>
                                            <td class="py-2 px-1.5 text-center whitespace-nowrap">-</td>
                                            <td class="py-2 px-2 text-left italic">SALDO AWAL PERIODE</td>
                                            <td class="py-2 px-1.5 text-center font-mono tabular-nums whitespace-nowrap">-</td>
                                            <td class="py-2 px-1.5 text-center font-mono tabular-nums whitespace-nowrap">-</td>
                                            <td class="py-2 px-1.5 text-center font-mono tabular-nums whitespace-nowrap">
                                                Rp {{ number_format($openingBalance, 0, ',', '.') }}
                                            </td>
                                        </tr>

                                        <!-- Detail Transaksi -->
                                        @forelse($entries as $entry)
                                            <tr class="hover:bg-gray-50/50">
                                                <td class="py-2 px-1.5 text-center whitespace-nowrap">
                                                    {{ $entry->journal->journal_date ? date('d/m/Y', strtotime($entry->journal->journal_date)) : '-' }}
                                                </td>
                                                <td class="py-2 px-1.5 text-center font-mono text-[10px] font-medium text-gray-800 break-all">
                                                    {{ $entry->journal->journal_number ?? '-' }}
                                                </td>
                                                <td class="py-2 px-2 text-left text-gray-700 leading-snug break-words">
                                                    {{ $entry->memo ?? $entry->journal->description ?? '-' }}
                                                </td>
                                                <td class="py-2 px-1.5 text-center font-mono tabular-nums whitespace-nowrap">
                                                    {{ $entry->debit > 0 ? 'Rp ' . number_format($entry->debit, 0, ',', '.') : '-' }}
                                                </td>
                                                <td class="py-2 px-1.5 text-center font-mono tabular-nums whitespace-nowrap">
                                                    {{ $entry->credit > 0 ? 'Rp ' . number_format($entry->credit, 0, ',', '.') : '-' }}
                                                </td>
                                                <td class="py-2 px-1.5 text-center font-mono font-medium tabular-nums whitespace-nowrap">
                                                    Rp {{ number_format($entry->running_balance ?? 0, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="py-2.5 px-3 text-center text-gray-400 italic">
                                                    Tidak ada transaksi jurnal pada periode ini.
                                                </td>
                                            </tr>
                                        @endforelse

                                        <!-- Ringkasan Saldo Akhir -->
                                        <tr class="border-t-2 border-gray-400 font-bold bg-gray-50 text-gray-900">
                                            <td colspan="3" class="py-2 px-3 text-center uppercase text-[10px]">SALDO AKHIR:</td>
                                            <td class="py-2 px-1.5 text-center font-mono tabular-nums text-emerald-700 whitespace-nowrap">
                                                Rp {{ number_format($entries->sum('debit') ?? 0, 0, ',', '.') }}
                                            </td>
                                            <td class="py-2 px-1.5 text-center font-mono tabular-nums text-red-700 whitespace-nowrap">
                                                Rp {{ number_format($entries->sum('credit') ?? 0, 0, ',', '.') }}
                                            </td>
                                            <td class="py-2 px-1.5 text-center font-mono tabular-nums text-blue-700 bg-blue-50/50 whitespace-nowrap">
                                                Rp {{ number_format($endingBalance, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Footer Tanda Tangan Cetak -->
                <div class="print-signature hidden justify-between pt-10 text-center text-xs">
                    <div class="w-56">
                        <p class="mb-14">Dibuat Oleh,</p>
                        <p class="font-bold underline">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-gray-500 mt-0.5">Staff Keuangan / Akuntan</p>
                    </div>
                    <div class="w-56">
                        <p class="mb-14">Disetujui Oleh,</p>
                        <p class="font-bold underline">( .................................... )</p>
                        <p class="text-[10px] text-gray-500 mt-0.5">Manager Keuangan</p>
                    </div>
                </div>

            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white p-12 rounded-xl shadow-sm border border-gray-100 text-center text-gray-500">
                <i class="fa-solid fa-folder-open text-4xl text-gray-300 mb-3"></i>
                <p class="text-base font-semibold text-gray-700">Tidak ada data untuk ditampilkan</p>
            </div>
        @endif

    </div>
@endsection