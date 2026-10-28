@extends('layouts.app')

@section('title', 'Otomatisasi Pajak (PPN & PPh)')

@section('content')
    <div x-data="{ openCreateModal: false, openEditModal: false, editTax: {}, taxPage: 1 }" class="feature-page mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Header Hero Banner - Otomatisasi & Kelola Pajak -->
        <section class="dashboard-enter relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-[#104B35] via-[#176B45] to-[#248052] px-4 py-6 text-white shadow-lg shadow-[#104B35]/10 sm:px-8 sm:py-8 mb-6">
            <div class="pointer-events-none absolute -right-12 -top-24 -z-10 h-72 w-72 rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute -right-2 -top-14 -z-10 h-52 w-52 rounded-full border border-white/10"></div>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#C6E4D0]">CTC General Ledger</p>
                    <div class="mt-2 text-xl font-bold tracking-tight text-white sm:text-2xl" role="heading" aria-level="1">
                        Otomatisasi & Kelola Pajak
                    </div>
                    <p class="mt-1 text-xs text-[#E8F4EC] sm:text-sm">
                        Hitung, Posting, & Manajemen Tarif Pajak Perusahaan.
                    </p>
                </div>
                <a href="{{ route('dashboard') }}" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-[#104B35] shadow-sm transition hover:bg-[#E8F4EC] focus:outline-none focus:ring-2 focus:ring-white/50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Kembali ke Dashboard
                </a>
            </div>
        </section>

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm font-medium">
                <ul class="list-disc list-inside space-y-1">
                    @if(session('error')) <li>{{ session('error') }}</li> @endif
                    @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Form Kalkulator Transaksi Pajak -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-800 border-b pb-3">Proses Pajak Transaksi</h2>
                <form action="{{ route('admin.taxes.process') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Tanggal Transaksi</label>
                        <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Jenis Transaksi</label>
                        <select name="transaction_type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="sales">Penjualan (+ PPN Keluaran)</option>
                            <option value="purchase">Pembelian Jasa/Barang (- Potongan PPh 23)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Pilih Jenis Pajak</label>
                        <select name="tax_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
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

                    <!-- Input Nilai Transaksi DPP dengan Format Titik Otomatis -->
                    <div x-data="{ 
                        rawSubtotal: '{{ old('subtotal') }}',
                        displaySubtotal: '{{ old('subtotal') ? number_format(old('subtotal'), 0, ',', '.') : '' }}',
                        formatInput(e) {
                            let numeric = e.target.value.replace(/\D/g, '');
                            this.rawSubtotal = numeric;
                            this.displaySubtotal = numeric ? new Intl.NumberFormat('id-ID').format(numeric) : '';
                        }
                    }">
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Nilai Transaksi DPP / Subtotal (Rp)</label>
                        <input 
                            type="text" 
                            inputmode="numeric"
                            x-model="displaySubtotal"
                            @input="formatInput($event)"
                            placeholder="0" 
                            required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500 font-mono font-semibold"
                        >
                        <input type="hidden" name="subtotal" :value="rawSubtotal">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Akun Kas / Bank (Pembayaran)</label>
                        <select name="cash_account_id" required class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500">
                            @foreach($cashAccounts as $cash)
                                <option value="{{ $cash->id }}">{{ $cash->code }} - {{ $cash->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Akun Pendapatan / Beban</label>
                        <select name="revenue_expense_id" required class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500">
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ strtoupper($acc->type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 uppercase mb-1">Keterangan / Memo</label>
                        <textarea name="description" rows="2" placeholder="Catatan transaksi pajak..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-emerald-700 hover:bg-emerald-800 text-white font-medium py-2.5 rounded-lg transition shadow-sm">
                        Hitung & Posting Jurnal Pajak
                    </button>
                </form>
            </div>

            <!-- Tabel Master Data Rates Pajak -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <div class="flex justify-between items-center border-b pb-3">
                    <h2 class="text-lg font-bold text-gray-800">Master Data Tarif & Akun Penampung Pajak</h2>
                    @role('admin')
                        <button @click="openCreateModal = true" class="text-xs bg-emerald-700 hover:bg-emerald-800 text-white px-3 py-2 rounded-lg font-semibold transition shadow-sm flex items-center gap-1">
                            ➕ Tambah Skema Pajak
                        </button>
                    @endrole
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b text-gray-500 uppercase bg-gray-50 font-semibold tracking-wider">
                                <th class="py-3 px-3 text-left">Kode</th>
                                <th class="py-3 px-3 text-left">Nama Pajak</th>
                                <th class="py-3 px-3 text-center">Tipe</th>
                                <th class="py-3 px-3 text-right">Tarif (%)</th>
                                <th class="py-3 px-3 text-left">Rekening Penampung</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                @role('admin')
                                    <th class="py-3 px-3 text-center">Aksi</th>
                                @endrole
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($taxes as $tax)
                                <tr x-cloak x-show="Math.floor({{ $loop->index }} / 10) + 1 === taxPage" class="hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-3 font-mono text-xs font-semibold text-emerald-800 whitespace-nowrap">{{ $tax->code }}</td>
                                    <td class="py-3 px-3 font-medium text-gray-800">{{ $tax->name }}</td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase bg-purple-100 text-purple-800 border border-purple-200">
                                            {{ $tax->type }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-bold tabular-nums text-emerald-700 whitespace-nowrap">{{ $tax->rate }}%</td>
                                    <td class="py-3 px-3 text-gray-600 font-mono text-xs whitespace-nowrap">{{ $tax->account->code ?? '-' }} - {{ $tax->account->name ?? '-' }}</td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase {{ $tax->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                            {{ $tax->is_active ? 'Aktif' : 'Non-Aktif' }}
                                        </span>
                                    </td>
                                    @role('admin')
                                        <td class="py-3 px-3 text-center whitespace-nowrap">
                                            <div class="flex items-center justify-center gap-2">
                                                <button @click="editTax = @js($tax->only(['id', 'code', 'name', 'rate', 'type', 'account_id', 'is_active'])); openEditModal = true" class="text-xs text-emerald-700 hover:text-emerald-900 font-semibold underline">Edit</button>
                                                <form action="{{ route('admin.taxes.destroy', $tax->id) }}" method="POST" class="inline-flex" onsubmit="return confirm('Hapus skema pajak ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800 font-semibold underline">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    @endrole
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-6 text-center text-gray-400 italic">Belum ada master tarif pajak.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination UI (Alpine.js) jika data > 10 -->
                @if($taxes->count() > 10)
                    <div class="flex items-center justify-end gap-1 border-t border-gray-100 pt-3">
                        @for($page = 1; $page <= ceil($taxes->count() / 10); $page++)
                            <button
                                type="button"
                                @click="taxPage = {{ $page }}"
                                :class="taxPage === {{ $page }} ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                                class="h-8 min-w-8 px-2 text-xs font-semibold rounded-lg border transition"
                            >
                                {{ $page }}
                            </button>
                        @endfor
                    </div>
                @endif
            </div>

        </div>

        <!-- MODAL TAMBAH MASTER PAJAK (ADMIN) -->
        <div x-show="openCreateModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" x-cloak>
            <div class="bg-white rounded-xl shadow-lg max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-gray-800 border-b pb-3">Tambah Skema Pajak</h3>
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
                        <button type="button" @click="openCreateModal = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-xs font-semibold">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-semibold">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL EDIT MASTER PAJAK (ADMIN) -->
        <div x-show="openEditModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" x-cloak>
            <div class="bg-white rounded-xl shadow-lg max-w-md w-full p-6 space-y-4">
                <h3 class="text-lg font-bold text-gray-800 border-b pb-3">Edit Skema Pajak</h3>
                <form :action="'{{ route('admin.taxes.update', ['tax' => '__TAX_ID__']) }}'.replace('__TAX_ID__', editTax.id)" method="POST" class="space-y-3">
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
                            <select name="is_active" x-model="editTax.is_active" required class="w-full px-3 py-2 border rounded-lg text-sm">
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
                        <button type="button" @click="openEditModal = false" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-xs font-semibold">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-semibold">Update</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection