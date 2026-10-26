@extends('layouts.app')

@section('title', 'Transaksi Kas & Bank')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Header -->
        <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Transaksi Kas & Bank</h1>
                <p class="text-sm text-gray-500">Pencatatan pemasukan dan pengeluaran kas operasional</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg font-medium">Kembali ke Dashboard</a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Form Input Transaksi -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Input Transaksi Baru</h2>
                <form action="{{ route('admin.cash-transactions.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Transaksi</label>
                        <select name="type" required class="w-full px-3 py-2 border rounded-lg outline-none">
                            <option value="in">Kas Masuk (Pemasukan)</option>
                            <option value="out">Kas Keluar (Pengeluaran)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Transaksi</label>
                        <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border rounded-lg outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Akun Kas / Bank Utama</label>
                        <select name="account_id" required class="w-full px-3 py-2 border rounded-lg outline-none">
                            <option value="">-- Pilih Akun Kas/Bank --</option>
                            @foreach($cashAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->code }} - {{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Akun Lawan (Kategori/Beban/Pendapatan)</label>
                        <select name="opposite_account_id" required class="w-full px-3 py-2 border rounded-lg outline-none">
                            <option value="">-- Pilih Akun Lawan --</option>
                            @foreach($allAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ strtoupper($acc->type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Transaksi (Rp)</label>
                        <input type="number" name="amount" min="1" step="0.01" placeholder="0" required class="w-full px-3 py-2 border rounded-lg outline-none font-semibold">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan / Memo</label>
                        <textarea name="description" rows="3" placeholder="Catatan transaksi..." class="w-full px-3 py-2 border rounded-lg outline-none text-sm"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition shadow-md">
                        Simpan Transaksi
                    </button>
                </form>
            </div>

            <!-- Riwayat Transaksi -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Riwayat Transaksi Kas & Bank</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b text-xs font-semibold text-gray-500 uppercase bg-gray-50">
                                <th class="py-3 px-3">No. Trx</th>
                                <th class="py-3 px-3">Tanggal</th>
                                <th class="py-3 px-3">Tipe</th>
                                <th class="py-3 px-3">Akun Kas</th>
                                <th class="py-3 px-3">Akun Lawan</th>
                                <th class="py-3 px-3 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($transactions as $trx)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-3 font-mono text-xs font-semibold">{{ $trx->transaction_number }}</td>
                                    <td class="py-3 px-3">{{ $trx->transaction_date }}</td>
                                    <td class="py-3 px-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $trx->type === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $trx->type === 'in' ? 'MASUK' : 'KELUAR' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-gray-600">{{ $trx->account->name }}</td>
                                    <td class="py-3 px-3 text-gray-600">{{ $trx->oppositeAccount->name }}</td>
                                    <td class="py-3 px-3 text-right font-bold {{ $trx->type === 'in' ? 'text-green-600' : 'text-red-600' }}">
                                        Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center text-gray-400 italic">Belum ada transaksi kas yang dicatat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pt-2">
                    {{ $transactions->links() }}
                </div>
            </div>

        </div>
    </div>
@endsection
