<x-app-layout title="Detail Batch {{ $batch->batch_number }}">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h3 class="text-lg font-semibold text-gray-900">{{ $batch->batch_number }}</h3>
                @include('batches._status', ['s' => $batch->status])
            </div>
            <p class="text-sm text-gray-500">{{ $batch->products->pluck('product.full_name')->implode(', ') }} &middot; {{ $batch->production_date->format('d/m/Y') }} &middot; {{ $batch->warehouse->name }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($batch->canBeReleased())
            @can('batch.release')
            <form method="POST" action="{{ route('batches.release', $batch) }}">@csrf<button class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">Release & Issue Bahan</button></form>
            @endcan
            @endif
            @if($batch->canBeStarted())
            @can('batch.start')
            <form method="POST" action="{{ route('batches.start', $batch) }}">@csrf<button class="px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition">Mulai Produksi</button></form>
            @endcan
            @endif
            @if($batch->canBeCompleted())
            @can('batch.complete')
            <button onclick="openCompleteBatch()" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition">Selesaikan Batch</button>

            <template id="complete-batch-template">
                <form method="POST" action="{{ route('batches.complete', $batch) }}" id="form-complete-batch" class="text-left">
                    @csrf
                    <p class="text-sm text-gray-500 mb-4 text-center">Silakan masukkan jumlah akhir (Unit Baik & Unit Rusak) untuk masing-masing produk pada batch ini.</p>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="border-b border-gray-200">
                                <tr>
                                    <th class="py-2 font-medium text-gray-600">Produk</th>
                                    <th class="py-2 font-medium text-gray-600">Jml Rencana</th>
                                    <th class="py-2 font-medium text-gray-600">Unit Baik (Hasil)</th>
                                    <th class="py-2 font-medium text-gray-600">Unit Rusak</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach($batch->products as $bp)
                                <tr>
                                    <td class="py-3"><span class="font-medium">{{ $bp->product->full_name }}</span><input type="hidden" name="results[{{ $loop->index }}][product_id]" value="{{ $bp->product_id }}"></td>
                                    <td class="py-3 text-gray-500">{{ $bp->planned_qty }}</td>
                                    <td class="py-3"><input type="number" name="results[{{ $loop->index }}][good_qty]" value="{{ old('results.'.$loop->index.'.good_qty', $bp->good_qty) }}" min="0" required class="w-20 px-2 py-1 rounded border border-gray-300 text-sm focus:ring-emerald-500 focus:border-emerald-500"></td>
                                    <td class="py-3"><input type="number" name="results[{{ $loop->index }}][defect_qty]" value="{{ old('results.'.$loop->index.'.defect_qty', $bp->defect_qty) }}" min="0" required class="w-20 px-2 py-1 rounded border border-gray-300 text-sm focus:ring-emerald-500 focus:border-emerald-500"></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>
            </template>
            <script>
                function openCompleteBatch() {
                    Swal.fire({
                        title: 'Selesaikan Batch',
                        html: document.getElementById('complete-batch-template').innerHTML,
                        showCancelButton: true,
                        confirmButtonText: 'Simpan & Selesaikan',
                        cancelButtonText: 'Batal',
                        confirmButtonColor: '#059669', // emerald-600
                        width: '800px',
                        preConfirm: () => {
                            const form = Swal.getPopup().querySelector('#form-complete-batch');
                            if (form.reportValidity()) {
                                form.submit();
                            } else {
                                return false;
                            }
                        }
                    });
                }
            </script>
            @endcan
            @endif
            @if($batch->canBeCancelled())
            @can('batch.cancel')
            <form method="POST" action="{{ route('batches.cancel', $batch) }}" class="inline-block" x-data="{
                confirmCancel(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Konfirmasi Pembatalan',
                        text: 'Yakin ingin membatalkan batch ini? Semua bahan yang telah dikeluarkan akan dikembalikan ke stok gudang.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Yakin, Batalkan',
                        cancelButtonText: 'Kembali'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $el.submit();
                        }
                    });
                }
            }" @submit="confirmCancel">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">Batalkan</button>
            </form>
            @endcan
            @endif
        </div>
    </div>

    {{-- Metrics --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-900">{{ $batch->products->sum('planned_qty') }}</p><p class="text-xs text-gray-500">Rencana Total</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600">{{ $batch->products->sum('good_qty') }}</p><p class="text-xs text-gray-500">Unit Baik Total</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $batch->products->sum('defect_qty') }}</p><p class="text-xs text-gray-500">Unit Rusak Total</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-primary-500">{{ $batch->yield ?? '-' }}%</p><p class="text-xs text-gray-500">Yield Rata-rata</p>
        </div>
    </div>

    {{-- Product List --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h4 class="text-sm font-semibold text-gray-900 mb-4">Daftar Produk</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-200"><tr>
                    <th class="py-2 text-left font-medium text-gray-600">Produk</th><th class="py-2 text-left font-medium text-gray-600">Rencana</th><th class="py-2 text-left font-medium text-gray-600">Baik</th><th class="py-2 text-left font-medium text-gray-600">Rusak</th><th class="py-2 text-left font-medium text-gray-600">Sisa</th><th class="py-2 text-left font-medium text-gray-600">Yield</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($batch->products as $bp)
                    <tr>
                        <td class="py-2"><span class="font-medium">{{ $bp->product->full_name }}</span></td>
                        <td class="py-2">{{ $bp->planned_qty }}</td>
                        <td class="py-2 text-emerald-600 font-medium">{{ $bp->good_qty }}</td>
                        <td class="py-2 text-red-600 font-medium">{{ $bp->defect_qty }}</td>
                        <td class="py-2">{{ $bp->planned_qty - $bp->good_qty - $bp->defect_qty }}</td>
                        <td class="py-2">{{ $bp->yield ?? '-' }}%</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Material Preview / Issued --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h4 class="text-sm font-semibold text-gray-900 mb-4">Kebutuhan Bahan</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-200"><tr>
                    <th class="py-2 text-left font-medium text-gray-600">Bahan</th><th class="py-2 text-left font-medium text-gray-600">Rencana</th><th class="py-2 text-left font-medium text-gray-600">Stok</th><th class="py-2 text-left font-medium text-gray-600">Dikeluarkan</th><th class="py-2 text-left font-medium text-gray-600">Status</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($preview as $item)
                    <tr class="{{ !$item['is_sufficient'] && $batch->status === 'draft' ? 'bg-red-50' : '' }}">
                        <td class="py-2"><span class="font-medium">{{ $item['material_name'] }}</span><br><span class="text-xs text-gray-400">{{ $item['material_code'] }}</span></td>
                        <td class="py-2">{{ number_format($item['planned_qty'], 1, ',', '.') }} {{ $item['unit'] }}</td>
                        <td class="py-2 {{ $item['stock_qty'] < $item['planned_qty'] ? 'text-red-600 font-semibold' : '' }}">{{ number_format($item['stock_qty'], 1, ',', '.') }} {{ $item['unit'] }}</td>
                        <td class="py-2">{{ number_format($batch->materials->firstWhere('material_id', $item['material_id'])?->issued_qty ?? 0, 1, ',', '.') }} {{ $item['unit'] }}</td>
                        <td class="py-2">
                            @if($item['is_sufficient'])<span class="text-emerald-600 text-xs font-medium">Cukup</span>@else<span class="text-red-600 text-xs font-medium">Kurang</span>@endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Action Forms (in_progress only) --}}
    @if($batch->status === 'in_progress')
    <div class="space-y-6 mb-6">
        
        {{-- Section: Product Good & Defect --}}
        @if($batch->canRecordOutput())
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h4 class="text-sm font-semibold text-gray-900 mb-4">Pencatatan Hasil Produksi (Unit Baik & Rusak)</h4>
            <div class="space-y-4">
                @foreach($batch->products as $bp)
                @php
                    $sisa = $bp->planned_qty - $bp->good_qty - $bp->defect_qty;
                @endphp
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-4 rounded-lg bg-gray-50 border border-gray-100">
                    <div class="flex-1 min-w-[200px]">
                        <span class="font-medium text-sm text-gray-900 block">{{ $bp->product->full_name }}</span>
                        <span class="text-xs text-gray-500">Rencana: {{ $bp->planned_qty }} &middot; Sisa: <strong class="{{ $sisa > 0 ? 'text-amber-600' : 'text-gray-400' }}">{{ $sisa }}</strong></span>
                    </div>
                    @if($sisa > 0)
                    <div class="flex flex-wrap items-center gap-6">
                        @can('batch.record_output')
                        <form method="POST" action="{{ route('batches.output', $batch) }}" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $bp->product_id }}">
                            <input type="number" name="good_qty" min="1" max="{{ $sisa }}" required class="w-20 px-2 py-1 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500" placeholder="Qty Baik">
                            <button type="submit" class="px-3 py-1.5 bg-emerald-600 text-white text-xs font-semibold rounded-lg hover:bg-emerald-700 transition">Catat Baik</button>
                        </form>
                        @endcan

                        @can('batch.record_defect')
                        <form method="POST" action="{{ route('batches.defect', $batch) }}" class="flex items-center gap-2 border-t lg:border-t-0 lg:border-l border-gray-200 pt-2 lg:pt-0 lg:pl-6">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $bp->product_id }}">
                            <input type="number" name="defect_qty" min="1" max="{{ $sisa }}" required class="w-20 px-2 py-1 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500" placeholder="Qty Rusak">
                            <select name="reason" required class="px-2 py-1 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-primary-500">
                                @foreach(\App\Models\BatchDefect::REASONS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="notes" class="w-28 px-2 py-1 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-primary-500" placeholder="Catatan (opsional)">
                            <button type="submit" class="px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition">Catat Rusak</button>
                        </form>
                        @endcan
                    </div>
                    @else
                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 text-xs font-medium rounded-full">Selesai Produksi</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Section: Material Defect & Top-up --}}
        @can('batch.topup')
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h4 class="text-sm font-semibold text-gray-900 mb-4">Pencatatan Masalah & Tambahan Bahan Baku</h4>
            <div class="space-y-4 mb-6">
                @foreach($preview as $item)
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-4 rounded-lg bg-gray-50 border border-gray-100">
                    <div class="flex-1 min-w-[200px]">
                        <span class="font-medium text-sm text-gray-900 block">{{ $item['material_name'] }}</span>
                        <span class="text-xs text-gray-400">{{ $item['material_code'] }} &middot; Unit: {{ $item['unit'] }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-6">
                        {{-- Form 1: Catat Bahan Baku Rusak (Ganti dari Gudang) --}}
                        <form method="POST" action="{{ route('batches.material', $batch) }}" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="type" value="defect">
                            <input type="hidden" name="material_id" value="{{ $item['material_id'] }}">
                            
                            @php
                                $usingProducts = collect($batch->products)->filter(function($bp) use ($item) {
                                    return $bp->product && $bp->product->activeBom && $bp->product->activeBom->items->contains('material_id', $item['material_id']);
                                });
                            @endphp
                            
                            @if($usingProducts->count() > 1)
                            <select name="product_id" required class="px-2 py-1 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-primary-500">
                                <option value="">-- Untuk Produk --</option>
                                @foreach($usingProducts as $bp)
                                    <option value="{{ $bp->product_id }}">{{ $bp->product->variant_name }}</option>
                                @endforeach
                            </select>
                            @elseif($usingProducts->count() === 1)
                            <input type="hidden" name="product_id" value="{{ $usingProducts->first()->product_id }}">
                            @endif

                            <input type="number" name="quantity" step="0.001" min="0.001" required class="w-20 px-2 py-1 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500" placeholder="Qty Rusak">
                            <input type="text" name="reason" required class="w-32 px-2 py-1 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-primary-500" placeholder="Keterangan Rusak">
                            <button type="submit" class="px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition">Catat Rusak</button>
                        </form>

                        {{-- Form 2: Top-up Bahan (Manual) --}}
                        <form method="POST" action="{{ route('batches.material', $batch) }}" class="flex items-center gap-2 border-t lg:border-t-0 lg:border-l border-gray-200 pt-2 lg:pt-0 lg:pl-6">
                            @csrf
                            <input type="hidden" name="type" value="topup">
                            <input type="hidden" name="material_id" value="{{ $item['material_id'] }}">
                            
                            <input type="number" name="quantity" step="0.001" min="0.001" required class="w-20 px-2 py-1 border border-gray-300 rounded-lg text-sm focus:ring-1 focus:ring-primary-500" placeholder="Qty Top-up">
                            <input type="text" name="reason" required class="w-32 px-2 py-1 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-primary-500" placeholder="Alasan">
                            <button type="submit" class="px-3 py-1.5 bg-amber-600 text-white text-xs font-semibold rounded-lg hover:bg-amber-700 transition">Top-up</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Fallback: Top-up Bahan Lainnya --}}
            @php
                $requiredMaterialIds = collect($preview)->pluck('material_id')->toArray();
                $otherMaterials = $materials->filter(fn($m) => !in_array($m->id, $requiredMaterialIds));
            @endphp
            @if($otherMaterials->isNotEmpty())
            <div class="border-t border-gray-100 pt-4 mt-6">
                <h5 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Top-up Bahan Lainnya (Di luar BOM)</h5>
                <form method="POST" action="{{ route('batches.material', $batch) }}" class="flex flex-wrap items-center gap-3">
                    @csrf
                    <input type="hidden" name="type" value="topup">
                    <select name="material_id" required class="px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-1 focus:ring-primary-500">
                        <option value="">-- Pilih Bahan Lain --</option>
                        @foreach($otherMaterials as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->unit }})</option>
                        @endforeach
                    </select>
                    <input type="number" name="quantity" step="0.001" min="0.001" required class="w-28 px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-1 focus:ring-primary-500" placeholder="Jumlah Qty">
                    <input type="text" name="reason" required class="flex-1 min-w-[200px] px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-1 focus:ring-primary-500" placeholder="Alasan Top-up">
                    <button type="submit" class="px-4 py-2 bg-amber-600 text-white text-sm font-semibold rounded-lg hover:bg-amber-700 transition">Tambah Bahan</button>
                </form>
            </div>
            @endif
        </div>
        @endcan

    </div>
    @endif

    {{-- Defects Log --}}
    @if($batch->defects->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h4 class="text-sm font-semibold text-gray-900 mb-4">Log Defect</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm"><thead class="border-b border-gray-200"><tr>
                <th class="py-2 text-left font-medium text-gray-600">Waktu</th><th class="py-2 text-left font-medium text-gray-600">Produk</th><th class="py-2 text-left font-medium text-gray-600">Qty</th><th class="py-2 text-left font-medium text-gray-600">Alasan</th><th class="py-2 text-left font-medium text-gray-600">Catatan</th>
            </tr></thead><tbody class="divide-y divide-gray-100">
                @foreach($batch->defects as $d)<tr><td class="py-2 text-xs">{{ $d->created_at->format('d/m/Y H:i') }}</td><td class="py-2 font-medium">{{ $d->product?->full_name }}</td><td class="py-2">{{ $d->defect_qty }}</td><td class="py-2">{{ $d->reason_label }}</td><td class="py-2 text-gray-500">{{ $d->notes ?? '-' }}</td></tr>@endforeach
            </tbody></table>
        </div>
    </div>
    @endif

    {{-- Additions Log --}}
    @if($batch->additions->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <h4 class="text-sm font-semibold text-gray-900 mb-4">Log Tambahan / Kerusakan Bahan</h4>
        <div class="overflow-x-auto">
            <table class="w-full text-sm"><thead class="border-b border-gray-200"><tr>
                <th class="py-2 text-left font-medium text-gray-600">Waktu</th><th class="py-2 text-left font-medium text-gray-600">Jenis</th><th class="py-2 text-left font-medium text-gray-600">Produk</th><th class="py-2 text-left font-medium text-gray-600">Bahan</th><th class="py-2 text-left font-medium text-gray-600">Qty</th><th class="py-2 text-left font-medium text-gray-600">Keterangan</th>
            </tr></thead><tbody class="divide-y divide-gray-100">
                @foreach($batch->additions as $a)<tr>
                    <td class="py-2 text-xs">{{ $a->created_at->format('d/m/Y H:i') }}</td>
                    <td class="py-2">
                        @if($a->type === 'defect')
                            <span class="px-2 py-1 bg-red-50 text-red-600 text-xs font-medium rounded-md">Bahan Rusak</span>
                        @else
                            <span class="px-2 py-1 bg-amber-50 text-amber-600 text-xs font-medium rounded-md">Top-up</span>
                        @endif
                    </td>
                    <td class="py-2 text-gray-600">{{ $a->product?->full_name ?? '-' }}</td>
                    <td class="py-2">{{ $a->material->name }}</td>
                    <td class="py-2">{{ number_format($a->quantity, 1, ',', '.') }} {{ $a->material->unit }}</td>
                    <td class="py-2 text-gray-500">{{ $a->reason }}</td>
                </tr>@endforeach
            </tbody></table>
        </div>
    </div>
    @endif
</x-app-layout>
