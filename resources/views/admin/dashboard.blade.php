@extends('layouts.app')

@section('title', 'Dashboard - Sistem Keuangan & Payroll CTC')

@section('content')
    <div class="mx-auto max-w-7xl space-y-8 p-4 sm:p-6 lg:p-8">
        <section class="dashboard-enter relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-[#104B35] via-[#176B45] to-[#248052] px-6 py-7 text-white shadow-lg shadow-[#104B35]/10 sm:px-8 sm:py-9">
            <div class="pointer-events-none absolute -right-12 -top-24 -z-10 h-72 w-72 rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute -right-2 -top-14 -z-10 h-52 w-52 rounded-full border border-white/10"></div>
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#C6E4D0]">CTC General Ledger</p>
                <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Ringkasan Keuangan</h1>
                <p class="mt-2 text-sm leading-6 text-[#E8F4EC] sm:text-base">
                    Ikhtisar arus kas, hasil usaha, dan aktivitas keuangan terbaru.
                </p>
            </div>
        </section>

        <section aria-labelledby="financial-summary-heading" class="dashboard-enter space-y-4" style="animation-delay: 70ms">
            <div>
                <h2 id="financial-summary-heading" class="text-lg font-bold tracking-tight text-[#202B25]">Ikhtisar Keuangan</h2>
                <p class="mt-1 text-sm text-[#68756D]">Ringkasan berdasarkan data keuangan yang tersedia.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="group min-w-0 rounded-2xl border border-[#E1E8E2] border-t-4 border-t-[#176B45] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-[#68756D]">Kas Masuk</p>
                            <p class="mt-3 break-words text-2xl font-bold tracking-tight text-[#176B45] sm:text-3xl">
                                Rp {{ number_format($data['kas_masuk'] ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#E8F4EC] text-[#176B45] transition group-hover:bg-[#D5EBDD]" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 0l-7 7m7-7l7 7"/></svg>
                        </span>
                    </div>
                </article>

                <article class="group min-w-0 rounded-2xl border border-[#E1E8E2] border-t-4 border-t-[#68756D] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-[#68756D]">Kas Keluar</p>
                            <p class="mt-3 break-words text-2xl font-bold tracking-tight text-[#202B25] sm:text-3xl">
                                Rp {{ number_format($data['kas_keluar'] ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#F6F8F6] text-[#68756D] transition group-hover:bg-[#E1E8E2]" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m0 0l7-7m-7 7l-7-7"/></svg>
                        </span>
                    </div>
                </article>

                <article class="group min-w-0 rounded-2xl border border-[#E1E8E2] border-t-4 {{ ($data['laba_rugi_bersih'] ?? 0) >= 0 ? 'border-t-[#176B45]' : 'border-t-red-500' }} bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-[#68756D]">Laba / Rugi Bersih</p>
                            <p class="mt-3 break-words text-2xl font-bold tracking-tight {{ ($data['laba_rugi_bersih'] ?? 0) >= 0 ? 'text-[#176B45]' : 'text-red-600' }} sm:text-3xl">
                                Rp {{ number_format($data['laba_rugi_bersih'] ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#E8F4EC] text-[#176B45] transition group-hover:bg-[#D5EBDD]" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6-6 4 4 8-8m0 0h-6m6 0v6"/></svg>
                        </span>
                    </div>
                </article>

                <article class="group min-w-0 rounded-2xl border border-[#E1E8E2] border-t-4 border-t-[#104B35] bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wider text-[#68756D]">Gaji Bulan Ini</p>
                            <p class="mt-3 break-words text-2xl font-bold tracking-tight text-[#104B35] sm:text-3xl">
                                Rp {{ number_format($data['gaji_bulan_ini'] ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#E8F4EC] text-[#104B35] transition group-hover:bg-[#D5EBDD]" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                </article>
            </div>
        </section>

        <section aria-labelledby="recent-activity-heading" class="dashboard-enter space-y-4" style="animation-delay: 140ms">
            <div>
                <h2 id="recent-activity-heading" class="text-lg font-bold tracking-tight text-[#202B25]">Aktivitas Terbaru</h2>
                <p class="mt-1 text-sm text-[#68756D]">Transaksi kas dan jurnal pembalikan yang tercatat.</p>
            </div>

            <div class="grid min-w-0 grid-cols-1 gap-5 xl:grid-cols-2">
                <section class="min-w-0 overflow-hidden rounded-2xl border border-[#E1E8E2] bg-white shadow-sm">
                    <div class="flex items-center gap-3 border-b border-[#E1E8E2] px-5 py-4 sm:px-6">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#E8F4EC] text-[#176B45]" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </span>
                        <div class="min-w-0">
                            <h3 class="truncate font-bold text-[#202B25]">Transaksi Kas Terbaru</h3>
                            <p class="mt-0.5 text-xs text-[#68756D]">Rincian transaksi kas</p>
                        </div>
                    </div>
                    <div class="max-w-full overflow-x-auto overscroll-x-contain">
                        <table class="w-full min-w-[600px] text-left">
                            <thead class="bg-[#F6F8F6]">
                                <tr class="text-[11px] font-semibold uppercase tracking-wider text-[#68756D]">
                                    <th scope="col" class="px-5 py-3 sm:px-6">No. Transaksi</th>
                                    <th scope="col" class="px-4 py-3">Tanggal</th>
                                    <th scope="col" class="px-4 py-3">Tipe</th>
                                    <th scope="col" class="px-5 py-3 text-right sm:px-6">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#E1E8E2] text-sm">
                                @forelse($data['transaksi_terakhir'] ?? [] as $trx)
                                    <tr class="transition-colors hover:bg-[#F6F8F6]">
                                        <td class="max-w-48 truncate px-5 py-4 font-mono text-xs font-medium text-[#202B25] sm:px-6">{{ $trx->transaction_number }}</td>
                                        <td class="whitespace-nowrap px-4 py-4 text-xs text-[#68756D]">{{ $trx->transaction_date }}</td>
                                        <td class="px-4 py-4">
                                            <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $trx->type === 'in' ? 'bg-[#E8F4EC] text-[#176B45]' : 'bg-[#F6F8F6] text-[#68756D]' }}">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $trx->type === 'in' ? 'bg-[#176B45]' : 'bg-[#68756D]' }}"></span>
                                                {{ $trx->type === 'in' ? 'Masuk' : 'Keluar' }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-4 text-right text-xs font-semibold text-[#202B25] sm:px-6">Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center">
                                            <p class="text-sm font-medium text-[#202B25]">Belum ada transaksi kas</p>
                                            <p class="mt-1 text-xs text-[#68756D]">Transaksi terbaru akan muncul di sini.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="min-w-0 overflow-hidden rounded-2xl border border-[#E1E8E2] bg-white shadow-sm">
                    <div class="flex items-center justify-between gap-3 border-b border-[#E1E8E2] px-5 py-4 sm:px-6">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#E8F4EC] text-[#176B45]" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            </span>
                            <div class="min-w-0">
                                <h3 class="truncate font-bold text-[#202B25]">Jurnal Pembalikan Terbaru</h3>
                                <p class="mt-0.5 text-xs text-[#68756D]">Aktivitas jurnal pembalikan</p>
                            </div>
                        </div>
                        @hasanyrole('admin|akuntan')('reverse-journals')
                            <a href="{{ route('admin.journals.reversals.index') }}" class="shrink-0 rounded-lg px-2 py-1.5 text-xs font-semibold text-[#176B45] transition hover:bg-[#E8F4EC] hover:text-[#104B35] focus:outline-none focus:ring-2 focus:ring-[#176B45]">
                                Buka modul
                            </a>
                        @endhasanyrole
                    </div>

                    <div class="space-y-3 p-4 sm:p-5">
                        @forelse($data['pembalikan_terakhir'] ?? [] as $rev)
                            <article class="rounded-xl border border-[#E1E8E2] bg-[#F6F8F6] p-4 transition-colors hover:border-[#C6D8CB]">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="max-w-full break-all font-mono text-xs font-bold text-[#176B45]">{{ $rev->journal_number }}</span>
                                    <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-[#68756D]">
                                        {{ $rev->status }}
                                    </span>
                                </div>
                                <p class="mt-3 break-words text-sm font-medium leading-5 text-[#202B25]">{{ $rev->description }}</p>
                                <p class="mt-2 break-words text-xs text-[#68756D]">{{ $rev->journal_date }} <span class="mx-1 text-[#E1E8E2]">|</span> Ref: {{ $rev->reference_number ?? '-' }}</p>
                            </article>
                        @empty
                            <div class="rounded-xl border border-dashed border-[#E1E8E2] px-5 py-12 text-center">
                                <p class="text-sm font-medium text-[#202B25]">Belum ada aktivitas pembalikan jurnal</p>
                                <p class="mt-1 text-xs text-[#68756D]">Jurnal pembalikan terbaru akan muncul di sini.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </section>
    </div>
@endsection
