<x-app-layout title="Pengguna">
    <div x-data="{ 
        confirmDelete(id) {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: 'Yakin ingin menghapus pengguna ini? Aksi ini tidak dapat dibatalkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yakin, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/production/users/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } })
                        .then(() => { 
                            $('#usersTable').DataTable().ajax.reload(); 
                        });
                }
            });
        }
    }" @delete-user.window="confirmDelete($event.detail)">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Daftar Pengguna</h3>
            <a href="{{ route('users.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-xl hover:bg-primary-600 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah User
            </a>
        </div>

        <div class="table-wrapper">
            <table id="usersTable" class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Nama</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Role</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Terdaftar</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>

    </div>

    @push('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#usersTable').DataTable({
            processing: true, 
            serverSide: true,
            ajax: '{{ route("users.data") }}',
            dom: "<'dt-layout-header'lf><'overflow-x-auto't><'dt-layout-footer'ip>",
            columns: [
                {data: 'name', name: 'name', render: function(data) {
                    var initial = data.charAt(0).toUpperCase();
                    return '<div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white text-xs font-semibold">' + initial + '</div><span class="font-medium text-gray-900">' + data + '</span></div>';
                }},
                {data: 'email', name: 'email'},
                {data: 'roles_label', name: 'roles_label', orderable: false, searchable: false},
                {data: 'created_at', name: 'created_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            language: {
                processing: "Memuat...",
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                paginate: {
                    previous: "Sebelumnya",
                    next: "Berikutnya"
                }
            }
        });
    });

    function deleteUser(id) {
        window.dispatchEvent(new CustomEvent('delete-user', { detail: id }));
    }
    </script>
    @endpush
</x-app-layout>
