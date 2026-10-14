<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengolahan Gaji (Payroll)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Pengolahan Gaji & PPh 21</h1>
                <p class="text-sm text-gray-500">Kelola penggajian karyawan dan integrasi jurnal otomatis</p>
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

            <!-- Form Process Payroll -->
            <div class="bg-white p-6 rounded-xl shadow-sm h-fit space-y-4">
                <h2 class="text-lg font-bold text-gray-800">Proses Gaji Baru</h2>
                <form action="{{ route('payrolls.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Karyawan</label>
                        <select name="user_id" required class="w-full px-3 py-2 border rounded-lg outline-none">
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
                        <input type="month" name="period" value="{{ $period }}" required class="w-full px-3 py-2 border rounded-lg outline-none">
                    </div>

                    <!-- Dynamic Component Item Example -->
                    <div class="border-t pt-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Tunjangan Tambahan (Optional)</label>
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <input type="text" name="allowances[0][name]" value="Tunjangan Makan" class="px-2 py-1.5 border rounded text-xs">
                            <input type="number" name="allowances[0][amount]" placeholder="Jumlah Rp" class="px-2 py-1.5 border rounded text-xs">
                        </div>
                    </div>

                    <div class="border-t pt-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Potongan Non-Pajak (Optional)</label>
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <input type="text" name="deductions[0][name]" value="BPJS Kesehatan" class="px-2 py-1.5 border rounded text-xs">
                            <input type="number" name="deductions[0][amount]" placeholder="Jumlah Rp" class="px-2 py-1.5 border rounded text-xs">
                        </div>
                    </div>

                    <!-- Posting Account Selection -->
                    <div class="border-t pt-3 space-y-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase">Pengaturan Jurnal Keuangan</label>

                        <div>
                            <label class="block text-xs text-gray-600">Akun Beban Gaji (Debit)</label>
                            <select name="salary_expense_account_id" required class="w-full px-2 py-1.5 border rounded text-xs">
                                @foreach($expenseAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-gray-600">Akun Pembayaran Kas/Bank (Kredit)</label>
                            <select name="cash_account_id" required class="w-full px-2 py-1.5 border rounded text-xs">
                                @foreach($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs text-gray-600">Akun Hutang PPh 21 (Kredit)</label>
                            <select name="pph_account_id" class="w-full px-2 py-1.5 border rounded text-xs">
                                <option value="">-- Gunakan Akun PPh Default --</option>
                                @foreach($liabilityAccounts as $acc)
                                    <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition shadow-md">
                        Hitung & Proses Gaji
                    </button>
                </form>
            </div>

            <!-- List Payroll Completed -->
            <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-sm space-y-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-800">Daftar Gaji Periode {{ $period }}</h2>
                    <form action="{{ route('payrolls.index') }}" method="GET">
                        <input type="month" name="period" value="{{ $period }}" onchange="this.form.submit()" class="px-3 py-1 border rounded-lg text-xs outline-none">
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b font-semibold text-gray-500 uppercase bg-gray-50">
                                <th class="py-3 px-3">Pegawai</th>
                                <th class="py-3 px-3 text-right">Gaji Pokok</th>
                                <th class="py-3 px-3 text-right">Tunjangan</th>
                                <th class="py-3 px-3 text-right text-red-600">PPh 21</th>
                                <th class="py-3 px-3 text-right font-bold">Gaji Bersih</th>
                                <th class="py-3 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($payrolls as $p)
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-3 font-semibold text-gray-800">{{ $p->user->name }}</td>
                                    <td class="py-3 px-3 text-right">Rp {{ number_format($p->basic_salary, 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right">Rp {{ number_format($p->total_allowance, 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right text-red-600">Rp {{ number_format($p->pph21_amount, 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-right font-bold text-blue-600">Rp {{ number_format($p->net_salary, 0, ',', '.') }}</td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                            {{ strtoupper($p->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-4 text-center text-gray-400 italic">Belum ada data penggajian untuk periode ini.</td>
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
