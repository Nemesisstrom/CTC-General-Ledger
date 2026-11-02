<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard - Sistem Keuangan & Payroll CTC')</title>

    {{-- Script Eksekusi Tercepat (Zero-Flicker Theme Engine) --}}
    <script>
        (function() {
            if (localStorage.getItem('sidebar_collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <!-- Font Awesome Icons -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Laravel Vite: Tailwind CSS & Application JS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script
        defer
        src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>



    @yield('styles')
</head>

<body class="min-h-screen overflow-x-hidden antialiased select-none"
    x-data="{
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('sidebar_collapsed') === 'true',
        darkMode: document.documentElement.classList.contains('dark'),

        toggleCollapse() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebar_collapsed', this.sidebarCollapsed);
            document.documentElement.classList.toggle('sidebar-collapsed', this.sidebarCollapsed);
        },

        toggleTheme() {
            this.darkMode = !this.darkMode;
            document.documentElement.classList.toggle('dark', this.darkMode);
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
        }
    }"
    @keydown.escape.window="sidebarOpen = false">

    {{-- Mobile Sidebar Backdrop --}}
    <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false"
        x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs lg:hidden" aria-hidden="true">
    </div>

    <div class="app-shell flex min-h-screen w-full">

        {{-- MAIN SIDEBAR --}}
        <aside id="main-navigation"
            class="app-sidebar fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r shadow-xl lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shadow-none"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" aria-label="Navigasi utama">

            {{-- Header Logo / Brand --}}
            <div class="sidebar-brand-header flex min-h-[4.5rem] items-center justify-between border-b border-[var(--sidebar-border)] px-4 py-3">
                <a href="{{ route('dashboard') }}" class="sidebar-brand flex min-w-0 items-center gap-3" title="CTC General Ledger">
                    <img src="{{ asset('images/Logo_CTC.png') }}" alt="Logo CTC" class="sidebar-brand-logo filter drop-shadow-xs">
                    <span class="sidebar-brand-copy min-w-0">
                        <span class="block truncate text-sm font-extrabold tracking-tight" style="color: var(--sidebar-brand-text)">SIK & Payroll</span>
                        <span class="mt-0.5 block truncate text-[10px] font-semibold uppercase tracking-wider text-[#176B45] dark:text-neutral-400">CTC General Ledger</span>
                    </span>
                </a>

                {{-- Toggle Desktop --}}
                <button type="button" @click="toggleCollapse()" title="Toggle Size Sidebar"
                    class="btn-press hidden h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-[var(--border-color)] bg-[var(--surface)] text-[var(--text-secondary)] transition hover:text-[var(--text-primary)] focus:outline-none lg:flex">
                    <i class="fa-solid fa-bars-staggered text-[11px]"></i>
                </button>

                {{-- Close Mobile --}}
                <button type="button" @click="sidebarOpen = false"
                    class="btn-press rounded-lg p-1.5 text-[var(--text-secondary)] hover:text-[var(--text-primary)] lg:hidden">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            {{-- Menu Navigasi --}}
            <nav id="sidebar-nav-container" x-ref="sidebarNav"
                @scroll.debounce.100ms="sessionStorage.setItem('ctc_sidebar_scroll', $event.target.scrollTop)"
                class="min-h-0 flex-1 space-y-1 overflow-y-auto sidebar-scroll px-3 py-4" aria-label="Menu aplikasi">

                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif
                    class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold">
                    <i class="sidebar-icon fa-solid fa-gauge-high text-sm w-5 text-center shrink-0"></i>
                    <span class="sidebar-label">Dashboard</span>
                    <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-neutral-700 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                        Dashboard Utama
                    </div>
                </a>

                {{-- Master Data --}}
                @role('admin')
                    <p class="sidebar-section-label px-3 pt-5 pb-1.5 text-[10px]">Master Data</p>

                    <a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold">
                        <i class="sidebar-icon fa-solid fa-users-gear text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Manajemen User</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-neutral-700 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Manajemen User
                        </div>
                    </a>

                    <a href="{{ route('admin.chart-of-accounts.index') }}" @if(request()->routeIs('admin.chart-of-accounts.*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold">
                        <i class="sidebar-icon fa-solid fa-folder-tree text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Rekening (COA)</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-neutral-700 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Rekening (COA)
                        </div>
                    </a>
                @endrole

                {{-- Akuntansi & Transaksi --}}
                @hasanyrole('admin|akuntan')
                    <p class="sidebar-section-label px-3 pt-5 pb-1.5 text-[10px]">Akuntansi & Transaksi</p>

                    <a href="{{ route('admin.journals.index') }}" @if(request()->routeIs('admin.journals.index')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold">
                        <i class="sidebar-icon fa-solid fa-book-journal-whills text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Jurnal Umum</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-neutral-700 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Jurnal Umum
                        </div>
                    </a>

                    <a href="{{ route('admin.journals.reversals.index') }}" @if(request()->routeIs('admin.journals.reversals.index')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold">
                        <i class="sidebar-icon fa-solid fa-arrow-rotate-left text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Jurnal Pembalikan</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-neutral-700 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Jurnal Pembalikan
                        </div>
                    </a>

                    <a href="{{ route('admin.bank-reconciliations.index') }}" @if(request()->routeIs('admin.bank-reconciliations.*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold">
                        <i class="sidebar-icon fa-solid fa-building-columns text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Rekonsiliasi Bank</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-neutral-700 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Rekonsiliasi Bank
                        </div>
                    </a>

                    <a href="{{ route('admin.adjustments.index') }}" @if(request()->routeIs('admin.adjustments.*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold">
                        <i class="sidebar-icon fa-solid fa-sliders text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Jurnal Penyesuaian</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-neutral-700 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Jurnal Penyesuaian
                        </div>
                    </a>

                    <a href="{{ route('admin.taxes.index') }}" @if(request()->routeIs('admin.taxes.*', 'admin.reports.tax*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold">
                        <i class="sidebar-icon fa-solid fa-file-invoice-dollar text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Perpajakan</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-neutral-700 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Perpajakan (PPN & PPh)
                        </div>
                    </a>
                @endhasanyrole

                {{-- Operasional --}}
                @hasanyrole('admin|akuntan|staff')
                    <p class="sidebar-section-label px-3 pt-5 pb-1.5 text-[10px]">Operasional</p>

                    <a href="{{ route('admin.cash-transactions.index') }}" @if(request()->routeIs('admin.cash-transactions.*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold">
                        <i class="sidebar-icon fa-solid fa-wallet text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Transaksi Kas</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-neutral-700 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Transaksi Kas & Bank
                        </div>
                    </a>

                    <a href="{{ route('admin.payrolls.index') }}" @if(request()->routeIs('admin.payrolls.*', 'admin.reports.payroll*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold">
                        <i class="sidebar-icon fa-solid fa-money-check-dollar text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Penggajian (Payroll)</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-neutral-700 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Penggajian (Payroll)
                        </div>
                    </a>
                @endhasanyrole

                {{-- Laporan Keuangan --}}
                @hasanyrole('admin|akuntan')
                    <p class="sidebar-section-label px-3 pt-5 pb-1.5 text-[10px]">Laporan Keuangan</p>

                    <a href="{{ route('admin.reports.general-ledger') }}" @if(request()->routeIs('admin.reports.general-ledger*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold">
                        <i class="sidebar-icon fa-solid fa-book text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Buku Besar</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-neutral-700 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Laporan Buku Besar
                        </div>
                    </a>

                    <a href="{{ route('admin.reports.profit-loss') }}" @if(request()->routeIs('admin.reports.profit-loss*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold">
                        <i class="sidebar-icon fa-solid fa-chart-line text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Laba Rugi</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-neutral-700 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Laporan Laba Rugi
                        </div>
                    </a>

                    <a href="{{ route('admin.reports.balance-sheet') }}" @if(request()->routeIs('admin.reports.balance-sheet*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold">
                        <i class="sidebar-icon fa-solid fa-scale-balanced text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Neraca</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-neutral-700 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Laporan Neraca
                        </div>
                    </a>
                @endhasanyrole
            </nav>

            {{-- Profil User Bottom Card --}}
            <div class="border-t border-[var(--sidebar-border)] p-3 shrink-0">
                <div class="sidebar-user-card flex items-center justify-between rounded-xl p-2 transition-colors"
                     style="background-color: var(--user-card-bg); border: 1px solid var(--user-card-border);">
                    <div class="sidebar-user-info flex items-center gap-2.5 min-w-0">
                        <div class="w-7 h-7 rounded-lg text-white font-extrabold flex items-center justify-center text-xs shrink-0 shadow-xs"
                             style="background-color: var(--user-card-avatar-bg);" title="{{ auth()->user()->name }}">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="sidebar-user-text min-w-0 leading-tight">
                            <p class="text-xs font-bold truncate" style="color: var(--user-card-name-text);">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] capitalize font-medium truncate" style="color: var(--text-secondary);">
                                {{ auth()->user()->getRoleNames()->first() ?? 'User' }}
                            </p>
                        </div>
                    </div>

                    {{-- Tombol Keluar Sesi MERAH --}}
                    <div class="flex items-center gap-1 shrink-0">
                        <form action="{{ route('logout') }}" method="POST" class="inline-flex">
                            @csrf
                            <button type="submit" title="Keluar Sesi"
                                class="btn-press w-7 h-7 rounded-lg flex items-center justify-center transition"
                                style="background-color: var(--logout-btn-bg); border: 1px solid var(--logout-btn-border); color: var(--logout-btn-text);">
                                <i class="fa-solid fa-right-from-bracket text-[10px]"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </aside>

        {{-- Main Area --}}
        <div class="flex min-h-screen min-w-0 flex-1 flex-col">

            {{-- Topbar Header --}}
            <header class="app-topbar sticky top-0 z-30 flex min-h-[4.25rem] items-center justify-between gap-2 border-b px-3.5 shadow-sm backdrop-blur sm:px-5 lg:px-7">
                <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                    <button type="button" @click="sidebarOpen = true" aria-label="Buka navigasi"
                        class="btn-press flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-[var(--border-color)] bg-[var(--surface)] text-[#176B45] dark:text-neutral-200 hover:bg-[var(--bg-main)] focus:outline-none lg:hidden">
                        <i class="fa-solid fa-bars text-base"></i>
                    </button>

                    <div class="min-w-0">
                        <p class="truncate text-xs font-bold text-[var(--text-primary)] sm:text-base">Sistem Keuangan & Payroll</p>
                        <p class="hidden truncate text-xs text-[var(--text-secondary)] md:block">CTC General Ledger — Dual Theme Edition</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 shrink-0">
                    {{-- Dual Theme Switcher Toggle (Light/Dark) --}}
                    <button type="button" @click="toggleTheme()" title="Ubah Tema (Light/Dark)"
                        class="btn-press relative flex h-9 w-9 items-center justify-center rounded-xl border border-[var(--border-color)] bg-[var(--surface)] text-[var(--text-primary)] hover:bg-[var(--bg-main)] focus:outline-none">
                        <i x-show="!darkMode" class="fa-solid fa-moon text-sm"></i>
                        <i x-show="darkMode" class="fa-solid fa-sun text-sm text-amber-400" x-cloak></i>
                    </button>

                    <div class="h-5 w-px bg-[var(--border-color)] hidden sm:block"></div>

                    {{-- Badge User Topbar Kanan Atas (Container Abu-Abu di Light Mode, Font Admin Putih & Samping Tetap Hijau CTC) --}}
                    @php($activeRole = auth()->user()->getRoleNames()->first() ?? session('active_role', 'GUEST'))
                    <div class="inline-flex items-center gap-2 rounded-xl border border-gray-300 dark:border-emerald-800/50 bg-gray-200 dark:bg-emerald-950/60 px-3 py-1.5 text-xs font-semibold shadow-2xs transition-colors">
                        <span class="hidden sm:inline-block font-bold text-gray-800 dark:text-emerald-300 text-xs max-w-[140px] md:max-w-[200px] lg:max-w-none truncate">
                            Halo, {{ auth()->user()->name }}
                        </span>
                        <span class="rounded-md bg-[#176B45] dark:bg-emerald-500 text-white dark:text-slate-950 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wider shrink-0 shadow-xs">
                            {{ str_contains(strtolower($activeRole), 'admin') ? 'Admin' : $activeRole }}
                        </span>
                    </div>
                </div>
            </header>

            {{-- Content Area --}}
            <main id="app-main" class="feature-page min-w-0 flex-1 overflow-x-hidden">
                @yield('content')
            </main>

            <footer class="border-t border-[var(--border-color)] bg-[var(--surface)] px-4 py-3 text-center text-xs text-[var(--text-secondary)] sm:px-6">
                CTC General Ledger <span class="mx-1">&middot;</span> Sistem Keuangan & Payroll
            </footer>
        </div>
    </div>

    @yield('scripts')
</body>
</html>