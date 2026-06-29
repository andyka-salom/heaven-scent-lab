<x-app-layout title="Buat Batch">
    <div class="max-w-4xl" x-data="batchForm()">
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Buat Batch Produksi Baru</h3>
            <form action="{{ route('batches.store') }}" method="POST" class="space-y-6">
                @csrf
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h4 class="text-base font-medium text-gray-900">Daftar Produk</h4>
                        <button type="button" @click="addProduct" class="text-sm text-primary-600 hover:text-primary-700 font-medium">+ Tambah Produk</button>
                    </div>
                    
                    <div class="space-y-3">
                        <template x-for="(item, index) in products" :key="index">
                            <div class="flex gap-4 items-start bg-gray-50 p-4 rounded-lg border border-gray-100">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Produk</label>
                                    <select :name="'products['+index+'][product_id]'" x-model="item.product_id" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->full_name }} ({{ $p->sku }})</option>@endforeach
                                    </select>
                                </div>
                                <div class="w-32">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Jml Rencana</label>
                                    <input type="number" :name="'products['+index+'][planned_qty]'" x-model="item.planned_qty" min="1" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                </div>
                                <div class="pt-6">
                                    <button type="button" @click="removeProduct(index)" x-show="products.length > 1" class="text-red-500 hover:text-red-700 p-2">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gudang Sumber Bahan</label>
                        <select name="warehouse_id" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="">-- Pilih Gudang --</option>
                            @foreach($warehouses as $id => $name)<option value="{{ $id }}" {{ old('warehouse_id') == $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach
                        </select>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Produksi</label><input type="date" name="production_date" value="{{ old('production_date', date('Y-m-d')) }}" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent"></div>
                </div>
                
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label><textarea name="notes" rows="2" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">{{ old('notes') }}</textarea></div>
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <a href="{{ route('batches.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Batal</a>
                    <button type="submit" class="px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-lg hover:bg-primary-600 transition">Simpan Batch</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('batchForm', () => ({
                products: [
                    { product_id: '', planned_qty: 1 }
                ],
                addProduct() {
                    this.products.push({ product_id: '', planned_qty: 1 });
                },
                removeProduct(index) {
                    if (this.products.length > 1) {
                        this.products.splice(index, 1);
                    }
                }
            }))
        })
    </script>
    @endpush
</x-app-layout>
