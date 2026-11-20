@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="container mx-auto max-w-4xl px-4 py-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Pengaturan Profil</h1>
        <p class="text-sm text-gray-500">Perbarui informasi akun, informasi cabang, dan kata sandi Anda</p>
    </div>

    <!-- Informasi Cabang & Role (Read Only) -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex flex-wrap gap-6 text-sm">
        <div>
            <span class="text-gray-500 block">Cabang Terdaftar:</span>
            <span class="font-bold text-blue-900">{{ $user->branch->name ?? 'Semua Cabang (Pusat)' }}</span>
        </div>
        <div>
            <span class="text-gray-500 block">Hak Akses / Role:</span>
            <span class="font-bold text-blue-900 uppercase">{{ $user->getRoleNames()->first() ?? $user->role ?? 'User' }}</span>
        </div>
    </div>

    <!-- Form Update Informasi Profil -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi Pribadi</h2>

        @if(session('success'))
            <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4 text-sm">{{ session('success') }}</div>
        @endif

        <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')

            <div>
                <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="mt-1 w-full border rounded-lg px-3 py-2">
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Alamat Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="mt-1 w-full border rounded-lg px-3 py-2">
                @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="pt-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <!-- Form Update Password -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Ubah Kata Sandi</h2>

        @if(session('success_password'))
            <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4 text-sm">{{ session('success_password') }}</div>
        @endif

        <form action="{{ route('profile.password') }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700">Kata Sandi Saat Ini</label>
                <input type="password" name="current_password" required class="mt-1 w-full border rounded-lg px-3 py-2">
                @error('current_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Kata Sandi Baru</label>
                <input type="password" name="password" required class="mt-1 w-full border rounded-lg px-3 py-2">
                @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Konfirmasi Kata Sandi Baru</label>
                <input type="password" name="password_confirmation" required class="mt-1 w-full border rounded-lg px-3 py-2">
            </div>

            <div class="pt-2">
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-2 rounded-lg text-sm font-medium">
                    Perbarui Kata Sandi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
