<x-app-layout title="Laporan Defect">
    {{-- Filter --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Dari</label>
                <input type="date" name="from" value="{{ $from }}" class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Sampai</label>
                <input type="date" name="to" value="{{ $to }}" class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
            </div>
            <button type="submit" name="action" value="filter" class="px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-lg hover:bg-primary-600 transition">Filter</button>
            <button type="submit" name="action" value="export" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel
            </button>
        </form>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- By Reason --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h4 class="font-semibold text-gray-900">Defect per Alasan</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-medium text-gray-600">Alasan</th>
                            <th class="px-4 py-2.5 text-right font-medium text-gray-600">Jumlah</th>
                            <th class="px-4 py-2.5 text-right font-medium text-gray-600">Kejadian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($byReason as $key => $data)
                        <tr>
                            <td class="px-4 py-2.5">{{ $data['label'] }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-red-600">{{ number_format($data['total']) }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-500">{{ $data['count'] }}x</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- By Product --}}
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="p-5 border-b border-gray-100">
                <h4 class="font-semibold text-gray-900">Defect per Produk</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-2.5 text-left font-medium text-gray-600">Produk</th>
                            <th class="px-4 py-2.5 text-right font-medium text-gray-600">Jumlah</th>
                            <th class="px-4 py-2.5 text-right font-medium text-gray-600">Kejadian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($byProduct as $product => $data)
                        <tr>
                            <td class="px-4 py-2.5">{{ $product }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold text-red-600">{{ number_format($data['total']) }}</td>
                            <td class="px-4 py-2.5 text-right text-gray-500">{{ $data['count'] }}x</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Detail Log --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-5 border-b border-gray-100">
            <h4 class="font-semibold text-gray-900">Log Detail Defect</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Tanggal</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Batch</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Produk</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Alasan</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Qty</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($defects as $d)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500">{{ $d->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $d->batch->batch_number }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $d->product?->full_name ?? $d->batch->products->pluck('product.full_name')->join(', ') }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 bg-red-50 text-red-600 text-xs rounded-full">{{ $d->reason_label }}</span></td>
                        <td class="px-4 py-3 text-right font-semibold text-red-600">{{ number_format($d->defect_qty) }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $d->notes }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">Tidak ada data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
