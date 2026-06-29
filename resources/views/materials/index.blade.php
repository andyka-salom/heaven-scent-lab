<x-app-layout title="Bahan">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Daftar Bahan</h3>
        @can('material.create')
        <a href="{{ route('materials.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-xl hover:bg-primary-600 transition shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Bahan
        </a>
        @endcan
    </div>
    <div class="table-wrapper">
        <table id="materialsTable" class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Kode</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Nama</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Tipe</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Satuan</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
    @push('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#materialsTable').DataTable({
            processing: true, serverSide: true,
            ajax: '{{ route("materials.data") }}',
            dom: "<'dt-layout-header'lf><'overflow-x-auto't><'dt-layout-footer'ip>",
            columns: [{data:'code'},{data:'name'},{data:'type'},{data:'unit'},{data:'is_active'},{data:'action', orderable: false, searchable: false}],
            language: {
                processing: "Memuat...",
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                paginate: { previous: "Sebelumnya", next: "Berikutnya" }
            }
        });
    });
    </script>
    @endpush
</x-app-layout>
