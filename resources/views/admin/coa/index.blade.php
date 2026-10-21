<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Bagan Akun (COA)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Master Data Bagan Akun (COA)</h1>
                <p class="text-sm text-gray-500">Daftar struktur akun keuangan perusahaan</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg font-medium">Kembali ke Dashboard</a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Form Tambah Akun -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Tambah Akun Baru</h2>
                <form action="{{ route('admin.chart-of-accounts.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kode Akun</label>
                        <input type="text" name="code" placeholder="Misal: 10101" required class="w-full px-3 py-2 border rounded-lg outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nama Akun</label>
                        <input type="text" name="name" placeholder="Misal: Kas Kecil" required class="w-full px-3 py-2 border rounded-lg outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipe Akun</label>
                        <select name="type" required class="w-full px-3 py-2 border rounded-lg outline-none">
                            <option value="asset">Aset (Asset)</option>
                            <option value="liability">Kewajiban (Liability)</option>
                            <option value="equity">Ekuitas (Equity)</option>
                            <option value="revenue">Pendapatan (Revenue)</option>
                            <option value="expense">Beban (Expense)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Saldo Normal</label>
                        <select name="normal_balance" required class="w-full px-3 py-2 border rounded-lg outline-none">
                            <option value="debit">Debit</option>
                            <option value="credit">Kredit</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Induk Akun (Optional)</label>
                        <select name="parent_id" class="w-full px-3 py-2 border rounded-lg outline-none">
                            <option value="">-- Tanpa Induk --</option>
                            @foreach($parentAccounts as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->code }} - {{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg">Simpan Akun</button>
                </form>
            </div>

            <!-- Tabel Daftar Akun -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Daftar Bagan Akun</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b text-xs font-semibold text-gray-500 uppercase bg-gray-50">
                                <th class="py-3 px-4">Kode</th>
                                <th class="py-3 px-4">Nama Akun</th>
                                <th class="py-3 px-4">Tipe</th>
                                <th class="py-3 px-4">Saldo Normal</th>
                                <th class="py-3 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y text-sm">
                            @forelse($accounts as $acc)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4 font-mono font-semibold">{{ $acc->code }}</td>
                                    <td class="py-3 px-4">
                                        @if($acc->parent_id)
                                            <span class="text-gray-400 pl-4">↳</span>
                                        @endif
                                        {{ $acc->name }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                            {{ strtoupper($acc->type) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 font-medium text-xs uppercase">{{ $acc->normal_balance }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <!-- Form Hapus Akun (Gunakan $acc->id) -->
                                        <form action="{{ route('admin.chart-of-accounts.destroy', $acc->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 font-medium">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-gray-500">Belum ada akun yang terdaftar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
