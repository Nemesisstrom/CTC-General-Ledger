@extends('layouts.app')

@section('title', 'Jurnal Pembalikan (Reversal Ledger)')

@section('content')
    <div class="feature-page mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Header Hero Banner - Modul Jurnal Pembalikan -->
        <section class="dashboard-enter relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-[#104B35] via-[#176B45] to-[#248052] px-4 py-6 text-white shadow-lg shadow-[#104B35]/10 sm:px-8 sm:py-8 mb-6">
            <div class="pointer-events-none absolute -right-12 -top-24 -z-10 h-72 w-72 rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute -right-2 -top-14 -z-10 h-52 w-52 rounded-full border border-white/10"></div>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#C6E4D0]">CTC General Ledger</p>
                    <div class="mt-2 text-xl font-bold tracking-tight text-white sm:text-2xl" role="heading" aria-level="1">
                        Modul Jurnal Pembalikan
                    </div>
                    <p class="mt-1 text-xs text-[#E8F4EC] sm:text-sm">
                        Eksekusi pembatalan transaksi & penyesuaian balance saldo.
                    </p>
                </div>
                <a href="{{ route('admin.journals.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-[#104B35] shadow-sm transition hover:bg-[#E8F4EC] focus:outline-none focus:ring-2 focus:ring-white/50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Jurnal Umum
                </a>
            </div>
        </section>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Kolom Kiri: Jurnal Aktif yang Bisa Di-Reverse -->
            <div class="bg-white p-6 rounded-xl shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-gray-800 border-b pb-2">Daftar Jurnal Aktif (Posted)</h2>

                <div class="space-y-3">
                    @forelse($postedJournals as $j)
                        <div class="border rounded-xl p-4 bg-gray-50 space-y-2">
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="font-mono font-bold text-sm text-blue-600">{{ $j->journal_number }}</span>
                                    <p class="text-xs text-gray-400">{{ $j->journal_date }}</p>
                                </div>

                                <!-- Form Eksekusi Reverse -->
                                <form action="{{ route('admin.journals.reverse', $j->id) }}" method="POST" onsubmit="return confirm('Proses pembalikan jurnal {{ $j->journal_number }}?');">
                                    @csrf
                                    <button type="submit" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg font-semibold transition shadow-sm">
                                        Eksekusi Reversal
                                    </button>
                                </form>
                            </div>
                            <p class="text-xs text-gray-600 font-medium">{{ $j->description }}</p>
                        </div>
                    @empty
                        <p class="text-center py-6 text-gray-400 text-xs italic">Tidak ada jurnal aktif yang perlu dibatalkan.</p>
                    @endforelse
                </div>

                <div>{{ $postedJournals->links() }}</div>
            </div>

            <!-- Kolom Kanan: Riwayat Jurnal Pembalik -->
            <div class="bg-white p-6 rounded-xl shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-gray-800 border-b pb-2">Riwayat Pembalikan / Reversed</h2>

                <div class="space-y-3">
                    @forelse($reversedJournals as $rj)
                        <div class="border rounded-xl p-4 bg-gray-50 space-y-2 opacity-85">
                            <div class="flex justify-between items-center">
                                <span class="font-mono font-bold text-xs text-gray-700">{{ $rj->journal_number }}</span>
                                <span class="text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded font-bold uppercase">
                                    {{ $rj->status }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 font-medium">{{ $rj->description }}</p>
                        </div>
                    @empty
                        <p class="text-center py-6 text-gray-400 text-xs italic">Belum ada riwayat pembalikan jurnal.</p>
                    @endforelse
                </div>

                <div>{{ $reversedJournals->links() }}</div>
            </div>

        </div>
    </div>
@endsection
