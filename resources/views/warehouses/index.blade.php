<x-app-layout title="Gudang">
    <div x-data="{ 
        confirmDelete(id) {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Yakin ingin menghapus gudang ini? Aksi ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yakin, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/production/warehouses/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })
                        .then(() => location.reload());
                }
            });
        }
    }">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Daftar Gudang</h3>
            <a href="{{ route('warehouses.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-xl hover:bg-primary-600 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Gudang
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($warehouses as $w)
            <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="font-semibold text-gray-900">{{ $w->name }}</h4>
                            @if($w->is_active)
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-medium rounded-full">Aktif</span>
                            @else
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-500 text-[10px] font-medium rounded-full">Nonaktif</span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 mb-2">Kode: {{ $w->code }}</p>
                        @if($w->location)
                            <p class="text-sm text-gray-600 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $w->location }}
                            </p>
                        @endif
                    </div>
                    <span class="text-xs text-gray-400 bg-gray-50 px-2 py-1 rounded-lg">{{ $w->stocks_count }} item</span>
                </div>
                <div class="mt-4 pt-3 border-t border-gray-100 flex gap-3">
                    <a href="{{ route('warehouses.edit', $w) }}" class="text-xs text-primary-600 hover:text-primary-800 font-medium">Edit</a>
                    <button @click="confirmDelete({{ $w->id }})" class="text-xs text-red-500 hover:text-red-700 font-medium">Hapus</button>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-12 text-gray-400 text-sm">Belum ada gudang.</div>
            @endforelse
        </div>

    </div>
</x-app-layout>
