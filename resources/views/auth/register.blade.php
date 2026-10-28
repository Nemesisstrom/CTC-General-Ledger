<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registrasi Karyawan Baru - CTC General Ledger</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        
        @keyframes floatLogo {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-7px) rotate(1.5deg); }
        }
        .animate-float {
            animation: floatLogo 4s ease-in-out infinite;
            will-change: transform;
        }

        @keyframes pulseGlow {
            0%, 100% { opacity: 0.35; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.1); }
        }
        .animate-glow-slow {
            animation: pulseGlow 8s ease-in-out infinite;
        }

        @keyframes pulseGlowReverse {
            0%, 100% { opacity: 0.5; transform: scale(1.05); }
            50% { opacity: 0.25; transform: scale(0.95); }
        }
        .animate-glow-reverse {
            animation: pulseGlowReverse 10s ease-in-out infinite;
        }

        .bg-grid-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.12) 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="min-h-screen bg-[#072419] flex items-center justify-center p-4 sm:p-6 relative overflow-x-hidden overflow-y-auto antialiased select-none">

    {{-- BACKGROUND LAYERS --}}
    <div class="fixed inset-0 bg-grid-pattern opacity-60 pointer-events-none"></div>
    <div class="animate-glow-slow fixed -top-28 -left-28 w-96 h-96 bg-[#176B45] rounded-full blur-[120px] pointer-events-none"></div>
    <div class="animate-glow-reverse fixed -bottom-32 -right-32 w-[30rem] h-[30rem] bg-[#104B35] rounded-full blur-[140px] pointer-events-none"></div>
    <div class="animate-glow-slow fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-emerald-500/20 rounded-full blur-[100px] pointer-events-none"></div>

    {{-- MAIN FORM CARD --}}
    <div class="relative z-10 w-full max-w-md bg-white/95 backdrop-blur-xl rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.5)] border border-white/30 p-8 sm:p-9 my-8">
        
        <div class="text-center mb-6">
            <div class="inline-block relative mb-2 group">
                <div class="absolute -inset-1 bg-gradient-to-r from-emerald-500 to-teal-400 rounded-2xl blur-md group-hover:blur-lg opacity-60 transition duration-300"></div>
                <img src="{{ asset('images/Logo_CTC.png') }}" alt="Logo CTC" class="animate-float relative w-14 h-14 object-contain mx-auto drop-shadow-md">
            </div>
            <h1 class="text-lg font-black tracking-tight text-[#202B25]">Pendaftaran Karyawan Baru</h1>
            <p class="text-xs text-[#68756D] mt-0.5">Lengkapi data untuk pembuatan akun operasional</p>
        </div>

        {{-- Safe Error Parsing --}}
        @if ($errors->any())
            @php($errorList = $errors->all())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-xs font-medium space-y-1">
                <div class="flex items-center gap-1.5 font-bold mb-1">
                    <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                    <span>Registrasi Gagal</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-[11px] opacity-90 pl-1">
                    @foreach ($errorList as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register.post') }}" method="POST" class="space-y-3.5" x-data="{ showPass: false, showConfirm: false }">
            @csrf

            {{-- Nama Lengkap --}}
            <div>
                <label for="name" class="block text-xs font-bold text-[#526057] mb-1">Nama Lengkap</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="fa-solid fa-user text-xs"></i>
                    </span>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required
                        placeholder="Nama sesuai KTP"
                        class="w-full pl-10 pr-4 py-2 bg-[#F9FBF9] border border-[#E1E8E2] rounded-xl text-xs text-[#202B25] outline-none transition focus:border-[#176B45] focus:bg-white focus:ring-2 focus:ring-[#176B45]/10">
                </div>
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-xs font-bold text-[#526057] mb-1">Alamat Email Perusahaan</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="fa-solid fa-envelope text-xs"></i>
                    </span>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required
                        placeholder="email@ctc.co.id"
                        class="w-full pl-10 pr-4 py-2 bg-[#F9FBF9] border border-[#E1E8E2] rounded-xl text-xs text-[#202B25] outline-none transition focus:border-[#176B45] focus:bg-white focus:ring-2 focus:ring-[#176B45]/10">
                </div>
            </div>

            {{-- Role Akun Operasional (Default: -- Pilih Role Operasional --) --}}
            <div>
                <label for="role" class="block text-xs font-bold text-[#526057] mb-1">Role/Divisi Operasional</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 pointer-events-none">
                        <i class="fa-solid fa-user-shield text-xs"></i>
                    </span>
                    <select id="role" name="role" required class="w-full pl-10 pr-8 py-2 bg-[#F9FBF9] border border-[#E1E8E2] rounded-xl text-xs text-[#202B25] outline-none transition focus:border-[#176B45] focus:bg-white focus:ring-2 focus:ring-[#176B45]/10 appearance-none">
                        <option value="">-- Pilih Role Operasional --</option>
                        <option value="akuntan" {{ old('role') == 'akuntan' ? 'selected' : '' }}>Akuntan / Finance</option>
                        <option value="staff" {{ old('role') == 'staff' ? 'selected' : '' }}>Staff Operasional</option>
                    </select>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 pointer-events-none">
                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    </span>
                </div>
                <p class="text-[10px] text-gray-400 mt-1">*Akun Administrator khusus dikelola oleh Manajemen Admin Pusat.</p>
            </div>

            {{-- Kata Sandi --}}
            <div>
                <label for="password" class="block text-xs font-bold text-[#526057] mb-1">Kata Sandi</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="fa-solid fa-lock text-xs"></i>
                    </span>
                    <input :type="showPass ? 'text' : 'password'" id="password" name="password" required placeholder="Minimal 8 karakter"
                        class="w-full pl-10 pr-10 py-2 bg-[#F9FBF9] border border-[#E1E8E2] rounded-xl text-xs text-[#202B25] outline-none transition focus:border-[#176B45] focus:bg-white focus:ring-2 focus:ring-[#176B45]/10">
                    <button type="button" @click="showPass = !showPass" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-[#176B45]">
                        <i class="fa-solid" :class="showPass ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            {{-- Konfirmasi Kata Sandi --}}
            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-[#526057] mb-1">Konfirmasi Kata Sandi</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="fa-solid fa-lock text-xs"></i>
                    </span>
                    <input :type="showConfirm ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required placeholder="Ulangi kata sandi"
                        class="w-full pl-10 pr-10 py-2 bg-[#F9FBF9] border border-[#E1E8E2] rounded-xl text-xs text-[#202B25] outline-none transition focus:border-[#176B45] focus:bg-white focus:ring-2 focus:ring-[#176B45]/10">
                    <button type="button" @click="showConfirm = !showConfirm" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-[#176B45]">
                        <i class="fa-solid" :class="showConfirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full bg-[#176B45] hover:bg-[#104B35] active:scale-[0.98] text-white font-bold py-2.5 rounded-xl transition duration-150 shadow-lg shadow-[#176B45]/25 flex items-center justify-center gap-2 text-xs pt-3">
                <i class="fa-solid fa-user-plus text-xs"></i>
                <span>Daftar Akun Baru</span>
            </button>
        </form>

        <p class="text-center text-xs text-[#68756D] mt-5 pt-3 border-t border-[#E1E8E2]">
            Sudah memiliki akun? 
            <a href="{{ route('login') }}" class="text-[#176B45] font-bold hover:underline">Masuk di sini</a>
        </p>
    </div>

</body>
</html>