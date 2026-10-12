<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Keuangan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center shadow-sm">
        <h1 class="text-xl font-bold text-gray-800">Sistem Keuangan & Payroll</h1>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-600">Halo, <strong>{{ auth()->user()->name }}</strong> ({{ strtoupper(auth()->user()->role) }})</span>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-xs font-semibold px-3 py-2 rounded-lg transition">
                    Logout
                </button>
            </form>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-6 py-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Ringkasan Ringkas Keuangan</h2>

        <!-- Key Metrics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-medium text-gray-500 uppercase">Kas Masuk</p>
                <p class="text-2xl font-bold text-green-600 mt-2">Rp {{ number_format($data['kas_masuk'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-medium text-gray-500 uppercase">Kas Keluar</p>
                <p class="text-2xl font-bold text-red-600 mt-2">Rp {{ number_format($data['kas_keluar'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-medium text-gray-500 uppercase">Laba / Rugi Bersih</p>
                <p class="text-2xl font-bold {{ $data['laba_rugi_bersih'] >= 0 ? 'text-blue-600' : 'text-red-600' }} mt-2">
                    Rp {{ number_format($data['laba_rugi_bersih'], 0, ',', '.') }}
                </p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <p class="text-xs font-medium text-gray-500 uppercase">Gaji Bulan Ini</p>
                <p class="text-2xl font-bold text-purple-600 mt-2">Rp {{ number_format($data['gaji_bulan_ini'], 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Recent Activity Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Transaksi Kas Terbaru</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase">
                            <th class="py-3 px-4">No. Transaksi</th>
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">Tipe</th>
                            <th class="py-3 px-4">Jumlah</th>
                            <th class="py-3 px-4">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($data['transaksi_terakhir'] as $trx)
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 px-4 font-mono text-xs">{{ $trx->transaction_number }}</td>
                                <td class="py-3 px-4">{{ $trx->transaction_date }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $trx->type === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $trx->type === 'in' ? 'Masuk' : 'Keluar' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-semibold">Rp {{ number_format($trx->amount, 0, ',', '.') }}</td>
                                <td class="py-3 px-4 text-gray-600">{{ $trx->description ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">Belum ada transaksi kas recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
