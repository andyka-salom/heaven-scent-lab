@section('title', 'Dashboard')
<x-app-layout title="Dashboard">
    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $activeBatches }}</p>
                    <p class="text-xs text-gray-500">Batch Aktif</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $avgYield ? number_format($avgYield, 1) . '%' : '-' }}</p>
                    <p class="text-xs text-gray-500">Rata-rata Yield</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $defectRate }}%</p>
                    <p class="text-xs text-gray-500">Defect Rate</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $lowStockCount }}</p>
                    <p class="text-xs text-gray-500">Stok Rendah</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Production Chart --}}
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6 flex flex-col">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Produksi 7 Hari Terakhir</h3>
            <div class="relative flex-1 w-full min-h-[250px]">
                <canvas id="productionChart"></canvas>
            </div>
        </div>

        {{-- Recent Batches --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Batch Terbaru</h3>
            <div class="space-y-3">
                @forelse($recentBatches as $batch)
                <a href="{{ route('batches.show', $batch) }}" class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ $batch->batch_number }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $batch->products->pluck('product.full_name')->join(', ') }}</p>
                    </div>
                    @include('batches._status', ['s' => $batch->status])
                </a>
                @empty
                <p class="text-sm text-gray-400 text-center py-8">Belum ada batch</p>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('productionChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($dates),
                    datasets: [
                        {
                            label: 'Unit Baik',
                            data: @json($goodData),
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderRadius: 6,
                        },
                        {
                            label: 'Unit Rusak',
                            data: @json($defectData),
                            backgroundColor: 'rgba(239, 68, 68, 0.7)',
                            borderRadius: 6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
