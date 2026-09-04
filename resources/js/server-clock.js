/**
 * Server Clock — Sinkronisasi jam POS dengan waktu server (drift-free).
 *
 * Masalah:
 *   Jam device (new Date()) tidak akurat/rawan drift, terutama di tablet POS
 *   yang jarang disinkronkan ke NTP. Jam server dianggap sumber kebenaran.
 *
 * Pendekatan:
 *   1. syncServerTime() → fetch /api/server-time, ukur RTT (round-trip time).
 *   2. Latency ≈ setengah RTT (asumsi latensi simetris request↔response).
 *   3. actualServerTime = serverTimestamp + latency  → estimasi waktu server
 *      saat response diterima (endTime).
 *   4. timeOffset = actualServerTime - endTime → offset tetap (relatif stabil
 *      selama clock device tidak meloncat), dipakai untuk semua tick berikutnya:
 *      renderClock(): nowDevice + timeOffset.
 *
 * Drift prevention:
 *   - setInterval(renderClock, 1000) hanya merender, tidak fetch ulang.
 *   - Resync otomatis tiap RESYNC_INTERVAL_MS (5 menit) mengoreksi offset.
 *   - Page Visibility API: saat tab kembali aktif dari background/throttle,
 *     langsung resync (karena browser bisa menghentikan timer saat background).
 */

// Endpoint server time (tanpa cache di server).
const SERVER_TIME_URL = '/api/server-time';

// Interval resync berkala: 5 menit.
const RESYNC_INTERVAL_MS = 5 * 60 * 1000;

// Interval render jam: 1 detik.
const TICK_INTERVAL_MS = 1000;

// Offset (ms) antara jam server dan jam device; null = belum pernah sync.
let serverTimeOffset = null;

// Penanda sedang proses sync (cegah request tumpang tindih).
let syncing = false;

// Handle setInterval resync berkala (untuk cleanup saat halaman ditutup).
let resyncTimer = null;

// Elemen #server-clock di-cache saat init — renderClock() tidak memanggil
// document.getElementById setiap detik.
let clockEl = null;

/**
 * Fetch timestamp server dan hitung offset dengan kompensasi latensi.
 *
 * Latency    = floor((endTime - startTime) / 2)   → estimasi waktu tempuh satu arah
 * actualTime = serverTimestamp + latency           → waktu server saat response diterima
 * timeOffset = actualTime - endTime                → offset konstan utk tick berikutnya
 */
async function syncServerTime() {
    // Hindari request paralel (mis. visibilitychange saat resync berkala berjalan).
    if (syncing) return;
    syncing = true;

    try {
        const startTime = Date.now();

        const res = await fetch(SERVER_TIME_URL, { cache: 'no-store' });
        if (!res.ok) throw new Error('Server time HTTP ' + res.status);

        const data = await res.json();
        const endTime = Date.now();

        // Guard: pastikan respons berisi timestamp valid.
        if (typeof data.timestamp !== 'number') {
            throw new Error('Server time tidak valid');
        }

        const serverTimestamp = data.timestamp;
        const latency = Math.floor((endTime - startTime) / 2);
        const actualServerTime = serverTimestamp + latency;
        const timeOffset = actualServerTime - endTime;

        serverTimeOffset = timeOffset;
    } catch (e) {
        // Jangan sampai gagal sync merusak UI: biarkan offset terakhir bertahan,
        // tick berikutnya tetap berjalan; resync berkala akan mencoba lagi.
        console.warn('[server-clock] Gagal sinkronisasi:', e);
    } finally {
        syncing = false;
    }
}

/**
 * Waktu server saat ini = waktu device + offset hasil sync terakhir.
 */
function getServerNow() {
    return new Date(Date.now() + (serverTimeOffset || 0));
}

/**
 * Render jam (HH:MM:SS WIB) ke #server-clock (elemen sudah di-cache di clockEl).
 * Tidak melakukan fetch — murni menghitung dari offset yang sudah disinkronkan.
 *
 * Format manual (bukan toLocaleTimeString('id-ID')) karena locale id-ID memakai
 * titik sebagai pemisah waktu (14.07.49), padahal struk/UI POS menginginkan
 * titik dua (14:07:49).
 *
 * Gunakan textContent (bukan innerHTML) — aman dari injeksi HTML/XSS.
 */
function renderClock() {
    if (!clockEl) return;

    const now = getServerNow();
    const pad = (n) => String(n).padStart(2, '0');
    const time = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
    clockEl.textContent = time + ' WIB';
}

/**
 * Inisialisasi: cache elemen → sync awal → mulai tick → resync berkala →
 * visibility listener.
 */
function initServerClock() {
    // Cache elemen SEKALI di sini; renderClock() cukup pakai clockEl.
    clockEl = document.getElementById('server-clock');
    if (!clockEl) return;

    // Sync pertama sebelum menampilkan jam (hindari waktu device yang meleset).
    syncServerTime().then(() => renderClock());

    // Tick render 1 detik — murni tampilan, tanpa drift karena memakai offset.
    setInterval(renderClock, TICK_INTERVAL_MS);

    // Koreksi offset berkala (mengatasi drift clock device & perubahan latensi).
    resyncTimer = setInterval(syncServerTime, RESYNC_INTERVAL_MS);

    // Resync paksa saat tab kembali fokus/aktif: browser bisa menghentikan
    // timer saat tab di background/sleep, sehingga offset perlu diperbarui.
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            syncServerTime();
        }
    });
}

// Jalankan init; jika #server-clock belum ada di DOM, berhenti diam-diam.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initServerClock);
} else {
    initServerClock();
}
