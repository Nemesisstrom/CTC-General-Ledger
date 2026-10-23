<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekonsiliasi Rekening Bank</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Rekonsiliasi Rekening Bank</h1>
                <p class="text-sm text-gray-500">Pencocokan saldo rekening bank dengan catatan pembukuan kas/bank</p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg font-medium">Kembali ke Dashboard</a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                <ul class="list-disc list-inside">
                    @if(session('error')) <li>{{ session('error') }}</li> @endif
                    @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Form Input Rekonsiliasi Bank -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Input Rekonsiliasi</h2>
                <form action="{{ route('admin.bank-reconciliations.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Rekening Bank</label>
                        <select name="account_id" required class="w-full px-3 py-2 border rounded-lg outline-none text-sm">
                            <option value="">-- Pilih Rekening Kas/Bank --</option>
                            @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->code }} - {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Rekening Koran</label>
                        <input type="date" name="statement_date" value="{{ old('statement_date', date('Y-m-d')) }}" required class="w-full px-3 py-2 border rounded-lg outline-none text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Saldo Rekening Koran (Bank)</label>
                        <input type="number" step="0.01" name="statement_balance" value="{{ old('statement_balance') }}" placeholder="Nominal Saldo Bank" required class="w-full px-3 py-2 border rounded-lg outline-none text-sm font-semibold">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan / Keterangan (Optional)</label>
                        <textarea name="notes" rows="2" placeholder="Catatan selisih atau penyesuaian..." class="w-full px-3 py-2 border rounded-lg outline-none text-sm">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition shadow-md">
                        Proses Rekonsiliasi
                    </button>
                </form>
            </div>

            <!-- Tabel Riwayat Rekonsiliasi Bank -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Riwayat Rekonsiliasi Rekening Bank</h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50 text-xs font-semibold text-gray-500 uppercase">
                                <th class="py-3 px-3">Tanggal</th>
                                <th class="py-3 px-3">No. Rekening & Nama</th>
                                <th class="py-3 px-3 text-right">Saldo Bank</th>
                                <th class="py-3 px-3 text-right">Saldo Buku</th>
                                <th class="py-3 px-3 text-right">Selisih</th>
                                <th class="py-3 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y text-xs">
                            @forelse($reconciliations as $rec)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="py-3 px-3 font-medium text-gray-700">
                                        {{ \Carbon\Carbon::parse($rec->statement_date)->format('d/m/Y') }}
                                        <p class="text-[10px] text-gray-400">Oleh: {{ $rec->reconciler->name ?? 'System' }}</p>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="font-mono font-bold text-blue-600">{{ $rec->account->code ?? '-' }}</span> -
                                        <span class="font-medium text-gray-800">{{ $rec->account->name ?? '-' }}</span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-semibold text-gray-800">
                                        Rp {{ number_format($rec->statement_balance, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-semibold text-gray-600">
                                        Rp {{ number_format($rec->book_balance, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-bold {{ $rec->difference == 0 ? 'text-green-600' : 'text-red-600' }}">
                                        Rp {{ number_format($rec->difference, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $rec->status === 'reconciled' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $rec->status === 'reconciled' ? 'MATCH' : 'SELISIH' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-gray-400 italic">Belum ada data rekonsiliasi bank yang dicatat.</td>
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
