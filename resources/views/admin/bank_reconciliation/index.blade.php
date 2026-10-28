@extends('layouts.app')

@section('title', 'Rekonsiliasi Rekening Bank')

@section('content')
    <div class="feature-page mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Header Hero Banner - Rekonsiliasi Rekening Bank -->
        <section class="dashboard-enter relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-[#104B35] via-[#176B45] to-[#248052] px-4 py-6 text-white shadow-lg shadow-[#104B35]/10 sm:px-8 sm:py-8 mb-6">
            <div class="pointer-events-none absolute -right-12 -top-24 -z-10 h-72 w-72 rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute -right-2 -top-14 -z-10 h-52 w-52 rounded-full border border-white/10"></div>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#C6E4D0]">CTC General Ledger</p>
                    <div class="mt-2 text-xl font-bold tracking-tight text-white sm:text-2xl" role="heading" aria-level="1">
                        Rekonsiliasi Rekening Bank
                    </div>
                    <p class="mt-1 text-xs text-[#E8F4EC] sm:text-sm">
                        Pencocokan saldo rekening bank dengan catatan pembukuan kas/bank.
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

        @if(session('error') || $errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm font-medium">
                <ul class="list-disc list-inside space-y-1">
                    @if(session('error')) <li>{{ session('error') }}</li> @endif
                    @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Form Input Rekonsiliasi Bank -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-800 border-b pb-3">Input Rekonsiliasi</h2>
                <form action="{{ route('admin.bank-reconciliations.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Pilih Rekening Bank</label>
                        <select name="account_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">-- Pilih Rekening Kas/Bank --</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Tanggal Rekening Koran</label>
                        <input type="date" name="statement_date" value="{{ old('statement_date', date('Y-m-d')) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <!-- Input Saldo Rekening Koran dengan Format Titik Otomatis -->
                    <div x-data="{ 
                        rawBalance: '{{ old('statement_balance') }}',
                        displayBalance: '{{ old('statement_balance') ? number_format(old('statement_balance'), 0, ',', '.') : '' }}',
                        formatInput(e) {
                            let numeric = e.target.value.replace(/\D/g, '');
                            this.rawBalance = numeric;
                            this.displayBalance = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
                        }
                    }">
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Saldo Rekening Koran (Bank)</label>
                        <input 
                            type="text" 
                            inputmode="numeric"
                            x-model="displayBalance"
                            @input="formatInput($event)"
                            placeholder="0" 
                            required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500 font-mono font-semibold"
                        >
                        <input type="hidden" name="statement_balance" :value="rawBalance">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Catatan / Keterangan (Opsional)</label>
                        <textarea name="notes" rows="2" placeholder="Catatan selisih atau penyesuaian..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-medium py-2.5 rounded-lg transition shadow-sm">
                        Proses Rekonsiliasi
                    </button>
                </form>
            </div>

            <!-- Tabel Riwayat Rekonsiliasi Bank -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <div class="border-b pb-3">
                    <h2 class="text-lg font-bold text-gray-800">Riwayat Rekonsiliasi Rekening Bank</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b text-gray-500 uppercase bg-gray-50 font-semibold tracking-wider">
                                <th class="py-3 px-3 text-left">Tanggal</th>
                                <th class="py-3 px-3 text-left">No. Rekening & Nama</th>
                                <th class="py-3 px-3 text-right">Saldo Bank</th>
                                <th class="py-3 px-3 text-right">Saldo Buku</th>
                                <th class="py-3 px-3 text-right">Selisih</th>
                                <th class="py-3 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($reconciliations as $rec)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-3 font-mono text-xs text-gray-700 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($rec->statement_date)->format('d/m/Y') }}
                                        <p class="text-[10px] text-gray-400 font-sans">Oleh: {{ $rec->reconciler->name ?? 'System' }}</p>
                                    </td>
                                    <td class="py-3 px-3 text-gray-800">
                                        <span class="font-mono font-semibold text-emerald-800">{{ $rec->account->code ?? '-' }}</span> -
                                        <span class="font-medium text-gray-800">{{ $rec->account->name ?? '-' }}</span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono tabular-nums font-medium text-gray-800 whitespace-nowrap">
                                        <span class="text-gray-400 font-sans text-[11px] mr-1">Rp</span>{{ number_format($rec->statement_balance, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono tabular-nums text-gray-600 whitespace-nowrap">
                                        <span class="text-gray-400 font-sans text-[11px] mr-1">Rp</span>{{ number_format($rec->book_balance, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono tabular-nums font-bold whitespace-nowrap {{ $rec->difference == 0 ? 'text-emerald-700' : 'text-red-600' }}">
                                        <span class="text-gray-400 font-sans text-[11px] mr-1">Rp</span>{{ number_format($rec->difference, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase {{ $rec->status === 'reconciled' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200' }}">
                                            {{ $rec->status === 'reconciled' ? 'MATCH' : 'SELISIH' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-gray-400 italic">Belum ada data rekonsiliasi bank yang dicatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Laravel -->
                @if($reconciliations->hasPages())
                    <div class="pt-3 border-t">
                        {{ $reconciliations->withQueryString()->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection