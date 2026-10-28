@extends('layouts.app')

@section('title', 'Transaksi Kas & Bank')

@section('content')
    <div class="feature-page mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Header Hero Banner - Transaksi Kas & Bank -->
        <section class="dashboard-enter relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-[#104B35] via-[#176B45] to-[#248052] px-4 py-6 text-white shadow-lg shadow-[#104B35]/10 sm:px-8 sm:py-8 mb-6">
            <div class="pointer-events-none absolute -right-12 -top-24 -z-10 h-72 w-72 rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute -right-2 -top-14 -z-10 h-52 w-52 rounded-full border border-white/10"></div>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#C6E4D0]">CTC General Ledger</p>
                    <div class="mt-2 text-xl font-bold tracking-tight text-white sm:text-2xl" role="heading" aria-level="1">
                        Transaksi Kas & Bank
                    </div>
                    <p class="mt-1 text-xs text-[#E8F4EC] sm:text-sm">
                        Pencatatan pemasukan dan pengeluaran kas operasional.
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

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert" aria-live="assertive">
                <p class="font-semibold">Transaksi belum tersimpan. Periksa kembali data berikut:</p>
                <ul class="mt-2 list-inside list-disc space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Form Input Transaksi -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-800 border-b pb-3">Input Transaksi Baru</h2>
                <form action="{{ route('admin.cash-transactions.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Jenis Transaksi</label>
                        <select name="type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="in">Kas Masuk (Pemasukan)</option>
                            <option value="out">Kas Keluar (Pengeluaran)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Tanggal Transaksi</label>
                        <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Akun Kas / Bank Utama</label>
                        <select name="account_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">-- Pilih Akun Kas/Bank --</option>
                            @foreach($cashAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Akun Lawan (Kategori/Beban/Pendapatan)</label>
                        <select name="opposite_account_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="">-- Pilih Akun Lawan --</option>
                            @foreach($allAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ strtoupper($acc->type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Input Jumlah Transaksi dengan Format Titik Otomatis -->
                    <div x-data="{ 
                        rawAmount: '{{ old('amount') }}',
                        displayAmount: '{{ old('amount') ? number_format(old('amount'), 0, ',', '.') : '' }}',
                        formatInput(e) {
                            let numeric = e.target.value.replace(/\D/g, '');
                            this.rawAmount = numeric;
                            this.displayAmount = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
                        }
                    }">
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Jumlah Transaksi (Rp)</label>
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
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Keterangan / Memo</label>
                        <textarea name="description" rows="3" placeholder="Catatan transaksi..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-medium py-2.5 rounded-lg transition shadow-sm">
                        Simpan Transaksi
                    </button>
                </form>
            </div>

            <!-- Riwayat Transaksi -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <div class="border-b pb-3">
                    <h2 class="text-lg font-bold text-gray-800">Riwayat Transaksi Kas & Bank</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b text-gray-500 uppercase bg-gray-50 font-semibold tracking-wider">
                                <th class="py-3 px-3 text-left">No. Trx</th>
                                <th class="py-3 px-3 text-left">Tanggal</th>
                                <th class="py-3 px-3 text-center">Tipe</th>
                                <th class="py-3 px-3 text-left">Akun Kas</th>
                                <th class="py-3 px-3 text-left">Akun Lawan</th>
                                <th class="py-3 px-3 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($transactions as $trx)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-3 font-mono text-xs font-semibold text-gray-800 whitespace-nowrap">{{ $trx->transaction_number }}</td>
                                    <td class="py-3 px-3 whitespace-nowrap text-gray-600">{{ $trx->transaction_date }}</td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase {{ $trx->type === 'in' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                            {{ $trx->type === 'in' ? 'MASUK' : 'KELUAR' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-gray-700 font-medium">{{ $trx->account->name }}</td>
                                    <td class="py-3 px-3 text-gray-700 font-medium">{{ $trx->oppositeAccount->name }}</td>
                                    <td class="py-3 px-3 text-right font-mono font-bold tabular-nums whitespace-nowrap {{ $trx->type === 'in' ? 'text-emerald-700' : 'text-red-600' }}">
                                        Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-gray-400 italic">Belum ada transaksi kas yang dicatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div class="pt-3 border-t">
                        {{ $transactions->withQueryString()->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection