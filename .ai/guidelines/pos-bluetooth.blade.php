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
