<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jurnal Umum</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Jurnal Umum</h1>
                <p class="text-sm text-gray-500">Pencatatan pembukuan berpasangan (Double-Entry Ledger)</p>
            </div>
            <div class="flex items-center gap-2">
                @hasanyrole('admin|akuntan')
                    <a href="{{ route('admin.journals.reversals.index') }}" class="text-sm bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition">
                        🔄 Ke Jurnal Pembalikan
                    </a>
                @endhasanyrole
                <a href="{{ route('dashboard') }}" class="text-sm bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg font-medium">Kembali ke Dashboard</a>
            </div>
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

            <!-- Form Input Jurnal Manual -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Buat Jurnal Manual</h2>
                <form action="{{ route('admin.journals.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Jurnal</label>
                        <input type="date" name="journal_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border rounded-lg outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">No. Referensi (Optional)</label>
                        <input type="text" name="reference_number" placeholder="Misal: INV-001" class="w-full px-3 py-2 border rounded-lg outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan Jurnal</label>
                        <textarea name="description" rows="2" placeholder="Deskripsi transaksi..." required class="w-full px-3 py-2 border rounded-lg outline-none text-sm"></textarea>
                    </div>

                    <!-- Baris Entri Rekening Double-Entry -->
                    <div class="border-t pt-3 space-y-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase">Baris Entri Rekening</label>

                        <!-- Baris 1 (Debit) -->
                        <div x-data="{ code: '' }" class="p-2 bg-gray-50 rounded border space-y-2">
                            <div>
                                <label class="block text-[11px] text-gray-500 font-medium mb-0.5">Nama Rekening Debit</label>
                                <select name="items[0][account_id]"
                                        @change="code = $event.target.options[$event.target.selectedIndex].dataset.code || ''"
                                        required class="w-full px-2 py-1 border rounded text-xs">
                                    <option value="" data-code="">-- Pilih Rekening Debit --</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}" data-code="{{ $acc->code }}">
                                            {{ $acc->parent_id ? '└─ ' : '' }}{{ $acc->name }} ({{ $acc->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] text-gray-500 font-medium mb-0.5">No. Rekening</label>
                                <input type="text" x-model="code" readonly placeholder="Otomatis..." class="w-full px-2 py-1 border bg-gray-100 rounded text-xs font-mono font-semibold text-blue-600 outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] text-gray-400">Debit (Rp)</label>
                                    <input type="number" name="items[0][debit]" placeholder="Nominal Rp" required class="w-full px-2 py-1 border rounded text-xs font-semibold">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-400">Kredit (Rp)</label>
                                    <input type="number" name="items[0][credit]" value="0" readonly class="w-full px-2 py-1 border bg-gray-100 rounded text-xs text-gray-400">
                                </div>
                            </div>
                        </div>

                        <!-- Baris 2 (Kredit) -->
                        <div x-data="{ code: '' }" class="p-2 bg-gray-50 rounded border space-y-2">
                            <div>
                                <label class="block text-[11px] text-gray-500 font-medium mb-0.5">Nama Rekening Kredit</label>
                                <select name="items[1][account_id]"
                                        @change="code = $event.target.options[$event.target.selectedIndex].dataset.code || ''"
                                        required class="w-full px-2 py-1 border rounded text-xs">
                                    <option value="" data-code="">-- Pilih Rekening Kredit --</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}" data-code="{{ $acc->code }}">
                                            {{ $acc->parent_id ? '└─ ' : '' }}{{ $acc->name }} ({{ $acc->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] text-gray-500 font-medium mb-0.5">No. Rekening</label>
                                <input type="text" x-model="code" readonly placeholder="Otomatis..." class="w-full px-2 py-1 border bg-gray-100 rounded text-xs font-mono font-semibold text-blue-600 outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] text-gray-400">Debit (Rp)</label>
                                    <input type="number" name="items[1][debit]" value="0" readonly class="w-full px-2 py-1 border bg-gray-100 rounded text-xs text-gray-400">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-400">Kredit (Rp)</label>
                                    <input type="number" name="items[1][credit]" placeholder="Nominal Rp" required class="w-full px-2 py-1 border rounded text-xs font-semibold">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition shadow-md">
                        Posting Jurnal
                    </button>
                </form>
            </div>

            <!-- Tabel Daftar Jurnal Umum -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Daftar Jurnal Umum</h2>

                <div class="space-y-4">
                    @forelse($journals as $j)
                        <div class="border rounded-xl p-4 bg-gray-50/50 space-y-3">
                            <div class="flex justify-between items-start border-b pb-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono font-bold text-sm text-blue-600">{{ $j->journal_number }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $j->status === 'posted' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ strtoupper($j->status) }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $j->journal_date }} | Ref: {{ $j->reference_number ?? '-' }}</p>
                                </div>
                            </div>

                            <p class="text-xs text-gray-700 font-medium">{{ $j->description }}</p>

                            <!-- Items Table Detail -->
                            <table class="w-full text-xs text-left bg-white rounded border">
                                <thead>
                                    <tr class="border-b bg-gray-100 text-gray-500 uppercase">
                                        <th class="py-1.5 px-2">NO. REKENING & NAMA REKENING</th>
                                        <th class="py-1.5 px-2 text-right">DEBIT</th>
                                        <th class="py-1.5 px-2 text-right">KREDIT</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($j->details as $d)
                                        <tr>
                                            <td class="py-1.5 px-2 {{ $d->credit > 0 ? 'pl-6' : '' }}">
                                                <span class="font-mono font-semibold text-blue-600">{{ $d->account->code ?? $d->chartOfAccount->code ?? '-' }}</span> -
                                                <span>{{ $d->account->name ?? $d->chartOfAccount->name ?? '-' }}</span>
                                            </td>
                                            <td class="py-1.5 px-2 text-right font-mono">{{ $d->debit > 0 ? 'Rp ' . number_format($d->debit, 0, ',', '.') : '-' }}</td>
                                            <td class="py-1.5 px-2 text-right font-mono">{{ $d->credit > 0 ? 'Rp ' . number_format($d->credit, 0, ',', '.') : '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-400 italic">Belum ada jurnal yang tercatat.</div>
                    @endforelse
                </div>

                <div class="pt-2">
                    {{ $journals->links() }}
                </div>
            </div>

        </div>
    </div>
</body>
</html>
