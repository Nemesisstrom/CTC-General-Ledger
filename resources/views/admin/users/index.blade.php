@extends('layouts.app')

@section('title', 'Manajemen User - Sistem Keuangan')

@section('content')
    <div x-data="{ openCreateModal: false, openEditModal: false, editData: {} }" class="min-w-0 p-4 sm:p-6 lg:p-8">
        <main class="mx-auto max-w-7xl space-y-6">
            <h1 class="text-2xl font-bold tracking-tight text-gray-800">Manajemen Pengguna (User Management)</h1>

            <!-- Alert Notifications -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-700 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-700 rounded-lg text-sm">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Header Action -->
            <div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <p class="text-sm text-gray-600">Kelola pengguna, hak akses peran (roles), dan informasi gaji dasar.</p>
                <button @click="openCreateModal = true" class="shrink-0 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                    + Tambah User Baru
                </button>
            </div>

            <!-- Tabel Data User -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] border-collapse text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase">
                            <th class="py-3 px-6">Nama</th>
                            <th class="py-3 px-6">Email</th>
                            <th class="py-3 px-6">Role Spatie</th>
                            <th class="py-3 px-6">Gaji Pokok</th>
                            <th class="py-3 px-6 text-center">Status</th>
                            <th class="py-3 px-6 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 px-6 font-semibold text-gray-800">{{ $user->name }}</td>
                                <td class="py-4 px-6 text-gray-600">{{ $user->email }}</td>
                                <td class="py-4 px-6">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold uppercase
                                        {{ $user->hasRole('admin') ? 'bg-purple-100 text-purple-800' : ($user->hasRole('akuntan') ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ $user->getRoleNames()->first() ?? 'Staff' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 font-semibold">Rp {{ number_format($user->base_salary ?? 0, 0, ',', '.') }}</td>
                                <td class="py-4 px-6 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        {{ $user->is_active ? 'Aktif' : 'Non-Aktif' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-center space-x-2">
                                    <button @click="openEditModal = true; editData = {
                                        id: '{{ $user->id }}',
                                        name: '{{ addslashes($user->name) }}',
                                        email: '{{ $user->email }}',
                                        role: '{{ $user->getRoleNames()->first() ?? '' }}',
                                        base_salary: '{{ $user->base_salary ?? 0 }}',
                                        is_active: '{{ $user->is_active ? 1 : 0 }}'
                                    }" class="text-blue-600 hover:text-blue-800 font-medium text-xs">
                                        Edit
                                    </button>
                                    @if(auth()->id() !== $user->id)
                                        <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium text-xs">
                                                Hapus
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-gray-500">Belum ada user terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </main>

        <!-- Modal Tambah User -->
        <div x-show="openCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-cloak>
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6" @click.away="openCreateModal = false">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Tambah User Baru</h3>
            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Role / Peran</label>
                    <select name="role" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white">
                        <option value="">Pilih Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ strtoupper($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Gaji Pokok (Rp)</label>
                    <input type="number" name="base_salary" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div class="flex justify-end space-x-2 pt-4">
                    <button type="button" @click="openCreateModal = false" class="px-4 py-2 bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>

        <!-- Modal Edit User -->
        <div x-show="openEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-cloak>
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6" @click.away="openEditModal = false">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Edit User</h3>
            <form :action="'/admin/users/' + editData.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" x-model="editData.name" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
                    <input type="email" name="email" x-model="editData.email" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Password Baru (Opsional)</label>
                    <input type="password" name="password" placeholder="Kosongkan jika tidak diubah" class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Role / Peran</label>
                    <select name="role" x-model="editData.role" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white">
                        <option value="">Pilih Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ strtoupper($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Gaji Pokok (Rp)</label>
                    <input type="number" name="base_salary" x-model="editData.base_salary" required class="w-full px-3 py-2 border rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status Akun</label>
                    <select name="is_active" x-model="editData.is_active" class="w-full px-3 py-2 border rounded-lg text-sm bg-white">
                        <option value="1">Aktif</option>
                        <option value="0">Non-Aktif</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-2 pt-4">
                    <button type="button" @click="openEditModal = false" class="px-4 py-2 bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg">Perbarui</button>
                </div>
            </form>
        </div>
        </div>
    </div>
@endsection
