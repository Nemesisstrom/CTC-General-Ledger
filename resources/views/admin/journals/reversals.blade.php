<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jurnal Pembalikan (Reversal Ledger)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- Header -->
        <div class="flex justify-between items-center bg-white p-6 rounded-xl shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Modul Jurnal Pembalikan</h1>
                <p class="text-sm text-gray-500">Eksekusi pembatalan transaksi & penyesuaian balance saldo</p>
            </div>
            <a href="{{ route('admin.journals.index') }}" class="text-sm bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg font-medium">
                Kembali ke Jurnal Umum
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Kolom Kiri: Jurnal Aktif yang Bisa Di-Reverse -->
            <div class="bg-white p-6 rounded-xl shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-gray-800 border-b pb-2">Daftar Jurnal Aktif (Posted)</h2>

                <div class="space-y-3">
                    @forelse($postedJournals as $j)
                        <div class="border rounded-xl p-4 bg-gray-50 space-y-2">
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="font-mono font-bold text-sm text-blue-600">{{ $j->journal_number }}</span>
                                    <p class="text-xs text-gray-400">{{ $j->journal_date }}</p>
                                </div>

                                <!-- Form Eksekusi Reverse -->
                                <form action="{{ route('admin.journals.reverse', $j->id) }}" method="POST" onsubmit="return confirm('Proses pembalikan jurnal {{ $j->journal_number }}?');">
                                    @csrf
                                    <button type="submit" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg font-semibold transition shadow-sm">
                                        🔄 Eksekusi Reversal
                                    </button>
                                </form>
                            </div>
                            <p class="text-xs text-gray-600 font-medium">{{ $j->description }}</p>
                        </div>
                    @empty
                        <p class="text-center py-6 text-gray-400 text-xs italic">Tidak ada jurnal aktif yang perlu dibatalkan.</p>
                    @endforelse
                </div>

                <div>{{ $postedJournals->links() }}</div>
            </div>

            <!-- Kolom Kanan: Riwayat Jurnal Pembalik -->
            <div class="bg-white p-6 rounded-xl shadow-sm space-y-4">
                <h2 class="text-lg font-bold text-gray-800 border-b pb-2">Riwayat Pembalikan / Reversed</h2>

                <div class="space-y-3">
                    @forelse($reversedJournals as $rj)
                        <div class="border rounded-xl p-4 bg-gray-50 space-y-2 opacity-85">
                            <div class="flex justify-between items-center">
                                <span class="font-mono font-bold text-xs text-gray-700">{{ $rj->journal_number }}</span>
                                <span class="text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded font-bold uppercase">
                                    {{ $rj->status }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 font-medium">{{ $rj->description }}</p>
                        </div>
                    @empty
                        <p class="text-center py-6 text-gray-400 text-xs italic">Belum ada riwayat pembalikan jurnal.</p>
                    @endforelse
                </div>

                <div>{{ $reversedJournals->links() }}</div>
            </div>

        </div>
    </div>
</body>
</html>
