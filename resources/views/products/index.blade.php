<x-app-layout title="Produk">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Daftar Produk</h3>
        @can('product.create')
        <a href="{{ route('products.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-xl hover:bg-primary-600 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Produk
        </a>
        @endcan
    </div>

    <div class="table-wrapper">
        <table id="productsTable" class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">SKU</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Nama Lengkap</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Satuan</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Gudang</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">BOM</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
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
        $('#productsTable').DataTable({
            processing: true, serverSide: true,
            ajax: '{{ route("products.data") }}',
            dom: "<'dt-layout-header'lf><'overflow-x-auto't><'dt-layout-footer'ip>",
            columns: [
                {data: 'sku', name: 'sku'},
                {data: 'full_name', name: 'full_name'},
                {data: 'unit', name: 'unit'},
                {data: 'warehouse', name: 'defaultWarehouse.name', orderable: false},
                {data: 'bom_count', name: 'boms_count', searchable: false, orderable: false},
                {data: 'is_active', name: 'is_active'},
                {data: 'action', orderable: false, searchable: false}
            ],
            language: { processing: "Memuat...", lengthMenu: "Tampil _MENU_", zeroRecords: "Data tidak ditemukan", info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data", infoEmpty: "Menampilkan 0 sampai 0 dari 0 data", search: "Cari:", paginate: {previous: "Sebelumnya", next: "Berikutnya"} }
        });
    });
    </script>
    @endpush
</x-app-layout>
