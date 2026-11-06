@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Green Banner Header -->
    <div class="bg-emerald-800 dark:bg-emerald-950 text-white p-8 rounded-2xl shadow-sm relative overflow-hidden transition">
        <div class="relative z-10 max-w-2xl">
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-200">CTC GENERAL LEDGER</span>
            <h1 class="text-3xl font-bold mt-1">Ringkasan Keuangan</h1>
            <p class="text-sm text-emerald-100 mt-2">Ikhtisar arus kas, hasil usaha, dan aktivitas keuangan terbaru.</p>
        </div>
        <!-- Graphic Circles background accent -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 border-4 border-emerald-700/40 rounded-full pointer-events-none"></div>
        <div class="absolute -right-20 -top-20 w-80 h-80 border-4 border-emerald-700/30 rounded-full pointer-events-none"></div>
    </div>

    <!-- Ikhtisar Keuangan (Cards) -->
    <div class="space-y-3">
        <div>
            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Ikhtisar Keuangan</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400">Ringkasan berdasarkan data keuangan yang tersedia.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Kas Masuk -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex justify-between items-center transition">
                <div>
                    <span class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider">KAS MASUK</span>
                    <h3 class="text-2xl font-extrabold text-emerald-800 dark:text-emerald-400 mt-2">
                        Rp {{ number_format($totalInflow ?? 0, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-700 dark:text-emerald-300 font-bold text-lg">
                    ↑
                </div>
            </div>

            <!-- Kas Keluar -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex justify-between items-center transition">
                <div>
                    <span class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider">KAS KELUAR</span>
                    <h3 class="text-2xl font-extrabold text-emerald-800 dark:text-emerald-400 mt-2">
                        Rp {{ number_format($totalOutflow ?? 0, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 font-bold text-lg">
                    ↓
                </div>
            </div>

            <!-- Laba / Rugi Bersih -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex justify-between items-center transition">
                <div>
                    <span class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider">LABA / RUGI BERSIH</span>
                    <h3 class="text-2xl font-extrabold text-emerald-800 dark:text-emerald-400 mt-2">
                        Rp {{ number_format(($totalInflow ?? 0) - ($totalOutflow ?? 0), 0, ',', '.') }}
                    </h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-700 dark:text-emerald-300 text-lg">
                    📈
                </div>
            </div>

            <!-- Gaji Bulan Ini -->
            <div class="bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm flex justify-between items-center transition">
                <div>
                    <span class="text-[11px] font-bold text-gray-400 dark:text-gray-400 uppercase tracking-wider">GAJI BULAN INI</span>
                    <h3 class="text-2xl font-extrabold text-emerald-800 dark:text-emerald-400 mt-2">
                        Rp {{ number_format($payrollTotal ?? 0, 0, ',', '.') }}
                    </h3>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-700 dark:text-emerald-300 font-bold text-lg">
                    $
                </div>
            </div>
        </div>
    </div>

    <!-- SEKSI GRAFIK VISUAL -->
    <div class="space-y-3 pt-2">
        <div>
            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Visualisasi & Analisis Grafik</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400">Grafik perbandingan arus kas bulanan dan komposisi pengeluaran.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Grafik Line: Tren Arus Kas -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 transition">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">Tren Arus Kas (Kas Masuk vs Kas Keluar)</h3>
                    <span class="text-xs bg-emerald-50 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 px-2 py-1 rounded font-semibold">6 Bulan Terakhir</span>
                </div>
                <div class="relative h-64">
                    <canvas id="cashFlowLineChart"></canvas>
                </div>
            </div>

            <!-- Grafik Doughnut: Distribusi Transaksi Kas -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 transition">
                <div class="mb-4">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">Perbandingan Kas Masuk & Keluar</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Proporsi alokasi dana</p>
                </div>
                <div class="relative h-64 flex items-center justify-center">
                    <canvas id="cashDoughnutChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Aktivitas Terbaru -->
    <div class="space-y-3 pt-2">
        <div>
            <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">Aktivitas Terbaru</h2>
            <p class="text-xs text-gray-500 dark:text-gray-400">Transaksi kas dan jurnal pembalikan yang tercatat.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Transaksi Kas Terbaru -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 transition">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg text-emerald-700 dark:text-emerald-300 text-lg">
                            💵
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-gray-800 dark:text-gray-100">Transaksi Kas Terbaru</h3>
                            <p class="text-xs text-gray-400">Rincian transaksi kas</p>
                        </div>
                    </div>
                    @permission('manage-cash-transactions')
                        <a href="{{ route('admin.cash-transactions.index') }}" class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Buka modul</a>
                    @endpermission
                </div>

                <div class="border border-dashed dark:border-gray-700 rounded-lg p-8 text-center min-h-[160px] flex flex-col justify-center items-center">
                    <p class="text-xs font-bold text-gray-600 dark:text-gray-300">Belum ada transaksi kas</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Transaksi terbaru akan muncul di sini.</p>
                </div>
            </div>

            <!-- Jurnal Pembalikan Terbaru -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5 transition">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg text-emerald-700 dark:text-emerald-300 text-lg">
                            🔄
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-gray-800 dark:text-gray-100">Jurnal Pembalikan Terbaru</h3>
                            <p class="text-xs text-gray-400">Aktivitas jurnal pembalikan</p>
                        </div>
                    </div>
                    @permission('manage-journals')
                        <a href="{{ route('admin.journals.index') }}" class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 hover:underline">Buka modul</a>
                    @endpermission
                </div>

                <div class="border border-dashed dark:border-gray-700 rounded-lg p-8 text-center min-h-[160px] flex flex-col justify-center items-center">
                    <p class="text-xs font-bold text-gray-600 dark:text-gray-300">Belum ada aktivitas pembalikan jurnal</p>
                    <p class="text-[11px] text-gray-400 mt-0.5">Jurnal pembalikan terbaru akan muncul di sini.</p>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- CDN Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const isDarkMode = document.documentElement.classList.contains('dark');
        const textColor = isDarkMode ? '#CBD5E1' : '#475569';
        const gridColor = isDarkMode ? '#334155' : '#F1F5F9';

        // Parsing data dari Blade ke JS dengan aman tanpa memicu sintaks error Linter
        const monthlyLabels = JSON.parse('{!! json_encode($monthlyLabels ?? ["Jun", "Jul", "Agt", "Sep", "Okt", "Nov"]) !!}');
        const incomeData = JSON.parse('{!! json_encode($incomeData ?? [0, 0, 0, 0, 0, 0]) !!}');
        const expenseData = JSON.parse('{!! json_encode($expenseData ?? [0, 0, 0, 0, 0, 0]) !!}');
        const totalInflow = Number('{{ $totalInflow ?? 0 }}');
        const totalOutflow = Number('{{ $totalOutflow ?? 0 }}');

        // 1. Line Chart Arus Kas
        const ctxLine = document.getElementById('cashFlowLineChart').getContext('2d');
        new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: monthlyLabels,
                datasets: [
                    {
                        label: 'Kas Masuk',
                        data: incomeData,
                        borderColor: '#065F46',
                        backgroundColor: 'rgba(6, 95, 70, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4
                    },
                    {
                        label: 'Kas Keluar',
                        data: expenseData,
                        borderColor: '#DC2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: textColor } }
                },
                scales: {
                    x: { ticks: { color: textColor }, grid: { color: gridColor } },
                    y: {
                        ticks: {
                            color: textColor,
                            callback: function (value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        },
                        grid: { color: gridColor }
                    }
                }
            }
        });

        // 2. Doughnut Chart Kas Masuk vs Keluar
        const ctxDoughnut = document.getElementById('cashDoughnutChart').getContext('2d');
        new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: ['Kas Masuk', 'Kas Keluar'],
                datasets: [{
                    data: [totalInflow, totalOutflow],
                    backgroundColor: ['#065F46', '#DC2626'],
                    borderWidth: 2,
                    borderColor: isDarkMode ? '#1E293B' : '#FFFFFF'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { color: textColor } }
                }
            }
        });
    });
</script>
@endsection
