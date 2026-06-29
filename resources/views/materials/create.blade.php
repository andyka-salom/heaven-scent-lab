<x-app-layout title="Tambah Bahan">
    <div class="max-w-2xl">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Form Bahan Baru</h3>
            <form action="{{ route('materials.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Kode</label><input type="text" name="code" value="{{ old('code') }}" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="OIL-SCN"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama</label><input type="text" name="name" value="{{ old('name') }}" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Tipe</label>
                        <select name="type" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            @foreach(\App\Models\Material::TYPES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                        </select></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Satuan</label>
                        <select name="unit" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            @foreach(\App\Models\Material::UNITS as $u)<option value="{{ $u }}">{{ $u }}</option>@endforeach
                        </select></div>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <a href="{{ route('materials.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-lg hover:bg-primary-600 transition">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
