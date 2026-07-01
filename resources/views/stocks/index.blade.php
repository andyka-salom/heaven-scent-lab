<x-app-layout title="Stok">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h3 class="text-lg font-semibold text-gray-900">Saldo Stok</h3>
        <div class="flex gap-2">
            @can('stock.in')<button onclick="openStockIn()" class="px-3 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition">Stok Masuk</button>@endcan
            @can('stock.adjust')<button onclick="openStockAdjust()" class="px-3 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition">Penyesuaian</button>@endcan
            @can('stock.set_alert')<button onclick="openStockAlertGeneral()" class="px-3 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">Set Alert Min</button>@endcan
        </div>
    </div>
    <div class="flex flex-wrap gap-3 mb-4">
        <select id="filterWarehouse" class="px-3 py-2 rounded-lg border border-gray-200 text-sm"><option value="">Semua Gudang</option>@foreach($warehouses as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
        <select id="filterMaterial" class="px-3 py-2 rounded-lg border border-gray-200 text-sm"><option value="">Semua Bahan</option>@foreach($materials as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
    </div>
    <div class="table-wrapper">
        <table id="stocksTable" class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Kode</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Bahan</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Gudang</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Stok</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Min</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-600">Aksi</th>
                </tr>
            </thead>
        </table>
    </div>

    {{-- Stock In Template --}}
    <template id="stock-in-template">
        <form method="POST" action="{{ route('stocks.in') }}" id="form-stock-in" class="space-y-4 text-left mt-4">
            @csrf
            <div>
                <select name="warehouse_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 transition"><option value="">-- Pilih Gudang --</option>@foreach($warehouses as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select>
            </div>
            <div>
                <select name="material_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 transition"><option value="">-- Pilih Bahan --</option>@foreach($materials as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select>
            </div>
            <div>
                <input type="number" name="quantity" step="0.001" min="0.001" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 transition" placeholder="Jumlah Masuk">
            </div>
            <div>
                <input type="text" name="notes" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 transition" placeholder="Catatan (opsional)">
            </div>
        </form>
    </template>

    {{-- Adjust Template --}}
    <template id="stock-adjust-template">
        <form method="POST" action="{{ route('stocks.adjust') }}" id="form-stock-adjust" class="space-y-4 text-left mt-4">
            @csrf
            <div>
                <select name="warehouse_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 transition"><option value="">-- Pilih Gudang --</option>@foreach($warehouses as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select>
            </div>
            <div>
                <select name="material_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 transition"><option value="">-- Pilih Bahan --</option>@foreach($materials as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select>
            </div>
            <div>
                <input type="number" name="quantity" step="0.001" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 transition" placeholder="Jumlah Penyesuaian (+/-)">
            </div>
            <div>
                <input type="text" name="notes" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 transition" placeholder="Alasan (wajib)">
            </div>
        </form>
    </template>

    {{-- Set Alert Template (Row) --}}
    <template id="stock-alert-template-row">
        <form method="POST" action="{{ route('stocks.alert') }}" id="form-stock-alert-row" class="space-y-4 text-left mt-4">
            @csrf
            <input type="hidden" name="warehouse_id" id="row-alert-warehouse-id">
            <input type="hidden" name="material_id" id="row-alert-material-id">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Gudang</label>
                <div id="row-alert-warehouse-name" class="px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-700 font-medium"></div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Bahan</label>
                <div id="row-alert-material-name" class="px-3 py-2 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-700 font-medium"></div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Ambang Peringatan (Min Stok)</label>
                <input type="number" name="min_alert" id="row-alert-min-alert" step="0.001" min="0" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 transition" placeholder="Masukkan Ambang Peringatan">
            </div>
        </form>
    </template>

    {{-- Set Alert Template (General) --}}
    <template id="stock-alert-template-general">
        <form method="POST" action="{{ route('stocks.alert') }}" id="form-stock-alert-general" class="space-y-4 text-left mt-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Gudang</label>
                <select name="warehouse_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 transition"><option value="">-- Pilih Gudang --</option>@foreach($warehouses as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Bahan</label>
                <select name="material_id" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 transition"><option value="">-- Pilih Bahan --</option>@foreach($materials as $id => $n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Ambang Peringatan (Min Stok)</label>
                <input type="number" name="min_alert" step="0.001" min="0" required class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:border-primary-500 focus:ring-primary-500 transition" placeholder="Ambang Peringatan (Min Stok)">
            </div>
        </form>
    </template>

    @push('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
    var table;
    $(document).ready(function(){
        table = $('#stocksTable').DataTable({
            processing:true, serverSide:true,
            ajax:{url:'{{ route("stocks.data") }}', data:function(d){d.warehouse_id=$('#filterWarehouse').val();d.material_id=$('#filterMaterial').val();}},
            dom: "<'dt-layout-header'lf><'overflow-x-auto't><'dt-layout-footer'ip>",
            columns:[
                {data:'material_code', name: 'material.code', orderable: false},
                {data:'material_name', name: 'material.name', orderable: false},
                {data:'warehouse_name', name: 'warehouse.name', orderable: false},
                {data:'quantity', name: 'quantity'},
                {data:'min_alert', name: 'min_alert'},
                {data:'action', orderable: false, searchable: false}
            ],
            language: { processing: "Memuat...", lengthMenu: "Tampil _MENU_", info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data", infoEmpty: "Menampilkan 0 sampai 0 dari 0 data", search: "Cari:", paginate: {previous: "Sebelumnya", next: "Berikutnya"} }
        });
        $('#filterWarehouse,#filterMaterial').change(function(){table.ajax.reload();});
    });

    function openStockIn() {
        Swal.fire({
            title: 'Stok Masuk',
            html: document.getElementById('stock-in-template').innerHTML,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#059669', // emerald-600
            preConfirm: () => {
                const form = Swal.getPopup().querySelector('#form-stock-in');
                if (form.reportValidity()) {
                    form.submit();
                } else {
                    return false;
                }
            }
        });
    }

    function openStockAdjust() {
        Swal.fire({
            title: 'Penyesuaian Stok',
            html: document.getElementById('stock-adjust-template').innerHTML,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d97706', // amber-600
            preConfirm: () => {
                const form = Swal.getPopup().querySelector('#form-stock-adjust');
                if (form.reportValidity()) {
                    form.submit();
                } else {
                    return false;
                }
            }
        });
    }

    function openSetAlert(warehouseId, materialId, materialName, warehouseName, minAlert) {
        Swal.fire({
            title: 'Atur Ambang Peringatan Stok',
            html: document.getElementById('stock-alert-template-row').innerHTML,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#4f46e5', // indigo-600
            didOpen: () => {
                const popup = Swal.getPopup();
                popup.querySelector('#row-alert-warehouse-id').value = warehouseId;
                popup.querySelector('#row-alert-material-id').value = materialId;
                popup.querySelector('#row-alert-warehouse-name').innerText = warehouseName;
                popup.querySelector('#row-alert-material-name').innerText = materialName;
                popup.querySelector('#row-alert-min-alert').value = minAlert;
            },
            preConfirm: () => {
                const form = Swal.getPopup().querySelector('#form-stock-alert-row');
                if (form.reportValidity()) {
                    form.submit();
                } else {
                    return false;
                }
            }
        });
    }

    function openStockAlertGeneral() {
        Swal.fire({
            title: 'Atur Ambang Peringatan Stok',
            html: document.getElementById('stock-alert-template-general').innerHTML,
            showCancelButton: true,
            confirmButtonText: 'Simpan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#4f46e5', // indigo-600
            preConfirm: () => {
                const form = Swal.getPopup().querySelector('#form-stock-alert-general');
                if (form.reportValidity()) {
                    form.submit();
                } else {
                    return false;
                }
            }
        });
    }
    </script>
    @endpush
</x-app-layout>
