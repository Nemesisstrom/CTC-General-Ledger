<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard - Sistem Keuangan & Payroll CTC')</title>

    {{-- Script Eksekusi Tercepat sebelum HTML/CSS digambar --}}
    <script>
        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>

    <!-- Font Awesome Icons & Tailwind CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        html { scroll-behavior: smooth; }
        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            overflow-x: clip;
        }

        /* Sidebar scrollbar */
        .sidebar-scroll {
            min-width: 0;
            overflow-x: hidden;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(23, 107, 69, 0.25) transparent;
        }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(23, 107, 69, 0.25);
            border-radius: 99px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover { background: rgba(23, 107, 69, 0.55); }

        /* Sidebar sizing & transition */
        .app-sidebar {
            box-sizing: border-box;
            flex: 0 0 auto;
            min-width: 0;
            overflow-x: hidden;
            transition: width 220ms cubic-bezier(0.4, 0, 0.2, 1), transform 250ms ease;
        }
        .app-topbar { transition: width 220ms cubic-bezier(0.4, 0, 0.2, 1); }
        .app-shell, .app-shell > .flex-1, #app-main {
            min-width: 0;
            max-width: 100%;
        }

        /* Navigation items */
        .sidebar-link {
            box-sizing: border-box;
            display: flex;
            align-items: center;
            min-width: 0;
            max-width: 100%;
            overflow: hidden;
            white-space: nowrap;
            gap: 0.75rem;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
            transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease,
                        box-shadow 0.2s ease, padding 220ms cubic-bezier(0.4, 0, 0.2, 1),
                        gap 220ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar-link:hover { transform: translateX(3px); }

        .sidebar-icon {
            flex: 0 0 1.25rem;
            width: 1.25rem;
            text-align: center;
            transition: transform 0.2s ease, color 0.2s ease;
        }
        .sidebar-link:hover .sidebar-icon { transform: scale(1.1); }

        /* Smooth Collapse Teks */
        .sidebar-label, .sidebar-brand-copy, .sidebar-user-text {
            display: inline-block;
            min-width: 0;
            max-width: 180px;
            opacity: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            transition: max-width 220ms cubic-bezier(0.4, 0, 0.2, 1), opacity 160ms ease,
                        margin 220ms ease, padding 220ms ease;
        }

        .sidebar-section-label {
            display: block;
            min-width: 0;
            max-height: 2.5rem;
            opacity: 1;
            overflow: hidden;
            white-space: nowrap;
            transition: max-height 220ms cubic-bezier(0.4, 0, 0.2, 1), opacity 150ms ease,
                        padding 220ms cubic-bezier(0.4, 0, 0.2, 1), margin 220ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Brand & Logo Clean Styling */
        .sidebar-brand-header {
            flex: 0 0 auto;
            min-width: 0;
            overflow: hidden;
            transition: padding 220ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-brand {
            flex: 1 1 auto;
            min-width: 0;
            overflow: hidden;
            transition: gap 220ms ease;
        }

        .sidebar-brand-logo {
            display: block;
            flex: 0 0 auto;
            width: 2.375rem;
            height: 2.375rem;
            object-fit: contain;
            transition: transform 220ms ease, width 220ms ease, height 220ms ease;
        }

        .sidebar-user-card {
            box-sizing: border-box;
            width: 100%;
            transition: padding 220ms cubic-bezier(0.4, 0, 0.2, 1), justify-content 220ms ease, gap 220ms ease;
        }

        @media (min-width: 1024px) {
            .sidebar-collapsed .app-sidebar { width: 5.25rem !important; }
        }

        .sidebar-collapsed .fa-bars-staggered { transform: rotate(180deg); }
        .sidebar-collapsed .app-sidebar { overflow-x: hidden; }

        .sidebar-collapsed .sidebar-section-label {
            max-height: 0 !important;
            opacity: 0 !important;
            padding: 0 !important;
            margin: 0 !important;
            pointer-events: none;
        }

        .sidebar-collapsed .sidebar-label,
        .sidebar-collapsed .sidebar-brand-copy,
        .sidebar-collapsed .sidebar-user-text {
            max-width: 0 !important;
            opacity: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            pointer-events: none;
        }

        /* Penataan Header & Card Saat Collapsed */
        .sidebar-collapsed .sidebar-brand-header {
            justify-content: space-between !important;
            padding-left: 0.625rem !important;
            padding-right: 0.625rem !important;
        }

        .sidebar-collapsed .sidebar-brand {
            flex: 0 0 auto !important;
            gap: 0 !important;
        }

        .sidebar-collapsed .sidebar-user-card {
            justify-content: center !important;
            gap: 0.35rem !important;
            padding: 0.375rem !important;
        }

        .sidebar-collapsed .sidebar-link {
            justify-content: flex-start;
            gap: 0;
            width: 100%;
            padding-left: 1.125rem;
            padding-right: 0;
            overflow: visible;
            transform: none;
        }
        .sidebar-collapsed .sidebar-link:hover { transform: scale(1.04); }
        .sidebar-collapsed .sidebar-icon { flex: 0 0 1.25rem; }
        .sidebar-collapsed .sidebar-tooltip { display: block; z-index: 100; }
        .sidebar-collapsed .sidebar-link[aria-current="page"] { box-shadow: inset 3px 0 0 #176B45; }

        .btn-press { transition: transform 0.15s ease, background-color 0.15s ease; }
        .btn-press:active { transform: scale(0.96); }

        .sidebar-link[aria-current="page"] {
            color: #176B45;
            background: #E8F4EC;
            box-shadow: inset 3px 0 0 #176B45;
            font-weight: 700;
        }
        .sidebar-link[aria-current="page"] .sidebar-icon { color: #176B45; }
        .sidebar-link:not([aria-current="page"]):hover { background: #F3F7F4; color: #104B35; }

        @media screen {
            .feature-page {
                --feature-primary: #176B45;
                --feature-dark: #104B35;
                --feature-light: #E8F4EC;
                --feature-surface: #FFFFFF;
                --feature-text: #202B25;
                --feature-muted: #68756D;
                --feature-border: #E1E8E2;
                min-width: 0;
                max-width: 100%;
                color: var(--feature-text);
            }
            .feature-page h1, .feature-page h2, .feature-page h3 { color: var(--feature-text); overflow-wrap: anywhere; }
            .feature-page .bg-white { border-color: var(--feature-border); }
            .feature-page .bg-white.rounded-xl { border-width: 1px; box-shadow: 0 1px 3px rgb(16 75 53 / 7%); }
            .feature-page [class~="bg-blue-600"], .feature-page [class~="bg-emerald-600"],
            .feature-page [class~="bg-green-600"], .feature-page [class~="bg-indigo-600"] { background-color: var(--feature-primary); }
            .feature-page [class~="hover:bg-blue-700"]:hover, .feature-page [class~="hover:bg-emerald-700"]:hover,
            .feature-page [class~="hover:bg-green-700"]:hover, .feature-page [class~="hover:bg-indigo-700"]:hover { background-color: var(--feature-dark); }
            .feature-page [class~="bg-blue-50"], .feature-page [class~="bg-emerald-50"], .feature-page [class~="bg-green-50"],
            .feature-page [class~="bg-green-100"], .feature-page [class~="bg-purple-100"], .feature-page [class~="bg-gray-200"] { background-color: var(--feature-light); }
            .feature-page [class~="text-blue-600"], .feature-page [class~="text-blue-700"], .feature-page [class~="text-blue-800"],
            .feature-page [class~="text-emerald-600"], .feature-page [class~="text-emerald-700"], .feature-page [class~="text-emerald-800"],
            .feature-page [class~="text-green-600"], .feature-page [class~="text-green-700"], .feature-page [class~="text-green-800"],
            .feature-page [class~="text-purple-700"], .feature-page [class~="text-purple-800"], .feature-page [class~="text-gray-700"] { color: var(--feature-primary); }
            .feature-page [class~="bg-gray-800"] { background-color: var(--feature-dark); }
            .feature-page [class~="hover:bg-gray-900"]:hover { background-color: #0B3C2A; }
            .feature-page [class~="bg-gray-50"], .feature-page [class~="bg-gray-100"] { background-color: #F6F8F6; }
            .feature-page [class~="border-gray-100"], .feature-page [class~="border-gray-200"], .feature-page [class~="border-gray-300"] { border-color: var(--feature-border); }
            .feature-page input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
            .feature-page select, .feature-page textarea {
                min-width: 0; max-width: 100%;
                border-color: var(--feature-border); border-radius: 0.625rem; color: var(--feature-text);
            }
            .feature-page input:focus, .feature-page select:focus, .feature-page textarea:focus {
                border-color: var(--feature-primary);
                outline: 2px solid rgb(23 107 69 / 15%);
                outline-offset: 1px;
            }
            .feature-page table { width: 100%; border-collapse: collapse; }
            .feature-page thead, .feature-page tfoot { background-color: #F6F8F6; }
            .feature-page th { color: var(--feature-muted); font-weight: 700; }
            .feature-page td, .feature-page th { border-color: var(--feature-border); }
            .feature-page .overflow-x-auto { max-width: 100%; overscroll-behavior-x: contain; }
        }
    </style>

    @yield('styles')
</head>

<body class="min-h-screen overflow-x-hidden bg-[#F6F8F6] text-[#202B25] antialiased select-none"
    x-data="{
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('sidebar_collapsed') === 'true',
        toggleCollapse() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
            localStorage.setItem('sidebar_collapsed', this.sidebarCollapsed);
            if (this.sidebarCollapsed) {
                document.documentElement.classList.add('sidebar-collapsed');
            } else {
                document.documentElement.classList.remove('sidebar-collapsed');
            }
        }
    }"
    @keydown.escape.window="sidebarOpen = false">

    {{-- Mobile Sidebar Backdrop --}}
    <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false"
        x-transition:enter="transition-opacity ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="app-sidebar-backdrop fixed inset-0 z-40 bg-[#104B35]/35 backdrop-blur-[2px] lg:hidden" aria-hidden="true">
    </div>

    <div class="app-shell flex min-h-screen w-full">

        {{-- MAIN SIDEBAR --}}
        <aside id="main-navigation"
            class="app-sidebar fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-[#E1E8E2] bg-white text-[#202B25] shadow-xl lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:shadow-none"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" aria-label="Navigasi utama">

            {{-- Header Logo / Brand --}}
            <div class="sidebar-brand-header flex min-h-[4.5rem] items-center justify-between border-b border-[#E1E8E2] px-4 py-3">
                <a href="{{ route('dashboard') }}" class="sidebar-brand flex min-w-0 items-center gap-3" title="CTC General Ledger">
                    <img src="{{ asset('images/Logo_CTC.png') }}" alt="Logo CTC" class="sidebar-brand-logo filter drop-shadow-xs">
                    <span class="sidebar-brand-copy min-w-0">
                        <span class="block truncate text-sm font-extrabold tracking-tight text-[#202B25]">SIK & Payroll</span>
                        <span class="mt-0.5 block truncate text-[10px] font-semibold uppercase tracking-wider text-[#176B45]">CTC General Ledger</span>
                    </span>
                </a>

                {{-- Toggle Desktop --}}
                <button type="button" @click="toggleCollapse()" title="Toggle Size Sidebar"
                    class="btn-press hidden h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-[#526057] transition hover:bg-[#E8F4EC] hover:text-[#176B45] focus:outline-none lg:flex">
                    <i class="fa-solid fa-bars-staggered text-[11px] transition-transform duration-200"></i>
                </button>

                {{-- Close Mobile --}}
                <button type="button" @click="sidebarOpen = false"
                    class="btn-press rounded-lg p-1.5 text-[#68756D] hover:bg-gray-100 hover:text-[#104B35] lg:hidden">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            {{-- Menu Navigasi --}}
            <nav id="sidebar-nav-container" x-ref="sidebarNav"
                @scroll.debounce.100ms="sessionStorage.setItem('ctc_sidebar_scroll', $event.target.scrollTop)"
                class="min-h-0 flex-1 space-y-1 overflow-y-auto sidebar-scroll px-3 py-4" aria-label="Menu aplikasi">

                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif
                    class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-[#176B45] {{ request()->routeIs('dashboard') ? 'bg-[#E8F4EC] font-bold text-[#176B45]' : 'text-[#526057]' }}">
                    <i class="sidebar-icon fa-solid fa-gauge-high text-sm w-5 text-center shrink-0"></i>
                    <span class="sidebar-label">Dashboard</span>
                    <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-emerald-500/30 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                        Dashboard Utama
                    </div>
                </a>

                {{-- Master Data --}}
                @role('admin')
                    <p class="sidebar-section-label px-3 pt-5 pb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-[#89958D]">Master Data</p>

                    <a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold transition {{ request()->routeIs('admin.users.*') ? 'bg-[#E8F4EC] font-bold text-[#176B45]' : 'text-[#526057]' }}">
                        <i class="sidebar-icon fa-solid fa-users-gear text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Manajemen User</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-emerald-500/30 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Manajemen User
                        </div>
                    </a>

                    <a href="{{ route('admin.chart-of-accounts.index') }}" @if(request()->routeIs('admin.chart-of-accounts.*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold transition {{ request()->routeIs('admin.chart-of-accounts.*') ? 'bg-[#E8F4EC] font-bold text-[#176B45]' : 'text-[#526057]' }}">
                        <i class="sidebar-icon fa-solid fa-folder-tree text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Rekening (COA)</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-emerald-500/30 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Rekening (COA)
                        </div>
                    </a>
                @endrole

                {{-- Akuntansi & Transaksi --}}
                @hasanyrole('admin|akuntan')
                    <p class="sidebar-section-label px-3 pt-5 pb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-[#89958D]">Akuntansi & Transaksi</p>

                    <a href="{{ route('admin.journals.index') }}" @if(request()->routeIs('admin.journals.index')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold transition {{ request()->routeIs('admin.journals.index') ? 'bg-[#E8F4EC] font-bold text-[#176B45]' : 'text-[#526057]' }}">
                        <i class="sidebar-icon fa-solid fa-book-journal-whills text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Jurnal Umum</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-emerald-500/30 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Jurnal Umum
                        </div>
                    </a>

                    <a href="{{ route('admin.journals.reversals.index') }}" @if(request()->routeIs('admin.journals.reversals.index')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold transition {{ request()->routeIs('admin.journals.reversals.index') ? 'bg-[#E8F4EC] font-bold text-[#176B45]' : 'text-[#526057]' }}">
                        <i class="sidebar-icon fa-solid fa-arrow-rotate-left text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Jurnal Pembalikan</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-emerald-500/30 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Jurnal Pembalikan
                        </div>
                    </a>

                    <a href="{{ route('admin.bank-reconciliations.index') }}" @if(request()->routeIs('admin.bank-reconciliations.*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold transition {{ request()->routeIs('admin.bank-reconciliations.*') ? 'bg-[#E8F4EC] font-bold text-[#176B45]' : 'text-[#526057]' }}">
                        <i class="sidebar-icon fa-solid fa-building-columns text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Rekonsiliasi Bank</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-emerald-500/30 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Rekonsiliasi Bank
                        </div>
                    </a>

                    <a href="{{ route('admin.adjustments.index') }}" @if(request()->routeIs('admin.adjustments.*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold transition {{ request()->routeIs('admin.adjustments.*') ? 'bg-[#E8F4EC] font-bold text-[#176B45]' : 'text-[#526057]' }}">
                        <i class="sidebar-icon fa-solid fa-sliders text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Jurnal Penyesuaian</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-emerald-500/30 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Jurnal Penyesuaian
                        </div>
                    </a>

                    <a href="{{ route('admin.taxes.index') }}" @if(request()->routeIs('admin.taxes.*', 'admin.reports.tax*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold transition {{ request()->routeIs('admin.taxes.*', 'admin.reports.tax*') ? 'bg-[#E8F4EC] font-bold text-[#176B45]' : 'text-[#526057]' }}">
                        <i class="sidebar-icon fa-solid fa-file-invoice-dollar text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Perpajakan</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-emerald-500/30 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Perpajakan (PPN & PPh)
                        </div>
                    </a>
                @endhasanyrole

                {{-- Operasional (Diperbarui untuk Admin, Akuntan, dan Staff) --}}
                @hasanyrole('admin|akuntan|staff')
                    <p class="sidebar-section-label px-3 pt-5 pb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-[#89958D]">Operasional</p>

                    <a href="{{ route('admin.cash-transactions.index') }}" @if(request()->routeIs('admin.cash-transactions.*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold transition {{ request()->routeIs('admin.cash-transactions.*') ? 'bg-[#E8F4EC] font-bold text-[#176B45]' : 'text-[#526057]' }}">
                        <i class="sidebar-icon fa-solid fa-wallet text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Transaksi Kas</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-emerald-500/30 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Transaksi Kas & Bank
                        </div>
                    </a>

                    <a href="{{ route('admin.payrolls.index') }}" @if(request()->routeIs('admin.payrolls.*', 'admin.reports.payroll*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold transition {{ request()->routeIs('admin.payrolls.*', 'admin.reports.payroll*') ? 'bg-[#E8F4EC] font-bold text-[#176B45]' : 'text-[#526057]' }}">
                        <i class="sidebar-icon fa-solid fa-money-check-dollar text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Penggajian (Payroll)</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-emerald-500/30 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Penggajian (Payroll)
                        </div>
                    </a>
                @endhasanyrole

                {{-- Laporan Keuangan --}}
                @hasanyrole('admin|akuntan')
                    <p class="sidebar-section-label px-3 pt-5 pb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-[#89958D]">Laporan Keuangan</p>

                    <a href="{{ route('admin.reports.general-ledger') }}" @if(request()->routeIs('admin.reports.general-ledger*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold transition {{ request()->routeIs('admin.reports.general-ledger*') ? 'bg-[#E8F4EC] font-bold text-[#176B45]' : 'text-[#526057]' }}">
                        <i class="sidebar-icon fa-solid fa-book text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Buku Besar</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-emerald-500/30 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Laporan Buku Besar
                        </div>
                    </a>

                    <a href="{{ route('admin.reports.profit-loss') }}" @if(request()->routeIs('admin.reports.profit-loss*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold transition {{ request()->routeIs('admin.reports.profit-loss*') ? 'bg-[#E8F4EC] font-bold text-[#176B45]' : 'text-[#526057]' }}">
                        <i class="sidebar-icon fa-solid fa-chart-line text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Laba Rugi</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-emerald-500/30 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Laporan Laba Rugi
                        </div>
                    </a>

                    <a href="{{ route('admin.reports.balance-sheet') }}" @if(request()->routeIs('admin.reports.balance-sheet*')) aria-current="page" @endif
                       class="sidebar-link relative group flex items-center rounded-xl py-2.5 text-xs font-semibold transition {{ request()->routeIs('admin.reports.balance-sheet*') ? 'bg-[#E8F4EC] font-bold text-[#176B45]' : 'text-[#526057]' }}">
                        <i class="sidebar-icon fa-solid fa-scale-balanced text-sm w-5 text-center shrink-0"></i>
                        <span class="sidebar-label">Neraca</span>
                        <div class="sidebar-tooltip hidden absolute left-full ml-3 px-3 py-1.5 bg-slate-900 border border-emerald-500/30 text-white text-xs font-semibold rounded-xl shadow-xl whitespace-nowrap pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-200 z-50">
                            Laporan Neraca
                        </div>
                    </a>
                @endhasanyrole
            </nav>

            {{-- Profil User Bottom Card --}}
            <div class="border-t border-[#E1E8E2] p-3 shrink-0">
                <div class="sidebar-user-card flex items-center justify-between rounded-xl bg-gray-50 border border-gray-200/80 p-2">
                    <div class="sidebar-user-info flex items-center gap-2.5 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-[#176B45] text-white font-extrabold flex items-center justify-center text-xs shrink-0 shadow-xs" title="{{ auth()->user()->name }}">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="sidebar-user-text min-w-0 leading-tight">
                            <p class="text-xs font-bold text-[#202B25] truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] text-[#68756D] capitalize font-medium truncate">
                                {{ auth()->user()->getRoleNames()->first() ?? 'User' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                        <form action="{{ route('logout') }}" method="POST" class="inline-flex">
                            @csrf
                            <button type="submit" title="Keluar Sesi"
                                class="btn-press w-7 h-7 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 flex items-center justify-center transition border border-red-200">
                                <i class="fa-solid fa-right-from-bracket text-[10px]"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <script>
                (function() {
                    const nav = document.getElementById('sidebar-nav-container');
                    const savedScroll = sessionStorage.getItem('ctc_sidebar_scroll');
                    if (nav && savedScroll) { nav.scrollTop = savedScroll; }
                })();
            </script>
        </aside>

        {{-- Main Area --}}
        <div class="flex min-h-screen min-w-0 flex-1 flex-col">

            {{-- Topbar Header --}}
            <header class="app-topbar sticky top-0 z-30 flex min-h-[4.25rem] items-center justify-between gap-2 border-b border-[#E1E8E2] bg-white/95 px-3.5 shadow-sm backdrop-blur sm:px-5 lg:px-7">
                <div class="flex min-w-0 items-center gap-2.5 sm:gap-3">
                    <button type="button" @click="sidebarOpen = true" aria-label="Buka navigasi"
                        class="btn-press flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-[#176B45] transition hover:bg-[#E8F4EC] focus:outline-none lg:hidden">
                        <i class="fa-solid fa-bars text-base"></i>
                    </button>

                    <div class="min-w-0">
                        <p class="truncate text-xs font-bold text-[#202B25] sm:text-base">Sistem Keuangan & Payroll</p>
                        <p class="hidden truncate text-xs text-[#68756D] md:block">CTC General Ledger</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 shrink-0">
                    @php($activeRole = auth()->user()->getRoleNames()->first() ?? session('active_role', 'GUEST'))
                    <div class="inline-flex items-center gap-2 rounded-xl border border-[#D5E8DA] bg-[#E8F4EC] px-3 py-1.5 text-xs font-semibold text-[#176B45]">
                        <span class="hidden sm:inline-block font-medium text-[#202B25] text-xs max-w-[140px] md:max-w-[200px] lg:max-w-none truncate">
                            Halo, {{ auth()->user()->name }}
                        </span>
                        <span class="rounded-md bg-[#176B45] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white shrink-0">
                            {{ str_contains(strtolower($activeRole), 'admin') ? 'Admin' : $activeRole }}
                        </span>
                    </div>
                </div>
            </header>

            {{-- Content Area --}}
            <main id="app-main" class="min-w-0 flex-1 overflow-x-hidden">
                @yield('content')
            </main>

            <footer class="border-t border-[#E1E8E2] bg-white px-4 py-3 text-center text-xs text-[#89958D] sm:px-6">
                CTC General Ledger <span class="mx-1">·</span> Sistem Keuangan & Payroll
            </footer>
        </div>
    </div>

    @yield('scripts')
</body>
</html>