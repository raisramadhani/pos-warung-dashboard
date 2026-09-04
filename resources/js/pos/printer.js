/**
 * Bluetooth printer lifecycle & printing routines.
 */

import { state } from './state.js';
import { openModal, closeModal, showError, showInfo, showToast } from './ui.js';

export let posBt = null;

export async function initBluetooth() {
    if (!window.PosBluetooth) {
        await new Promise(resolve => {
            const check = () => (window.PosBluetooth ? resolve() : setTimeout(check, 50));
            check();
        });
    }
    posBt = new window.PosBluetooth();

    posBt.onStatusChange = (status, message) => {
        const dot = document.getElementById('bt-status-dot');
        const statusIcon = document.getElementById('bt-status-icon');
        const statusText = document.getElementById('bt-status-text');
        const deviceNameEl = document.getElementById('bt-device-name');
        const btnDisconnect = document.getElementById('btn-bt-disconnect');
        const btnScan = document.getElementById('btn-bt-scan');
        const btnPrint = document.getElementById('btn-print-receipt');

        if (status === 'connected') {
            if (dot) dot.className = 'absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-green-500 border-2 border-primary dark:border-gray-800';
            if (statusIcon) {
                statusIcon.className = 'w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0';
                statusIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
            }
            if (statusText) statusText.innerText = 'Terhubung';
            if (deviceNameEl) deviceNameEl.innerText = message || '-';
            if (btnDisconnect) btnDisconnect.classList.remove('hidden');
            if (btnScan) btnScan.innerText = 'Ganti Printer';
            if (btnPrint) {
                btnPrint.disabled = false;
                btnPrint.title = '';
            }
        } else if (status === 'connecting') {
            if (dot) dot.className = 'absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-yellow-500 border-2 border-primary dark:border-gray-800 animate-pulse';
            if (statusIcon) {
                statusIcon.className = 'w-10 h-10 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center shrink-0';
                statusIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-500 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>';
            }
            if (statusText) statusText.innerText = 'Menghubungkan...';
            if (deviceNameEl) deviceNameEl.innerText = message || '-';
        } else {
            if (dot) dot.className = 'absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-red-500 border-2 border-primary dark:border-gray-800';
            if (statusIcon) {
                statusIcon.className = 'w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0';
                statusIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.5 6.5l11 11L12 23V1l5.5 5.5-11 11" /></svg>';
            }
            if (statusText) statusText.innerText = status === 'error' ? (message || 'Gagal terhubung') : 'Tidak Terhubung';
            if (deviceNameEl) deviceNameEl.innerText = '-';
            if (btnDisconnect) btnDisconnect.classList.add('hidden');
            if (btnScan) btnScan.innerText = 'Cari Printer Baru';
            if (btnPrint) {
                btnPrint.disabled = false;
                btnPrint.title = 'Printer tidak terhubung. Klik tombol Bluetooth di header untuk menghubungkan.';
            }
        }
    };
}

export async function btScanAndConnect(onPrintCallback) {
    if (!posBt) return;
    if (!posBt.isSupported()) {
        showInfo('Bluetooth Tidak Didukung', 'Browser tidak mendukung Bluetooth. Gunakan Chrome di Android atau desktop.');
        return;
    }
    try {
        await posBt.scanAndConnect();
        closeModal('bluetooth-modal');
        if (state.lastTransactionData && typeof onPrintCallback === 'function') {
            await onPrintCallback();
        }
    } catch (e) {
        if (e.message && !e.message.includes('dipilih')) {
            showError('Gagal Menghubungkan', e.message);
        }
    }
}

export async function btDisconnect() {
    if (!posBt) return;
    const btn = document.getElementById('btn-bt-disconnect');
    const originalText = btn ? btn.innerText : '';
    if (btn) {
        btn.disabled = true;
        btn.innerText = 'Memutuskan...';
    }
    try {
        await posBt.disconnect();
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerText = originalText;
        }
    }
}

export async function printReceipt(resetPosCallback) {
    if (!posBt || !posBt.isConnected()) {
        openModal('success-modal');
        openModal('bluetooth-modal');
        return;
    }

    if (!state.lastTransactionData) {
        showInfo('Tidak Ada Data', 'Tidak ada data transaksi untuk dicetak.');
        return;
    }

    const btn = document.getElementById('btn-print-receipt');
    const originalText = btn ? btn.innerHTML : '';

    try {
        await posBt.printReceipt(state.lastTransactionData);
        if (typeof resetPosCallback === 'function') {
            resetPosCallback();
        }
    } catch (e) {
        showError('Gagal Mencetak', e.message);
        if (btn) {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
}
