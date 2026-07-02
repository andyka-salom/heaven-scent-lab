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
                                    <div x-data="{ open: false, search: '' }" @click.outside="open = false" class="relative">
                                        <input type="hidden" :name="'products['+index+'][product_id]'" :value="item.product_id">
                                        <button type="button" @click="open = !open" class="w-full px-3 py-2 text-left bg-white rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent flex items-center justify-between">
                                            <span x-text="productsList.find(i => i.id === item.product_id)?.name || '-- Pilih Produk --'" class="truncate"></span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                        </button>
                                        <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-2 space-y-2">
                                            <input type="text" x-model="search" placeholder="Cari produk..." class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:ring-1 focus:ring-primary-500 focus:border-transparent">
                                            <div class="max-h-48 overflow-y-auto space-y-0.5">
                                                <template x-for="p in productsList.filter(i => i.name.toLowerCase().includes(search.toLowerCase()))" :key="p.id">
                                                    <button type="button" @click="item.product_id = p.id; open = false; search = ''" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded-lg" x-text="p.name"></button>
                                                </template>
                                                <div x-show="productsList.filter(i => i.name.toLowerCase().includes(search.toLowerCase())).length === 0" class="text-center py-2 text-xs text-gray-400">Tidak ditemukan</div>
                                            </div>
                                        </div>
                                    </div>
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
                        <div x-data="{ open: false, search: '' }" @click.outside="open = false" class="relative">
                            <input type="hidden" name="warehouse_id" :value="warehouse_id" required>
                            <button type="button" @click="open = !open" class="w-full px-3 py-2 text-left bg-white rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent flex items-center justify-between">
                                <span x-text="warehousesList.find(i => i.id === warehouse_id)?.name || '-- Pilih Gudang --'" class="truncate"></span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                            <div x-show="open" x-cloak class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-2 space-y-2">
                                <input type="text" x-model="search" placeholder="Cari gudang..." class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:ring-1 focus:ring-primary-500 focus:border-transparent">
                                <div class="max-h-48 overflow-y-auto space-y-0.5">
                                    <template x-for="item in warehousesList.filter(i => i.name.toLowerCase().includes(search.toLowerCase()))" :key="item.id">
                                        <button type="button" @click="warehouse_id = item.id; open = false; search = ''" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded-lg" x-text="item.name"></button>
                                    </template>
                                    <div x-show="warehousesList.filter(i => i.name.toLowerCase().includes(search.toLowerCase())).length === 0" class="text-center py-2 text-xs text-gray-400">Tidak ditemukan</div>
                                </div>
                            </div>
                        </div>
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
                productsList: @json($products->map(fn($p) => ['id' => (string) $p->id, 'name' => $p->full_name . ' (' . $p->sku . ')'])),
                warehousesList: @json(collect($warehouses)->map(fn($name, $id) => ['id' => (string) $id, 'name' => $name])->values()),
                warehouse_id: '{{ old('warehouse_id', '') }}',
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
