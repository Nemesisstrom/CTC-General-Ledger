@extends('layouts.app')

@section('title', 'Jurnal Penyesuaian & Penyusutan')

@section('content')
    <div class="feature-page mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Header Hero Banner - Jurnal Penyesuaian & Penyusutan -->
        <section class="dashboard-enter relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-[#104B35] via-[#176B45] to-[#248052] px-4 py-6 text-white shadow-lg shadow-[#104B35]/10 sm:px-8 sm:py-8 mb-6">
            <div class="pointer-events-none absolute -right-12 -top-24 -z-10 h-72 w-72 rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute -right-2 -top-14 -z-10 h-52 w-52 rounded-full border border-white/10"></div>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#C6E4D0]">CTC General Ledger</p>
                    <div class="mt-2 text-xl font-bold tracking-tight text-white sm:text-2xl" role="heading" aria-level="1">
                        Jurnal Penyesuaian & Penyusutan
                    </div>
                    <p class="mt-1 text-xs text-[#E8F4EC] sm:text-sm">
                        Pencatatan penyusutan aset tetap & penyesuaian akhir periode.
                    </p>
                </div>
                <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-[#104B35] shadow-sm transition hover:bg-[#E8F4EC] focus:outline-none focus:ring-2 focus:ring-white/50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </section>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Form Input Penyesuaian -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-800 border-b pb-3">Input Penyesuaian Baru</h2>
                <form action="{{ route('admin.adjustments.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Tanggal Penyesuaian</label>
                        <input type="date" name="adjustment_date" value="{{ old('adjustment_date', date('Y-m-d')) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Akun Beban / Debit</label>
                        <select name="expense_account_id" required class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">-- Pilih Akun Beban --</option>
                            @foreach($expenseAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('expense_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Akun Akumulasi / Kredit</label>
                        <select name="accumulated_account_id" required class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500">
                            <option value="">-- Pilih Akun Akumulasi/Kredit --</option>
                            @foreach($accumulatedAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('accumulated_account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name }} ({{ strtoupper($acc->type) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Input Nilai Penyesuaian dengan Format Titik Otomatis -->
                    <div x-data="{ 
                        rawAmount: '{{ old('amount') }}',
                        displayAmount: '{{ old('amount') ? number_format(old('amount'), 0, ',', '.') : '' }}',
                        formatInput(e) {
                            let numeric = e.target.value.replace(/\D/g, '');
                            this.rawAmount = numeric;
                            this.displayAmount = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
                        }
                    }">
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nilai Penyesuaian</label>
                        <input 
                            type="text" 
                            inputmode="numeric"
                            x-model="displayAmount"
                            @input="formatInput($event)"
                            placeholder="0" 
                            required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500 font-mono font-semibold"
                        >
                        <input type="hidden" name="amount" :value="rawAmount">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Keterangan / Deskripsi</label>
                        <textarea name="description" rows="3" placeholder="Contoh: Penyusutan inventaris bulan ini..." required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">{{ old('description') }}</textarea>
                    </div>

                    <button type="submit" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-medium py-2.5 rounded-lg transition shadow-sm">
                        Posting Jurnal Penyesuaian
                    </button>
                </form>
            </div>

            <!-- Riwayat Jurnal Penyesuaian -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <div class="border-b pb-3">
                    <h2 class="text-lg font-bold text-gray-800">Riwayat Jurnal Penyesuaian</h2>
                </div>

                <div class="space-y-4">
                    @forelse($adjustments as $adj)
                        <div class="border border-gray-200 rounded-xl p-4 bg-gray-50/50 space-y-3 hover:border-emerald-200 transition-colors">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-gray-200 pb-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-xs text-emerald-800">{{ $adj->journal_number }}</span>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase {{ $adj->status === 'posted' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                        {{ strtoupper($adj->status) }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-3 self-end sm:self-auto">
                                    <span class="text-xs text-gray-500 font-mono">{{ $adj->journal_date }}</span>

                                    <!-- Tombol Reverse / Batal -->
                                    @if($adj->status !== 'reversed' && !str_starts_with($adj->journal_number, 'REV-'))
                                        <form action="{{ route('admin.adjustments.reverse', $adj->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan (reverse) jurnal penyesuaian ini?');">
                                            @csrf
                                            <button type="submit" class="text-xs bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-800 px-2.5 py-1 rounded-lg transition font-medium shadow-xs">
                                                Reverse
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>

                            <p class="text-xs text-gray-700 font-medium leading-relaxed">{{ $adj->description }}</p>

                            <div class="max-w-full overflow-x-auto rounded-lg border border-gray-200">
                                <table class="w-full text-xs text-left bg-white border-collapse">
                                    <thead>
                                        <tr class="border-b border-gray-200 bg-gray-50 text-gray-500 uppercase font-semibold tracking-wider">
                                            <th class="py-2 px-3">Akun</th>
                                            <th class="py-2 px-3 text-right">Debit</th>
                                            <th class="py-2 px-3 text-right">Kredit</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($adj->details as $d)
                                            <tr class="hover:bg-gray-50/70">
                                                <td class="py-2 px-3 text-gray-800 font-medium {{ $d->credit > 0 ? 'pl-6 text-gray-600' : '' }}">
                                                    {{ $d->account->code ?? $d->chartOfAccount->code ?? '-' }} - {{ $d->account->name ?? $d->chartOfAccount->name ?? '-' }}
                                                </td>
                                                <td class="py-2 px-3 text-right font-mono tabular-nums text-gray-700 whitespace-nowrap">
                                                    @if($d->debit > 0)
                                                        <span class="text-gray-400 font-sans text-[11px] mr-1">Rp</span>{{ number_format($d->debit, 0, ',', '.') }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="py-2 px-3 text-right font-mono tabular-nums text-gray-700 whitespace-nowrap">
                                                    @if($d->credit > 0)
                                                        <span class="text-gray-400 font-sans text-[11px] mr-1">Rp</span>{{ number_format($d->credit, 0, ',', '.') }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-400 italic">Belum ada jurnal penyesuaian.</div>
                    @endforelse
                </div>

                <!-- Pagination Laravel -->
                @if($adjustments->hasPages())
                    <div class="pt-3 border-t">
                        {{ $adjustments->withQueryString()->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection