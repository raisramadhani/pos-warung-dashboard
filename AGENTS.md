<laravel-boost-guidelines>
=== .ai/business-flows rules ===

## Business Flows — Abra POS

### Alur Purchasing (PO)

1. SPV create Items (master data) dengan satuan terkecil
2. SPV create PO → pilih supplier, item, jumlah, harga → **auto-approve** (status langsung `Approved`)
3. Supplier kirim barang → buat Penerimaan Barang (GoodsReceipt) → SPV verifikasi 1-1 per item (quantity_received)
4. Klik Verifikasi pada GR → `quantity_received` tercatat → stok gudang bertambah (bahan baku) / aset dibuat (alat)

**Catatan status PO:**

- Saat ini PO **langsung `Approved`** saat disimpan (auto-approve). Alasannya: fitur approval oleh accounting belum ada,
  jadi untuk sementara PO disimpan langsung berstatus `Approved`. Kedepannya: PO dibuat `Draft` → accounting menyetujui →
  `Approved` → baru bisa diproses penerimaan barang.
- **Penerimaan parsial** didukung: boleh terima sebagian barang. Namun **hanya 1 Penerimaan Barang aktif per PO** — jika
  masih ada GR berstatus Draft, tidak bisa membuat GR baru.
- **PO boleh diedit HANYA saat status `Approved`** (`EditAction` visible hanya `Approved`, form di-disable + `halt()` di
  save saat status selain `Approved`). Alasan: setelah PO disetujui/diproses menjadi `Receiving`, PO sudah menjadi acuan
  GR; mengubah item/jumlah/harga akan mengganggu proses penerimaan barang (mismatch antara PO dan GR).
- **PO tidak boleh dihapus** (tidak ada `DeleteAction` di list maupun header edit).
- **PO bisa ditutup manual** via aksi "Tutup PO" (`ClosePurchaseOrderAction`) saat status `Approved` / `Receiving` →
  menjadi `Finished`. PO juga otomatis `Finished` saat semua item sudah diterima (`GoodsReceiptObserver`).

### Alur Penerimaan Barang (GoodsReceipt)

1. GR dibuat dari PO (source `purchasing`) dengan status `Draft`
2. Saat GR **diverifikasi** (status → `Verified`), stok langsung terpengaruh:

- Item `RawMaterial` → stok gudang bertambah (`StockMovementType::GoodsReceiptIn`)
- Item `Tool` → stok gudang bertambah (`StockMovementType::GoodsReceiptIn`) **dan** dibuatkan **Aset** per unit

3. **GR boleh diedit hanya saat status `Draft`** (form di-disable saat `Verified` karena stok sudah terproses)
4. **GR boleh dihapus hanya saat status `Draft`** (`DeleteAction` visible hanya `Draft`)
5. **Policy aktif**: item `Tool` memengaruhi stok **dan** aset agar kuantitas fisik dan daftar aset sama-sama terlacak.

### Alur Distribusi Barang

1. SPV (Admin) pilih **sumber** (`source_merchant_id`) dan **tujuan** (`merchant_id`) → pilih item + qty
2. **Stok langsung terpengaruh saat create** (status `Sent`): stok sumber berkurang (`DistributionOut`) via
   `DistributionItemObserver::created`
3. Staff merchant verifikasi item 1-1 → isi `quantity_received`
4. Klik Finish → status `Finished` → stok tujuan bertambah (`DistributionIn`) via `DistributionObserver::updated`

**Catatan status & edit:**

- Status: `Sent` → `Receiving` → `Finished` (atau `Canceled`)
- **Edit Distribusi di-disable total** karena stok sudah terpengaruh sejak create (form `$schema->disabled()` + `halt()`
  di save). Alasan: saat Distribusi di-create, stok sumber langsung berkurang (`DistributionItemObserver::created`); jika
  edit diperbolehkan, perubahan sumber/tujuan/qty akan menimbulkan inkonsistensi stok (double count/skew) karena tidak ada
  mekanisme rollback.
- **Distribusi tidak boleh dihapus sama sekali** — alasan sama: stok sudah terpengaruh sejak create tanpa mekanisme
  reverse stok, sehingga hapus akan membuat data stok tidak konsisten.
- **Sumber bisa gudang ATAU merchant** (merchant→merchant didukung). Default: gudang→merchant. Hanya Admin yang membuat
  distribusi.

### Alur Produk

1. Merchant create produk + resep (bahan per porsi) di panel merchant

### Alur Kasir (POS) — Redaksi Produk vs Item

Di fitur kasir (POS), istilah **Produk** dan **Item** berbeda makna dan tidak boleh tertukar:

- **Produk** = jumlah **jenis produk berbeda** dalam satu transaksi, sama dengan jumlah **baris** `transaction_items`
  (`transaction_items_count`, dikirim sebagai `line_items_count` di riwayat POS).
- **Item** = **total pcs/kuantitas** seluruh produk dalam transaksi, yaitu penjumlahan kolom `quantity` dari semua baris
  `transaction_items` (`transactions.items_count`).

Contoh: dalam satu transaksi berisi Es Teh Jumbo 1 pcs + Mie Desa 7 pcs + Es Teh Kampul 1 pcs, maka tercatat
**3 Produk** (3 baris) dan **9 Item** (1 + 7 + 1).

### Alur Item & Stok Awal (Opening Stock)

1. SPV/Admin create Item (master data bahan baku & alat) dengan satuan terkecil
2. Saat create item, ada field opsional **Stok Awal (Opening Stock)** — hanya untuk item `RawMaterial`
   dan hanya tampil saat create (tidak tampil saat edit)
3. Jika `opening_stock > 0` diisi: `StockMovementService::increase()` ke **gudang utama**
   (`Merchant::warehouse()`) dengan `StockMovementType::Opening`, reference = item
4. `merchant_stocks` gudang bertambah + `stock_movements` tercatat (audit trail)
5. **Peringatan double input**: jangan isi stok awal jika item juga akan masuk via PO / Penerimaan Barang /
   Distribusi, agar stok tidak bertambah dua kali

### Sumber Stok Masuk — PO vs GR

**`PurchaseOrderSource` (enum PO):**

- **Hanya `Purchasing`**. Enum sengaja dipertahankan walaupun hanya 1 case, karena dulu ada `Donation` dll sebelum ada
  GoodsReceipt. Setelah ada GR, sumber Donation/Opening dipindah ke `ReceiptSourceType`. Dipertahankan untuk antisipasi
  pengembangan.

**`ReceiptSourceType` (enum GR):**

- `Purchasing`: Beli dari supplier (ada supplier_id, ada harga)
- `Donation`: Hibah/donasi (tanpa supplier, tanpa harga)
- `Opening`: Stok awal/migrasi data

### Status Purchase Order (PurchaseOrderStatus)

- `Draft`: Baru dibuat, belum disetujui
- `Approved`: Disetujui (saat ini auto-approve saat simpan)
- `Receiving`: Sedang dalam proses penerimaan barang
- `Finished`: PO selesai, semua barang sudah diterima / ditutup manual
- `Canceled`: Dibatalkan

### Status Lainnya

- `GoodsReceiptStatus`: `Draft` → `Verified`
- `DistributionStatus`: `Sent` → `Receiving` → `Finished` (atau `Canceled`)

### Alur Aset & Penyusutan

1. Item bertipe `Tool` (Alat) digunakan sebagai aset, item bertipe `RawMaterial` (Bahan Baku) sebagai stok
2. Alur pendaftaran aset: Purchasing (PO) → Penerimaan Barang (GoodsReceipt) → Aset
3. Saat GR diverifikasi, `GoodsReceiptObserver::updated()` menambah stok gudang untuk semua item, dan untuk item `Tool`
   juga otomatis membuat Aset per unit (nama dari `items.name`, harga dari `goods_receipt_items.unit_price`)
4. Saat Distribusi `Sent`, stok sumber berkurang untuk semua item (termasuk `Tool`). Saat `Finished`, stok tujuan
   bertambah untuk semua item (termasuk `Tool`), dan item `Tool` tetap dibuatkan aset per unit
5. Penyusutan dihitung via `AssetDepreciationService` (Straight Line / Reduce Balance), jadwal di halaman
   `DepreciationSchedule`

**Kapan stok tercatat (masuk/keluar):**

| Tipe Item                                                | Stok Masuk                                                                                           | Stok Keluar                        | Catatan |
| -------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- | ---------------------------------- | ------- |
| `RawMaterial` (Bahan Baku)                               | GR `Verified` → `GoodsReceiptIn`; Distribusi `Finished` → `DistributionIn`; Retur masuk →            |
| `ReturnIn`                                               | Distribusi `Sent` → `DistributionOut`; Penjualan (konsumsi bahan) → `TransactionOut`; Retur keluar → |
| `ReturnOut`                                              | `Adjustment` via stock opname / koreksi                                                              |
| `Tool` (Alat/Aset)                                       | GR `Verified` → `GoodsReceiptIn`; Distribusi `Finished` → `DistributionIn`; Retur masuk →            |
| `ReturnIn`                                               | Distribusi `Sent` → `DistributionOut`; Retur keluar → `ReturnOut`                                    | Selain stok kuantitas, item `Tool` |
| juga dibuatkan aset per unit untuk monitoring depresiasi |

### Bug Detail (kondisi sekarang → problem → harusnya)

1. **Status update (solved)**

- Bug distribusi alat double count dan stok alat negatif yang berbasis asumsi "Tool tidak ikut stok" dinyatakan selesai
  karena policy aktif kini menetapkan item `Tool` ikut memengaruhi stok pada GR dan Distribusi, sambil tetap membuat aset.

4. **Bug subtotal saat edit Penerimaan Barang (GR)**

- _Kondisi sekarang_: form GR menghitung `subtotal = quantity_received × unit_price` via `afterStateUpdated` dan
  `mutateRelationshipDataBeforeSaveUsing`. Saat edit item, perhitungan ulang tidak selalu konsisten.
- _Problem_: subtotal bisa salah saat mengedit GR berstatus Draft.
- _Harusnya_: hitung ulang subtotal secara konsisten dari `quantity_received × unit_price` pada setiap save, termasuk
  saat edit.

5. **Kolom "Item Terverifikasi" di tabel Distribusi / Penerimaan Barang / Stock Opname (deferred bug)**

- _Kondisi sekarang_: kolom `Item Terverifikasi` (nama field sementara `items_count2` / `items_count2bug`) masih
  memakai `->counts('items')` — sama persis dengan kolom `Item Tersedia`, belum menghitung item yang benar-benar
  terverifikasi/diterima/dihitung.
- _Problem_: angka "Item Terverifikasi" tidak akurat (menampilkan total item, bukan yang sudah diverifikasi).
- _Harusnya_: **(deferred)** hitung jumlah item yang sudah diverifikasi (mis. `quantity_received > 0` di GR/Distribusi,
  atau item yang sudah dihitung di Stock Opname), bukan sekadar total item. Belum dikerjakan.

### Catatan Desain (bukan bug)

- **Aset dari distribusi `acquisition_cost = 0`** — wajar karena `DistributionItem` tidak punya kolom harga. Tujuan
  daftar aset adalah monitoring depresiasi, bukan mencatat nilai beli.
- **Depresiasi aset dari observer di-hardcode** (`StraightLine`, `useful_life_months=12`, `acquisition_date=now()`) —
  dianggap baik karena default-nya di enum; user tetap bisa mengubah di form Aset.

### Alur Promosi (Promo)

1. Merchant/Admin create promo → pilih tipe promo, syarat (produk + jumlah minimum), hadiah → `is_active=true`
2. **Aturan Kunci**: **Hanya 1 promo AKTIF per produk** — form memblokir dropdown produk yang sudah punya promo aktif
   lain; create/edit menolak dengan error jika conflict terdeteksi
3. Tipe promo dan aturan field reward:

- `BuyXGetY` (Beli X Dapat Y Gratis): Hadiah = Item Gratis; **Quantity** wajib, **Value** hidden
- `FlashSalePrice` (Harga Flash Sale): Hadiah = Harga Khusus; **Quantity** hidden, **Value** wajib (harga satuan flash
  sale)
- `BundleFixedPrice` (Beli Sekian Harga Pas): Hadiah = Harga Khusus; **Quantity** hidden, **Value** wajib (total
  harga pas)
- `PercentDiscount` (Diskon Persen): Hadiah = Diskon Persen; **Quantity** hidden, **Value** wajib + suffix `%`
- `FixedDiscount` (Diskon Nominal): Hadiah = Diskon Nominal; **Quantity** hidden, **Value** wajib + prefix `Rp`

4. Struktur form promo saat ini (single-entry):

- Card **Syarat (Beli)** single-entry: `Repeater::make('conditions')` dengan `minItems(1)`, `maxItems(1)`,
  `addable(false)`, `deletable(false)`, `reorderable(false)` — 1 syarat per promo.
- Card **Hadiah (Dapat)** single-entry: `Repeater::make('rewards')` dengan batasan yang sama — 1 hadiah per promo.
- **Catatan arsitektur DB vs form**: dari sisi DB, relasi `promotion_conditions` dan `promotion_rewards` adalah
  `hasMany` (sudah mendukung multi syarat & multi hadiah). Namun form sengaja dibatasi **single-entry** untuk tahap awal
  guna meminimalisir risiko/kompleksitas evaluasi di halaman POS kasir. Pembatasan ini di level form (`maxItems(1)`),
  bukan di DB — jadi jika kelak ingin multi, cukup longgarkan form dan sesuaikan `PromotionService::evaluateCart()`.

5. Tampilan detail promo (infolist):

- Bagian Syarat dan Hadiah ditampilkan dalam format card/teks (bukan table)
- Nilai hadiah mengikuti konteks tipe reward (prefix `Rp`, suffix `%`, serta deskripsi bantu)

6. Saat checkout POS:

- `PromotionService::evaluateCart()` mengambil promo aktif merchant → evaluasi syarat per item
- Promo diterapkan hanya ke baris pertama yang belum ada promo (check `promotion_id === null`)
- **Double promo dicegah**: setiap baris hanya bisa dapat 1 promo karena form blocking produk yang sudah punya promo
  aktif
- Item gratis (`FreeItem`) ditambahkan sebagai baris baru dengan `is_free=true` & `unit_price=0`; tidak dihitung di
  subtotal

7. Periode/jadwal promo:

- Periode: field `date_range` di form → disimpan ke `starts_at` / `ends_at`
- Jadwal: repeater hari + jam; kosong = berlaku sepanjang hari
- `isInSchedule()` mengecek apakah promo aktif pada waktu transaksi

**Catatan teknis:**

- `fixedPriceQuantity()`: untuk reward type `FixedPrice`, kuantitas mengikuti `condition.min_quantity`
- Form syarat & hadiah saat ini single-entry (1 syarat & 1 hadiah per promo), bukan multi-entry (lihat poin 4)
- Redemption tercatat otomatis via `recordRedemptions()` setelah transaksi tersimpan

### Alur Cash Flow (Keuangan)

1. Catat pemasukan/pengeluaran per merchant di resource Keuangan (panel Admin & Merchant)
2. `type` = `Income` / `Expense` (`CashFlowType`)
3. `amount` bertanda (signed): pemasukan positif, pengeluaran negatif
4. `affects_cash_drawer = true` → nominal ikut memengaruhi akumulasi cashdrawer saat tutup shift

### Alur Shift Kas (CashDrawer)

1. Buka Shift: catat `opening_amount` (saldo awal cashdrawer) + `opening_note` (catatan pembukaan, opsional)
2. Tutup Shift: `computeClosing()` menghitung:

```
expected_drawer = opening_amount
+ Σ income (affects_cash_drawer = true)
+ Σ expense (affects_cash_drawer = true) // bernilai negatif
+ Σ total_amount transaksi cash dalam rentang [opened_at, closed_at)
```

3. Total transaksi cash = `SUM(total_amount)` transaksi `payment_method = cash` (bukan `amount_received`), QRIS
   dikecualikan
4. `expected_cash_amount` (akumulasi) dibandingkan `declared_cash_amount` (nominal fisik) → `difference`
5. Form Tutup Shift juga punya field **Keterangan** (`notes`), disimpan ke kolom `notes` dan ditampilkan di infolist
   shift (terpisah dari `opening_note` yang diisi saat buka shift).

**Catatan implementasi sumCashBetween di models:**

- Perhitungan transaksi cash hidup di **model `Transaction`** lewat static method
  `Transaction::sumCashBetween(int $merchantId, Carbon $start, Carbon $end): int` —
  query transaksi diletakkan di domain transaksi, bukan di service cashdrawer.
- `CashDrawerService::totalCashTransactions()` **mendelegasikan** ke method tersebut
  (menjaga rentang `[opened_at, closed_at)` di service, query sum di model).
- Gunakan `Transaction::sumCashBetween()` di tempat lain yang butuh rekap kas
  (laporan shift, rekap harian) agar tidak duplikasi query.

### Alur Laporan (Admin)

Ada 2 halaman laporan di grup navigasi **Laporan** pada panel Admin:

**1. Laporan Pendapatan (`RevenueReport`)**

- Resource `App\Filament\Admin\Resources\RevenueReport` (slug `revenue-report`), read-only, model `Transaction`.
- **Granularitas per produk** (baris = 1 transaction item): query join `transaction_items`, ambil `product_name` dari
  `product_data` (JSON), `item_quantity`, `item_subtotal`, dan `gross_profit = subtotal − (qty × cost_price snapshot)`.
- Kolom: Tanggal, Outlet, Metode Bayar, Produk, Quantity, Pendapatan, Keuntungan (+ summarizer total).
- Filter: rentang tanggal transaksi, outlet, metode bayar. Filter Produk per-id **dihapus**: `product_id`
  bersifat per-outlet (produk bernama sama di beberapa outlet punya id berbeda), sehingga filter by id
  berpotensi double/ambigu dan tidak sesuai niat user (mencari nama). Pencarian produk dilakukan via
  searchbar kolom Produk yang menyaring `product_data.name` (nama historis). Ke depannya perlu filter
  **berbasis snapshot (nama)**, bukan id — butuh penanganan khusus dan akan dikembangkan.
- Pendapatan & HPP hanya untuk outlet (`merchantsOnly`); produk diambil historis dari `product_data`.

**2. Laporan Laba Rugi (`ProfitLossReport`)**

- Page `App\Filament\Admin\Pages\ProfitLossReport` (slug `laba-rugi`), perhitungan terpusat di
  `App\Services\ProfitLossService::compute()`.
- Tampilan **Tabs**: tab **Ringkasan** (statement laba rugi) + tab **Detail** (6 tabel detail grup per Outlet).
- Rumus statement:
- Pendapatan Penjualan = `SUM(transactions.total_amount)` (outlet).
- HPP = `SUM(quantity × product_data.cost_price)`.
- Laba Kotor = Pendapatan − HPP.
- Pendapatan Lain-lain = `SUM(cash_flows type=income)`.
- Beban Gaji = `SUM(payrolls status=Paid, overlap periode)`.
- Beban Operasional = `SUM(cash_flows type=expense)`.
- Beban Penyusutan = `SUM(asset_depreciations.depreciation_amount)`.
- Laba/Rugi Bersih = Laba Kotor + Pendapatan Lain − Total Beban.
- Cakupan: Revenue & HPP hanya outlet; CashFlow/Payroll/Depresiasi mencakup gudang + outlet (gudang ikut diakui sebagai
  beban/pemasukan). Filter periode + outlet.
- **Penting**: HPP bergantung pada struktur `product_data.cost_price`. Jika struktur `product_data` diubah,
  pastikan fitur yang memakainya ikut disesuaikan (Laporan Pendapatan, Laporan Laba Rugi, dan penurunan stok saat
  penjualan).

### Aturan Penting

- Stok gudang = merchant dengan type=Warehouse (bukan tabel terpisah)
- Distribusi bisa dari gudang→merchant atau merchant→merchant (default gudang→merchant, oleh Admin)
- Merchant hanya bisa lihat/milik data miliknya sendiri
- **PO**: tidak boleh hapus; edit hanya saat status `Approved`.
- **Distribusi**: tidak boleh hapus & edit (di-disable total) karena stok terpengaruh sejak create.
- **Penerimaan Barang (GR)**: boleh hapus & edit hanya saat status `Draft`.
- Aset untuk item `Tool` saat GR dibuat otomatis per unit (jumlah pecahan dibulatkan ke bawah)
- Transaksi cash untuk cashdrawer memakai `total_amount` (bukan `amount_received`), QRIS tidak dihitung
- **Promo: 1 produk = 1 promo aktif max** — form blocking & create/edit validation memastikan tidak ada produk yang
  terpakai di 2+ promo aktif
- **Promo: reward type harga khusus tidak memakai field quantity** — kuantitas mengikuti `min_quantity` pada syarat beli
- **Tampilan Daftar Pesanan & Cetak Struk POS (Sinkronisasi)**: Card "Pesanan Baru" (index), "Daftar Pesanan"
  (checkout), dan Modal "Detail Transaksi" (history) beserta hasil cetak struknya harus selalu **identik/sinkron**.
  Perubahan _logic_ atau _payload_ pada salah satu bagian wajib diterapkan juga pada bagian lainnya.

### Halaman Panduan Penggunaan Sistem (SystemFlow)

- Halaman statis **"Penggunaan Sistem"** di group navigasi **Dashboard** (panel Admin & Merchant) menampilkan
  panduan langkah demi langkah penggunaan sistem untuk **pengguna aplikasi** (bukan programmer).
- File: `app/Filament/Admin/Pages/SystemFlow.php` + `app/Filament/Merchant/Pages/SystemFlow.php`. Konten alur ada di
  **partial bersama** `resources/views/system-flow/{admin,outlet}.blade.php` (komponen Blade
  `resources/views/components/system-flow/*`). Konten **hardcoded di view**, ikon dari
  `secondnetwork/blade-tabler-icons` (`x-tabler-{nama}` / `tabler-{nama}`).
- Halaman **Admin** memakai `content()` + `Tabs` (tab **Admin** / **Outlet**) via
  `Filament\Schemas\Components\View::make()`.
  Halaman **Merchant** memakai view `filament.merchant.system-flow` yang `@include('system-flow.outlet')`.
- Redaksi memakai label menu asli aplikasi (mis. "Master Data → Outlet", "Persediaan → Purchase Order") dan label
  status user-friendly (Disetujui, Dikirim, Selesai, Terverifikasi) — BUKAN nama file/class/variabel.
- **WAJIB disinkronkan** setiap kali alur di file ini berubah, karena ini panduan pengguna.
- Konten Admin saat ini: Outlet & Pengguna, Item & Aset, PO/Penerimaan Barang, Distribusi, Keuangan, Penggajian &
  Jadwal Shift, Stock Opname. **Kasir/POS, Shift Kas, dan Laporan TIDAK ditampilkan di halaman Admin** karena
  merupakan alur outlet (merchant) dan bukan alur Admin; Laporan bukan alur operasional.
- Konten Outlet saat ini: Produk/Kategori/Promo, Verifikasi Distribusi, Stock Opname, Kasir (POS), Buka/Tutup Shift,
  Keuangan, Aset.
- Catatan penting yang tercantum di halaman: item Non Bahan Baku menjadi aset saat barang diterima
  (GR diverifikasi / Distribusi selesai), bukan saat create item; aset manual tanpa keterikatan item tidak
  memengaruhi stok dan tidak bisa dipakai di PO/Distribusi/Penerimaan Barang.

### Wajib Perbarui Dokumentasi Ini

- **Setiap ada fitur baru, perubahan alur, atau rombak (refactor) besar, wajib perbarui
  file ini** (`.ai/guidelines/business-flows.blade.php`) lalu regenerate `AGENTS.md`
  via `php artisan boost:update --no-discover --no-interaction` (revert efek samping
  skills/boost.json setelahnya).
- **Setiap bug yang sudah diperbaiki**: hapus/ubah bagian "Bug Detail" yang sudah solved,
  atau pindah ke catatan desain jika memang menjadi keputusan baru.
- **Setiap fitur baru / beda alur / rombak**: tulis di sini agar agent berikutnya
  mendapat konteks yang sama (jangan hanya simpan di file `zdev/*.md`).
- File `zdev/*.md` boleh dipakai untuk detail teknis sementara, tapi **ringkasan alur
  bisnis tetap harus ada di file ini**.

=== .ai/code-quality rules ===

## Code Quality

- All changes and feature additions must pass `composer lint` (Pint + PHPStan) without errors before committing.
- Any changes to Models (new model, adding relationships, modifying fillable/casts/attributes) must run `composer generate && composer lint` to ensure IDE helper files and static analysis stay in sync.
- Run `composer lint` as the final verification step after completing any task.

=== .ai/commit rules ===

## GIT Commit Rule

### Commit Message Guidelines

Generate commit messages in Conventional Commits format. Follow this exact structure:

```
<type>[optional scope]: <description>

[optional body]
```

Examples:

- `feat(auth): add new login validation`
- `fix(api): resolve user data fetch timeout`
- `docs: update README installation steps`
- `docs: add JSDoc comments to media component`
- `style: format code according to linting rules`
- `refactor: restructure video platform detection logic`

Types must be one of:

- `feat`: A new feature
- `fix`: A bug fix
- `docs`: Documentation changes including README updates, JSDoc comments, and code documentation
- `style`: Changes not affecting code functionality (formatting, whitespace, etc)
- `refactor`: Code structure changes that neither fix bugs nor add features
- `perf`: Code change that improves performance
- `test`: Adding or correcting tests
- `build`: Changes affecting build system or dependencies
- `ci`: Changes to CI configuration such as GitHub Actions or Jenkins
- `chore`: Other changes not modifying src or test files
- `revert`: Reverting a previous commit

Important: Use `docs` for JSDoc additions/changes, not `refactor`.

For the body:

- Use bullet points (`*`) for multiple items
- Explain WHY the change was needed
- Include relevant context or technical details

Ensure the message is professional and clearly communicates the purpose of the commit.

### Before pushing the commit

- Before pushing the commit, ensure that you have run all tests and that they pass successfully.
- Before pushing the commit, make sure to pull the latest changes from the remote repository to avoid any merge conflicts.
- Before pushing the commit, run linter with `./vendor/bin/pint --parallel`.

=== .ai/filament-blueprint rules ===

## Filament Blueprint

You are writing Filament v5 implementation plans. Plans must be specific enough
that an implementing agent can write code without making decisions.

**Start here**: Read
`.ai/planning/overview.md` for plan format,
required sections, and what to clarify with the user before planning.

=== .ai/filament-tables rules ===

# Filament Tables — Heading & Description

Saat membuat atau mengubah Table di Filament, patuhi aturan ini untuk menghindari duplikasi heading:

- Jangan menambahkan `->heading()` dan `->description()` di dalam Table untuk
  table milik **Resource** atau **custom Page**. Karena Heading/subheading sebaiknya
  didefinisikan di `Pages/List*` (`$heading` / `$subheading`), jadi menambahkannya
  di table hanya duplikasi UI.
- Untuk table di dalam **widget** (`TableWidget`): cukup `->heading()` saja,
  tanpa `->description()`.

## Soft Deletes — View vs. System

Beberapa model master data menggunakan `SoftDeletes` agar data tidak hilang
permanen. Namun dari sisi tampilan (view) **tidak perlu** menampilkan:

- `TrashedFilter::make()` (filter untuk menampilkan data yang sudah dihapus)
- `ForceDeleteAction::make()` (tombol "Hapus Permanen")
- `RestoreAction::make()` (tombol "Pulihkan dari Sampah")

Cukup gunakan `DeleteAction::make()` dengan label "Hapus" yang akan melakukan
soft delete. Komponen `DeleteAction`, `ForceDeleteAction`, `RestoreAction`, dan
`TrashedFilter` sudah dikonfigurasi secara global di `ComponentServiceProvider`,
jadi per table hanya perlu **tidak menambahkannya** (`ForceDeleteAction`,
`RestoreAction`, `TrashedFilter`) — `DeleteAction` sudah otomatis tampil dengan
ikon dan label yang sesuai.

=== .ai/main rules ===

# MAIN

Behavioral guidelines to reduce common LLM coding mistakes. Merge with project-specific instructions as needed.

**Tradeoff:** These guidelines bias toward caution over speed. For trivial tasks, use judgment.

## 1. Think Before Coding

**Don't assume. Don't hide confusion. Surface tradeoffs.**

Before implementing:

- State your assumptions explicitly. If uncertain, ask.
- If multiple interpretations exist, present them - don't pick silently.
- If a simpler approach exists, say so. Push back when warranted.
- If something is unclear, stop. Name what's confusing. Ask.

## 2. Simplicity First

**Minimum code that solves the problem. Nothing speculative.**

- No features beyond what was asked.
- No abstractions for single-use code.
- No "flexibility" or "configurability" that wasn't requested.
- No error handling for impossible scenarios.
- If you write 200 lines and it could be 50, rewrite it.

Ask yourself: "Would a senior engineer say this is overcomplicated?" If yes, simplify.

## 3. Surgical Changes

**Touch only what you must. Clean up only your own mess.**

When editing existing code:

- Don't "improve" adjacent code, comments, or formatting.
- Don't refactor things that aren't broken.
- Match existing style, even if you'd do it differently.
- If you notice unrelated dead code, mention it - don't delete it.

When your changes create orphans:

- Remove imports/variables/functions that YOUR changes made unused.
- Don't remove pre-existing dead code unless asked.

The test: Every changed line should trace directly to the user's request.

## 4. Goal-Driven Execution

**Define success criteria. Loop until verified.**

Transform tasks into verifiable goals:

- "Add validation" → "Write tests for invalid inputs, then make them pass"
- "Fix the bug" → "Write a test that reproduces it, then make it pass"
- "Refactor X" → "Ensure tests pass before and after"

For multi-step tasks, state a brief plan:

```
1. [Step] → verify: [check]
2. [Step] → verify: [check]
3. [Step] → verify: [check]
```

Strong success criteria let you loop independently. Weak criteria ("make it work") require constant clarification.

---

**These guidelines are working if:** fewer unnecessary changes in diffs, fewer rewrites due to overcomplication, and clarifying questions come before implementation rather than after mistakes.

=== .ai/model-status-management rules ===

# Model Status Management

When managing model statuses, auditing, and state tracking within this Laravel application, strictly adhere to the following architectural guidelines.

## 1. Package Architecture & Extension

- Always use the `spatie/laravel-model-status` package for tracking model status history.
- Never instantiate or reference the default `Spatie\ModelStatus\Status` model directly. Instead, always use the extended application model `App\Models\Status`.
- Ensure that `config/model-status.php` maps `'status_model'` to `App\Models\Status::class`.
- The config also contains `'model_primary_key_attribute'` (default `'model_id'`). If you publish a custom migration that renames this column, update the config key accordingly.

## 2. Automatic Actor and Timestamp Tracking

- Do not manually pass `user_id` inside the `$extraAttributes` array when calling `$model->setStatus()`.
- Actor tracking must be handled globally and automatically via the `booted` method inside `App\Models\Status`:

```php
protected static function booted()
{
    static::creating(function ($status) {
        if (auth()->check() && !$status->user_id) {
            $status->user_id = auth()->id();
        }
    });
}
```

- Timestamps (`created_at`) serve as the official status change date. Do not create a separate date column for status timing.

## 3. Performance & The Hybrid Strategy

**Strict Rule:** Never use the package's native `currentStatus()` or `otherCurrentStatus()` local scopes for querying or filtering models by status on high-volume tables (e.g., Orders, Invoices, Transactions). They utilize heavy polymorphic subqueries that cause severe performance degradation.

- Always enforce the **Hybrid Strategy**:
    1. Maintain a native string/enum column named `current_status` directly on the parent model's table.
    2. Index the `current_status` column in the database migration.
    3. Alias the trait's `setStatus` method and override it using the exact signature (2 parameters) to keep the local `current_status` column synchronized.

### Parent Model Implementation Example (Corrected Signature)

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\ModelStatus\HasStatuses;

class Order extends Model
{
    // Alias the trait method to avoid naming conflicts and allow overriding
    use HasStatuses {
        setStatus as spatieSetStatus;
    }

    protected $fillable = ['current_status', 'total_amount'];

    /**
     * Override the setStatus method with correct signature (2 parameters).
     */
    public function setStatus(string|UnitEnum $name, ?string $reason = null): Model
    {
        // Call the aliased trait method with exactly 2 parameters
        $status = $this->spatieSetStatus($name, $reason);

        // Synchronize with the local indexed column
        $this->update(['current_status' => $name]);

        return $status;
    }
}
```

## 4. Querying and Reading Data

- **Filtering Data:** When writing Eloquent queries to filter records by their active status, always query the local column:

```php
// GOOD: Executes an indexed, instant query
$completedOrders = Order::where('current_status', 'completed')->get();

// BAD: Triggers slow polymorphic subqueries
$completedOrders = Order::currentStatus('completed')->get();
```

- **Excluding by Status:** Similarly, avoid `otherCurrentStatus()` for filtering. Use a negated local-column query instead:

```php
// GOOD
$nonPendingOrders = Order::where('current_status', '!=', 'pending')->get();

// BAD
$nonPendingOrders = Order::otherCurrentStatus('pending')->get();
```

- **Eager Loading Logs:** When displaying the audit trail or history log in views or API resources, always eager load the custom status relation along with its actor to avoid $N+1$ query problems:

```php
$order = Order::with('statuses.actor')->find($id);

```

## 5. Type Safety with Enums

- Always use backed PHP Enums (typically `string`) to define allowed states instead of hardcoded strings to prevent invalid statuses from entering the database:

```php
namespace App\Enums;

enum OrderStatus: string {
    case DRAFT = 'draft';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
    case REJECTED = 'rejected';
}

```

- `setStatus()` accepts both strings and enums. Backed enums are stored using their value; unit enums are stored using their case name.
- Use `$model->statusEnum()` to retrieve the latest status as its enum case. Returns `null` when there is no status yet, or when the stored name does not map to a case in the configured `statusEnumClass()`.

- **Restrict Statuses at the Model Level:** Override the `statusEnumClass()` method on models using `HasStatuses` to enforce allowed statuses directly via the package instead of relying solely on controller-level validation:

```php
use App\Enums\OrderStatus;

class Order extends Model
{
    use HasStatuses;

    public function statusEnumClass(): ?string
    {
        return OrderStatus::class;
    }
}
```

When `statusEnumClass()` returns an enum class, the package will reject any status name not defined in that enum. This is the preferred method — it fails fast at the model layer regardless of where `setStatus()` is called.

Note: `forceSetStatus()` bypasses both `isValidStatus()` validation and the `statusEnumClass()` restriction. Use it only when you explicitly need to bypass all checks.

- Enforce type validation in controllers or service classes before firing the `setStatus` method.

## 6. Custom Status Validation (`isValidStatus` & `forceSetStatus`)

- Override `isValidStatus()` on your model to add custom business rules before a status is set:

```php
public function isValidStatus(string $name, ?string $reason = null): bool
{
    if ($name === 'shipped' && $this->current_status !== 'paid') {
        return false;
    }

    return true;
}
```

A return value of `false` throws a `Spatie\ModelStatus\Exceptions\InvalidStatus` exception.

- Use `forceSetStatus()` to bypass both `isValidStatus()` and `statusEnumClass()` restrictions entirely:

```php
$model->forceSetStatus('override-status');
```

This is useful for internal/system operations where normal validation should not apply.

## 7. Status History Operations

- **Check current status match:**

```php
$model->hasStatus('pending'); // true/false
```

- **Check if a status has ever been assigned:**

```php
$model->hasEverHadStatus('shipped'); // true if shipped was ever set
$model->hasNeverHadStatus('rejected'); // true if rejected was never set
```

- **Retrieve all distinct status names that have been applied:**

```php
$names = $model->getStatusNames(); // Collection of strings
```

- **Retrieve the latest status among specific names (accepts array or variadic args):**

```php
$latest = $model->latestStatus(['pending', 'initiated']);

// or equivalently
$latest = $model->latestStatus('pending', 'initiated');
```

- **Delete a status (or multiple statuses) from the history:**

```php
$model->deleteStatus('draft');           // single
$model->deleteStatus(['draft', 'initiated']); // multiple
```

## 8. Events

- A `Spatie\ModelStatus\Events\StatusUpdated` event is dispatched whenever a status is updated via `setStatus()` or `forceSetStatus()`.
- The event exposes three public properties:

```php
use Spatie\ModelStatus\Events\StatusUpdated;
use Spatie\ModelStatus\Status;
use Illuminate\Database\Eloquent\Model;

class StatusUpdated
{
    public ?Status $oldStatus;
    public Status $newStatus;
    public Model $model;
}
```

- Register a listener in your service provider to react to status changes:

```php
use App\Listeners\LogStatusChange;
use Spatie\ModelStatus\Events\StatusUpdated;

public function boot(): void
{
    Event::listen(
        StatusUpdated::class,
        LogStatusChange::class,
    );
}
```

=== .ai/model-strict-mode rules ===

# Model Strict Mode & Query Efficiency

`Model::shouldBeStrict()` is enabled in local environments via `AppServiceProvider`. All code must work under these constraints.

---

## Always Start Queries with `Model::query()`

Always use `Model::query()` as the entry point for Eloquent queries, not static facade methods. This ensures proper IDE autocompletion, static analysis compatibility, and consistency across the codebase:

```php
// ❌ DON'T — static method call, inconsistent
$users = User::where('active', true)->get();
$merchants = Merchant::where('type', 'merchant')->pluck('name', 'id');

// ✅ DO — explicit query builder entry point
$users = User::query()->where('active', true)->get();
$merchants = Merchant::query()->where('type', 'merchant')->pluck('name', 'id');
```

`Model::query()` is also required for relationships:

```php
// ✅ DO — use query() on relationships too
$record->items()->sum('amount');
$record->items()->where('component_name', 'Bonus')->count();
```

---

## Always Eager Load Relationships

Lazy loading throws `LazyLoadingViolationException` in strict mode. Every relationship access **must** be eager-loaded first with `->load()` or `->with()`:

```php
// ❌ DON'T — lazy load throws exception
$record->items->sum('amount');
$record->user->name;

// ✅ DO — eager load first
$record->load(['items', 'user']);
$record->items->sum('amount');
$record->user->name;
```

### Only load what you access

Don't eager-load relationships that are never used. Every `load()`/`with()` call must be justified by a concrete access in the code that follows.

---

## Prefer Aggregate Queries Over Collection Loading

When you only need `SUM`, `COUNT`, `AVG`, `MIN`, `MAX` from a relationship — use the query builder, not the collection:

```php
// ❌ DON'T — loads all items into memory just to sum
$record->load('items');
$total = $record->items->sum('amount');

// ✅ DO — runs SELECT SUM(amount) in the database
$total = $record->items()->sum('amount');
```

The query builder approach:

- Runs a single aggregate SQL query (O(1) memory)
- Avoids hydrating hundreds of Eloquent models
- Still works under strict mode (no lazy-loading violation)

### When to load the collection

Only load the full relationship when you actually need the models — e.g., iterating items, accessing nested relations, or calling model methods.

---

## Fillable Attributes

All attributes passed to `create()` or `update()` must be listed in the model's `$fillable` array. Strict mode throws when you write to non-fillable attributes.

```php
// ❌ DON'T — 'publish_at' not in $fillable
Post::create(['title' => '...', 'publish_at' => now()]);

// ✅ DO — add to $fillable, or use forceFill for one-off
```

---

## Summary Checklist

Before submitting code, verify:

- [ ] Queries start with `Model::query()` or `$relation->query()`
- [ ] Every relationship access has a corresponding `load()`/`with()`
- [ ] Aggregate operations use query builder (`items()->sum()`, not `items->sum()`)
- [ ] Eager-loaded relationships are actually used later in the code
- [ ] Mass-assigned attributes are in `$fillable`

=== .ai/pos-bluetooth rules ===

# POS Bluetooth Printer (EPPOS)

## Fitur

Halaman POS kasir (`resources/views/pos/index.blade.php`) mencetak struk langsung via web ke printer thermal EPPOS menggunakan **Web Bluetooth API** (`navigator.bluetooth`) — tanpa aplikasi tambahan.

## File inti

- `resources/js/pos-bluetooth.js` — modul ESC/POS (printer 58mm, `LINE_WIDTH = 32`), diekspos sebagai global `window.PosBluetooth`.
- `resources/views/pos/index.blade.php` — satu instance `posBt` per sesi; flow pairing, auto-reconnect, dan status di header.

## Constraint KUNCI: dilarang refresh halaman

Selama koneksi Bluetooth aktif, **dilarang refresh/reload halaman**. Refresh memutus koneksi GATT dan printer harus pairing ulang.

- `beforeunload` sengaja memanggil `posBt.disconnect()`.
- Jangan tambah fitur yang memicu reload (navigasi penuh, `<meta refresh>`, dst.) saat printer tersambung.
- Prefer state update tanpa reload (Livewire/Alpine), atau disconnect dulu sebelum navigasi.

## Persistence & reconnect

- `deviceId` & nama disimpan di `localStorage` (`pos_printer_device_id`, `pos_printer_device_name`).
- Auto-reconnect memakai `navigator.bluetooth.requestDevice()` + filter nama (bukan `getDevices()`, tidak reliable untuk silent reconnect).
- Jangan hapus handler `gattserverdisconnected` maupun logika single-instance `posBt`.

## Browser support

Web Bluetooth hanya berjalan di Chrome/Chromium (Android + desktop). Tidak didukung Firefox/Safari — UI harus tetap jalan (cetak dinonaktifkan, bukan error page).

=== .ai/pos-ui rules ===

# POS UI/UX — Device Priority

## Device Priority

POS dioptimalkan untuk **tablet 8–11 inch** dengan interaksi utama **layar sentuh (touch)**, bukan keyboard/mouse desktop. POS ini digunakan untuk Booth Es Teh/Makanan/Minuman dengan jumlah produk 20an produk. Sehingga jangan sampai overengineering.

Saat membuat atau mengubah UI/UX halaman POS, **WAJIB menjaga optimasi ini**:

## Prinsip utama

- **Touch target besar**: tombol minimal ~40px tinggi (mis. `py-2.5`/`py-3`), jangan buat elemen yang sulit diketuk jari.
- **Feedback tap**: gunakan `active:scale-95` + transisi agar pengguna tahu tombol tersentuh.
- **Minimalkan ketikan manual**: untuk input numerik gunakan numpad (sudah ada untuk nominal bayar), jangan mengandalkan keyboard fisik.
- **Layout lega**: ruang kerja utama (daftar produk & keranjang) harus tetap terlihat luas pada layar 8–11".
- **Aksi penting mudah dijangkau**: tombol Bayar, Cetak Struk, Hapus, dll. harus besar dan jelas posisinya.
- **Jangan jadikan interaksi keyboard-only** sebagai satu-satunya cara (mis. shortcut yang butuh tombol fisik).

=== .ai/test-enforcement rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== .ai/test-standard rules ===

## Guidelines for Laravel and Filament Feature Testing

Follow these strict guidelines when generating feature tests for Laravel and Filament applications.

### 1. Test Coverage Scope

- **Resource Classes (`.../Resource.php`)**: Test for general availability and basic rendering. Verify that standard pages (Create, Edit, List, View) can be accessed and that Infolists can be displayed. Focus on checking if the data/components can be rendered.
- **Resource Pages (`.../Pages/...`)**: Test comprehensively.
- **Tables/Lists**: Verify that specific columns render, and explicitly test `searchable`, `sortable`, and `grouping` functionalities.
- **Forms**: Verify that specific form fields exist, can be filled, and form submissions work.
- **Business Logic**: Thoroughly test creation, editing, and ensure all validation rules are applied correctly.
- **Actions**: Briefly verify that specific expected actions exist on the page.

- **Relation Managers**: Test comprehensively, treating them similarly to List pages. Verify that `searchable`, `sortable`, and `grouping` functions work based on the manager's specific content.
- **Standalone Actions (`.../Actions/...`)**: Test comprehensively. Focus entirely on ensuring the underlying business logic executes correctly.
- **Exclusions**: Do not write feature tests for database schemas or tables.

### 2. Test Scenario Requirements

Every component tested must explicitly include the following scenarios:

- **Happy Path**: The ideal scenario where inputs are correct and operations succeed.
- **Sad Path**: Scenarios involving invalid inputs, validation failures, or unauthorized access.
- **Edge Cases**: Boundary conditions, extreme values, or unusual but possible data states.

### 3. File and Directory Structure Rules

Test case locations must strictly mirror the original application file structure, placed inside the `tests/Feature/` directory.

**Example Mapping:**

_Source Files:_
`app/Filament/Resource/Users/UserResource.php`
`app/Filament/Admin/Resources/Users/Pages/CreateUser.php`
`app/Filament/Admin/Resources/Users/Pages/EditUser.php`
`app/Filament/Admin/Resources/Users/Pages/ListUsers.php`
`app/Filament/Admin/Resources/Users/Pages/ViewUser.php`
`app/Filament/Admin/Resources/Users/Actions/ChangePasswordAction.php`

_Target Test Files:_
`tests/Feature/Filament/Resource/Users/UserResource.php`
`tests/Feature/Filament/Admin/Resources/Users/Pages/CreateUser.php`
`tests/Feature/Filament/Admin/Resources/Users/Pages/EditUser.php`
`tests/Feature/Filament/Admin/Resources/Users/Pages/ListUsers.php`
`tests/Feature/Filament/Admin/Resources/Users/Pages/ViewUser.php`
`tests/Feature/Filament/Admin/Resources/Users/Actions/ChangePasswordAction.php`

### 4. Running Tests

Test suites in this project can take a very long time because the database is migrated and seeded on every run. Additionally, direct terminal output can be silenced or swallowed by subshell buffering. **Always clear the previous log file, redirect execution output to it, and read the file afterwards.** Follow these strict rules:

- **DO NOT SPAM COMMANDS WHILE TESTS ARE RUNNING. WAIT UNTIL EXECUTION COMPLETES BEFORE INTERACTING WITH THE TERMINAL.**
- **Always remove the old log file and redirect both stdout and stderr:** Ensure a clean slate by deleting previous test logs before execution, then pipe all output:

```bash
rm -f storage/logs/pest-test.log && ./vendor/bin/pest --parallel --compact > storage/logs/pest-test.log 2>&1
```

_(For targeted tests: `rm -f storage/logs/pest-test.log && ./vendor/bin/pest --parallel --compact <test-path> > storage/logs/pest-test.log 2>&1`)_

- **Inspect the log file to evaluate results:** Once the command exits and returns a prompt, read the newly generated log to check status, assertions, and stack traces:

```bash
cat storage/logs/pest-test.log

# or inspect recent output

tail -n 50 storage/logs/pest-test.log

```

- **Never interrupt an ongoing test run:** Once triggered, do not send secondary commands, key strokes (like `^C`), or poll the terminal stream aggressively. Allow the process to return its exit code naturally.
- **Wait for true completion:** A run is only complete when the subshell returns control with an exit code. Immediately verify test results by reading the redirected log file rather than checking standard terminal scrollback.
- **Use focused runs during development:** Run single files or directories using the clean-and-pipe pattern for active changes, and only execute the full suite before finalizing tasks.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:

- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
    - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== filament/filament/core rules ===

## Filament

- Filament is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent, chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Inspect required options before running, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
->options(CompanyType::class)
->required()
->live(),

TextInput::make('company_name')
->required()
->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

<code-snippet name="Reactive field update" lang="php">
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make('title')
->required()
->live(onBlur: true)
->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
)),

TextInput::make('slug')
->required(),

</code-snippet>

Compose layout by nesting `Section` and `Grid`. Children need explicit `->columnSpan()` or `->columnSpanFull()`:

<code-snippet name="Section and Grid layout" lang="php">
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Details')
->schema([
Grid::make(2)->schema([
TextInput::make('first_name')
->columnSpan(1),
TextInput::make('last_name')
->columnSpan(1),
TextInput::make('bio')
->columnSpanFull(),
]),
]),

</code-snippet>

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the field name:

<code-snippet name="Repeater for HasMany" lang="php">
use Filament\Forms\Components\Repeater;

Repeater::make('qualifications')
->relationship()
->schema([
TextInput::make('institution')
->required(),
TextInput::make('qualification')
->required(),
])
->columns(2),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

<code-snippet name="Table filters" lang="php">
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('status')
->options(UserStatus::class),

SelectFilter::make('author')
->relationship('author', 'name'),

Filter::make('verified')
->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

</code-snippet>

Actions are buttons that encapsulate optional modal forms and behavior:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;

Action::make('updateEmail')
->schema([
TextInput::make('email')
->email()
->required(),
])
->action(fn (array $data, User $record) => $record->update($data)),

</code-snippet>

### Testing

Testing setup (requires `pestphp/pest-plugin-livewire` in `composer.json`):

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- For edit pages, pass `['record' => $user->id]`, use `->call('save')` (not `->call('create')`), and do not assert `->assertRedirect()` (edit pages do not redirect after save).

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
->fillForm([
'name' => 'Test',
'email' => 'test@example.com',
])
->call('create')
->assertNotified()
->assertHasNoFormErrors()
->assertRedirect();

assertDatabaseHas(User::class, [
'name' => 'Test',
'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Edit resource test" lang="php">
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
'id' => $user->id,
'name' => 'Updated',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))` for table actions:

<code-snippet name="Calling actions" lang="php">
use Filament\Actions\Testing\TestAction;

livewire(ListUsers::class)
->callAction(TestAction::make('promote')->table($user), [
'role' => 'admin',
])
->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Filament\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Filament\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for BelongsTo fields.** `BelongsToSelect` does not exist in v4.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have union types or changed modifiers that must be preserved:
    - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
    - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
    - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes

=== spatie/laravel-activitylog/core rules ===

# spatie/laravel-activitylog

Activity logging package for Laravel. Logs model events and manual activities to a database table.

## Key Concepts

- **Activity**: An Eloquent model (`Spatie\Activitylog\Models\Activity`) storing log entries with subject, causer, event, attribute_changes, and properties.
- **Subject**: The model being acted upon (polymorphic `subject_type`/`subject_id`).
- **Causer**: The model that caused the action, typically the authenticated user (polymorphic `causer_type`/`causer_id`).
- **LogOptions**: Fluent configuration object returned by `getActivitylogOptions()` on models using the `LogsActivity` trait.
- **ActivityEvent**: Enum with cases `Created`, `Updated`, `Deleted`, `Restored`.
- **`attribute_changes`** column: stores `{"attributes": {...}, "old": {...}}` for tracked model changes.
- **`properties`** column: stores custom user data set via `withProperties()`.

## Traits

### `LogsActivity`

Add to models to automatically log create/update/delete events. Optionally implement `getActivitylogOptions()` to configure which attributes to track (defaults to logging events without attribute changes).

```php
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Article extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
```

### `CausesActivity`

Add to user/causer models. Provides `activitiesAsCauser()` relationship.

### `HasActivity`

Combines `LogsActivity` and `CausesActivity`. Provides `activities()`, `activitiesAsSubject()`, and `activitiesAsCauser()`.

## Manual Logging

```php
activity()
    ->performedOn($article)
    ->causedBy($user)
    ->event(ActivityEvent::Updated)
    ->withProperties(['key' => 'value'])
    ->log('Article was updated');
```

## LogOptions Methods

| Method                                  | Description                                      |
| --------------------------------------- | ------------------------------------------------ |
| `logFillable()`                         | Log all fillable attributes                      |
| `logAll()`                              | Log all attributes                               |
| `logOnly(array)`                        | Log specific attributes                          |
| `logExcept(array)`                      | Exclude attributes                               |
| `logOnlyDirty()`                        | Only log changed attributes                      |
| `dontLogEmptyChanges()`                 | Skip logging when no tracked attributes changed  |
| `dontLogIfAttributesChangedOnly(array)` | Ignore updates that only change these attributes |
| `useLogName(string)`                    | Set custom log name                              |
| `setDescriptionForEvent(Closure)`       | Custom description per event                     |
| `useAttributeRawValues(array)`          | Store raw (uncast) values                        |

## Querying Activities

```php
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Enums\ActivityEvent;

Activity::forEvent(ActivityEvent::Created)->get();
Activity::causedBy($user)->get();
Activity::forSubject($article)->get();
Activity::inLog('orders')->get();
```

## Setting the causer

Override the causer for a block of code:

```php
use Spatie\Activitylog\Facades\Activity;

Activity::defaultCauser($admin, function () {
    // all activities here are caused by $admin
});

// or set globally for the rest of the request
Activity::defaultCauser($admin);
```

## Disabling Logging

```php
activity()->withoutLogging(function () {
    // no activities logged here
});
```

## Accessing Changes and Properties

```php
$activity = Activity::latest()->first();

// Tracked model changes (set automatically by LogsActivity)
$activity->attribute_changes; // Collection: {"attributes": {...}, "old": {...}}

// Custom user data (set via withProperties)
$activity->properties; // Collection
$activity->getProperty('key'); // single value
```

## Custom Activity Model

Set `activity_model` in `config/activitylog.php` to a class that extends `Model` and implements `Spatie\Activitylog\Contracts\Activity`. Use a custom model for custom table names or database connections.

## Customizing Actions

The package uses action classes (`LogActivityAction`, `CleanActivityLogAction`) that can be extended and swapped via config:

```php
// config/activitylog.php
'actions' => [
    'log_activity' => \App\Actions\CustomLogActivityAction::class,
    'clean_log' => \App\Actions\CustomCleanAction::class,
],
```

Custom action classes must extend the originals. Override protected methods (`save()`, `beforeActivityLogged()`, `resolveDescription()`, etc.) to customize behavior.

## Configuration

Key config options in `config/activitylog.php`:

- `enabled`: Master on/off switch (env: `ACTIVITYLOG_ENABLED`)
- `clean_after_days`: Days to keep records for `activitylog:clean` command
- `default_log_name`: Default log name (string)
- `default_auth_driver`: Auth driver for causer resolution
- `include_soft_deleted_subjects`: Include soft-deleted subjects
- `activity_model`: Custom Activity model class
- `default_except_attributes`: Globally excluded attributes
- `actions.log_activity`: Action class for logging activities
- `actions.clean_log`: Action class for cleaning old activities

</laravel-boost-guidelines>
