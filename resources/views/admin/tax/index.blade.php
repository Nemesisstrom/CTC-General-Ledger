<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Otomatisasi Pajak (PPN & PPh)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Otomatisasi Pajak (PPN & PPh)</h1>
                <p class="text-sm text-gray-500">Hitung & Posting Otomatis Pajak ke Jurnal Keuangan</p>
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

            <!-- Form Kalkulator Transaksi Pajak -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Proses Pajak Transaksi</h2>
                <form action="{{ route('admin.taxes.process') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Transaksi</label>
                        <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border rounded-lg outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Transaksi</label>
                        <select name="transaction_type" required class="w-full px-3 py-2 border rounded-lg outline-none">
                            <option value="sales">Penjualan (+ PPN Keluaran)</option>
                            <option value="purchase">Pembelian Jasa/Barang (- Potongan PPh 23)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Jenis Pajak</label>
                        <select name="tax_id" required class="w-full px-3 py-2 border rounded-lg outline-none">
                            <option value="">-- Pilih Skema Pajak --</option>
                            @foreach($taxes as $t)
                                <option value="{{ $t->id }}">
                                    {{ $t->code }} - {{ $t->name }} ({{ $t->rate }}%)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nilai Transaksi DPP / Subtotal (Rp)</label>
                        <input type="number" name="subtotal" placeholder="0" min="1" required class="w-full px-3 py-2 border rounded-lg outline-none font-semibold">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Akun Kas / Bank (Pembayaran)</label>
                        <select name="cash_account_id" required class="w-full px-3 py-2 border rounded-lg outline-none text-xs">
                            @foreach($cashAccounts as $cash)
                                <option value="{{ $cash->id }}">{{ $cash->code }} - {{ $cash->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Akun Pendapatan / Beban</label>
                        <select name="revenue_expense_id" required class="w-full px-3 py-2 border rounded-lg outline-none text-xs">
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ strtoupper($acc->type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan / Memo</label>
                        <textarea name="description" rows="2" placeholder="Catatan transaksi pajak..." class="w-full px-3 py-2 border rounded-lg outline-none text-sm"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition shadow-md">
                        Hitung & Posting Jurnal Pajak
                    </button>
                </form>
            </div>

            <!-- Master Data Rates Pajak -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Master Data Tarif & Akun Penampung Pajak</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b text-xs font-semibold text-gray-500 uppercase bg-gray-50">
                                <th class="py-3 px-3">Kode</th>
                                <th class="py-3 px-3">Nama Pajak</th>
                                <th class="py-3 px-3">Tipe</th>
                                <th class="py-3 px-3 text-right">Tarif (%)</th>
                                <th class="py-3 px-3">Akun COA Penampung</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($taxes as $tax)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-3 font-mono font-semibold text-xs">{{ $tax->code }}</td>
                                    <td class="py-3 px-3 font-medium">{{ $tax->name }}</td>
                                    <td class="py-3 px-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                            {{ $tax->type }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold text-blue-600">{{ $tax->rate }}%</td>
                                    <td class="py-3 px-3 text-gray-600">{{ $tax->account->code }} - {{ $tax->account->name }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-center text-gray-400 italic">Belum ada master tarif pajak.</td>
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
