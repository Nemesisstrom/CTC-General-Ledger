@extends('layouts.app')

@section('title', 'Manajemen User - Sistem Keuangan CTC')

@section('content')
    <div x-data="{ openCreateModal: false, openEditModal: false, editData: {}, search: '', roleFilter: '' }" class="feature-page min-w-0 p-3 sm:p-6 lg:p-8 space-y-6">
        <main class="mx-auto max-w-7xl space-y-6">
            
            {{-- Header Banner --}}
            <section class="dashboard-enter relative isolate overflow-hidden rounded-3xl bg-gradient-to-br from-[#104B35] via-[#176B45] to-[#248052] px-4 py-6 text-white shadow-lg shadow-[#104B35]/10 sm:px-8 sm:py-8">
                <div class="pointer-events-none absolute -right-12 -top-24 -z-10 h-72 w-72 rounded-full border border-white/10"></div>
                <div class="pointer-events-none absolute -right-2 -top-14 -z-10 h-52 w-52 rounded-full border border-white/10"></div>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#C6E4D0]">CTC General Ledger</p>
                        <div class="mt-2 text-xl font-bold tracking-tight text-white sm:text-2xl" role="heading" aria-level="1">
                            Manajemen Pengguna
                        </div>
                        <p class="mt-1 text-xs text-[#E8F4EC] sm:text-sm">
                            Kelola akun pengguna, hak akses (roles), dan informasi gaji dasar.
                        </p>
                    </div>
                    <button @click="openCreateModal = true" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-[#104B35] shadow-sm transition hover:bg-[#E8F4EC] focus:outline-none focus:ring-2 focus:ring-white/50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Tambah User Baru
                    </button>
                </div>
            </section>

            {{-- Alert Notifications --}}
            @if(session('success'))
                <div class="flex items-center gap-3 rounded-xl border border-[#C6E4D0] bg-[#E8F4EC] p-4 text-xs font-medium text-[#104B35] shadow-sm sm:text-sm">
                    <svg class="h-5 w-5 shrink-0 text-[#176B45]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-medium text-red-800 shadow-sm sm:text-sm">
                    <svg class="h-5 w-5 shrink-0 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs font-medium text-red-800 shadow-sm sm:text-sm">
                    <div class="flex items-center gap-2 font-bold mb-1">
                        <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Terdapat kesalahan pengisian:
                    </div>
                    <ul class="list-disc pl-5 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Filter & Table Container --}}
            <div class="rounded-2xl border border-[#E1E8E2] bg-white shadow-sm overflow-hidden min-w-0">
                <tbody class="divide-y divide-[#E1E8E2] text-sm">
                
                {{-- Tabel Data User --}}
                <div class="w-full overflow-x-auto overscroll-x-contain">
                    <table class="w-full min-w-[800px] text-left">
                        <thead class="bg-[#F6F8F6]">
                            <tr class="text-[11px] font-semibold uppercase tracking-wider text-[#68756D]">
                                <th scope="col" class="py-3.5 px-5">Pengguna</th>
                                <th scope="col" class="py-3.5 px-4">Role</th>
                                <th scope="col" class="py-3.5 px-4">Gaji Pokok</th>
                                <th scope="col" class="py-3.5 px-4 text-center">Status</th>
                                <th scope="col" class="py-3.5 px-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E1E8E2] text-sm">
                            @forelse($users as $user)
                                <tr class="transition-colors hover:bg-[#F6F8F6]">
                                    <td class="py-3.5 px-5">
                                        <div class="font-semibold text-[#202B25]">{{ $user->name }}</div>
                                        <div class="text-xs text-[#68756D] font-mono mt-0.5">{{ $user->email }}</div>
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider
                                            {{ $user->hasRole('admin') ? 'bg-purple-100 text-purple-800 border border-purple-200' : ($user->hasRole('akuntan') ? 'bg-[#E8F4EC] text-[#176B45] border border-[#C6E4D0]' : 'bg-[#F6F8F6] text-[#68756D] border border-[#E1E8E2]') }}">
                                            {{ $user->getRoleNames()->first() ?? 'Staff' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-xs font-semibold tabular-nums text-[#202B25] whitespace-nowrap">
                                        Rp {{ number_format($user->base_salary ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $user->is_active ? 'bg-[#E8F4EC] text-[#176B45]' : 'bg-red-50 text-red-600 border border-red-100' }}">
                                            <span class="h-1.5 w-1.5 rounded-full {{ $user->is_active ? 'bg-[#176B45]' : 'bg-red-500' }}"></span>
                                            {{ $user->is_active ? 'Aktif' : 'Non-Aktif' }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-2 justify-end">
                                            <button @click="openEditModal = true; editData = {
                                                id: '{{ $user->id }}',
                                                name: '{{ addslashes($user->name) }}',
                                                email: '{{ $user->email }}',
                                                role: '{{ $user->getRoleNames()->first() ?? '' }}',
                                                base_salary: '{{ $user->base_salary ?? 0 }}',
                                                is_active: '{{ $user->is_active ? 1 : 0 }}'
                                            }" class="rounded-lg bg-[#F6F8F6] px-2.5 py-1.5 text-xs font-semibold text-[#176B45] border border-[#E1E8E2] transition hover:bg-[#E8F4EC] hover:border-[#C6E4D0]">
                                                Edit
                                            </button>
                                            @if(auth()->id() !== $user->id)
                                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus user ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-lg bg-red-50 px-2.5 py-1.5 text-xs font-semibold text-red-600 border border-red-100 transition hover:bg-red-100">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-[#68756D]">
                                        <p class="text-sm font-medium text-[#202B25]">Belum ada data pengguna</p>
                                        <p class="mt-1 text-xs">Klik tombol "Tambah User Baru" untuk menambahkan.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        {{-- Modal Tambah User --}}
        <div x-show="openCreateModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" x-cloak>
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 border border-[#E1E8E2]" @click.away="openCreateModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-[#E1E8E2] mb-4">
                    <h3 class="text-base font-bold text-[#202B25]">Tambah User Baru</h3>
                    <button type="button" @click="openCreateModal = false" class="text-[#68756D] hover:text-[#202B25]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-[#68756D] mb-1">Nama Lengkap</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border border-[#E1E8E2] rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#176B45] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#68756D] mb-1">Email</label>
                        <input type="email" name="email" required class="w-full px-3 py-2 border border-[#E1E8E2] rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#176B45] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#68756D] mb-1">Password</label>
                        <input type="password" name="password" required class="w-full px-3 py-2 border border-[#E1E8E2] rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#176B45] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#68756D] mb-1">Role / Peran</label>
                        <select name="role" required class="w-full px-3 py-2 border border-[#E1E8E2] rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#176B45] focus:border-transparent">
                            <option value="">Pilih Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ strtoupper($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#68756D] mb-1">Gaji Pokok (Rp)</label>
                        <input type="number" name="base_salary" required class="w-full px-3 py-2 border border-[#E1E8E2] rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#176B45] focus:border-transparent">
                    </div>
                    <div class="flex justify-end space-x-2 pt-4 border-t border-[#E1E8E2]">
                        <button type="button" @click="openCreateModal = false" class="px-4 py-2 bg-[#F6F8F6] text-[#68756D] text-xs font-semibold rounded-xl transition hover:bg-[#E1E8E2]">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-[#176B45] text-white text-xs font-semibold rounded-xl transition hover:bg-[#104B35]">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Edit User --}}
        <div x-show="openEditModal" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" x-cloak>
            <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 border border-[#E1E8E2]" @click.away="openEditModal = false">
                <div class="flex items-center justify-between pb-3 border-b border-[#E1E8E2] mb-4">
                    <h3 class="text-base font-bold text-[#202B25]">Edit User</h3>
                    <button type="button" @click="openEditModal = false" class="text-[#68756D] hover:text-[#202B25]">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form :action="'/admin/users/' + editData.id" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-semibold text-[#68756D] mb-1">Nama Lengkap</label>
                        <input type="text" name="name" x-model="editData.name" required class="w-full px-3 py-2 border border-[#E1E8E2] rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#176B45] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#68756D] mb-1">Email</label>
                        <input type="email" name="email" x-model="editData.email" required class="w-full px-3 py-2 border border-[#E1E8E2] rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#176B45] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#68756D] mb-1">Password Baru (Opsional)</label>
                        <input type="password" name="password" placeholder="Kosongkan jika tidak diubah" class="w-full px-3 py-2 border border-[#E1E8E2] rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#176B45] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#68756D] mb-1">Role / Peran</label>
                        <select name="role" x-model="editData.role" required class="w-full px-3 py-2 border border-[#E1E8E2] rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#176B45] focus:border-transparent">
                            <option value="">Pilih Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ strtoupper($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#68756D] mb-1">Gaji Pokok (Rp)</label>
                        <input type="number" name="base_salary" x-model="editData.base_salary" required class="w-full px-3 py-2 border border-[#E1E8E2] rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#176B45] focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#68756D] mb-1">Status Akun</label>
                        <select name="is_active" x-model="editData.is_active" class="w-full px-3 py-2 border border-[#E1E8E2] rounded-xl text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#176B45] focus:border-transparent">
                            <option value="1">Aktif</option>
                            <option value="0">Non-Aktif</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-2 pt-4 border-t border-[#E1E8E2]">
                        <button type="button" @click="openEditModal = false" class="px-4 py-2 bg-[#F6F8F6] text-[#68756D] text-xs font-semibold rounded-xl transition hover:bg-[#E1E8E2]">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-[#176B45] text-white text-xs font-semibold rounded-xl transition hover:bg-[#104B35]">Perbarui</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection