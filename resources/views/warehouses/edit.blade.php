<x-app-layout title="Edit Gudang">
    <div class="max-w-2xl">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Edit Gudang</h3>
            <form action="{{ route('warehouses.update', $warehouse) }}" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode</label>
                        <input type="text" name="code" value="{{ old('code', $warehouse->code) }}" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Gudang</label>
                        <input type="text" name="name" value="{{ old('name', $warehouse->name) }}" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi</label>
                    <input type="text" name="location" value="{{ old('location', $warehouse->location) }}" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                </div>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $warehouse->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                        <span class="text-sm text-gray-700">Aktif</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="allow_negative_stock" value="0">
                        <input type="checkbox" name="allow_negative_stock" value="1" {{ old('allow_negative_stock', $warehouse->allow_negative_stock) ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                        <span class="text-sm text-gray-700">Izinkan Stok Negatif</span>
                    </label>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <a href="{{ route('warehouses.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-lg hover:bg-primary-600 transition">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
