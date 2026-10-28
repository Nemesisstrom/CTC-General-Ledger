@extends('layouts.app')

@section('title', 'Pengolahan Gaji (Payroll)')

@section('content')
    <div class="feature-page mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">

        <!-- Header Hero Banner - Pengolahan Gaji & PPh 21 -->
        <section class="dashboard-enter relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-[#104B35] via-[#176B45] to-[#248052] px-4 py-6 text-white shadow-lg shadow-[#104B35]/10 sm:px-8 sm:py-8 mb-6">
            <div class="pointer-events-none absolute -right-12 -top-24 -z-10 h-72 w-72 rounded-full border border-white/10"></div>
            <div class="pointer-events-none absolute -right-2 -top-14 -z-10 h-52 w-52 rounded-full border border-white/10"></div>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#C6E4D0]">CTC General Ledger</p>
                    <div class="mt-2 text-xl font-bold tracking-tight text-white sm:text-2xl" role="heading" aria-level="1">
                        Pengolahan Gaji & PPh 21
                    </div>
                    <p class="mt-1 text-xs text-[#E8F4EC] sm:text-sm">
                        Kelola penggajian karyawan dan integrasi jurnal otomatis.
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

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Form Process Payroll -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4 border border-gray-100">
                <h2 class="text-lg font-bold text-gray-800 border-b pb-3">Proses Gaji Baru</h2>
                
                <form action="{{ route('admin.payrolls.store') }}" method="POST" onsubmit="cleanRupiahBeforeSubmit(this)" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Karyawan</label>
                        <select name="user_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                            <option value="">-- Pilih Karyawan --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">
                                    {{ $emp->name }} (Gaji Pokok: Rp {{ number_format($emp->base_salary, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Periode Gaji (YYYY-MM)</label>
                        <input type="month" name="period" value="{{ $period }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg outline-none focus:ring-2 focus:ring-emerald-500 text-sm">
                    </div>

                    <!-- Dynamic Component Item Example -->
                    <div class="border-t pt-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Tunjangan Tambahan (Optional)</label>
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <input type="text" name="allowances[0][name]" value="Tunjangan Makan" placeholder="Nama Tunjangan" class="px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500">
                            <input type="text" inputmode="numeric" name="allowances[0][amount]" placeholder="Jumlah Rp (cth: 100.000)" oninput="formatRupiahInput(this)" class="currency-mask px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div class="border-t pt-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Potongan Non-Pajak (Optional)</label>
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <input type="text" name="deductions[0][name]" value="BPJS Kesehatan" placeholder="Nama Potongan" class="px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500">
                            <input type="text" inputmode="numeric" name="deductions[0][amount]" placeholder="Jumlah Rp (cth: 100.000)" oninput="formatRupiahInput(this)" class="currency-mask px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>

                    <!-- Posting Account Selection -->
                    <div class="border-t pt-3 space-y-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pengaturan Jurnal Keuangan</label>

                        <div>
                            <label class="block text-xs text-gray-600 mb-0.5">Akun Beban Gaji (Debit)</label>
                            <select name="salary_expense_account_id" required class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500">
                                @foreach($expenseAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-gray-600 mb-0.5">Akun Pembayaran Kas/Bank (Kredit)</label>
                            <select name="cash_account_id" required class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500">
                                @foreach($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-gray-600 mb-0.5">Akun Hutang PPh 21 (Kredit)</label>
                            <select name="pph_account_id" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-1 focus:ring-emerald-500">
                                <option value="">-- Gunakan Akun PPh Default --</option>
                                @foreach($liabilityAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-press w-full bg-[#176B45] hover:bg-[#104B35] text-white font-medium py-2.5 rounded-lg transition shadow-md">
                        Hitung & Proses Gaji
                    </button>
                </form>
            </div>

            <!-- List Payroll Completed -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4 border border-gray-100">
                <div class="flex justify-between items-center border-b pb-3">
                    <h2 class="text-lg font-bold text-gray-800">Daftar Gaji Periode {{ $period }}</h2>
                    <form action="{{ route('admin.payrolls.index') }}" method="GET">
                        <input type="month" name="period" value="{{ $period }}" onchange="this.form.submit()" class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs outline-none focus:ring-2 focus:ring-emerald-500">
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b text-gray-500 uppercase bg-gray-50 font-semibold tracking-wider">
                                <th class="py-3 px-3 text-left">Pegawai</th>
                                <th class="py-3 px-3 text-right">Gaji Pokok</th>
                                <th class="py-3 px-3 text-right">Tunjangan</th>
                                <th class="py-3 px-3 text-right text-red-600">PPh 21</th>
                                <th class="py-3 px-3 text-right">Gaji Bersih</th>
                                <th class="py-3 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($payrolls as $p)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-3 px-3 text-left font-semibold text-gray-800 whitespace-nowrap">{{ $p->user->name }}</td>
                                    <td class="py-3 px-3 text-right font-mono tabular-nums text-gray-700 whitespace-nowrap">Rp {{ number_format($p->basic_salary, 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right font-mono tabular-nums text-gray-700 whitespace-nowrap">Rp {{ number_format($p->total_allowance, 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right font-mono tabular-nums text-red-600 font-medium whitespace-nowrap">Rp {{ number_format($p->pph21_amount, 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right font-mono font-bold tabular-nums text-[#176B45] whitespace-nowrap">Rp {{ number_format($p->net_salary, 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-center whitespace-nowrap">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase bg-emerald-100 text-[#176B45] border border-emerald-200">
                                            {{ strtoupper($p->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-gray-400 italic">Belum ada data penggajian untuk periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination yang sudah ada tetap dipertahankan --}}
                @if($payrolls->hasPages())
                    <div class="pt-3 border-t">
                        {{ $payrolls->withQueryString()->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
@endsection

@section('scripts')
<script>
    /**
     * Format input angka secara real-time dengan titik ribuan (Contoh: 100.000)
     */
    function formatRupiahInput(input) {
        let value = input.value.replace(/\D/g, '');
        if (value) {
            input.value = new Intl.NumberFormat('id-ID').format(value);
        } else {
            input.value = '';
        }
    }

    /**
     * Bersihkan tanda titik sebelum form di-submit agar backend menerima nilai integer bersih
     */
    function cleanRupiahBeforeSubmit(form) {
        form.querySelectorAll('.currency-mask').forEach(function(input) {
            input.value = input.value.replace(/\D/g, '');
        });
    }
</script>
@endsection