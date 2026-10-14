<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jurnal Penyesuaian & Penyusutan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Jurnal Penyesuaian & Penyusutan</h1>
                <p class="text-sm text-gray-500">Pencatatan penyusutan aset tetap & penyesuaian akhir periode</p>
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

            <!-- Form Input Penyesuaian -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Input Penyesuaian Baru</h2>
                <form action="{{ route('adjustments.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Penyesuaian</label>
                        <input type="date" name="adjustment_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border rounded-lg outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Akun Beban / Debit</label>
                        <select name="expense_account_id" required class="w-full px-3 py-2 border rounded-lg outline-none text-xs">
                            <option value="">-- Pilih Akun Beban --</option>
                            @foreach($expenseAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Akun Akumulasi / Kredit</label>
                        <select name="accumulated_account_id" required class="w-full px-3 py-2 border rounded-lg outline-none text-xs">
                            <option value="">-- Pilih Akun Akumulasi/Kredit --</option>
                            @foreach($accumulatedAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ strtoupper($acc->type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nilai Penyesuaian (Rp)</label>
                        <input type="number" name="amount" min="1" step="0.01" placeholder="0" required class="w-full px-3 py-2 border rounded-lg outline-none font-bold text-blue-600">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan / Deskripsi</label>
                        <textarea name="description" rows="3" placeholder="Contoh: Penyusutan inventaris bulan ini..." required class="w-full px-3 py-2 border rounded-lg outline-none text-sm"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition shadow-md">
                        Posting Jurnal Penyesuaian
                    </button>
                </form>
            </div>

            <!-- Tabel Riwayat Penyesuaian -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Riwayat Jurnal Penyesuaian</h2>

                <div class="space-y-3">
                    @forelse($adjustments as $adj)
                        <div class="border rounded-xl p-4 bg-gray-50/50 space-y-2">
                            <div class="flex justify-between items-center border-b pb-2">
                                <span class="font-mono font-bold text-xs text-blue-600">{{ $adj->journal_number }}</span>
                                <span class="text-xs text-gray-500">{{ $adj->journal_date }}</span>
                            </div>
                            <p class="text-xs text-gray-700 font-medium">{{ $adj->description }}</p>

                            <table class="w-full text-xs text-left bg-white rounded border">
                                <thead>
                                    <tr class="border-b bg-gray-100 text-gray-500 uppercase">
                                        <th class="py-1 px-2">Akun</th>
                                        <th class="py-1 px-2 text-right">Debit</th>
                                        <th class="py-1 px-2 text-right">Kredit</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($adj->details as $d)
                                        <tr>
                                            <td class="py-1 px-2 {{ $d->credit > 0 ? 'pl-6' : '' }}">
                                                {{ $d->account->code }} - {{ $d->account->name }}
                                            </td>
                                            <td class="py-1 px-2 text-right">{{ $d->debit > 0 ? 'Rp ' . number_format($d->debit, 0, ',', '.') : '-' }}</td>
                                            <td class="py-1 px-2 text-right">{{ $d->credit > 0 ? 'Rp ' . number_format($d->credit, 0, ',', '.') : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-400 italic">Belum ada jurnal penyesuaian.</div>
                    @endforelse
                </div>

                <div class="pt-2">
                    {{ $adjustments->links() }}
                </div>
            </div>

        </div>
    </div>
</body>
</html>
