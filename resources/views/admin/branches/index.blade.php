@extends('layouts.app')

@section('title', 'Manajemen Cabang')

@section('content')
<div class="container-fluid px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Manajemen Cabang</h1>
            <p class="text-sm text-gray-500">Kelola daftar kantor cabang operasional perusahaan</p>
        </div>
        <button onclick="openModal('modalAddBranch')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow">
            + Tambah Cabang
        </button>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nama Cabang</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kontak & Alamat</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Jumlah User</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @forelse($branches as $branch)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-mono font-bold text-blue-600">{{ $branch->code }}</td>
                    <td class="px-6 py-4 font-medium text-gray-900">{{ $branch->name }}</td>
                    <td class="px-6 py-4 text-gray-600">
                        <div>{{ $branch->phone ?? '-' }}</div>
                        <div class="text-xs text-gray-400">{{ $branch->address ?? '-' }}</div>
                    </td>
                    <td class="px-6 py-4 text-center">{{ $branch->users_count }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ ($branch->is_active ?? true) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ($branch->is_active ?? true) ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <button type="button"
                            data-id="{{ $branch->id }}"
                            data-code="{{ $branch->code }}"
                            data-name="{{ $branch->name }}"
                            data-phone="{{ $branch->phone ?? '' }}"
                            data-address="{{ $branch->address ?? '' }}"
                            data-active="{{ ($branch->is_active ?? true) ? '1' : '0' }}"
                            onclick="openEditModal(this.dataset)"
                            class="text-amber-600 hover:text-amber-800 font-medium">
                            Edit
                        </button>
                        <form action="{{ route('admin.branches.destroy', $branch) }}" method="POST" class="inline" onsubmit="return confirm('Hapus cabang ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">Belum ada data cabang.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t">
            {{ $branches->links() }}
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div id="modalAddBranch" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold mb-4">Tambah Cabang Baru</h3>
        <form action="{{ route('admin.branches.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kode Cabang</label>
                    <input type="text" name="code" placeholder="CBG-01" required class="mt-1 w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Cabang</label>
                    <input type="text" name="name" placeholder="Cabang Bekasi" required class="mt-1 w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telepon</label>
                    <input type="text" name="phone" class="mt-1 w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Alamat</label>
                    <textarea name="address" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2"></textarea>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" onclick="closeModal('modalAddBranch')" class="px-4 py-2 border rounded-lg text-gray-600">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div id="modalEditBranch" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl max-w-md w-full p-6">
        <h3 class="text-lg font-bold mb-4">Edit Cabang</h3>
        <form id="formEditBranch" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Kode Cabang</label>
                    <input type="text" name="code" id="edit_code" required class="mt-1 w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Cabang</label>
                    <input type="text" name="name" id="edit_name" required class="mt-1 w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Telepon</label>
                    <input type="text" name="phone" id="edit_phone" class="mt-1 w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Alamat</label>
                    <textarea name="address" id="edit_address" rows="3" class="mt-1 w-full border rounded-lg px-3 py-2"></textarea>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="rounded border-gray-300 text-blue-600">
                    <label for="edit_is_active" class="ml-2 text-sm text-gray-700">Cabang Aktif</label>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" onclick="closeModal('modalEditBranch')" class="px-4 py-2 border rounded-lg text-gray-600">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.getElementById(id).classList.add('flex');
    }
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.getElementById(id).classList.remove('flex');
    }
    function openEditModal(branch) {
        document.getElementById('formEditBranch').action = `/admin/branches/${branch.id}`;
        document.getElementById('edit_code').value = branch.code;
        document.getElementById('edit_name').value = branch.name;
        document.getElementById('edit_phone').value = branch.phone;
        document.getElementById('edit_address').value = branch.address;
        document.getElementById('edit_is_active').checked = branch.active === '1';
        openModal('modalEditBranch');
    }
</script>
@endsection
