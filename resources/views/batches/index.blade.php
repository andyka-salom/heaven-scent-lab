<x-app-layout title="Batch Produksi">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Daftar Batch Produksi</h3>
        @can('batch.create')
        <a href="{{ route('batches.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-xl hover:bg-primary-600 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Batch
        </a>
        @endcan
    </div>
    <div class="table-wrapper">
        <table id="batchesTable" class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">No. Batch</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Produk</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Gudang</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Rencana</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Baik</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Rusak</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Yield</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Tanggal</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Aksi</th>
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
        $('#batchesTable').DataTable({
            processing: true, serverSide: true, order: [[0, 'desc']],
            ajax: '{{ route("batches.data") }}',
            dom: "<'dt-layout-header'lf><'overflow-x-auto't><'dt-layout-footer'ip>",
            columns: [
                {data:'batch_number', name:'batch_number'},
                {data:'product', name:'product', orderable: false, searchable: true},
                {data:'warehouse', name:'warehouse', orderable: false, searchable: true},
                {data:'planned_qty', name:'planned_qty', orderable: false, searchable: false},
                {data:'good_qty', name:'good_qty', orderable: false, searchable: false},
                {data:'defect_qty', name:'defect_qty', orderable: false, searchable: false},
                {data:'yield', searchable:false, orderable: false},
                {data:'status', name:'status'},
                {data:'production_date', name:'production_date'},
                {data:'action', orderable: false, searchable: false}
            ],
            language: { processing: "Memuat...", lengthMenu: "Tampil _MENU_", zeroRecords: "Data tidak ditemukan", info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data", infoEmpty: "Menampilkan 0 sampai 0 dari 0 data", search: "Cari:", paginate: {previous: "Sebelumnya", next: "Berikutnya"} }
        });
    });
    </script>
    @endpush
</x-app-layout>
