<x-app-layout title="BOM: {{ $product->full_name }}">
    <div class="mb-6">
        <a href="{{ route('products.index') }}" class="text-sm text-primary-600 hover:text-primary-800">&larr; Kembali ke Produk</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-gold-50 flex items-center justify-center">
                <svg class="w-6 h-6 text-gold-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $product->full_name }}</h3>
                <p class="text-sm text-gray-500">SKU: {{ $product->sku }} &middot; BOM Aktif: v{{ $product->activeBom?->version ?? '-' }}</p>
            </div>
        </div>
    </div>

    <div x-data="bomEditor()" x-init="init()">
        <form method="POST" action="{{ route('bom.update', $product) }}" class="space-y-6">
            @csrf @method('PUT')

            {{-- Notes --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Catatan BOM</label>
                <textarea name="notes" rows="2" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent" placeholder="Catatan versi BOM...">{{ old('notes', $product->activeBom?->notes) }}</textarea>
            </div>

            {{-- Items --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h4 class="font-semibold text-gray-900">Komposisi Bahan</h4>
                    <button type="button" @click="addRow()" class="px-3 py-1.5 bg-primary-50 text-primary-600 text-sm font-medium rounded-lg hover:bg-primary-100 transition">+ Tambah Bahan</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-600 w-2/5">Bahan</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600 w-1/5">Jumlah</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600 w-1/5">Satuan</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-600 w-16"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, idx) in items" :key="idx">
                                <tr class="border-b border-gray-100">
                                    <td class="px-4 py-2">
                                        <select :name="'items['+idx+'][material_id]'" x-model="item.material_id" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500">
                                            <option value="">-- Pilih Bahan --</option>
                                            <template x-for="m in materials" :key="m.id">
                                                <option :value="m.id" x-text="m.name" :selected="m.id == item.material_id"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="px-4 py-2">
                                        <input type="number" :name="'items['+idx+'][quantity]'" x-model="item.quantity" step="0.001" min="0.001" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500">
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="text-sm text-gray-500" x-text="getUnit(item.material_id)"></span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <button type="button" @click="removeRow(idx)" class="text-red-400 hover:text-red-600 p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div x-show="items.length === 0" class="p-8 text-center text-gray-400 text-sm">Belum ada bahan. Klik "Tambah Bahan" untuk menambahkan.</div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('products.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Batal</a>
                <button type="submit" class="px-6 py-2 bg-primary-500 text-white text-sm font-medium rounded-lg hover:bg-primary-600 transition">Simpan BOM</button>
            </div>
        </form>

        {{-- CSV Import --}}
        @can('bom.import')
        <div class="mt-6 bg-white rounded-xl border border-gray-200 p-6">
            <h4 class="font-semibold text-gray-900 mb-3">Import BOM dari CSV</h4>
            <p class="text-xs text-gray-500 mb-3">Format: Item Name, Variant Name, Material Code, Quantity</p>
            <form method="POST" action="{{ route('bom.import') }}" enctype="multipart/form-data" class="flex items-center gap-3">
                @csrf
                <input type="file" name="file" accept=".csv" required class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-primary-50 file:text-primary-600 hover:file:bg-primary-100">
                <button class="px-4 py-2 bg-primary-500 text-white text-sm rounded-lg hover:bg-primary-600">Import</button>
            </form>
        </div>
        @endcan

        {{-- Duplicate BOM --}}
        @can('bom.manage')
        <div class="mt-4 bg-white rounded-xl border border-gray-200 p-6">
            <h4 class="font-semibold text-gray-900 mb-3">Duplikasi BOM dari Produk Lain</h4>
            <form method="POST" action="{{ route('bom.duplicate', $product) }}" class="flex items-center gap-3">
                @csrf
                <select name="source_product_id" required class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm">
                    <option value="">-- Pilih Produk Sumber --</option>
                    @foreach(\App\Models\Product::where('id', '!=', $product->id)->whereHas('activeBom')->get() as $p)
                        <option value="{{ $p->id }}">{{ $p->full_name }}</option>
                    @endforeach
                </select>
                <button class="px-4 py-2 bg-gold-500 text-white text-sm rounded-lg hover:bg-gold-600">Duplikasi</button>
            </form>
        </div>
        @endcan
    </div>

    @push('scripts')
    <script>
    function bomEditor() {
        return {
            items: [],
            materials: @json($materials->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'unit' => $m->unit])),
            init() {
                @if($product->activeBom && $product->activeBom->items->count())
                    this.items = @json($product->activeBom->items->map(fn($i) => ['material_id' => $i->material_id, 'quantity' => (float)$i->quantity]));
                @else
                    this.items = [{material_id: '', quantity: 0}];
                @endif
            },
            addRow() { this.items.push({material_id: '', quantity: 0}); },
            removeRow(idx) { this.items.splice(idx, 1); },
            getUnit(materialId) {
                var m = this.materials.find(x => x.id == materialId);
                return m ? m.unit : '-';
            }
        }
    }
    </script>
    @endpush
</x-app-layout>
