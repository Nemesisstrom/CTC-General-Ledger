@extends('layouts.app')

@section('title', 'Laporan Neraca Keuangan')

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
            <form action="{{ route('admin.reports.balance-sheet') }}" method="GET" class="flex flex-wrap sm:flex-nowrap items-end gap-3 w-full lg:w-auto">
                <div class="w-full sm:w-auto">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Per Tanggal</label>
                    <input type="date" name="as_of_date" value="{{ $reportData['as_of_date'] }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div>
                    <button type="submit" class="btn-press w-full sm:w-auto whitespace-nowrap bg-[#176B45] hover:bg-[#104B35] text-white font-medium text-sm px-4 py-2 rounded-lg transition shadow-sm">
                        Tampilkan
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

        <!-- Warning Alert Status Balance (No Print) -->
        @if(!$reportData['is_balanced'])
            <div class="no-print bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm text-center font-medium">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                <strong>Perhatian:</strong> Neraca tidak seimbang! Total Aset (Rp {{ number_format($reportData['total_assets'], 0, ',', '.') }}) tidak sama dengan Total Kewajiban & Ekuitas (Rp {{ number_format($reportData['total_liabilities_and_equity'], 0, ',', '.') }}).
            </div>
        @endif

        <!-- Document Sheet Utama -->
        <div class="document-sheet bg-white p-6 sm:p-8 rounded-xl shadow-sm space-y-6 border border-gray-100">

            <!-- Header Dokumen (Flexbox: Judul Center, Tanggal Dibuat Pojok Kanan) -->
            <div class="flex flex-col sm:flex-row items-center sm:items-start justify-between border-b pb-4 gap-2">
                <!-- Spacer Kiri agar Judul Center Presisi -->
                <div class="hidden sm:block w-36"></div>

                <!-- Header Center -->
                <div class="text-center flex-1">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900 uppercase tracking-tight">LAPORAN POSISI KEUANGAN</h1>
                    <p class="text-xs font-semibold text-[#176B45] uppercase tracking-wide mt-0.5">BALANCE SHEET STATEMENT</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Per Tanggal: {{ \Carbon\Carbon::parse($reportData['as_of_date'])->translatedFormat('d F Y') }}
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

            <!-- BLOK KATEGORI PER KELOMPOK AKUN NERACA -->
            <div class="space-y-8">

                <!-- 1. ASET / AKTIVA (ASSETS) -->
                <div class="report-section-block space-y-2 mb-6">
                    <div class="bg-[#E8F4EC] border border-gray-300 py-2 px-3 rounded-t-lg font-bold text-[#104B35] text-xs text-center uppercase tracking-wide">
                        1. ASET / AKTIVA (ASSETS)
                    </div>
                    <div class="report-table-wrapper">
                        <table class="w-full text-left border-collapse text-xs table-fixed">
                            <thead>
                                <tr class="border-b border-gray-400 text-[10px] font-semibold text-gray-700 uppercase bg-gray-100">
                                    <th class="py-2 px-2 text-center w-[20%]">KODE AKUN</th>
                                    <th class="py-2 px-3 text-left w-[50%]">NAMA AKUN</th>
                                    <th class="py-2 px-2 text-center w-[30%]">SALDO (RP)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($reportData['assets'] as $asset)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-2 px-2 text-center font-mono text-gray-600 whitespace-nowrap">{{ $asset->code }}</td>
                                        <td class="py-2 px-3 text-left text-gray-800 font-medium">{{ $asset->name }}</td>
                                        <td class="py-2 px-2 text-center font-mono tabular-nums whitespace-nowrap">
                                            Rp {{ number_format($asset->balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-2.5 px-3 text-center text-gray-400 italic">
                                            Tidak ada saldo akun aset per tanggal ini.
                                        </td>
                                    </tr>
                                @endforelse
                                <tr class="border-t-2 border-gray-400 font-bold bg-gray-50 text-gray-900">
                                    <td colspan="2" class="py-2 px-3 text-center uppercase text-[10px]">TOTAL ASET:</td>
                                    <td class="py-2 px-2 text-center font-mono tabular-nums text-emerald-700 whitespace-nowrap">
                                        Rp {{ number_format($reportData['total_assets'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. KEWAJIBAN / LIABILITAS (LIABILITIES) -->
                <div class="report-section-block space-y-2 mb-6">
                    <div class="bg-[#E8F4EC] border border-gray-300 py-2 px-3 rounded-t-lg font-bold text-[#104B35] text-xs text-center uppercase tracking-wide">
                        2. KEWAJIBAN / LIABILITAS (LIABILITIES)
                    </div>
                    <div class="report-table-wrapper">
                        <table class="w-full text-left border-collapse text-xs table-fixed">
                            <thead>
                                <tr class="border-b border-gray-400 text-[10px] font-semibold text-gray-700 uppercase bg-gray-100">
                                    <th class="py-2 px-2 text-center w-[20%]">KODE AKUN</th>
                                    <th class="py-2 px-3 text-left w-[50%]">NAMA AKUN</th>
                                    <th class="py-2 px-2 text-center w-[30%]">SALDO (RP)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($reportData['liabilities'] as $liab)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-2 px-2 text-center font-mono text-gray-600 whitespace-nowrap">{{ $liab->code }}</td>
                                        <td class="py-2 px-3 text-left text-gray-800 font-medium">{{ $liab->name }}</td>
                                        <td class="py-2 px-2 text-center font-mono tabular-nums whitespace-nowrap">
                                            Rp {{ number_format($liab->balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="py-2.5 px-3 text-center text-gray-400 italic">
                                            Tidak ada saldo akun kewajiban per tanggal ini.
                                        </td>
                                    </tr>
                                @endforelse
                                <tr class="border-t-2 border-gray-400 font-bold bg-gray-50 text-gray-900">
                                    <td colspan="2" class="py-2 px-3 text-center uppercase text-[10px]">TOTAL KEWAJIBAN:</td>
                                    <td class="py-2 px-2 text-center font-mono tabular-nums text-red-700 whitespace-nowrap">
                                        Rp {{ number_format($reportData['total_liabilities'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. EKUITAS / MODAL (EQUITY) -->
                <div class="report-section-block space-y-2 mb-6">
                    <div class="bg-[#E8F4EC] border border-gray-300 py-2 px-3 rounded-t-lg font-bold text-[#104B35] text-xs text-center uppercase tracking-wide">
                        3. EKUITAS / MODAL (EQUITY)
                    </div>
                    <div class="report-table-wrapper">
                        <table class="w-full text-left border-collapse text-xs table-fixed">
                            <thead>
                                <tr class="border-b border-gray-400 text-[10px] font-semibold text-gray-700 uppercase bg-gray-100">
                                    <th class="py-2 px-2 text-center w-[20%]">KODE AKUN</th>
                                    <th class="py-2 px-3 text-left w-[50%]">NAMA AKUN</th>
                                    <th class="py-2 px-2 text-center w-[30%]">SALDO (RP)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($reportData['equities'] as $equity)
                                    <tr class="hover:bg-gray-50/50">
                                        <td class="py-2 px-2 text-center font-mono text-gray-600 whitespace-nowrap">{{ $equity->code }}</td>
                                        <td class="py-2 px-3 text-left text-gray-800 font-medium">{{ $equity->name }}</td>
                                        <td class="py-2 px-2 text-center font-mono tabular-nums whitespace-nowrap">
                                            Rp {{ number_format($equity->balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                                <!-- Laba / Rugi Berjalan -->
                                <tr class="hover:bg-gray-50/50 bg-blue-50/30">
                                    <td class="py-2 px-2 text-center font-mono text-gray-400 whitespace-nowrap">-</td>
                                    <td class="py-2 px-3 text-left text-gray-800 font-medium italic">Laba / (Rugi) Tahun Berjalan</td>
                                    <td class="py-2 px-2 text-center font-mono tabular-nums whitespace-nowrap">
                                        Rp {{ number_format($reportData['current_earnings'], 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr class="border-t-2 border-gray-400 font-bold bg-gray-50 text-gray-900">
                                    <td colspan="2" class="py-2 px-3 text-center uppercase text-[10px]">TOTAL EKUITAS:</td>
                                    <td class="py-2 px-2 text-center font-mono tabular-nums text-blue-700 whitespace-nowrap">
                                        Rp {{ number_format($reportData['total_equity'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4. TOTAL PASIVA / REKAPITULASI (TOTAL KEWAJIBAN & EKUITAS) -->
                <div class="report-section-block">
                    <table class="w-full text-left border-collapse text-xs table-fixed">
                        <tbody>
                            <tr class="border-2 font-bold text-xs bg-gray-100 border-gray-800 text-gray-900">
                                <td class="py-3 px-3 text-center uppercase tracking-wide w-[70%]">
                                    TOTAL KEWAJIBAN & EKUITAS (TOTAL PASIVA):
                                </td>
                                <td class="py-3 px-2 text-center font-mono tabular-nums text-sm whitespace-nowrap w-[30%]">
                                    Rp {{ number_format($reportData['total_liabilities_and_equity'], 0, ',', '.') }}
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