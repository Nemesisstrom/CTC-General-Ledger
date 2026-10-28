<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Password Baru - CTC General Ledger</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#0B3C2A] via-[#104B35] to-[#176B45] flex items-center justify-center p-4 antialiased select-none">

    <div class="w-full max-w-md bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-white/20 p-8">
        
        <div class="text-center mb-6">
            <h1 class="text-lg font-black text-[#202B25]">Buat Kata Sandi Baru</h1>
            <p class="text-xs text-[#68756D] mt-1">Silakan masukkan kata sandi baru untuk akun Anda</p>
        </div>

        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-xs font-medium space-y-1">
                <ul class="list-disc list-inside space-y-0.5 text-[11px]">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST" class="space-y-4" x-data="{ showPass: false, showConfirm: false }">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-xs font-bold text-[#526057] mb-1">Alamat Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required readonly
                    class="w-full px-4 py-2.5 bg-gray-100 border border-[#E1E8E2] rounded-xl text-xs text-gray-500 cursor-not-allowed">
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-[#526057] mb-1">Kata Sandi Baru</label>
                <div class="relative">
                    <input :type="showPass ? 'text' : 'password'" id="password" name="password" required placeholder="Minimal 8 karakter"
                        class="w-full pl-4 pr-10 py-2.5 bg-[#F9FBF9] border border-[#E1E8E2] rounded-xl text-xs text-[#202B25] outline-none transition focus:border-[#176B45]">
                    <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-[#176B45]">
                        <i class="fa-solid" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-[#526057] mb-1">Konfirmasi Kata Sandi Baru</label>
                <div class="relative">
                    <input :type="showConfirm ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required placeholder="Ulangi kata sandi"
                        class="w-full pl-4 pr-10 py-2.5 bg-[#F9FBF9] border border-[#E1E8E2] rounded-xl text-xs text-[#202B25] outline-none transition focus:border-[#176B45]">
                    <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-[#176B45]">
                        <i class="fa-solid" :class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full bg-[#176B45] hover:bg-[#104B35] active:scale-[0.98] text-white font-bold py-2.5 rounded-xl transition duration-150 shadow-md shadow-[#176B45]/20 text-xs">
                Simpan Kata Sandi Baru
            </button>
        </form>
    </div>

</body>
</html>