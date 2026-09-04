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
