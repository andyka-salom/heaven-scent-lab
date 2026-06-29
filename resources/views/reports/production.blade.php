<x-app-layout title="Laporan Produksi">
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
            <button class="px-4 py-2 bg-primary-500 text-white text-sm rounded-lg hover:bg-primary-600 transition">Filter</button>
        </form>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 mb-1">Total Batch</p>
            <p class="text-2xl font-bold text-gray-900">{{ $summaryData['total_batches'] }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 mb-1">Total Bagus</p>
            <p class="text-2xl font-bold text-emerald-600">{{ number_format($summaryData['total_good'], 0) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 mb-1">Total Defect</p>
            <p class="text-2xl font-bold text-red-500">{{ number_format($summaryData['total_defect'], 0) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs text-gray-500 mb-1">Rata-rata Yield</p>
            <p class="text-2xl font-bold text-primary-600">{{ number_format($summaryData['avg_yield'] ?? 0, 1) }}%</p>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Batch</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Produk</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Gudang</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Tanggal</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Rencana</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Bagus</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Defect</th>
                        <th class="px-4 py-3 text-center font-medium text-gray-600">Status</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($batches as $b)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $b->batch_number }}</td>
                        <td class="px-4 py-3 text-gray-700">
                            <div class="flex flex-col space-y-1">
                                @foreach($b->products as $p)
                                    <span class="text-xs">{{ $p->product->full_name }} <span class="text-gray-400">({{ $p->planned_qty }} pcs)</span></span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $b->warehouse->name }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $b->production_date?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($b->products->sum('planned_qty'), 0) }}</td>
                        <td class="px-4 py-3 text-right text-emerald-600 font-medium">{{ number_format($b->products->sum('good_qty'), 0) }}</td>
                        <td class="px-4 py-3 text-right text-red-500">{{ number_format($b->products->sum('defect_qty'), 0) }}</td>
                        <td class="px-4 py-3 text-center">@include('batches._status', ['s' => $b->status])</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('batches.show', $b) }}" class="inline-flex items-center gap-1 px-2 py-1 bg-gray-50 text-gray-600 text-xs font-medium rounded border border-gray-200 hover:bg-white hover:text-primary-600 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">Tidak ada data pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
