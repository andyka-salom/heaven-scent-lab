<x-app-layout title="Laporan Pemakaian Bahan">
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

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-6 border-b border-gray-100">
            <h4 class="font-semibold text-gray-900">Rekap Pemakaian Bahan</h4>
            <p class="text-xs text-gray-500 mt-1">Periode {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Kode</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Bahan</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Tipe</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Dikeluarkan</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Tambahan</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600">Total Pakai</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Satuan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($usage as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $item->code }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $item->name }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">{{ $item->unit }}</span></td>
                        <td class="px-4 py-3 text-right">{{ number_format($item->issued, 1, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-amber-600">{{ number_format($item->additions, 1, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ number_format($item->issued + $item->additions, 1, ',', '.') }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $item->unit }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Tidak ada data pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
