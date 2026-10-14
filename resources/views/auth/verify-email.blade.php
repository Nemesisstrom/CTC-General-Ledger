<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Email Anda</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg p-8 text-center space-y-4">
        <h2 class="text-2xl font-bold text-gray-800">Verifikasi Email Anda</h2>
        <p class="text-sm text-gray-600">
            Terima kasih telah mendaftar! Sebelum memulai, harap verifikasi alamat email Anda dengan mengklik link yang baru saja kami kirimkan.
        </p>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('verification.send') }}" method="POST">
            @csrf
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded-lg transition">
                Kirim Ulang Email Verifikasi
            </button>
        </form>

        <form action="{{ route('logout') }}" method="POST" class="pt-2">
            @csrf
            <button type="submit" class="text-sm text-red-600 hover:underline">Keluar / Logout</button>
        </form>
    </div>
</body>
</html>
