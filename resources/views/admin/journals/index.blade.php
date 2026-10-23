<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Umum - Sistem Keuangan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-4 sm:p-6" x-data="{ isLoading: false }">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-6 rounded-xl shadow-sm gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Jurnal Umum</h1>
                <p class="text-sm text-gray-500">Pencatatan pembukuan berpasangan (Double-Entry Ledger)</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @hasanyrole('admin|akuntan')
                    <a href="{{ route('admin.journals.reversals.index') }}" class="text-sm bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition">
                        🔄 Jurnal Pembalikan
                    </a>
                @endhasanyrole
                <a href="{{ route('dashboard') }}" class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg font-medium transition">Kembali ke Dashboard</a>
            </div>
        </div>

        <!-- Alert Notifikasi Sukses -->
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <!-- Alert Notifikasi Error / Validasi -->
        @if(session('error') || $errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm shadow-sm">
                <ul class="list-disc list-inside space-y-1">
                    @if(session('error')) <li>{{ session('error') }}</li> @endif
                    @foreach($errors->all() as $err) <li>{{$err }}</li> @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Form Input Jurnal Manual -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-800 border-b pb-2">Buat Jurnal Manual</h2>

                <form action="{{ route('admin.journals.store') }}" method="POST" @submit="isLoading = true" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Jurnal</label>
                        <input type="date" name="journal_date" value="{{ old('journal_date', date('Y-m-d')) }}" required class="w-full px-3 py-2 border rounded-lg outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">No. Referensi (Opsional)</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number') }}" placeholder="Misal: INV-001" class="w-full px-3 py-2 border rounded-lg outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan Jurnal</label>
                        <textarea name="description" rows="2" placeholder="Deskripsi transaksi..." required class="w-full px-3 py-2 border rounded-lg outline-none focus:ring-2 focus:ring-blue-500 text-sm">{{ old('description') }}</textarea>
                    </div>

                    <!-- Baris Entri Rekening Double-Entry -->
                    <div class="border-t pt-3 space-y-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Baris Entri Rekening</label>

                        <!-- Baris 1 (Debit) -->
                        <div x-data="{ code: '' }" class="p-3 bg-gray-50 rounded-lg border space-y-2">
                            <div>
                                <label class="block text-[11px] text-gray-600 font-medium mb-1">Akun Debit</label>
                                <select name="items[0][account_id]"
                                        @change="code = $event.target.options[$event.target.selectedIndex].dataset.code || ''"
                                        required class="w-full px-2 py-1.5 border rounded bg-white text-xs">
                                    <option value="" data-code="">-- Pilih Rekening Debit --</option>
                                    @foreach($accounts as$acc)
                                        <option value="{{ $acc->id }}" data-code="{{ $acc->code }}" {{ old('items.0.account_id') == $acc->id ? 'selected' : '' }}>
                                            {{ $acc->name }} ({{$acc->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] text-gray-500 font-medium mb-0.5">Kode Rekening</label>
                                <input type="text" x-model="code" readonly placeholder="Otomatis..." class="w-full px-2 py-1 border bg-gray-100 rounded text-xs font-mono font-semibold text-blue-600 outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] text-gray-500">Debit (Rp)</label>
                                    <input type="number" name="items[0][debit]" value="{{ old('items.0.debit') }}" placeholder="0" min="0" step="any" required class="w-full px-2 py-1 border rounded text-xs font-semibold">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-400">Kredit (Rp)</label>
                                    <input type="number" name="items[0][credit]" value="0" readonly class="w-full px-2 py-1 border bg-gray-100 rounded text-xs text-gray-400">
                                </div>
                            </div>
                        </div>

                        <!-- Baris 2 (Kredit) -->
                        <div x-data="{ code: '' }" class="p-3 bg-gray-50 rounded-lg border space-y-2">
                            <div>
                                <label class="block text-[11px] text-gray-600 font-medium mb-1">Akun Kredit</label>
                                <select name="items[1][account_id]"
                                        @change="code = $event.target.options[$event.target.selectedIndex].dataset.code || ''"
                                        required class="w-full px-2 py-1.5 border rounded bg-white text-xs">
                                    <option value="" data-code="">-- Pilih Rekening Kredit --</option>
                                    @foreach($accounts as$acc)
                                        <option value="{{ $acc->id }}" data-code="{{ $acc->code }}" {{ old('items.1.account_id') == $acc->id ? 'selected' : '' }}>
                                            {{ $acc->name }} ({{$acc->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] text-gray-500 font-medium mb-0.5">Kode Rekening</label>
                                <input type="text" x-model="code" readonly placeholder="Otomatis..." class="w-full px-2 py-1 border bg-gray-100 rounded text-xs font-mono font-semibold text-blue-600 outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] text-gray-400">Debit (Rp)</label>
                                    <input type="number" name="items[1][debit]" value="0" readonly class="w-full px-2 py-1 border bg-gray-100 rounded text-xs text-gray-400">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-500">Kredit (Rp)</label>
                                    <input type="number" name="items[1][credit]" value="{{ old('items.1.credit') }}" placeholder="0" min="0" step="any" required class="w-full px-2 py-1 border rounded text-xs font-semibold">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" :disabled="isLoading" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition shadow-md flex items-center justify-center space-x-2">
                        <span x-show="isLoading">Memproses...</span>
                        <span x-show="!isLoading">Posting Jurnal</span>
                    </button>
                </form>
            </div>

            <!-- Tabel Daftar Jurnal Umum -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-gray-800 border-b pb-2">Daftar Jurnal Umum</h2>

                <div class="space-y-4">
                    @forelse($journals as$j)
                        <div class="border rounded-xl p-4 bg-gray-50/50 space-y-3 shadow-xs">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b pb-2 gap-2">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-mono font-bold text-sm text-blue-600">{{ $j->journal_number }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $j->status === 'posted' ? 'bg-green-100 text-green-800' : 'bg-red-100 text
