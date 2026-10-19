<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekonsiliasi Bank</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Rekonsiliasi Rekening Bank</h1>
                <p class="text-sm text-gray-500">Pencocokan saldo rekening koran bank dengan pembukuan sistem</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg font-medium">Kembali ke Dashboard</a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Form Input Rekonsiliasi -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Proses Rekonsiliasi Baru</h2>
                <form action="{{ route('admin.bank-reconciliations.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Akun Rekening Bank</label>
                        <select name="account_id" required class="w-full px-3 py-2 border rounded-lg outline-none">
                            <option value="">-- Pilih Akun Bank --</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Rekening Koran</label>
                        <input type="date" name="statement_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border rounded-lg outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Saldo Rekening Koran Bank (Rp)</label>
                        <input type="number" name="statement_balance" step="0.01" placeholder="0" required class="w-full px-3 py-2 border rounded-lg outline-none font-bold text-blue-600">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan / Keterangan</label>
                        <textarea name="notes" rows="3" placeholder="Catatan penyesuaian/selisih..." class="w-full px-3 py-2 border rounded-lg outline-none text-sm"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition shadow-md">
                        Jalankan Rekonsiliasi
                    </button>
                </form>
            </div>

            <!-- Tabel Riwayat Rekonsiliasi -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Riwayat Rekonsiliasi Bank</h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b font-semibold text-gray-500 uppercase bg-gray-50">
                                <th class="py-3 px-3">Tanggal Koran</th>
                                <th class="py-3 px-3">Akun Bank</th>
                                <th class="py-3 px-3 text-right">Saldo Koran</th>
                                <th class="py-3 px-3 text-right">Saldo Buku</th>
                                <th class="py-3 px-3 text-right">Selisih</th>
                                <th class="py-3 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($reconciliations as $rec)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-3 font-semibold">{{ $rec->statement_date }}</td>
                                    <td class="py-3 px-3 text-gray-700">{{ $rec->account->name }}</td>
                                    <td class="py-3 px-3 text-right font-medium">Rp {{ number_format($rec->statement_balance, 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right text-gray-600">Rp {{ number_format($rec->book_balance, 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right font-bold {{ $rec->difference == 0 ? 'text-green-600' : 'text-red-600' }}">
                                        Rp {{ number_format($rec->difference, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $rec->status === 'reconciled' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ strtoupper($rec->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center text-gray-400 italic">Belum ada riwayat rekonsiliasi bank.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pt-2">
                    {{ $reconciliations->links() }}
                </div>
            </div>

        </div>
    </div>
</body>
</html>
