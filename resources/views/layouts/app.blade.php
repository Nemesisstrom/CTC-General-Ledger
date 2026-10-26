<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard - Sistem Keuangan & Payroll CTC')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }

        @keyframes dashboard-enter {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dashboard-enter { animation: dashboard-enter 400ms ease-out both; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        @media print {
            body { background: #fff !important; overflow: visible !important; }
            .app-sidebar, .app-sidebar-backdrop, .app-topbar { display: none !important; }
            .app-shell { display: block !important; min-height: 0 !important; }
            #app-main { overflow: visible !important; }
            #app-main .overflow-x-auto { overflow: visible !important; }
        }
    </style>
    @yield('styles')
</head>
<body class="min-h-screen overflow-x-hidden bg-[#F6F8F6] font-sans text-[#202B25] antialiased"
      x-data="{ sidebarOpen: false }"
      @keydown.escape.window="sidebarOpen = false">
    <div x-cloak
         x-show="sidebarOpen"
         @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="app-sidebar-backdrop fixed inset-0 z-40 bg-[#104B35]/45 backdrop-blur-[2px] lg:hidden"
         aria-hidden="true"></div>

    <div class="app-shell flex min-h-screen w-full">
        <aside id="main-navigation" class="app-sidebar fixed inset-y-0 left-0 z-50 flex w-[min(18rem,calc(100vw-2rem))] -translate-x-full flex-col bg-[#104B35] text-white shadow-2xl transition-transform duration-300 ease-out lg:sticky lg:top-0 lg:h-screen lg:w-72 lg:translate-x-0 lg:shadow-none"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               aria-label="Navigasi utama">
            <div class="flex items-center justify-between border-b border-white/10 px-5 py-5">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#C6E4D0]" aria-label="SIK & Payroll - Dashboard">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-lg font-extrabold text-[#176B45] shadow-sm">S</span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-bold tracking-wide text-white">SIK & Payroll</span>
                        <span class="mt-0.5 block text-[10px] font-medium uppercase tracking-[0.16em] text-[#C6E4D0]">CTC General Ledger</span>
                    </span>
                </a>
                <button type="button"
                        @click="sidebarOpen = false"
                        class="rounded-lg p-2 text-[#C6E4D0] transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] lg:hidden"
                        aria-label="Tutup menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto overscroll-contain px-3 py-5" aria-label="Menu aplikasi">
                <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] {{ request()->routeIs('dashboard') ? 'bg-white text-[#104B35] shadow-sm hover:bg-[#E8F4EC]' : 'text-[#E8F4EC]/85 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-5 w-5 shrink-0 text-[#176B45]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 00-1 1m-6 0h6"/></svg>
                    Dashboard
                </a>

                @role('admin')
                    <p class="px-3.5 pb-1 pt-6 text-[10px] font-bold uppercase tracking-[0.16em] text-[#C6E4D0]/75">Master Data</p>
                    <a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] {{ request()->routeIs('admin.users.*') ? 'bg-white/15 text-white' : 'text-[#E8F4EC]/85 hover:bg-white/10 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0 text-[#C6E4D0]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Manajemen User
                    </a>
                    <a href="{{ route('admin.chart-of-accounts.index') }}" @if(request()->routeIs('admin.chart-of-accounts.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] {{ request()->routeIs('admin.chart-of-accounts.*') ? 'bg-white/15 text-white' : 'text-[#E8F4EC]/85 hover:bg-white/10 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0 text-[#C6E4D0]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Rekening (COA)
                    </a>
                @endrole

                @hasanyrole('admin|akuntan')
                    <p class="px-3.5 pb-1 pt-6 text-[10px] font-bold uppercase tracking-[0.16em] text-[#C6E4D0]/75">Akuntansi & Transaksi</p>

                    @hasanyrole('admin|akuntan')
                    <a href="{{ route('admin.journals.index') }}" @if(request()->routeIs('admin.journals.index')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] {{ request()->routeIs('admin.journals.index') ? 'bg-white/15 text-white' : 'text-[#E8F4EC]/85 hover:bg-white/10 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0 text-[#C6E4D0]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Jurnal Umum
                    </a>
                    @endhasanyrole

                    @hasanyrole('admin|akuntan')
                    <a href="{{ route('admin.journals.reversals.index') }}" @if(request()->routeIs('admin.journals.reversals.index')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] {{ request()->routeIs('admin.journals.reversals.index') ? 'bg-white/15 text-white' : 'text-[#C6E4D0] hover:bg-white/10 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Jurnal Pembalikan
                    </a>
                    @endhasanyrole

                    @hasanyrole('admin|akuntan')
                    <a href="{{ route('admin.bank-reconciliations.index') }}" @if(request()->routeIs('admin.bank-reconciliations.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] {{ request()->routeIs('admin.bank-reconciliations.*') ? 'bg-white/15 text-white' : 'text-[#E8F4EC]/85 hover:bg-white/10 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0 text-[#C6E4D0]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                        Rekonsiliasi Bank
                    </a>
                    @endhasanyrole

                    @hasanyrole('admin|akuntan')
                    <a href="{{ route('admin.adjustments.index') }}" @if(request()->routeIs('admin.adjustments.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] {{ request()->routeIs('admin.adjustments.*') ? 'bg-white/15 text-white' : 'text-[#E8F4EC]/85 hover:bg-white/10 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0 text-[#C6E4D0]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        Jurnal Penyesuaian
                    </a>
                    @endhasanyrole

                    @hasanyrole('admin|akuntan')
                    <a href="{{ route('admin.taxes.index') }}" @if(request()->routeIs('admin.taxes.*', 'admin.reports.tax*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] {{ request()->routeIs('admin.taxes.*', 'admin.reports.tax*') ? 'bg-white/15 text-white' : 'text-[#E8F4EC]/85 hover:bg-white/10 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0 text-[#C6E4D0]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Perpajakan
                    </a>
                    @endhasanyrole
                @endhasanyrole

                <p class="px-3.5 pb-1 pt-6 text-[10px] font-bold uppercase tracking-[0.16em] text-[#C6E4D0]/75">Operasional</p>

                @hasanyrole('admin|akuntan')
                <a href="{{ route('admin.cash-transactions.index') }}" @if(request()->routeIs('admin.cash-transactions.*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] {{ request()->routeIs('admin.cash-transactions.*') ? 'bg-white/15 text-white' : 'text-[#E8F4EC]/85 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-5 w-5 shrink-0 text-[#C6E4D0]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Transaksi Kas
                </a>
                @endhasanyrole

                @hasanyrole('admin|akuntan')
                <a href="{{ route('admin.payrolls.index') }}" @if(request()->routeIs('admin.payrolls.*', 'admin.reports.payroll*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] {{ request()->routeIs('admin.payrolls.*', 'admin.reports.payroll*') ? 'bg-white/15 text-white' : 'text-[#E8F4EC]/85 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-5 w-5 shrink-0 text-[#C6E4D0]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Penggajian (Payroll)
                </a>
                @endhasanyrole

                @hasanyrole('admin|akuntan')
                    <p class="px-3.5 pb-1 pt-6 text-[10px] font-bold uppercase tracking-[0.16em] text-[#C6E4D0]/75">Laporan Keuangan</p>

                    @hasanyrole('admin|akuntan')
                    <a href="{{ route('admin.reports.general-ledger') }}" @if(request()->routeIs('admin.reports.general-ledger*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] {{ request()->routeIs('admin.reports.general-ledger*') ? 'bg-white/15 text-white' : 'text-[#E8F4EC]/85 hover:bg-white/10 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0 text-[#C6E4D0]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Buku Besar
                    </a>
                    @endhasanyrole

                    @hasanyrole('admin|akuntan')
                    <a href="{{ route('admin.reports.profit-loss') }}" @if(request()->routeIs('admin.reports.profit-loss*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] {{ request()->routeIs('admin.reports.profit-loss*') ? 'bg-white/15 text-white' : 'text-[#E8F4EC]/85 hover:bg-white/10 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0 text-[#C6E4D0]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        Laba Rugi
                    </a>
                    @endhasanyrole

                    @hasanyrole('admin|akuntan')
                    <a href="{{ route('admin.reports.balance-sheet') }}" @if(request()->routeIs('admin.reports.balance-sheet*')) aria-current="page" @endif class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-[#C6E4D0] {{ request()->routeIs('admin.reports.balance-sheet*') ? 'bg-white/15 text-white' : 'text-[#E8F4EC]/85 hover:bg-white/10 hover:text-white' }}">
                        <svg class="h-5 w-5 shrink-0 text-[#C6E4D0]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5 5 0 006 0l-3-9M6 7l6 2m6-2l-6 2m6-2l3 1m-3-1l-3 9a5 5 0 006 0l-3-9"/></svg>
                        Neraca
                    </a>
                    @endhasanyrole
                @endhasanyrole
            </nav>

            <div class="border-t border-white/10 p-3">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-[#F1D8D4] transition hover:bg-white/10 hover:text-white focus:outline-none focus:ring-2 focus:ring-[#C6E4D0]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        Keluar Sesi
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col">
            <header class="app-topbar sticky top-0 z-30 flex min-h-[4.5rem] items-center justify-between gap-3 border-b border-[#E1E8E2] bg-white/95 px-4 shadow-sm backdrop-blur sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button"
                            @click="sidebarOpen = !sidebarOpen"
                            :aria-expanded="sidebarOpen.toString()"
                            aria-controls="main-navigation"
                            aria-label="Buka menu navigasi"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-[#176B45] transition hover:bg-[#E8F4EC] focus:outline-none focus:ring-2 focus:ring-[#176B45] lg:hidden">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-bold text-[#202B25] sm:text-base">Sistem Keuangan & Payroll</p>
                        <p class="hidden text-xs text-[#68756D] sm:block">CTC General Ledger</p>
                    </div>
                </div>

                <div class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <span class="hidden max-w-48 truncate text-sm text-[#68756D] md:inline">
                        Halo, <strong class="font-semibold text-[#202B25]">{{ auth()->user()->name }}</strong>
                    </span>
                    <span class="max-w-32 truncate rounded-full border border-[#D5E8DA] bg-[#E8F4EC] px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-[#176B45] sm:max-w-48 sm:px-3 sm:text-xs">
                        {{ auth()->user()->getRoleNames()->first() ?? session('active_role', 'GUEST') }}
                    </span>
                </div>
            </header>

            <main id="app-main" class="min-w-0 flex-1 overflow-x-hidden">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
