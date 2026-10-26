@extends('layouts.app')

@section('title', 'Kelola Rekening Keuangan')

@section('content')
    <div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Header -->
        <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Master Data Rekening</h1>
                <p class="text-sm text-gray-500">Struktur dan hirarki Nomor Rekening keuangan perusahaan</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg font-medium transition">
                Kembali ke Dashboard
            </a>
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

            <!-- Form Tambah Rekening Baru -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Tambah Rekening Baru</h2>
                <form action="{{ route('admin.chart-of-accounts.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Rekening</label>
                        <input type="text" name="code" placeholder="Misal: 10101" required class="w-full px-3 py-2 border rounded-lg outline-none focus:ring-2 focus:ring-blue-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Rekening</label>
                        <input type="text" name="name" placeholder="Misal: Kas Kecil" required class="w-full px-3 py-2 border rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Rekening</label>
                        <select name="type" required class="w-full px-3 py-2 border rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="asset">Aset (Asset)</option>
                            <option value="liability">Kewajiban (Liability)</option>
                            <option value="equity">Ekuitas (Equity)</option>
                            <option value="revenue">Pendapatan (Revenue)</option>
                            <option value="expense">Beban (Expense)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Saldo Normal</label>
                        <select name="normal_balance" required class="w-full px-3 py-2 border rounded-lg outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="debit">Debit</option>
                            <option value="credit">Kredit</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Induk Rekening (Optional)</label>
                        <select name="parent_id" class="w-full px-3 py-2 border rounded-lg outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="">-- Tanpa Induk (Rekening Utama) --</option>
                            @foreach($parentAccounts as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->code }} - {{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition shadow-md">
                        Simpan Rekening
                    </button>
                </form>
            </div>

            <!-- Tabel Daftar Rekening -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Daftar Nomor & Nama Rekening</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b text-xs font-semibold text-gray-500 uppercase bg-gray-50">
                                <th class="py-3 px-4">No. Rekening</th>
                                <th class="py-3 px-4">Nama Rekening</th>
                                <th class="py-3 px-4">Tipe</th>
                                <th class="py-3 px-4">Saldo Normal</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y text-sm">
                            @forelse($accounts as $acc)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-3 px-4 font-mono font-bold text-blue-600">{{ $acc->code }}</td>
                                    <td class="py-3 px-4 font-medium {{ $acc->parent_id ? 'pl-8 text-gray-600' : 'text-gray-900' }}">
                                        @if($acc->parent_id)
                                            <span class="text-gray-400 font-bold mr-1">└─</span>
                                        @endif
                                        {{ $acc->name }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-gray-100 text-gray-700 uppercase">
                                            {{ strtoupper($acc->type) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-medium text-xs uppercase text-gray-600">{{ $acc->normal_balance }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <!-- Form Hapus Rekening (Khusus Admin) -->
                                        <form action="{{ route('admin.chart-of-accounts.destroy', $acc->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus rekening {{ $acc->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-semibold transition">
                                                Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-6 text-center text-gray-400 italic">Belum ada rekening yang terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection
