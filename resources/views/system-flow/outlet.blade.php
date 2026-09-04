<div class="space-y-6">
    {{-- 1. Produk, Kategori & Promo --}}
    <x-filament::section icon="tabler-shopping-bag" icon-color="primary"
        heading="1. Siapkan Produk, Kategori & Promo"
        description="Menyiapkan menu yang akan dijual di outlet, lengkap dengan resep dan promo.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-category" title="Buat Kategori">
                <x-slot name="description">Buka menu <strong>Master Data → Kategori → Tambah Kategori</strong> untuk
                    mengelompokkan produk. Contoh kategori yang sudah tersedia: <strong>Racik Series</strong> (es teh),
                    <strong>Fruit Series</strong> (es buah), <strong>Macchiatto Series</strong>, dan <strong>Mie
                        Desa</strong>.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-shopping-bag" title="Buat Produk">
                <x-slot name="description">Buka menu <strong>Master Data → Produk → Tambah Produk</strong>. Isi nama,
                    harga jual, harga modal, kategori, dan status aktif. Contoh produk Racik Series: <strong>Es Teh
                        Jumbo</strong> Rp 4.000, <strong>Es Teh Reguler</strong> Rp 3.000, <strong>Es Teh Kampul</strong>
                    Rp 5.000, <strong>Teh Hangat</strong> Rp 3.000, dan <strong>Es Jeruk Peras</strong> Rp 6.000. Contoh
                    lainnya: <strong>Es Cappucino Macchiatto</strong> Rp 6.000, <strong>Es Thai Tea</strong> Rp 6.000,
                    dan <strong>Mie Original Desa</strong> Rp 8.000.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-flask" title="Atur Resep / Bahan">
                <x-slot name="description">Pada detail produk, tambahkan <strong>bahan baku</strong> beserta jumlahnya
                    per porsi. Contoh resep Es Teh Jumbo: 1 Cup ETD 22oz, 1 Lid Sealer, 1 Sedotan, 2 Teh Racik, 2 Gula,
                    dan 1 Es Batu Kristal. Stok bahan baku otomatis berkurang saat produk terjual.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-discount" title="Buat Promo"
                note="Satu produk hanya boleh memiliki satu promo aktif. Produk yang sudah dipakai promo aktif lain tidak bisa dipilih.">
                <x-slot name="description">Buka menu <strong>Master Data → Promo → Tambah Promo</strong>. Pilih tipe
                    promo: Beli X Dapat Y Gratis, Harga Flash Sale, Beli Sekian Harga Pas, Diskon Persen, atau Diskon
                    Nominal. Tentukan syarat beli, hadiah, dan jadwal aktif.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-sparkles" title="Contoh Promo">
                <x-slot name="description">Berikut contoh promo yang bisa dibuat:
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li><strong>Flash Sale Es Teh Jumbo Rp 2.500</strong> — berlaku pukul 07.00–09.00 dan pukul
                            22.00–00.00 setiap hari.</li>
                        <li><strong>Jumat Berkah</strong> — beli 3 Es Teh Jumbo cukup bayar Rp 10.000 (setiap hari
                            Jumat).</li>
                        <li><strong>Buy 1 Get 1 Es Teh Original</strong> — beli 1 Es Teh Original gratis 1 Es Teh
                            Original.</li>
                    </ul>
                </x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>

    {{-- 2. Verifikasi Distribusi --}}
    <x-filament::section icon="tabler-truck" icon-color="primary" heading="2. Verifikasi Distribusi Barang"
        description="Menerima barang kiriman dari gudang pusat.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-truck" title="Cek Distribusi Masuk"
                note="Outlet hanya menerima barang dari gudang pusat (SPV) melalui distribusi. Outlet tidak membuat Purchase Order maupun Penerimaan Barang sendiri.">
                <x-slot name="description">Buka menu <strong>Persediaan → Distribusi Barang</strong>. Cari distribusi
                    dengan status <strong>Dikirim</strong> yang ditujukan ke outlet Anda.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-checklist" title="Verifikasi Item"
                note="Stok belum bertambah selama proses ini. Jumlah yang diterima dicatat satu per satu.">
                <x-slot name="description">Buka detail distribusi, lalu klik <strong>Verifikasi</strong> pada setiap
                    item dan isi <strong>Jumlah Diterima</strong>. Status berubah menjadi <strong>Sedang
                        Diterima</strong>.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-circle-check" title="Selesaikan Distribusi">
                <x-slot name="description">Setelah semua item terverifikasi, klik <strong>Selesaikan</strong>. Status
                    menjadi <strong>Selesai</strong> dan <strong>stok outlet bertambah</strong> sesuai jumlah yang
                    diterima.</x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>

    {{-- 3. Stock Opname --}}
    <x-filament::section icon="tabler-checklist" icon-color="primary" heading="3. Stock Opname"
        description="Menghitung ulang stok fisik untuk menyamakan dengan catatan sistem.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-checklist" title="Buat Stock Opname">
                <x-slot name="description">Buka menu <strong>Persediaan → Stock Opname → Tambah</strong>. Pilih item
                    yang akan dihitung, status <strong>Draft</strong>.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-search" title="Mulai Hitung">
                <x-slot name="description">Klik <strong>Mulai Hitung</strong>. Status menjadi <strong>Menghitung</strong>,
                    stok sistem di-freeze (snapshot), lalu isi stok fisik untuk setiap item.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-clipboard" title="Review Hasil">
                <x-slot name="description">Klik <strong>Review Hasil</strong>. Status menjadi <strong>Review</strong> dan
                    Anda dapat melihat selisih (surplus/kekurangan) antara stok sistem dan stok fisik.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-circle-check" title="Selesaikan"
                note="Saat ada stock opname aktif, transaksi penjualan dapat dikunci agar angka stok tetap konsisten selama penghitungan.">
                <x-slot name="description">Klik <strong>Selesaikan</strong> untuk menyesuaikan stok sesuai hasil opname.
                    Tindakan ini tidak dapat dibatalkan.</x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>

    {{-- 4. Kasir (POS) --}}
    <x-filament::section icon="tabler-shopping-cart" icon-color="primary" heading="4. Kasir (POS)"
        description="Melayani penjualan langsung di outlet.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-clock" title="Sebelum Mulai Penjualan"
                note="Koneksi Bluetooth akan tetap aktif selama halaman Kasir dibuka dan tidak berpindah ke halaman lain. Selama itu, tidak perlu menyambungkan ulang printer.">
                <x-slot name="description">Sebelum melayani pelanggan, disarankan menyiapkan dua hal berikut:
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        <li><strong>Buka Shift</strong> — buka menu <strong>Transaksi → Buka/Tutup Shift</strong> dan isi
                            <strong>Saldo Awal</strong> (uang di laci). Transaksi penjualan baru terhitung ke shift yang
                            sedang berjalan. Penjelasan lengkap ada di poin 5.</li>
                        <li><strong>Koneksikan Printer</strong> — jika akan mencetak struk, klik ikon <strong>Bluetooth</strong>
                            pada halaman Kasir dan pilih printer thermal. Koneksi cukup dilakukan sekali.</li>
                    </ul>
                </x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-shopping-cart" title="Pilih Produk">
                <x-slot name="description">Buka menu <strong>Kasir</strong> dari navigasi. Pilih produk dari daftar untuk
                    menambahkan ke keranjang. Harga promo aktif diterapkan otomatis.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-wallet" title="Pembayaran">
                <x-slot name="description">Pilih metode pembayaran <strong>Cash</strong> atau <strong>QRIS</strong>.
                    Untuk tunai, masukkan nominal uang diterima menggunakan numpad; sistem menghitung kembalian
                    otomatis.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-receipt-2" title="Cetak Struk (Opsional)">
                <x-slot name="description">Setelah transaksi selesai, struk bisa dicetak ke printer thermal melalui
                    sambungan Bluetooth yang sudah dikoneksikan.</x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>

    {{-- 5. Shift Kas --}}
    <x-filament::section icon="tabler-clock" icon-color="primary" heading="5. Buka / Tutup Shift"
        description="Mengelola buka-tutup laci kas di setiap shift.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-clock" title="Buka Shift">
                <x-slot name="description">Buka menu <strong>Transaksi → Buka/Tutup Shift</strong> lalu buka shift baru.
                    Isi <strong>Saldo Awal</strong> (uang di laci) dan catatan pembukaan (opsional).</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-cash-register" title="Berjualan Selama Shift">
                <x-slot name="description">Lakukan transaksi penjualan seperti biasa. Semua transaksi tunai pada shift ini
                    otomatis terhitung.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-calculator" title="Tutup Shift"
                note="Transaksi QRIS tidak dihitung ke kas tunai karena uangnya tidak masuk laci fisik. Transaksi tunai memakai total transaksi, bukan nominal uang diterima.">
                <x-slot name="description">Saat shift berakhir, sistem menghitung <strong>Saldo yang Diharapkan</strong>
                    (saldo awal + pemasukan + pengeluaran kas + transaksi tunai). Bandingkan dengan <strong>Uang
                        Fisik</strong> dan isi keterangan.</x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>

    {{-- 6. Keuangan --}}
    <x-filament::section icon="tabler-coins" icon-color="primary" heading="6. Keuangan"
        description="Mencatat pemasukan dan pengeluaran di luar penjualan.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-coins" title="Catat Pemasukan / Pengeluaran">
                <x-slot name="description">Buka menu <strong>Transaksi → Keuangan → Tambah</strong>. Pilih jenis
                    <strong>Pemasukan</strong> atau <strong>Pengeluaran</strong>, isi nominal dan keterangan.</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-wallet" title="Pengaruh ke Laci Kas">
                <x-slot name="description">Jika catatan ini memengaruhi laci kas, nominalnya ikut dihitung saat penutupan
                    shift.</x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>

    {{-- 7. Aset --}}
    <x-filament::section icon="tabler-asset" icon-color="primary" heading="7. Aset"
        description="Memantau aset (alat) yang dimiliki outlet.">
        <div class="space-y-6">
            <x-system-flow.step icon="tabler-building-warehouse" title="Aset dari Distribusi">
                <x-slot name="description">Saat distribusi dari gudang berstatus <strong>Selesai</strong>, item Non Bahan
                    Baku yang diterima otomatis tercatat sebagai <strong>Aset</strong> milik outlet (1 unit per barang
                    yang diterima).</x-slot>
            </x-system-flow.step>
            <x-system-flow.step icon="tabler-chart-line" title="Penyusutan">
                <x-slot name="description">Aset bisa dihitung penyusutannya (metode Garis Lurus, Saldo Menurun, atau Tanpa
                    Penyusutan). Daftar dan jadwal penyusutan dapat dilihat di menu <strong>Persediaan → Aset</strong>.</x-slot>
            </x-system-flow.step>
        </div>
    </x-filament::section>
</div>
