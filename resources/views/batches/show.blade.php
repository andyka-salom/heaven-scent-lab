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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Record Output (in_progress only) --}}
        @if($batch->canRecordOutput())
        @can('batch.record_output')
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h4 class="text-sm font-semibold text-gray-900 mb-4">Catat Unit Baik</h4>
            <form method="POST" action="{{ route('batches.output', $batch) }}" class="flex flex-col gap-3">
                @csrf
                <select name="product_id" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="">-- Pilih Produk --</option>
                    @foreach($batch->products as $bp)
                    @if($bp->planned_qty - $bp->good_qty - $bp->defect_qty > 0)
                    <option value="{{ $bp->product_id }}">{{ $bp->product->full_name }} (Sisa: {{ $bp->planned_qty - $bp->good_qty - $bp->defect_qty }})</option>
                    @endif
                    @endforeach
                </select>
                <div class="flex gap-3">
                    <input type="number" name="good_qty" min="1" required class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="Jumlah">
                    <button class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition">Catat</button>
                </div>
            </form>
        </div>
        @endcan
        @can('batch.record_defect')
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h4 class="text-sm font-semibold text-gray-900 mb-4">Catat Unit Rusak</h4>
            <form method="POST" action="{{ route('batches.defect', $batch) }}" class="flex flex-col gap-3">
                @csrf
                <select name="product_id" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="">-- Pilih Produk --</option>
                    @foreach($batch->products as $bp)
                    @if($bp->planned_qty - $bp->good_qty - $bp->defect_qty > 0)
                    <option value="{{ $bp->product_id }}">{{ $bp->product->full_name }} (Sisa: {{ $bp->planned_qty - $bp->good_qty - $bp->defect_qty }})</option>
                    @endif
                    @endforeach
                </select>
                <div class="flex gap-3">
                    <input type="number" name="defect_qty" min="1" required class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="Jumlah">
                    <select name="reason" required class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm">
                        @foreach(\App\Models\BatchDefect::REASONS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="flex gap-3">
                    <input type="text" name="notes" class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="Catatan (opsional)">
                    <button class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">Catat Rusak</button>
                </div>
            </form>
        </div>
        @endcan
        @endif

        {{-- Top-up Material & Defect (in_progress only) --}}
        @if($batch->status === 'in_progress')
        @can('batch.topup')
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h4 class="text-sm font-semibold text-gray-900 mb-4">Catat Bahan Baku Rusak</h4>
            <form method="POST" action="{{ route('batches.material', $batch) }}" class="flex flex-col gap-3">
                @csrf
                <input type="hidden" name="type" value="defect">
                <select name="product_id" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="">-- Pilih Produk --</option>
                    @foreach($batch->products as $bp)
                    <option value="{{ $bp->product_id }}">{{ $bp->product->full_name }}</option>
                    @endforeach
                </select>
                <select name="material_id" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="">-- Bahan Rusak --</option>
                    @foreach($materials as $m)<option value="{{ $m->id }}">{{ $m->name }} ({{ $m->unit }})</option>@endforeach
                </select>
                <div class="flex gap-3">
                    <input type="number" name="quantity" step="0.001" min="0.001" required class="w-32 px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="Qty Rusak">
                    <input type="text" name="reason" required class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="Keterangan Rusak">
                </div>
                <button class="w-full px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition mt-2">Catat & Ganti dari Gudang</button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h4 class="text-sm font-semibold text-gray-900 mb-4">Top-up Bahan (Manual)</h4>
            <form method="POST" action="{{ route('batches.material', $batch) }}" class="flex flex-col gap-3">
                @csrf
                <input type="hidden" name="type" value="topup">
                <select name="material_id" required class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <option value="">-- Bahan --</option>
                    @foreach($materials as $m)<option value="{{ $m->id }}">{{ $m->name }} ({{ $m->unit }})</option>@endforeach
                </select>
                <div class="flex gap-3">
                    <input type="number" name="quantity" step="0.001" min="0.001" required class="w-32 px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="Qty">
                    <input type="text" name="reason" required class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="Alasan">
                </div>
                <button class="w-full px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition mt-2">Tambah Bahan</button>
            </form>
        </div>
        @endcan
        @endif
    </div>

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
