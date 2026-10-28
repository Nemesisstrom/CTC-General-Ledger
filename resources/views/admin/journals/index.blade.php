@extends('layouts.app')

@section('title', 'Jurnal Umum')

@section('content')
    <div class="feature-page mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Header Hero Banner -->
        <section class="dashboard-enter relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-[#104B35] via-[#176B45] to-[#248052] px-4 py-6 text-white shadow-lg shadow-[#104B35]/10 sm:px-8 sm:py-8 mb-6">
            <div class="pointer-events-none absolute -right-12 -top-24 -z-10 h-72 w-72 rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute -right-2 -top-14 -z-10 h-52 w-52 rounded-full border border-white/10"></div>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#C6E4D0]">CTC General Ledger</p>
                    <div class="mt-2 text-xl font-bold tracking-tight text-white sm:text-2xl" role="heading" aria-level="1">
                        Jurnal Umum
                    </div>
                    <p class="mt-1 text-xs text-[#E8F4EC] sm:text-sm">
                        Pencatatan pembukuan berpasangan (Double-Entry Ledger)
                    </p>
                </div>
                <div class="flex items-center gap-2.5 flex-wrap shrink-0">
                    @hasanyrole('admin|akuntan')
                        <a href="{{ route('admin.journals.reversals.index') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-amber-400/20 text-amber-200 border border-amber-300/30 px-4 py-2.5 text-xs font-bold transition hover:bg-amber-400/30 backdrop-blur-sm focus:outline-none focus:ring-2 focus:ring-amber-300/50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Ke Jurnal Pembalikan
                        </a>
                    @endhasanyrole
                    <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-[#104B35] shadow-sm transition hover:bg-[#E8F4EC] focus:outline-none focus:ring-2 focus:ring-white/50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </section>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm font-medium">
                <ul class="list-disc list-inside space-y-1">
                    @if(session('error')) <li>{{ session('error') }}</li> @endif
                    @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Form Input Jurnal Manual -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-800 border-b pb-3">Buat Jurnal Manual</h2>
                <form action="{{ route('admin.journals.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Tanggal Jurnal</label>
                        <input type="date" name="journal_date" value="{{ old('journal_date', date('Y-m-d')) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">No. Referensi (Opsional)</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number') }}" placeholder="Misal: INV-001" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Keterangan Jurnal</label>
                        <textarea name="description" rows="2" placeholder="Deskripsi transaksi..." required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">{{ old('description') }}</textarea>
                    </div>

                    <!-- Baris Entri Rekening Double-Entry -->
                    <div class="border-t pt-3 space-y-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase">Baris Entri Rekening</label>

                        <!-- Baris 1 (Debit) -->
                        <div x-data="{ 
                            code: '',
                            rawDebit: '{{ old('items.0.debit') }}',
                            displayDebit: '{{ old('items.0.debit') ? number_format(old('items.0.debit'), 0, ',', '.') : '' }}',
                            formatDebit(e) {
                                let numeric = e.target.value.replace(/\D/g, '');
                                this.rawDebit = numeric;
                                this.displayDebit = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
                            }
                        }" class="p-3 bg-gray-50/70 rounded-lg border border-gray-200 space-y-2.5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Rekening Debit</label>
                                <select name="items[0][account_id]"
                                        @change="code = $event.target.options[$event.target.selectedIndex].dataset.code || ''"
                                        required class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500">
                                    <option value="" data-code="">-- Pilih Rekening Debit --</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}" data-code="{{ $acc->code }}" {{ old('items.0.account_id') == $acc->id ? 'selected' : '' }}>
                                            {{ $acc->parent_id ? '└─ ' : '' }}{{ $acc->name }} ({{ $acc->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 font-medium mb-0.5">No. Rekening</label>
                                <input type="text" x-model="code" readonly placeholder="Otomatis..." class="w-full px-2.5 py-1.5 border border-gray-200 bg-gray-100 rounded-lg text-xs font-mono font-semibold text-emerald-800 outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Debit (Rp)</label>
                                    <input 
                                        type="text" 
                                        inputmode="numeric"
                                        x-model="displayDebit"
                                        @input="formatDebit($event)"
                                        placeholder="0" 
                                        required 
                                        class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500 font-mono font-semibold"
                                    >
                                    <input type="hidden" name="items[0][debit]" :value="rawDebit">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-gray-400 mb-1">Kredit (Rp)</label>
                                    <input type="number" name="items[0][credit]" value="0" readonly class="w-full px-2.5 py-1.5 border border-gray-200 bg-gray-100 rounded-lg text-xs font-mono text-gray-400 cursor-not-allowed">
                                </div>
                            </div>
                        </div>

                        <!-- Baris 2 (Kredit) -->
                        <div x-data="{ 
                            code: '',
                            rawCredit: '{{ old('items.1.credit') }}',
                            displayCredit: '{{ old('items.1.credit') ? number_format(old('items.1.credit'), 0, ',', '.') : '' }}',
                            formatCredit(e) {
                                let numeric = e.target.value.replace(/\D/g, '');
                                this.rawCredit = numeric;
                                this.displayCredit = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
                            }
                        }" class="p-3 bg-gray-50/70 rounded-lg border border-gray-200 space-y-2.5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Rekening Kredit</label>
                                <select name="items[1][account_id]"
                                        @change="code = $event.target.options[$event.target.selectedIndex].dataset.code || ''"
                                        required class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500">
                                    <option value="" data-code="">-- Pilih Rekening Kredit --</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}" data-code="{{ $acc->code }}" {{ old('items.1.account_id') == $acc->id ? 'selected' : '' }}>
                                            {{ $acc->parent_id ? '└─ ' : '' }}{{ $acc->name }} ({{ $acc->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 font-medium mb-0.5">No. Rekening</label>
                                <input type="text" x-model="code" readonly placeholder="Otomatis..." class="w-full px-2.5 py-1.5 border border-gray-200 bg-gray-100 rounded-lg text-xs font-mono font-semibold text-emerald-800 outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[11px] font-medium text-gray-400 mb-1">Debit (Rp)</label>
                                    <input type="number" name="items[1][debit]" value="0" readonly class="w-full px-2.5 py-1.5 border border-gray-200 bg-gray-100 rounded-lg text-xs font-mono text-gray-400 cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-600 mb-1">Kredit (Rp)</label>
                                    <input 
                                        type="text" 
                                        inputmode="numeric"
                                        x-model="displayCredit"
                                        @input="formatCredit($event)"
                                        placeholder="0" 
                                        required 
                                        class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500 font-mono font-semibold"
                                    >
                                    <input type="hidden" name="items[1][credit]" :value="rawCredit">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-medium py-2.5 rounded-lg transition shadow-sm">
                        Posting Jurnal
                    </button>
                </form>
            </div>

            <!-- Tabel Daftar Jurnal Umum -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <div class="border-b pb-3">
                    <h2 class="text-lg font-bold text-gray-800">Daftar Jurnal Umum</h2>
                </div>

                <div class="space-y-4">
                    @forelse($journals as $j)
                        <div class="border border-gray-200 rounded-xl p-4 bg-gray-50/50 space-y-3 hover:border-emerald-200 transition-colors">
                            <div class="flex justify-between items-start border-b border-gray-200 pb-2.5">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-xs text-emerald-800">{{ $j->journal_number }}</span>
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wide uppercase {{ $j->status === 'posted' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                            {{ strtoupper($j->status) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 font-mono">{{ $j->journal_date }} | Ref: {{ $j->reference_number ?? '-' }}</p>
                                </div>
                            </div>

                            <p class="text-xs text-gray-700 font-medium leading-relaxed">{{ $j->description }}</p>

                            <!-- Items Table Detail -->
                            <div class="max-w-full overflow-x-auto rounded-lg border border-gray-200">
                                <table class="w-full text-xs text-left bg-white border-collapse">
                                    <thead>
                                        <tr class="border-b border-gray-200 bg-gray-50 text-gray-500 uppercase font-semibold tracking-wider">
                                            <th class="py-2 px-3">NO. REKENING & NAMA REKENING</th>
                                            <th class="py-2 px-3 text-right">DEBIT</th>
                                            <th class="py-2 px-3 text-right">KREDIT</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($j->details as $d)
                                            <tr class="hover:bg-gray-50/70">
                                                <td class="py-2 px-3 text-gray-800 font-medium {{ $d->credit > 0 ? 'pl-6 text-gray-600' : '' }}">
                                                    <span class="font-mono font-semibold text-emerald-800">{{ $d->account->code ?? $d->chartOfAccount->code ?? '-' }}</span> -
                                                    <span>{{ $d->account->name ?? $d->chartOfAccount->name ?? '-' }}</span>
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
                        <div class="text-center py-8 text-gray-400 italic">Belum ada jurnal yang tercatat.</div>
                    @endforelse
                </div>

                <!-- Pagination Laravel -->
                @if($journals->hasPages())
                    <div class="pt-3 border-t">
                        {{ $journals->withQueryString()->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection