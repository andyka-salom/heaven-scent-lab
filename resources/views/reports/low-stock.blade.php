<x-app-layout title="Peringatan Stok Rendah">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Peringatan Stok Rendah</h3>
                <p class="text-sm text-gray-500">Bahan yang stoknya di bawah ambang batas minimum.</p>
            </div>
        </div>
        <div>
            <a href="{{ route('reports.low-stock', ['action' => 'export']) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-xl hover:bg-emerald-700 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export Excel
            </a>
        </div>
    </div>

    <div class="table-wrapper">
        <table id="lowStockTable" class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Kode</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Bahan</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Gudang</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Stok Saat Ini</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Ambang Min</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Selisih</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Satuan</th>
                </tr>
            </thead>
        </table>
    </div>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script>
    $(document).ready(function() {
        $('#lowStockTable').DataTable({
            processing: true, serverSide: true, order: [[1, 'asc']],
            ajax: '{{ route("reports.low-stock.data") }}',
            dom: "<'dt-layout-header'lf><'overflow-x-auto't><'dt-layout-footer'ip>",
            columns: [
                {data: 'material_code', name: 'material.code', orderable: false},
                {data: 'material_name', name: 'material.name', orderable: false},
                {data: 'warehouse_name', name: 'warehouse.name', orderable: false},
                {data: 'quantity', name: 'quantity'},
                {data: 'min_alert', name: 'min_alert'},
                {data: 'difference', searchable: false, orderable: false},
                {data: 'unit', name: 'material.unit', orderable: false, searchable: false}
            ],
            language: { 
                processing: "Memuat...", 
                lengthMenu: "Tampil _MENU_", 
                zeroRecords: `<div class="flex flex-col items-center gap-2 py-8">
                    <svg class="w-10 h-10 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-gray-400">Semua stok dalam kondisi aman.</p>
                </div>`, 
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data", 
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 data", 
                search: "Cari:", 
                paginate: {previous: "Sebelumnya", next: "Berikutnya"} 
            },
            createdRow: function(row, data, dataIndex) {
                $(row).addClass('hover:bg-red-50/50 transition-colors');
                $('td:eq(3)', row).addClass('text-right');
                $('td:eq(4)', row).addClass('text-right');
                $('td:eq(5)', row).addClass('text-right');
            }
        });
    });
    </script>
    @endpush
</x-app-layout>
