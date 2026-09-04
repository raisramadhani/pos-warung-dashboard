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

| Tipe Item | Stok Masuk | Stok Keluar | Catatan |
|---|---|---|---|
| `RawMaterial` (Bahan Baku) | GR `Verified` → `GoodsReceiptIn`; Distribusi `Finished` → `DistributionIn`; Retur masuk →
`ReturnIn` | Distribusi `Sent` → `DistributionOut`; Penjualan (konsumsi bahan) → `TransactionOut`; Retur keluar →
`ReturnOut` | `Adjustment` via stock opname / koreksi |
| `Tool` (Alat/Aset) | GR `Verified` → `GoodsReceiptIn`; Distribusi `Finished` → `DistributionIn`; Retur masuk →
`ReturnIn` | Distribusi `Sent` → `DistributionOut`; Retur keluar → `ReturnOut` | Selain stok kuantitas, item `Tool`
juga dibuatkan aset per unit untuk monitoring depresiasi |

### Bug Detail (kondisi sekarang → problem → harusnya)

1. **Status update (solved)**
- Bug distribusi alat double count dan stok alat negatif yang berbasis asumsi "Tool tidak ikut stok" dinyatakan selesai
karena policy aktif kini menetapkan item `Tool` ikut memengaruhi stok pada GR dan Distribusi, sambil tetap membuat aset.

4. **Bug subtotal saat edit Penerimaan Barang (GR)**
- *Kondisi sekarang*: form GR menghitung `subtotal = quantity_received × unit_price` via `afterStateUpdated` dan
`mutateRelationshipDataBeforeSaveUsing`. Saat edit item, perhitungan ulang tidak selalu konsisten.
- *Problem*: subtotal bisa salah saat mengedit GR berstatus Draft.
- *Harusnya*: hitung ulang subtotal secara konsisten dari `quantity_received × unit_price` pada setiap save, termasuk
saat edit.

5. **Kolom "Item Terverifikasi" di tabel Distribusi / Penerimaan Barang / Stock Opname (deferred bug)**
- *Kondisi sekarang*: kolom `Item Terverifikasi` (nama field sementara `items_count2` / `items_count2bug`) masih
memakai `->counts('items')` — sama persis dengan kolom `Item Tersedia`, belum menghitung item yang benar-benar
terverifikasi/diterima/dihitung.
- *Problem*: angka "Item Terverifikasi" tidak akurat (menampilkan total item, bukan yang sudah diverifikasi).
- *Harusnya*: **(deferred)** hitung jumlah item yang sudah diverifikasi (mis. `quantity_received > 0` di GR/Distribusi,
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
Perubahan *logic* atau *payload* pada salah satu bagian wajib diterapkan juga pada bagian lainnya.


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
