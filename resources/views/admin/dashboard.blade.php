<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Keuangan & Payroll CTC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col lg:flex-row" x-data="{ sidebarOpen: false }">

    <!-- OVERLAY BACKDROP (Khusus Layar Mobile) -->
    <div x-show="sidebarOpen"
         @click="sidebarOpen = false"
         x-cloak
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden">
    </div>

    <!-- 1. SIDEBAR NAVIGASI RESPONSIF -->
    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-white transform transition-transform duration-300 ease-in-out flex flex-col justify-between shadow-xl
                  lg:translate-x-0 lg:static lg:inset-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        <div>
            <!-- Header Sidebar & Tombol Close (Mobile) -->
            <div class="px-6 py-5 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center font-bold text-lg">S</div>
                    <span class="font-bold text-lg tracking-wide text-slate-100">SIK & Payroll</span>
                </div>
                <!-- Tombol Close Sidebar (Mobile Only) -->
                <button @click="sidebarOpen = false" class="lg:hidden text-slate-400 hover:text-white focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Nav Links -->
            <nav class="px-4 py-6 space-y-1 overflow-y-auto max-h-[calc(100vh-140px)]">
                <!-- Dashboard (Semua Role) -->
                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-sm font-semibold text-white bg-blue-600 rounded-lg shadow-sm">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 00-1 1m-6 0h6"/></svg>
                    Dashboard
                </a>

                <!-- ROLE ADMIN -->
                @role('admin')
                    <div class="pt-4 pb-1 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Master Data</div>
                    <a href="{{ route('admin.users.index') }}" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Manajemen User
                    </a>
                    <a href="{{ route('admin.chart-of-accounts.index') }}" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Chart of Accounts (COA)
                    </a>
                @endrole

                <!-- ADMIN & AKUNTAN -->
                @hasanyrole('admin|akuntan')
                    <div class="pt-4 pb-1 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Akuntansi & Transaksi</div>

                    @can('manage-journals')
                    <a href="{{ route('admin.journals.index') }}" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Jurnal Umum
                    </a>
                    @endcan

                    @can('manage-bank-reconciliations')
                    <a href="{{ route('admin.bank-reconciliations.index') }}" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                        Rekonsiliasi Bank
                    </a>
                    @endcan

                    @can('manage-adjustments')
                    <a href="{{ route('admin.adjustments.index') }}" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        Jurnal Penyesuaian
                    </a>
                    @endcan

                    @can('process-taxes')
                    <a href="{{ route('admin.taxes.index') }}" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Perpajakan
                    </a>
                    @endcan
                @endhasanyrole

                <!-- OPERASIONAL (STAFF, AKUNTAN, ADMIN) -->
                <div class="pt-4 pb-1 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Operasional</div>

                @can('manage-cash-transactions')
                <a href="{{ route('admin.cash-transactions.index') }}" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Transaksi Kas
                </a>
                @endcan

                @can('manage-payrolls')
                <a href="{{ route('admin.payrolls.index') }}" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                    <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Penggajian (Payroll)
                </a>
                @endcan

                <!-- LAPORAN KEUANGAN -->
                @hasanyrole('admin|akuntan')
                    <div class="pt-4 pb-1 px-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Laporan Keuangan</div>

                    @can('view-reports-general-ledger')
                    <a href="{{ route('admin.reports.general-ledger') }}" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Buku Besar
                    </a>
                    @endcan

                    @can('view-reports-profit-loss')
                    <a href="{{ route('admin.reports.profit-loss') }}" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        Laba Rugi
                    </a>
                    @endcan

                    @can('view-reports-balance-sheet')
                    <a href="{{ route('admin.reports.balance-sheet') }}" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg transition">
                        <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5 5 0 006 0l-3-9eM6 7l6 2m6-2l-6 2m6-2l3 1m-3-1l-3 9a5 5 0 006 0l-3-9"/></svg>
                        Neraca
                    </a>
                    @endcan
                @endhasanyrole
            </nav>
        </div>

        <!-- Logout Button -->
        <div class="p-4 border-t border-slate-800">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center px-4 py-2 text-sm font-semibold text-red-400 hover:bg-red-500/10 hover:text-red-300 rounded-lg transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Keluar Sesi
                </button>
            </form>
        </div>
    </aside>

    <!-- 2. KONTEN UTAMA DENGAN NAVBAR RESPONSIF -->
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Header / Navbar Atas -->
        <header class="bg-white border-b border-gray-200 px-4 sm:px-8 py-4 flex justify-between items-center shadow-sm sticky top-0 z-30">
            <div class="flex items-center space-x-3">
                <!-- Tombol Hamburger (Mobile Only) -->
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-base sm:text-xl font-bold text-gray-800 truncate">Sistem Keuangan & Payroll</h1>
            </div>

            <!-- Profile Info & Role Badge -->
            <div class="flex items-center space-x-2 sm:space-x-3">
                <span class="text-xs sm:text-sm text-gray-600 hidden sm:inline">
                    Halo, <strong>{{ auth()->user()->name }}</strong>
                </span>
                <span class="text-[10px] sm:text-xs font-bold px-2 sm:px-2.5 py-0.5 sm:py-1 rounded-full bg-blue-100 text-blue-800 uppercase tracking-wide">
                    {{ auth()->user()->getRoleNames()->first() ?? session('active_role', 'GUEST') }}
                </span>
            </div>
        </header>

        <!-- Main Content Body -->
        <main class="p-4 sm:p-8 flex-1">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-6">Ringkasan Ringkas Keuangan</h2>

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Kas Masuk</p>
                    <p class="text-xl sm:text-2xl font-bold text-green-600 mt-2">Rp {{ number_format($data['kas_masuk'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Kas Keluar</p>
                    <p class="text-xl sm:text-2xl font-bold text-red-600 mt-2">Rp {{ number_format($data['kas_keluar'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Laba / Rugi Bersih</p>
                    <p class="text-xl sm:text-2xl font-bold {{ ($data['laba_rugi_bersih'] ?? 0) >= 0 ? 'text-blue-600' : 'text-red-600' }} mt-2">
                        Rp {{ number_format($data['laba_rugi_bersih'] ?? 0, 0, ',', '.') }}
                    </p>
                </div>
                <div class="bg-white p-5 sm:p-6 rounded-xl shadow-sm border border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase">Gaji Bulan Ini</p>
                    <p class="text-xl sm:text-2xl font-bold text-purple-600 mt-2">Rp {{ number_format($data['gaji_bulan_ini'] ?? 0, 0, ',', '.') }}</p>
                </div>
            </div>

            <!-- Table Transaksi Kas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-6">
                <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-4">Transaksi Kas Terbaru</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase whitespace-nowrap">
                                <th class="py-3 px-4">No. Transaksi</th>
                                <th class="py-3 px-4">Tanggal</th>
                                <th class="py-3 px-4">Tipe</th>
                                <th class="py-3 px-4">Jumlah</th>
                                <th class="py-3 px-4">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm whitespace-nowrap">
                            @forelse($data['transaksi_terakhir'] ?? [] as $trx)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4 font-mono text-xs">{{ $trx->transaction_number }}</td>
                                    <td class="py-3 px-4">{{ $trx->transaction_date }}</td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $trx->type === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $trx->type === 'in' ? 'Masuk' : 'Keluar' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-semibold">Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                                    <td class="py-3 px-4 text-gray-600">{{ $trx->description ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-gray-500">Belum ada transaksi kas recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
