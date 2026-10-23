<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Otomatisasi & Kelola Pajak (PPN & PPh)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="bg-gray-100 min-h-screen p-6" x-data="{ openCreateModal: false, openEditModal: false, editTax: {} }">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Otomatisasi & Kelola Pajak</h1>
                <p class="text-sm text-gray-500">Hitung, Posting, & Manajemen Tarif Pajak Perusahaan</p>
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
                            @foreach($taxes as $tax)
                                @if($tax->is_active)
                                    <option value="{{ $tax->id }}">
                                        {{ $tax->code }} - {{ $tax->name }} ({{ $tax->rate }}%)
                                    </option>
                                @endif
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

            <!-- Tabel Master Data Rates Pajak -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <div class="flex justify-between items-center border-b pb-3">
                    <h2 class="text-lg font-bold text-gray-800">Master Data Tarif & Akun Penampung Pajak</h2>
                    @role('admin')
                        <button @click="openCreateModal = true" class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg font-semibold transition shadow-sm flex items-center gap-1">
                            ➕ Tambah Skema Pajak
                        </button>
                    @endrole
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="border-b text-xs font-semibold text-gray-500 uppercase bg-gray-50">
                                <th class="py-3 px-3">Kode</th>
                                <th class="py-3 px-3">Nama Pajak</th>
                                <th class="py-3 px-3">Tipe</th>
                                <th class="py-3 px-3 text-right">Tarif (%)</th>
                                <th class="py-3 px-3">Rekening Penampung</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                @role('admin')
                                    <th class="py-3 px-3 text-center">Aksi</th>
                                @endrole
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($taxes as $tax)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-3 font-mono font-semibold text-xs text-blue-600">{{ $tax->code }}</td>
                                    <td class="py-3 px-3 font-medium">{{ $tax->name }}</td>
                                    <td class="py-3 px-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 uppercase">
                                            {{ $tax->type }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold text-blue-600">{{ $tax->rate }}%</td>
                                    <td class="py-3 px-3 text-xs text-gray-600">{{ $tax->account->code ?? '-' }} - {{ $tax->account->name ?? '-' }}</td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $tax->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $tax->is_active ? 'Aktif' : 'Non-Aktif' }}
                                        </span>
                                    </td>
                                    @role('admin')
                                        <td class="py-3 px-3 text-center">
                                            <div class="flex justify-center items-center gap-2">
                                                <button @click="editTax = {{ json_encode($tax) }}; openEditModal = true" class="text-xs text-blue-600 hover:underline">Edit</button>
                                                <form action="{{ route('admin.taxes.destroy', $tax->id) }}" method="POST" onsubmit="return confirm('Hapus skema pajak ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-600 hover:underline">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    @endrole
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-4 text-center text-gray-400 italic">Belum ada master tarif pajak.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <!-- MODAL TAMBAH MASTER PAJAK (ADMIN) -->
    <div x-show="openCreateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-white rounded-xl shadow-lg max-w-md w-full p-6 space-y-4">
            <h3 class="text-lg font-bold text-gray-800">Tambah Skema Pajak</h3>
            <form action="{{ route('admin.taxes.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Kode Pajak</label>
                    <input type="text" name="code" placeholder="Misal: PPN12" required class="w-full px-3 py-2 border rounded-lg text-sm uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Pajak</label>
                    <input type="text" name="name" placeholder="Pajak Pertambahan Nilai 12%" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tarif (%)</label>
                        <input type="number" step="0.01" name="rate" placeholder="12" required class="w-full px-3 py-2 border rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tipe Skema</label>
                        <select name="type" required class="w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="PPN">PPN</option>
                            <option value="PPh21">PPh21</option>
                            <option value="PPh23">PPh23</option>
                            <option value="PPh_Final">PPh_Final</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Akun Rekening Penampung</label>
                    <select name="account_id" required class="w-full px-3 py-2 border rounded-lg text-xs">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openCreateModal = false" class="px-4 py-2 bg-gray-200 rounded-lg text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg text-xs font-semibold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT MASTER PAJAK (ADMIN) -->
    <div x-show="openEditModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="bg-white rounded-xl shadow-lg max-w-md w-full p-6 space-y-4">
            <h3 class="text-lg font-bold text-gray-800">Edit Skema Pajak</h3>
            <form :action="'/admin/taxes/master/' + editTax.id" method="POST" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Kode Pajak</label>
                    <input type="text" name="code" x-model="editTax.code" required class="w-full px-3 py-2 border rounded-lg text-sm uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Pajak</label>
                    <input type="text" name="name" x-model="editTax.name" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tarif (%)</label>
                        <input type="number" step="0.01" name="rate" x-model="editTax.rate" required class="w-full px-3 py-2 border rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                        <select name="is_active" x-model="editTax.is_active" class="w-full px-3 py-2 border rounded-lg text-sm">
                            <option value="1">Aktif</option>
                            <option value="0">Non-Aktif</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tipe Skema</label>
                    <select name="type" x-model="editTax.type" required class="w-full px-3 py-2 border rounded-lg text-sm">
                        <option value="PPN">PPN</option>
                        <option value="PPh21">PPh21</option>
                        <option value="PPh23">PPh23</option>
                        <option value="PPh_Final">PPh_Final</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Akun Rekening Penampung</label>
                    <select name="account_id" x-model="editTax.account_id" required class="w-full px-3 py-2 border rounded-lg text-xs">
                        @foreach($accounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openEditModal = false" class="px-4 py-2 bg-gray-200 rounded-lg text-xs font-semibold">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-xs font-semibold">Update</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
