<div class="space-y-6">
    {{-- 1. Kelola Outlet & Pengguna --}}
    <x-filament::section icon="tabler-building-store" icon-color="primary" heading="1. Kelola Outlet & Pengguna"
        description="Menyiapkan outlet (cabang usaha) beserta akun yang bisa masuk ke panel outlet.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-building-store" title="Buat Outlet">
                <x-slot name="description">Buka menu <strong>Master Data → Outlet → Tambah Outlet</strong>. Isi nama
                    outlet, alamat, tipe kepemilikan, dan status (Aktif atau Nonaktif), lalu simpan.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-user-plus" title="Buat Akun Pengguna Outlet">
                <x-slot name="description">Pada form Tambah Outlet tersedia bagian <strong>Akun Pengguna</strong>.
                    Isi nama, username, dan password. Akun dibuat otomatis dan langsung terhubung ke outlet
                    tersebut.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-user-check" title="Tambah Pengguna Lain (Opsional)"
                note="Seorang pengguna hanya bisa masuk ke panel outlet yang sudah dihubungkan. Outlet dapat menampung lebih dari satu pengguna (misalnya kasir dan pengelola).">
                <x-slot name="description">Buka menu <strong>Master Data → Pengguna → Tambah Pengguna</strong> dan
                    pilih role. Lalu buka <strong>Detail Pengguna → tab Outlet → Attach</strong> untuk
                    menghubungkan pengguna ke outlet.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-user-star" title="Buka Menu Outlet (Panel Outlet)"
                note="Menu ini hanya tersedia untuk Admin (Super Admin). Akses yang dimiliki Admin sama persis dengan yang dimiliki pengguna outlet — seluruh menu di panel Outlet terbuka untuk Admin.">
                <x-slot name="description">Di halaman Dashboard panel Admin, klik menu <strong>Panel Outlet</strong>
                    pada grup <strong>Dashboard</strong>. Admin akan dibawa ke panel Merchant (Outlet) dan dapat
                    menjalankan seluruh aktivitas outlet — dari menyiapkan produk & promo, menerima distribusi,
                    menjalankan kasir (POS), membuka/menutup shift, hingga mencatat keuangan dan mengelola
                    aset.</x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>

    {{-- 3. Siapkan Item & Aset --}}
    <x-filament::section icon="tabler-box" icon-color="primary" heading="3. Siapkan Item & Aset"
        description="Item adalah master data barang: bahan baku untuk membuat produk, atau alat (Non Bahan Baku) untuk mendukung produksi.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-box" title="Buat Item">
                <x-slot name="description">Buka menu <strong>Master Data → Item / Barang → Tambah Item</strong>. Isi
                    nama item, pilih tipe, satuan, dan status.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-paper-bag" title="Tipe Bahan Baku">
                <x-slot name="description">Item yang dipakai untuk membuat produk. Contoh: gula, teh, susu, gelas
                    plastik. Item bahan baku bisa dijadikan stok awal dan dipakai dalam resep produk.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-tool" title="Tipe Non Bahan Baku">
                <x-slot name="description">Item berupa alat/aset pendukung produksi. Contoh: mesin, blender, kompor,
                    timbangan. Item ini nantinya menjadi aset saat barang diterima.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-scale" title="Pilih Satuan Terkecil">
                <x-slot name="description">Wajib memilih satuan terkecil (Gram, Ml, Pcs, dan lain-lain), bukan
                    satuan besar. Gunanya agar komposisi bahan baku per produk bisa diatur dengan presisi.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-flask" title="Contoh Penggunaan Satuan">
                <x-slot name="description">Daftarkan item <strong>Gula</strong> dengan satuan <strong>Gram</strong>.
                    Di menu <strong>Data Outlet → Produk → Teh Original</strong>, atur komposisi <strong>5 gram
                        gula</strong> per porsi. Saat Teh Original terjual, stok gula otomatis berkurang 5
                    gram.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-wallet" title="Stok Awal (Opsional)">
                <x-slot name="description">Isi stok awal hanya untuk Bahan Baku dan hanya saat pertama membuat item.
                    Jangan isi jika stok akan masuk lewat PO, Penerimaan Barang, atau Distribusi agar tidak terjadi
                    stok ganda.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-building-warehouse" title="Non Bahan Baku & Aset">
                <x-slot name="description">Item Non Bahan Baku otomatis menjadi aset saat barang diterima: melalui
                    Penerimaan Barang (status Terverifikasi) atau Distribusi (status Selesai). Aset dibuat 1 unit
                    per barang yang diterima.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-asset" title="Daftarkan Aset Manual (Opsional)"
                note="Aset tanpa keterikatan item tetap tercatat dan bisa dihitung penyusutannya, tetapi tidak masuk stok dan tidak bisa dipakai di PO, Distribusi, maupun Penerimaan Barang. Untuk stok dan pergerakan barang, gunakan item lalu biarkan aset terbentuk otomatis saat barang diterima.">
                <x-slot name="description">Aset juga bisa didaftarkan langsung di menu <strong>Persediaan → Aset →
                        Tambah Aset</strong>. Isi nama aset, tanggal akuisisi, harga, penyusutan, dan pilihan
                    <strong>Terikat Item</strong>.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-chart-line" title="Penyusutan">
                <x-slot name="description">Metode penyusutan diatur di form Aset: Garis Lurus, Saldo Menurun, atau
                    Tanpa Penyusutan. Jadwal penyusutan bisa dilihat di halaman <strong>Depreciation
                        Schedule</strong>.</x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>

    {{-- 4. Beli & Terima Barang (PO → Penerimaan Barang) --}}
    <x-filament::section icon="tabler-file-invoice" icon-color="primary"
        heading="4. Beli & Terima Barang (Purchase Order → Penerimaan Barang)"
        description="Alur pembelian barang dari supplier sampai barang masuk ke stok gudang.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-database" title="Buat Supplier (Opsional)">
                <x-slot name="description">Buka menu <strong>Master Data → Supplier → Tambah Supplier</strong> untuk
                    mendaftarkan pemasok barang.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-file-invoice" title="Buat Purchase Order (PO)" note="">
                <x-slot name="description">Buka menu <strong>Persediaan → Purchase Order → Tambah PO</strong>. Pilih
                    supplier, tambahkan item beserta jumlah dan harga. Saat disimpan, status langsung
                    <strong>Disetujui</strong>.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-package" title="Buat Penerimaan Barang"
                note="Satu PO hanya boleh memiliki satu Penerimaan Barang aktif. Jika masih ada yang berstatus Draft, tidak bisa membuat penerimaan baru.">
                <x-slot name="description">Pada PO berstatus Disetujui, klik <strong>Terima Barang</strong> untuk
                    membuat Penerimaan Barang berstatus <strong>Draft</strong>. PO berubah menjadi <strong>Sedang
                        Diterima</strong>. Penerimaan dapat dilakukan sebagian (parsial).</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-checklist" title="Verifikasi Item">
                <x-slot name="description">Buka detail Penerimaan Barang, lalu klik <strong>Verifikasi</strong> pada
                    setiap item. Isi <strong>Jumlah Diterima</strong> dan <strong>Harga Satuan</strong>.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-circle-check" title="Verifikasi Penerimaan"
                note="Item Non Bahan Baku yang diterima di sini otomatis menjadi aset (1 unit per barang yang diterima).">
                <x-slot name="description">Setelah semua item terverifikasi, klik <strong>Verifikasi
                        Penerimaan</strong>. Status berubah menjadi <strong>Terverifikasi</strong> dan stok gudang
                    bertambah.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-clock" title="Status PO yang Perlu Diketahui"
                note="PO tidak bisa dihapus dan hanya bisa diedit saat berstatus Disetujui. Penerimaan Barang hanya bisa diedit/dihapus saat Draft; setelah Terverifikasi stok sudah masuk dan tidak bisa diubah lagi.">
                <x-slot name="description">Status PO: <strong>Disetujui</strong> (siap dibuatkan penerimaan),
                    <strong>Sedang Diterima</strong> (penerimaan berjalan), <strong>Selesai</strong> (semua barang
                    sudah diterima, otomatis atau via <strong>Tutup PO</strong>), dan
                    <strong>Dibatalkan</strong>.</x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>

    {{-- 5. Distribusi Barang ke Outlet --}}
    <x-filament::section icon="tabler-route" icon-color="primary" heading="5. Distribusi Barang ke Outlet"
        description="Mengirim barang dari gudang pusat atau antar outlet.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-route" title="Buat Distribusi">
                <x-slot name="description">Buka menu <strong>Persediaan → Distribusi Barang → Tambah
                        Distribusi</strong>. Pilih sumber (gudang atau outlet), tujuan, tambahkan item beserta
                    jumlah. Saat disimpan, status <strong>Dikirim</strong> dan stok sumber langsung
                    berkurang.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-truck" title="Verifikasi oleh Outlet Tujuan">
                <x-slot name="description">Outlet tujuan memverifikasi item satu per satu dengan mengisi jumlah yang
                    diterima. Status berubah menjadi <strong>Sedang Diterima</strong>.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-circle-check" title="Tutup Distribusi"
                note="Distribusi tidak bisa diedit maupun dihapus. Stok tujuan hanya bertambah saat status Selesai. Sumber bisa gudang ke outlet atau antar outlet.">
                <x-slot name="description">Setelah semua item diverifikasi, klik <strong>Tutup Distribusi</strong>.
                    Status menjadi <strong>Selesai</strong> dan stok tujuan bertambah.</x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>

    {{-- 6. Keuangan --}}
    <x-filament::section icon="tabler-coins" icon-color="primary" heading="6. Keuangan"
        description="Mencatat pemasukan dan pengeluaran di luar penjualan.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-coins" title="Catat Pemasukan / Pengeluaran">
                <x-slot name="description">Buka menu <strong>Transaksi → Keuangan → Tambah</strong>. Pilih jenis
                    <strong>Pemasukan</strong> atau <strong>Pengeluaran</strong>, isi nominal dan
                    keterangan.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-wallet" title="Pengaruh ke Laci Kas">
                <x-slot name="description">Jika catatan ini memengaruhi laci kas, nominalnya ikut dihitung saat
                    penutupan shift.</x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>

    {{-- 7. Penggajian & Jadwal Shift Karyawan --}}
    <x-filament::section icon="tabler-id-badge" icon-color="primary" heading="7. Penggajian & Jadwal Shift Karyawan"
        description="Mengelola jadwal, kehadiran, dan gaji karyawan.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-calendar" title="Atur Jadwal Shift">
                <x-slot name="description">Buka menu <strong>Jadwal Shift → Kelola Jadwal</strong> untuk mengatur
                    jadwal karyawan di kalender.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-clipboard" title="Catat Kehadiran">
                <x-slot name="description">Buka menu <strong>Penggajian → Kehadiran</strong> untuk mencatat
                    kehadiran karyawan.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-wallet" title="Buat Penggajian">
                <x-slot name="description">Buka menu <strong>Penggajian → Penggajian</strong> untuk membuat slip
                    gaji. Slip berstatus <strong>Dibayar</strong> akan dihitung sebagai beban gaji pada Laporan Laba
                    Rugi.</x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>

    {{-- 8. Stock Opname --}}
    <x-filament::section icon="tabler-checklist" icon-color="primary" heading="8. Stock Opname"
        description="Menghitung ulang stok fisik untuk menyamakan dengan catatan sistem.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-checklist" title="Buat Stock Opname">
                <x-slot name="description">Buka menu <strong>Data Outlet → Stock Opname → Tambah</strong>. Pilih
                    item yang akan dihitung, status <strong>Draft</strong>.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-search" title="Mulai Hitung">
                <x-slot name="description">Klik <strong>Mulai Hitung</strong>. Status menjadi
                    <strong>Menghitung</strong>, stok sistem di-freeze (snapshot), lalu isi stok fisik untuk setiap
                    item.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-clipboard" title="Review Hasil">
                <x-slot name="description">Klik <strong>Review Hasil</strong>. Status menjadi
                    <strong>Review</strong> dan Anda dapat melihat selisih (surplus/kekurangan) antara stok sistem
                    dan stok fisik.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-circle-check" title="Selesaikan"
                note="Saat ada stock opname aktif, transaksi penjualan dapat dikunci agar angka stok tetap konsisten selama penghitungan.">
                <x-slot name="description">Klik <strong>Selesaikan</strong> untuk menyesuaikan stok sesuai hasil
                    opname. Tindakan ini tidak dapat dibatalkan.</x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>
</div>
