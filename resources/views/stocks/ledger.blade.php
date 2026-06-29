<x-app-layout title="Buku Besar Stok">
    <div class="mb-6">
        <a href="{{ route('stocks.index') }}" class="text-sm text-primary-600 hover:text-primary-800">&larr; Kembali ke Stok</a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary-50 flex items-center justify-center">
                <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ $material->name }}</h3>
                <p class="text-sm text-gray-500">{{ $material->code }} &middot; {{ $material->unit }}</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-4">
        <div class="flex flex-wrap gap-3">
            <select id="filterWarehouse" class="px-3 py-2 rounded-lg border border-gray-200 text-sm">
                <option value="">Semua Gudang</option>
                @foreach($warehouses as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <input type="date" id="filterFrom" class="px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="Dari">
            <input type="date" id="filterTo" class="px-3 py-2 rounded-lg border border-gray-200 text-sm" placeholder="Sampai">
            <button id="btnFilter" class="px-4 py-2 bg-primary-500 text-white text-sm rounded-lg hover:bg-primary-600 transition">Filter</button>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-wrapper">
        <table id="ledgerTable" class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Tanggal</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Tipe</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Gudang</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Jumlah</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-600">Saldo</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">User</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Catatan</th>
                </tr>
            </thead>
        </table>
    </div>

    @push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script>
    $(document).ready(function(){
        var table = $('#ledgerTable').DataTable({
            processing: true, serverSide: true,
            ajax: {
                url: '{{ route("stocks.ledger.data", $material) }}',
                data: function(d) {
                    d.warehouse_id = $('#filterWarehouse').val();
                    d.from = $('#filterFrom').val();
                    d.to = $('#filterTo').val();
                }
            },
            dom: "<'dt-layout-header'lf><'overflow-x-auto't><'dt-layout-footer'ip>",
            columns: [
                {data: 'created_at', name: 'created_at'},
                {data: 'type_label', orderable: false, searchable: false},
                {data: 'warehouse_name', name: 'warehouse.name', orderable: false},
                {data: 'quantity', className: 'text-right', name: 'quantity'},
                {data: 'balance_after', className: 'text-right', name: 'balance_after'},
                {data: 'user_name', name: 'user.name', orderable: false},
                {data: 'notes', orderable: false}
            ],
            order: [[0, 'desc']],
            language: { processing: "Memuat...", lengthMenu: "Tampil _MENU_", info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data", infoEmpty: "Menampilkan 0 sampai 0 dari 0 data", search: "Cari:", paginate: {previous: "Sebelumnya", next: "Berikutnya"} }
        });
        $('#btnFilter').click(function(){ table.ajax.reload(); });
    });
    </script>
    @endpush
</x-app-layout>
