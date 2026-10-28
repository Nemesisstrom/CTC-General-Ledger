<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lupa Kata Sandi - CTC General Ledger</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
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
<body class="min-h-screen bg-[#072419] flex items-center justify-center p-4 relative overflow-hidden antialiased select-none">

    {{-- KREATIF BACKGROUND LAYERS --}}
    <div class="absolute inset-0 bg-grid-pattern opacity-60 pointer-events-none"></div>
    <div class="animate-glow-slow absolute -top-28 -left-28 w-96 h-96 bg-[#176B45] rounded-full blur-[120px] pointer-events-none"></div>
    <div class="animate-glow-reverse absolute -bottom-32 -right-32 w-[30rem] h-[30rem] bg-[#104B35] rounded-full blur-[140px] pointer-events-none"></div>
    <div class="animate-glow-slow absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-emerald-500/20 rounded-full blur-[100px] pointer-events-none"></div>

    {{-- MAIN CARD --}}
    <div class="relative z-10 w-full max-w-md bg-white/95 backdrop-blur-xl rounded-3xl shadow-[0_20px_60px_-15px_rgba(0,0,0,0.5)] border border-white/30 p-8 sm:p-9">
        
        <div class="text-center mb-6">
            <div class="inline-block relative mb-3 group">
                <div class="absolute -inset-1.5 bg-gradient-to-r from-emerald-500 to-teal-400 rounded-2xl blur-md group-hover:blur-lg opacity-60 transition duration-300"></div>
                <img 
                    src="{{ asset('images/Logo_CTC.png') }}" 
                    alt="Logo CTC" 
                    class="animate-float relative w-16 h-16 object-contain mx-auto drop-shadow-md"
                >
            </div>
            <h1 class="text-xl font-black tracking-tight text-[#202B25]">Reset Kata Sandi</h1>
            <p class="text-xs text-[#68756D] mt-1 leading-relaxed">
                Masukkan email terdaftar Anda. Tautan pemulihan akan dikirimkan ke kotak masuk email.
            </p>
        </div>

        @if (session('success'))
            <div class="mb-5 bg-[#E8F4EC] border border-[#D5E8DA] text-[#176B45] px-4 py-3 rounded-xl text-xs font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-sm shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-5 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-xs font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-sm shrink-0"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-xs font-bold text-[#526057] mb-1.5">Alamat Email Terdaftar</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="fa-solid fa-envelope text-xs"></i>
                    </span>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        required 
                        autofocus
                        placeholder="karyawan@ctc.co.id"
                        class="w-full pl-10 pr-4 py-2.5 bg-[#F9FBF9] border border-[#E1E8E2] rounded-xl text-xs text-[#202B25] outline-none transition focus:border-[#176B45] focus:bg-white focus:ring-2 focus:ring-[#176B45]/10"
                    >
                </div>
            </div>

            <button 
                type="submit" 
                class="w-full bg-[#176B45] hover:bg-[#104B35] active:scale-[0.98] text-white font-bold py-2.5 rounded-xl transition duration-150 shadow-lg shadow-[#176B45]/25 flex items-center justify-center gap-2 text-xs"
            >
                <i class="fa-solid fa-paper-plane text-xs"></i>
                <span>Kirim Link Reset Password</span>
            </button>
        </form>

        <p class="text-center text-xs text-[#68756D] mt-6 pt-4 border-t border-[#E1E8E2]">
            Kembali ke 
            <a href="{{ route('login') }}" class="text-[#176B45] font-bold hover:underline">Halaman Login</a>
        </p>
    </div>

</body>
</html>