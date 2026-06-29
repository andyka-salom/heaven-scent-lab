# PRD — Sistem Pencatatan Batch Produksi Parfum

**Production Batch · Material Issuance · Defect Tracking · Stock Ledger**

| Field | Detail |
| --- | --- |
| Dokumen | PRD — Sistem Pencatatan Batch Produksi Parfum |
| Versi | 2.0 |
| Tanggal | 25 Juni 2026 |
| Status | Ready for Development |
| Disusun oleh | Team IT — Heaven Scent |
| Tech Stack | Laravel 13 · PostgreSQL 16 · Blade + Tailwind CSS · Yajra DataTables · Spatie Permission |
| Sumber BOM | Stamps POS Recipes export (Cibinong City Mall) — 369 resep, 110 bahan |

---

## 1. Latar Belakang & Tujuan

Heaven Scent memproduksi parfum berbasis resep (BOM) di mana setiap produk jadi tersusun dari beberapa bahan: oil concentrate, alkohol, botol, tutup, ring/spray, atomizer, hingga kemasan. Data BOM sudah terstandarisasi di Stamps POS — 369 kombinasi produk-varian, 110 bahan unik (66 oil + 44 kemasan/bahan dasar), satuan **ml** dan **pcs**.

**Masalah:** Proses produksi belum tercatat sistematis. Konsumsi bahan, jumlah baik vs rusak, dan penambahan bahan dikelola manual — sulit melacak variance pemakaian, defect rate, dan selisih stok.

**Solusi:** Sistem web internal yang mencatat setiap batch produksi end-to-end: dari perhitungan kebutuhan bahan otomatis (BOM explode), pengeluaran bahan (issuance), pencatatan hasil baik/rusak, penambahan bahan, hingga kartu stok dan laporan.

### Dalam Scope
- Master data: Produk, Bahan, BOM (dengan versi)
- Manajemen stok: multi-gudang, stock alert, kartu stok (append-only ledger)
- Batch produksi: state machine draft → released → in_progress → completed/cancelled
- Laporan: ringkasan produksi, pemakaian bahan, defect, variance, stok rendah
- Auth & hak akses berbasis role/permission (Spatie)

### Di Luar Scope (fase berikutnya)
- Integrasi real-time ke POS Stamps/Moka
- Modul Purchasing / Purchase Order
- Costing / HPP detail
- Mobile native app
- Multi-currency & multi-company

---

## 2. Terminologi

| Istilah | Definisi |
| --- | --- |
| BOM | Daftar bahan + kuantitas standar untuk 1 unit produk jadi |
| Batch Produksi | Satu sesi/work order produksi sejumlah unit dari satu produk |
| Issuance | Pengeluaran bahan dari gudang → mengurangi stok |
| Defect | Unit gagal/cacat; tetap konsumsi bahan, tidak menghasilkan unit jual |
| Top-up | Penambahan bahan saat bahan terencana kurang (tumpah, over-pour) |
| Yield (%) | `good_qty ÷ planned_qty × 100` |
| Defect Rate (%) | `defect_qty ÷ (good_qty + defect_qty) × 100` |
| Variance Bahan | Total bahan terpakai − standar BOM untuk unit baik (positif = boros) |
| Kartu Stok | Ledger append-only setiap pergerakan stok dengan saldo berjalan |

---

## 3. Peran & Permission (Spatie)

### Role

| Role | Tanggung Jawab |
| --- | --- |
| `super_admin` | Full access semua modul + kelola user/role/permission |
| `production_manager` | Rencanakan & setujui batch, pantau yield/defect/variance, kelola BOM |
| `production_operator` | Jalankan produksi, catat hasil baik/rusak, ajukan top-up bahan |
| `warehouse_staff` | Kelola stok, proses issuance, penyesuaian stok, kartu stok |
| `viewer` | Read-only seluruh modul & laporan |

### Permission Granular

```
# Master Data
product.view        product.create      product.edit        product.delete
material.view       material.create     material.edit       material.delete
bom.view            bom.manage          bom.import

# Stok
stock.view          stock.in            stock.adjust        stock.set_alert
stock.ledger.view

# Batch Produksi
batch.view          batch.create        batch.edit
batch.release       batch.start
batch.record_output batch.record_defect
batch.topup
batch.complete      batch.cancel

# Laporan
report.view         report.export

# Admin
user.manage         role.manage
```

### Pemetaan Role → Permission (default)

| Permission | super_admin | production_manager | production_operator | warehouse_staff | viewer |
| --- | :---: | :---: | :---: | :---: | :---: |
| product.* / material.* / bom.* | ✅ | ✅ | — | — | view only |
| stock.* | ✅ | view | — | ✅ | view only |
| batch.view / create | ✅ | ✅ | ✅ | — | ✅ |
| batch.release | ✅ | ✅ | — | ✅ | — |
| batch.record_output / defect / topup | ✅ | ✅ | ✅ | — | — |
| batch.complete / cancel | ✅ | ✅ | — | — | — |
| report.* | ✅ | ✅ | — | view | view |
| user.manage / role.manage | ✅ | — | — | — | — |

> Pemetaan dapat diubah lewat UI admin tanpa deploy ulang. Role super_admin dikecualikan dari gate check (`Gate::before`).

---

## 4. Tech Stack

| Lapisan | Teknologi | Catatan |
| --- | --- | --- |
| Framework | **Laravel 13** (PHP 8.3+) | MVC, Eloquent ORM, Form Request, Service Layer |
| Database | **PostgreSQL 16** | ACID, `DECIMAL` untuk kuantitas, `SELECT ... FOR UPDATE` untuk lock stok |
| Frontend | Blade + **Tailwind CSS** | Server-rendered, design system Heaven Scent (dark + aksen gold) |
| DataTables | **Yajra Laravel DataTables** (server-side) | Grid ribuan baris — pagination/search/sort di server |
| Auth & Permission | Laravel Auth + **Spatie Laravel Permission v6** | Role & permission granular, cache permission |
| Queue (opsional) | Laravel Queue | Rekalkulasi/ekspor berat agar tidak blok request |
| Dev Tools | Laravel Debugbar, Telescope | Verifikasi N+1, profiling query |

### Prinsip Desain Sistem

- **Single source of truth stok:** Semua mutasi stok wajib lewat `StockService` — menulis `material_stocks` + `stock_movements` dalam satu transaksi.
- **Snapshot BOM:** Saat batch dibuat, BOM di-explode ke `batch_materials`. Perubahan BOM di kemudian hari tidak mengubah batch lama.
- **Append-only ledger:** Kartu stok tidak pernah di-update/hapus; koreksi = baris adjustment baru.
- **State machine terkunci:** Transisi status batch dikunci di service layer — tidak bisa ganda atau urutan salah.

---

## 5. Skema Database (PostgreSQL)

> Semua tabel memiliki `id BIGSERIAL PK`, `created_at`, `updated_at` kecuali disebutkan lain. Gunakan tipe `DECIMAL(14,3)` untuk semua kuantitas (bukan `FLOAT`).

### 5.1 ERD Ringkas

```
products            ||--o{ boms
boms                ||--o{ bom_items
materials           ||--o{ bom_items
warehouses          ||--o{ material_stocks
materials           ||--o{ material_stocks        (unique: warehouse_id + material_id)
materials           ||--o{ stock_movements
products            ||--o{ production_batches
warehouses          ||--o{ production_batches
production_batches  ||--o{ batch_materials
production_batches  ||--o{ batch_material_additions
production_batches  ||--o{ batch_defects
production_batches  ||--o{ batch_outputs
users               ||--o{ production_batches / stock_movements / batch_material_additions / batch_defects
```

### 5.2 Master Data

#### `products`
| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| sku | VARCHAR(64) UNIQUE | Kode produk (mis. LF50-SCN) |
| item_name | VARCHAR(120) | Kategori/grup (mis. Luxury Fragrance 50ml) |
| variant_name | VARCHAR(120) | Varian/scent (mis. Scandalous) |
| full_name | VARCHAR(200) | Gabungan item + varian (generated / stored) |
| unit | VARCHAR(16) DEFAULT 'pcs' | Satuan produk jadi |
| default_warehouse_id | BIGINT FK NULL | Gudang default produksi |
| is_active | BOOLEAN DEFAULT true | — |

#### `materials`
| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| code | VARCHAR(64) UNIQUE | Kode bahan (mis. OIL-SCN, ALKOHOL) |
| name | VARCHAR(150) | Nama bahan |
| type | VARCHAR(30) | `oil` \| `alcohol` \| `bottle` \| `cap` \| `spray` \| `atomizer` \| `box` \| `paperbag` \| `card` \| `other` |
| unit | VARCHAR(10) | `ml` atau `pcs` |
| is_active | BOOLEAN DEFAULT true | — |

#### `boms`
| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| product_id | BIGINT FK | — |
| version | INT DEFAULT 1 | Versi BOM |
| is_active | BOOLEAN DEFAULT true | Hanya satu aktif per produk |
| notes | TEXT NULL | — |

#### `bom_items`
| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| bom_id | BIGINT FK | — |
| material_id | BIGINT FK | — |
| quantity | DECIMAL(12,3) | Qty standar per 1 unit produk |
| unit | VARCHAR(10) | Disalin dari material |

### 5.3 Gudang & Stok

#### `warehouses`
`id, code VARCHAR(64) UNIQUE, name VARCHAR(150), location VARCHAR(200) NULL, is_active BOOLEAN, allow_negative_stock BOOLEAN DEFAULT false`

#### `material_stocks`
| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| warehouse_id | BIGINT FK | — |
| material_id | BIGINT FK | — |
| quantity | DECIMAL(14,3) DEFAULT 0 | Saldo saat ini |
| min_alert | DECIMAL(14,3) DEFAULT 0 | Ambang peringatan stok rendah |
| | UNIQUE(warehouse_id, material_id) | Satu baris saldo per kombinasi |

#### `stock_movements` (append-only, tanpa `updated_at`)
| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| warehouse_id | BIGINT FK | — |
| material_id | BIGINT FK | — |
| type | VARCHAR(20) | `in` \| `out` \| `adjustment` |
| quantity | DECIMAL(14,3) | Selalu positif; arah dari `type` |
| balance_after | DECIMAL(14,3) | Saldo setelah transaksi |
| reference_type | VARCHAR(120) NULL | Morph: `ProductionBatch`, `BatchMaterialAddition`, `manual` |
| reference_id | BIGINT NULL | ID dokumen sumber |
| notes | VARCHAR(255) NULL | — |
| user_id | BIGINT FK | Pelaku |
| created_at | TIMESTAMP | — |

### 5.4 Produksi

#### `production_batches`
| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| batch_number | VARCHAR(40) UNIQUE | Format: `BTH-YYYYMMDD-###` |
| product_id | BIGINT FK | — |
| warehouse_id | BIGINT FK | Gudang sumber bahan |
| planned_qty | INT | Rencana jumlah unit |
| good_qty | INT DEFAULT 0 | Akumulasi unit baik |
| defect_qty | INT DEFAULT 0 | Akumulasi unit rusak |
| status | VARCHAR(20) | `draft` \| `released` \| `in_progress` \| `completed` \| `cancelled` |
| production_date | DATE | — |
| created_by | BIGINT FK | — |
| closed_at | TIMESTAMP NULL | Set saat completed/cancelled |
| notes | TEXT NULL | — |

#### `batch_materials`
| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| production_batch_id | BIGINT FK | — |
| material_id | BIGINT FK | — |
| planned_qty | DECIMAL(14,3) | `bom_qty × batch.planned_qty` (snapshot saat batch dibuat) |
| issued_qty | DECIMAL(14,3) DEFAULT 0 | Qty yang benar-benar dikeluarkan |
| unit | VARCHAR(10) | Disalin dari material |

#### `batch_material_additions`
| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| production_batch_id | BIGINT FK | — |
| material_id | BIGINT FK | — |
| quantity | DECIMAL(14,3) | Jumlah tambahan |
| reason | VARCHAR(255) | Alasan top-up |
| created_by | BIGINT FK | — |
| created_at | TIMESTAMP | — |

#### `batch_defects`
| Kolom | Tipe | Keterangan |
| --- | --- | --- |
| production_batch_id | BIGINT FK | — |
| defect_qty | INT | Jumlah unit rusak |
| reason | VARCHAR(50) | `bottle_broken` \| `spray_fault` \| `contamination` \| `color_off` \| `leak` \| `other` |
| notes | VARCHAR(255) NULL | Detail tambahan |
| created_by | BIGINT FK | — |
| created_at | TIMESTAMP | — |

#### `batch_outputs`
`id, production_batch_id FK, good_qty INT, created_by FK, created_at` — pencatatan hasil baik bertahap; akumulasinya = `production_batches.good_qty`.

### 5.5 Index Wajib (PostgreSQL)

```sql
CREATE INDEX idx_batches_status       ON production_batches(status);
CREATE INDEX idx_batches_date         ON production_batches(production_date);
CREATE INDEX idx_batches_product      ON production_batches(product_id);
CREATE INDEX idx_stock_movements_mat  ON stock_movements(material_id, warehouse_id);
CREATE INDEX idx_stock_movements_ref  ON stock_movements(reference_type, reference_id);
CREATE INDEX idx_batch_materials_bat  ON batch_materials(production_batch_id);
```

---

## 6. State Machine Batch

| Status | Aksi Diizinkan | Efek Stok |
| --- | --- | --- |
| `draft` | Edit, Rilis, Hapus | Tidak ada |
| `released` | Mulai, Batalkan | Stok bahan berkurang (out); issued_qty terisi |
| `in_progress` | Catat baik, Catat rusak, Top-up, Selesaikan | Top-up → stok berkurang lagi |
| `completed` | View/cetak saja (terkunci) | Tidak ada |
| `cancelled` | View saja | Jika released/in_progress → stok dikembalikan (in) |

```
[*] --> draft        : Buat batch (BOM explode)
draft --> released   : Rilis → ISSUE bahan
draft --> cancelled  : Batalkan (tanpa efek stok)
released --> in_progress : Mulai produksi
released --> cancelled   : Batalkan → RETURN bahan
in_progress --> in_progress : Catat baik/rusak/top-up
in_progress --> completed   : Selesaikan (kunci)
```

---

## 7. Kebutuhan Fungsional

### 7.1 Master Data

| ID | Kebutuhan | Detail |
| --- | --- | --- |
| FR-01 | Kelola Produk | CRUD produk (item + varian, SKU, gudang default). Grid DataTables. |
| FR-02 | Kelola Bahan | CRUD bahan (type, unit ml/pcs). Validasi konsistensi unit vs BOM. |
| FR-03 | Kelola BOM | Resep per produk: tambah/edit/hapus baris bahan + qty. Support versi & aktif/nonaktif. |
| FR-04 | Impor BOM CSV | Impor dari ekspor Stamps (forward-fill item/varian, mapping bahan, deteksi bahan baru). |
| FR-05 | Duplikasi BOM | Salin BOM dari produk lain sebagai dasar varian baru. |

### 7.2 Stok Gudang

| ID | Kebutuhan | Detail |
| --- | --- | --- |
| FR-06 | Saldo Stok | Grid stok per bahan per gudang; penanda merah jika `quantity ≤ min_alert`. |
| FR-07 | Stok Masuk | Catat penerimaan bahan → menambah saldo + kartu stok. |
| FR-08 | Penyesuaian | Koreksi stok dengan alasan wajib; tidak menimpa histori. |
| FR-09 | Kartu Stok | Riwayat per bahan: in/out/adjustment, saldo berjalan, referensi dokumen. |
| FR-10 | Stock Alert | Set ambang minimum per bahan per gudang. |

### 7.3 Batch Produksi

| ID | Kebutuhan | Detail |
| --- | --- | --- |
| FR-11 | Buat Batch | Pilih produk + planned_qty + gudang + tanggal. Auto-explode BOM → `batch_materials`. |
| FR-12 | Pratinjau Kebutuhan | Tabel kebutuhan vs stok tersedia; sorot bahan kurang (merah); tombol rilis nonaktif jika kurang. |
| FR-13 | Rilis & Issuance | Validasi stok cukup → potong stok semua bahan (satu transaksi) → isi `issued_qty` → tulis kartu stok → status `released`. |
| FR-14 | Mulai Produksi | Status `released` → `in_progress`. |
| FR-15 | Catat Hasil Baik | Tambah `good_qty` via `batch_outputs`. Validasi `good_qty + defect_qty ≤ planned_qty`. |
| FR-16 | Catat Rusak | Tambah `batch_defects` (qty + alasan). `defect_qty` terakumulasi. |
| FR-17 | Top-up Bahan | Validasi stok → potong stok → catat `batch_material_additions` + kartu stok (out). |
| FR-18 | Selesaikan Batch | Kunci batch `completed`, hitung yield/defect/variance, set `closed_at`. |
| FR-19 | Batalkan Batch | Jika `released`/`in_progress` → kembalikan semua bahan ke stok (in) → `cancelled`. |
| FR-20 | Nomor Batch Otomatis | Generate `BTH-YYYYMMDD-###`, reset harian. |

### 7.4 Laporan & Dashboard

| ID | Kebutuhan | Detail |
| --- | --- | --- |
| FR-21 | Dashboard | KPI cards: batch aktif, yield rata-rata, defect rate, stok rendah. Grafik produksi. |
| FR-22 | Ringkasan Produksi | Per periode: jumlah batch, total good, defect, yield rata-rata. |
| FR-23 | Pemakaian Bahan | Total konsumsi per bahan (issued + top-up) per periode. |
| FR-24 | Laporan Defect | Defect per produk & per alasan. |
| FR-25 | Variance Bahan | Aktual vs standar BOM untuk unit baik. |
| FR-26 | Stok Rendah | Daftar bahan dengan stok ≤ min_alert. |
| FR-27 | Ekspor | Grid & laporan → Excel/CSV. |

---

## 8. Aturan Bisnis

### 8.1 Perhitungan BOM Explode
`batch_material.planned_qty = bom_item.quantity × batch.planned_qty`

**Contoh:** Batch 100 pcs LF50/Scandalous:
- Oil Scn: 18 × 100 = **1.800 ml**
- Alkohol: 32 × 100 = **3.200 ml**
- Botol Merah: 1 × 100 = **100 pcs**
- Tutup Merah Gold: 100 pcs
- Ring Spray Semipress: 100 pcs

### 8.2 Validasi Stok saat Rilis
- Default: tolak rilis jika ada bahan dengan stok < kebutuhan. Tampilkan daftar bahan kurang.
- Opsi: `warehouses.allow_negative_stock = true` → izinkan dengan peringatan; stok bisa negatif.
- Seluruh pemotongan bahan dalam satu transaksi — gagal = rollback penuh.

### 8.3 Rumus Metrik
| Metrik | Rumus |
| --- | --- |
| Yield | `good_qty ÷ planned_qty × 100` |
| Defect Rate | `defect_qty ÷ (good_qty + defect_qty) × 100` |
| Total konsumsi bahan | `Σ(issued_qty) + Σ(batch_material_additions.quantity)` per bahan |
| Standar untuk unit baik | `bom_item.quantity × good_qty` per bahan |
| Variance | Total konsumsi − Standar unit baik |

### 8.4 Integritas
- Semua kuantitas: `DECIMAL`, bukan `FLOAT` (hindari galat pembulatan ml).
- Kartu stok append-only; koreksi via baris adjustment baru.
- Setiap aksi kritis menyimpan `created_by` + timestamp.
- Tidak ada transisi status mundur (mis. completed → in_progress diblok).

---

## 9. Implementasi Spatie Permission (Laravel 13)

### Setup

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

### Model User

```php
// app/Models/User.php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;
}
```

### Seeder Permission

```php
// database/seeders/PermissionSeeder.php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Master
            'product.view', 'product.create', 'product.edit', 'product.delete',
            'material.view', 'material.create', 'material.edit', 'material.delete',
            'bom.view', 'bom.manage', 'bom.import',
            // Stok
            'stock.view', 'stock.in', 'stock.adjust', 'stock.set_alert', 'stock.ledger.view',
            // Batch
            'batch.view', 'batch.create', 'batch.edit',
            'batch.release', 'batch.start',
            'batch.record_output', 'batch.record_defect', 'batch.topup',
            'batch.complete', 'batch.cancel',
            // Laporan
            'report.view', 'report.export',
            // Admin
            'user.manage', 'role.manage',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Super Admin
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());

        // Production Manager
        $manager = Role::firstOrCreate(['name' => 'production_manager']);
        $manager->syncPermissions([
            'product.view', 'material.view', 'bom.view', 'bom.manage', 'bom.import',
            'stock.view', 'stock.ledger.view',
            'batch.view', 'batch.create', 'batch.edit', 'batch.release', 'batch.start',
            'batch.record_output', 'batch.record_defect', 'batch.topup',
            'batch.complete', 'batch.cancel',
            'report.view', 'report.export',
        ]);

        // Production Operator
        $operator = Role::firstOrCreate(['name' => 'production_operator']);
        $operator->syncPermissions([
            'product.view', 'material.view', 'bom.view',
            'stock.view',
            'batch.view', 'batch.create',
            'batch.record_output', 'batch.record_defect', 'batch.topup',
            'report.view',
        ]);

        // Warehouse Staff
        $warehouse = Role::firstOrCreate(['name' => 'warehouse_staff']);
        $warehouse->syncPermissions([
            'material.view', 'stock.view', 'stock.in', 'stock.adjust',
            'stock.set_alert', 'stock.ledger.view',
            'batch.view', 'batch.release',
            'report.view',
        ]);

        // Viewer
        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->syncPermissions([
            'product.view', 'material.view', 'bom.view',
            'stock.view', 'stock.ledger.view',
            'batch.view', 'report.view',
        ]);
    }
}
```

### Gate Super Admin (AppServiceProvider)

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    // Super admin bypass semua gate check
    Gate::before(function ($user, $ability) {
        return $user->hasRole('super_admin') ? true : null;
    });

    // N+1 prevention (non-production)
    \Illuminate\Database\Eloquent\Model::preventLazyLoading(! app()->isProduction());
}
```

### Middleware Route

```php
// routes/web.php
Route::prefix('production')->middleware(['auth', 'verified'])->group(function () {

    // Master Data
    Route::middleware('can:product.view')->group(function () {
        Route::resource('products', ProductController::class)
            ->middleware([
                'store'   => 'can:product.create',
                'update'  => 'can:product.edit',
                'destroy' => 'can:product.delete',
            ]);
        Route::get('products/data', [ProductController::class, 'data'])->name('products.data');
    });

    Route::middleware('can:material.view')->group(function () {
        Route::resource('materials', MaterialController::class)
            ->middleware([
                'store'   => 'can:material.create',
                'update'  => 'can:material.edit',
                'destroy' => 'can:material.delete',
            ]);
        Route::get('materials/data', [MaterialController::class, 'data'])->name('materials.data');
    });

    Route::middleware('can:bom.view')->group(function () {
        Route::get('products/{product}/bom',  [BomController::class, 'edit'])->name('bom.edit');
        Route::put('products/{product}/bom',  [BomController::class, 'update'])
            ->middleware('can:bom.manage')->name('bom.update');
        Route::post('bom/import', [BomController::class, 'import'])
            ->middleware('can:bom.import')->name('bom.import');
    });

    // Stok
    Route::middleware('can:stock.view')->group(function () {
        Route::get('stocks',      [StockController::class, 'index'])->name('stocks.index');
        Route::get('stocks/data', [StockController::class, 'data'])->name('stocks.data');
        Route::post('stocks/in',     [StockController::class, 'stockIn'])
            ->middleware('can:stock.in')->name('stocks.in');
        Route::post('stocks/adjust', [StockController::class, 'adjust'])
            ->middleware('can:stock.adjust')->name('stocks.adjust');
        Route::get('stocks/{material}/ledger',      [StockController::class, 'ledger'])
            ->middleware('can:stock.ledger.view')->name('stocks.ledger');
        Route::get('stocks/{material}/ledger/data', [StockController::class, 'ledgerData'])
            ->middleware('can:stock.ledger.view')->name('stocks.ledger.data');
    });

    // Batch Produksi
    Route::middleware('can:batch.view')->group(function () {
        Route::get('batches',       [BatchController::class, 'index'])->name('batches.index');
        Route::get('batches/data',  [BatchController::class, 'data'])->name('batches.data');
        Route::get('batches/create',[BatchController::class, 'create'])->middleware('can:batch.create')->name('batches.create');
        Route::post('batches',      [BatchController::class, 'store'])->middleware('can:batch.create')->name('batches.store');
        Route::get('batches/{batch}', [BatchController::class, 'show'])->name('batches.show');

        Route::post('batches/{batch}/release',  [BatchController::class, 'release'])
            ->middleware('can:batch.release')->name('batches.release');
        Route::post('batches/{batch}/start',    [BatchController::class, 'start'])
            ->middleware('can:batch.start')->name('batches.start');
        Route::post('batches/{batch}/output',   [BatchController::class, 'recordOutput'])
            ->middleware('can:batch.record_output')->name('batches.output');
        Route::post('batches/{batch}/defect',   [BatchController::class, 'recordDefect'])
            ->middleware('can:batch.record_defect')->name('batches.defect');
        Route::post('batches/{batch}/material', [BatchController::class, 'addMaterial'])
            ->middleware('can:batch.topup')->name('batches.material');
        Route::post('batches/{batch}/complete', [BatchController::class, 'complete'])
            ->middleware('can:batch.complete')->name('batches.complete');
        Route::post('batches/{batch}/cancel',   [BatchController::class, 'cancel'])
            ->middleware('can:batch.cancel')->name('batches.cancel');
    });

    // Laporan
    Route::middleware('can:report.view')->group(function () {
        Route::get('reports/production', [ReportController::class, 'production'])->name('reports.production');
        Route::get('reports/material',   [ReportController::class, 'material'])->name('reports.material');
        Route::get('reports/defect',     [ReportController::class, 'defect'])->name('reports.defect');
    });

    // Admin
    Route::middleware('can:user.manage')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class)->middleware('can:role.manage');
    });
});
```

### Cek Permission di Controller

```php
// Pilih salah satu cara:

// 1. Gate facade
Gate::authorize('batch.release');

// 2. $this->authorize (dalam Controller)
$this->authorize('batch.release');

// 3. Blade directive
@can('batch.create')
    <a href="{{ route('batches.create') }}">Buat Batch</a>
@endcan

// 4. Inline cek
if (auth()->user()->can('batch.topup')) { ... }
```

---

## 10. Service Layer

### StockService

```php
// app/Services/StockService.php
class StockService
{
    public function move(
        int $warehouseId,
        int $materialId,
        string $type,        // 'in' | 'out' | 'adjustment'
        float $qty,
        ?string $refType = null,
        ?int $refId = null,
        ?string $notes = null
    ): void {
        DB::transaction(function () use ($warehouseId, $materialId, $type, $qty, $refType, $refId, $notes) {
            $stock = MaterialStock::where('warehouse_id', $warehouseId)
                ->where('material_id', $materialId)
                ->lockForUpdate()   // PostgreSQL: SELECT ... FOR UPDATE
                ->firstOrFail();

            $delta = $type === 'in' ? $qty : -$qty;
            $stock->quantity += $delta;
            $stock->save();

            StockMovement::create([
                'warehouse_id'   => $warehouseId,
                'material_id'    => $materialId,
                'type'           => $type,
                'quantity'       => abs($qty),
                'balance_after'  => $stock->quantity,
                'reference_type' => $refType,
                'reference_id'   => $refId,
                'notes'          => $notes,
                'user_id'        => auth()->id(),
            ]);
        });
    }
}
```

### BatchService (ringkas)

```php
// app/Services/BatchService.php
class BatchService
{
    public function __construct(private StockService $stock) {}

    public function release(ProductionBatch $batch): void
    {
        throw_if($batch->status !== 'draft', \Exception::class, 'Batch tidak dalam status draft.');

        $shortages = [];
        foreach ($batch->materials as $bm) {
            $stock = MaterialStock::where('warehouse_id', $batch->warehouse_id)
                ->where('material_id', $bm->material_id)
                ->value('quantity');

            if ($stock < $bm->planned_qty) {
                $shortages[] = ['material' => $bm->material->name, 'need' => $bm->planned_qty, 'have' => $stock];
            }
        }

        $warehouse = $batch->warehouse;
        if (!empty($shortages) && !$warehouse->allow_negative_stock) {
            throw new \Exception(json_encode($shortages));
        }

        DB::transaction(function () use ($batch) {
            foreach ($batch->materials as $bm) {
                $this->stock->move(
                    $batch->warehouse_id, $bm->material_id, 'out',
                    $bm->planned_qty, 'ProductionBatch', $batch->id,
                    "Issuance batch #{$batch->batch_number}"
                );
                $bm->update(['issued_qty' => $bm->planned_qty]);
            }
            $batch->update(['status' => 'released']);
        });
    }

    public function cancel(ProductionBatch $batch): void
    {
        throw_if(
            !in_array($batch->status, ['draft', 'released', 'in_progress']),
            \Exception::class, 'Batch tidak bisa dibatalkan.'
        );

        DB::transaction(function () use ($batch) {
            if (in_array($batch->status, ['released', 'in_progress'])) {
                // Kembalikan semua bahan yang sudah dikeluarkan
                foreach ($batch->materials as $bm) {
                    if ($bm->issued_qty > 0) {
                        $this->stock->move(
                            $batch->warehouse_id, $bm->material_id, 'in',
                            $bm->issued_qty, 'ProductionBatch', $batch->id,
                            "Return cancel batch #{$batch->batch_number}"
                        );
                    }
                }
                // Kembalikan juga top-up additions
                foreach ($batch->additions as $add) {
                    $this->stock->move(
                        $batch->warehouse_id, $add->material_id, 'in',
                        $add->quantity, 'BatchMaterialAddition', $add->id,
                        "Return top-up cancel batch #{$batch->batch_number}"
                    );
                }
            }
            $batch->update(['status' => 'cancelled', 'closed_at' => now()]);
        });
    }
}
```

---

## 11. DataTables (N+1 Prevention)

### Pola Benar

```php
// SELALU eager load + DataTables::eloquent (bukan ::of)
public function data(): JsonResponse
{
    $this->authorize('batch.view');

    $query = ProductionBatch::query()
        ->select('production_batches.*')
        ->with([
            'product:id,sku,full_name',
            'warehouse:id,name',
            'creator:id,name',
        ])
        ->withSum('additions as additions_total', 'quantity');

    return DataTables::eloquent($query)
        ->addColumn('product',   fn ($b) => $b->product->full_name)
        ->addColumn('warehouse', fn ($b) => $b->warehouse->name)
        ->addColumn('yield',     fn ($b) => $b->planned_qty > 0
            ? round($b->good_qty / $b->planned_qty * 100, 1) . '%' : '-')
        ->editColumn('status',          fn ($b) => view('batch._status', ['s' => $b->status])->render())
        ->editColumn('production_date', fn ($b) => optional($b->production_date)->format('d/m/Y'))
        ->addColumn('action',           fn ($b) => view('batch._actions', ['b' => $b])->render())
        ->rawColumns(['status', 'action'])
        ->toJson();
}
```

### Filter Kolom Relasi (PostgreSQL `ILIKE`)

```php
->filterColumn('product', function ($q, $kw) {
    $q->whereHas('product', fn ($p) =>
        $p->where('full_name', 'ilike', "%{$kw}%")   // PostgreSQL case-insensitive
          ->orWhere('sku', 'ilike', "%{$kw}%")
    );
})
```

---

## 12. Halaman UI

| Halaman | Komponen | Aksi |
| --- | --- | --- |
| Dashboard | KPI cards (batch aktif, yield, defect rate, stok rendah), grafik produksi | Navigasi cepat |
| Daftar Batch | DataTables: no. batch, produk, rencana, baik, rusak, yield, status, tanggal | Buat, lihat, rilis, batal |
| Buat Batch | Form: produk + qty + gudang + tanggal; pratinjau kebutuhan bahan vs stok | Simpan (draft) |
| Detail Batch | Header status; tabel bahan (rencana/keluar); panel hasil & rusak; panel top-up; ringkasan yield/variance | Rilis, mulai, catat, selesaikan |
| Produk & BOM | Daftar produk + jumlah bahan; editor BOM | CRUD, edit BOM, impor CSV |
| Bahan & Stok | Grid: kode, nama, type, satuan, stok, penanda rendah | Stok masuk, penyesuaian, set alert |
| Kartu Stok | Riwayat pergerakan + saldo berjalan per bahan | Filter periode, ekspor |
| Laporan | Produksi, pemakaian, defect, variance, stok rendah | Filter, ekspor Excel/CSV |
| Kelola User | CRUD user + assign role | — |
| Kelola Role | CRUD role + assign permission | — |

**Catatan UI:**
- Tombol aksi muncul kondisional sesuai status batch DAN permission user.
- Form "Top-up Bahan" & "Catat Rusak" = modal; refresh panel via AJAX tanpa reload.
- Baris merah di pratinjau kebutuhan = stok kurang; tombol rilis disabled.

---

## 13. Non-Fungsional

| Aspek | Target |
| --- | --- |
| Performa | Grid server-side < 500 ms untuk 10rb+ baris; query konstan (bebas N+1) |
| Integritas | Semua operasi stok dalam transaksi DB + `lockForUpdate`; kuantitas DECIMAL |
| Keamanan | Auth wajib; otorisasi per permission (Spatie); Form Request validation; CSRF; `$fillable` dijaga |
| Auditability | Kartu stok append-only; `created_by` + timestamp pada setiap aksi; status tidak mundur |
| Skalabilitas | Index pada FK & kolom filter utama |
| Lokalisasi | Antarmuka Bahasa Indonesia; format `d/m/Y`; desimal koma |

---

## 14. Acceptance Criteria

| ID | Skenario | Hasil yang Diharapkan |
| --- | --- | --- |
| AC-1 | Buat batch 100 pcs LF50/Scandalous | `batch_materials`: Oil 1.800 ml, Alkohol 3.200 ml, Botol 100, Tutup 100, Ring 100. Status `draft`. |
| AC-2 | Rilis batch, stok cukup | Stok semua bahan berkurang; 5 kartu stok (out) dibuat; `issued_qty` terisi; status `released`. |
| AC-3 | Rilis batch, satu bahan kurang | Rilis ditolak; daftar bahan kurang tampil; stok tidak berubah. |
| AC-4 | Catat 5 unit rusak (botol pecah) | `batch_defects` bertambah; `defect_qty = 5`; alasan tersimpan. |
| AC-5 | Top-up Alkohol 200 ml | Stok Alkohol berkurang 200 ml; addition tercatat; kartu stok (out, ref addition) dibuat. |
| AC-6 | Selesaikan batch (good 95, defect 5) | Yield 95%, defect rate 5%, variance dihitung; status `completed`; `closed_at` terisi. |
| AC-7 | Batalkan batch yang sudah released | Semua bahan dikembalikan ke stok (in); status `cancelled`. |
| AC-8 | Grid batch 5.000 baris | Load cepat; query konstan (Debugbar/Telescope). |
| AC-9 | User production_operator tidak bisa rilis | HTTP 403 saat akses endpoint `batches.release`. |
| AC-10 | Impor CSV BOM Stamps | Item/varian ter-forward-fill; bahan ter-mapping; bahan baru terdeteksi & dilaporkan. |

---

## 15. Roadmap Implementasi

| Fase | Lingkup | Output |
| --- | --- | --- |
| **Fase 1 — Fondasi** | Skema DB (migrasi PostgreSQL), model + relasi, seeder permission, auth, impor BOM CSV | Master data & BOM siap, permission terkonfigurasi |
| **Fase 2 — Stok** | `StockService`, stok masuk, penyesuaian, kartu stok, grid DataTables stok | Manajemen stok + ledger berjalan |
| **Fase 3 — Batch Inti** | Buat batch + BOM explode, pratinjau kebutuhan, rilis & issuance, state machine | Pengeluaran bahan dari gudang tercatat |
| **Fase 4 — Hasil Produksi** | Catat baik/rusak, top-up bahan, selesaikan/batal, metrik yield/defect/variance | Pencatatan hasil end-to-end lengkap |
| **Fase 5 — Laporan & Polish** | Dashboard, laporan produksi/bahan/defect/variance, ekspor, optimasi query, UI final | Sistem siap produksi |

---

## Lampiran A — Ringkasan BOM Aktual

| Metrik | Nilai |
| --- | --- |
| Total baris BOM | 1.665 |
| Total resep (produk + varian) | 369 |
| Kategori produk | 15 |
| Bahan unik | 110 (66 oil + 44 kemasan/bahan dasar) |
| Satuan | ml (cairan) & pcs (komponen) |
| Rata-rata bahan per resep | ≈ 4,5 (min 1, maks 6) |

### Bahan Paling Sering Dipakai

| Bahan | Frekuensi (resep) |
| --- | --- |
| ALKOHOL | 348 |
| Ring Spray Semipress | 228 |
| Atomizer | 108 |
| Spray Refill Atomizer | 108 |

### Contoh Resep

```
Luxury Fragrance 50ml / Scandalous
  - Oil Scn ................ 18,00 ml
  - ALKOHOL ................ 32,00 ml
  - BOTOL MERAH ............  1 pcs
  - TUTUP MERAH GOLD .......  1 pcs
  - Ring Spray Semipress ...  1 pcs

Niche Signature / Ani X
  - Oil Ani ................ 50,00 ml
  - ALKOHOL ................ 50,00 ml
  - BOTOL NICHE BENING .....  1 pcs
  - TUTUP RING NICHE RUSA ..  1 pcs
  - SPRAY NICHE ............  1 pcs
  - BOX NICHE RUSA .........  1 pcs

Travel Perfume / Scandalous
  - Oil Scn ................  5,00 ml
  - ALKOHOL ................  5,00 ml
  - Atomizer ...............  1 pcs
  - Spray Refill Atomizer ..  1 pcs
```

---

*— Akhir Dokumen v2.0 —*
