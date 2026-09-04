/**
 * Payment processing, numpad interaction, change calculation, and order submission.
 */

import { state } from './state.js';
import { formatNumber } from './utils.js';
import { openModal, closeModal, showError } from './ui.js';
import { formatReceiptItem } from './receipt.js';
import { updateCartUI } from './cart.js';

export const setPaymentMethod = (method) => {
    state.paymentMethod = method;
    const cashBtn = document.getElementById('btn-pay-cash');
    const qrisBtn = document.getElementById('btn-pay-qris');
    const numpadSection = document.getElementById('numpad-section');
    const labelUang = document.getElementById('label-uang-diterima');
    const inputUang = document.getElementById('input-uang');
    const lockedIcon = document.getElementById('input-uang-locked-icon');

    if (method === 'cash') {
        if (cashBtn) cashBtn.className = 'py-2.5 rounded-lg border-2 border-primary bg-blue-50 dark:bg-gray-700 text-primary dark:text-blue-400 font-bold text-xs transition-colors';
        if (qrisBtn) qrisBtn.className = 'py-2.5 rounded-lg border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 font-bold text-xs hover:border-blue-300 transition-colors';
        if (numpadSection) numpadSection.classList.remove('hidden');
        if (labelUang) labelUang.innerText = 'Uang Diterima';
        if (inputUang) {
            inputUang.classList.remove('opacity-60');
            inputUang.innerText = '0';
        }
        if (lockedIcon) {
            lockedIcon.classList.add('hidden');
            lockedIcon.classList.remove('flex');
        }
        state.uangDiterima = 0;
    } else {
        if (qrisBtn) qrisBtn.className = 'py-2.5 rounded-lg border-2 border-primary bg-blue-50 dark:bg-gray-700 text-primary dark:text-blue-400 font-bold text-xs transition-colors';
        if (cashBtn) cashBtn.className = 'py-2.5 rounded-lg border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 font-bold text-xs hover:border-blue-300 transition-colors';
        if (numpadSection) numpadSection.classList.add('hidden');
        if (labelUang) labelUang.innerText = 'Total Dibayar (QRIS)';
        if (inputUang) {
            inputUang.classList.add('opacity-60');
            inputUang.innerText = state.grandTotal > 0 ? formatNumber(state.grandTotal) : '0';
        }
        if (lockedIcon) {
            lockedIcon.classList.remove('hidden');
            lockedIcon.classList.add('flex');
        }
        state.uangDiterima = state.grandTotal;
    }
    updateUangPasState();
    calculateChange();
};

export const setExactAmount = () => {
    state.uangDiterima = state.grandTotal;
    const inputUang = document.getElementById('input-uang');
    if (inputUang) inputUang.innerText = state.uangDiterima > 0 ? formatNumber(state.uangDiterima) : '0';
    calculateChange();
    updateUangPasState();
};

export const clearAmount = () => {
    state.uangDiterima = 0;
    const inputUang = document.getElementById('input-uang');
    if (inputUang) inputUang.innerText = '0';
    calculateChange();
    updateUangPasState();
};

export const appendDigit = (digit) => {
    const str = (state.uangDiterima === 0 ? '' : String(state.uangDiterima)) + digit;
    state.uangDiterima = parseInt(str, 10) || 0;
    const inputUang = document.getElementById('input-uang');
    if (inputUang) inputUang.innerText = formatNumber(state.uangDiterima);
    calculateChange();
    updateUangPasState();
};

export const backspaceDigit = () => {
    const str = String(state.uangDiterima).slice(0, -1);
    state.uangDiterima = str ? parseInt(str, 10) : 0;
    const inputUang = document.getElementById('input-uang');
    if (inputUang) inputUang.innerText = state.uangDiterima > 0 ? formatNumber(state.uangDiterima) : '0';
    calculateChange();
    updateUangPasState();
};

export const updateUangPasState = () => {
    const btn = document.getElementById('btn-uang-pas');
    if (!btn) return;
    const isSelected = state.uangDiterima === state.grandTotal && state.grandTotal > 0;
    btn.className = isSelected ?
        'py-2.5 border-2 font-bold text-[11px] rounded-lg transition-all active:scale-95 bg-primary text-white border-primary shadow-md shadow-blue-500/30' :
        'py-2.5 border-2 font-bold text-[11px] rounded-lg transition-all active:scale-95 bg-white text-gray-700 border-gray-200 hover:border-primary hover:text-primary dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200';
};

export const calculateChange = () => {
    const kembalian = state.uangDiterima - state.grandTotal;
    const labelKembalian = document.getElementById('label-kembalian');
    const btnProcess = document.getElementById('btn-process');
    if (state.uangDiterima >= state.grandTotal) {
        if (labelKembalian) {
            labelKembalian.innerText = 'Rp ' + formatNumber(kembalian);
            labelKembalian.classList.remove('text-red-500');
            labelKembalian.classList.add('text-white');
        }
        if (btnProcess) btnProcess.disabled = false;
    } else {
        if (labelKembalian) {
            labelKembalian.innerText = 'Kurang';
            labelKembalian.classList.remove('text-white');
            labelKembalian.classList.add('text-red-500');
        }
        if (btnProcess) btnProcess.disabled = true;
    }
};

export const processPayment = () => {
    if (state.cfg.isTransactionLocked) {
        openModal('stock-opname-lock-modal');
        return;
    }

    if (state.lastTransactionData) {
        openModal('success-modal');
        return;
    }

    const items = state.cart.map(item => ({
        product_id: item.id,
        quantity: item.qty,
    }));
    const btnProcess = document.getElementById('btn-process');
    const btnProcessLabel = document.getElementById('btn-process-label');
    if (btnProcess) btnProcess.disabled = true;
    if (btnProcessLabel) btnProcessLabel.innerText = 'MEMPROSES...';

    state.activeIdempotencyKey = state.activeIdempotencyKey || crypto.randomUUID();

    const transactionNotes = state.selectedNotes || document.getElementById('input-notes')?.value?.trim() || null;

    fetch(state.cfg.routes.process, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': state.cfg.csrfToken,
        },
        body: JSON.stringify({
            payment_method: state.paymentMethod,
            idempotency_key: state.activeIdempotencyKey,
            items: items,
            amount_received: state.uangDiterima,
            customer_id: state.selectedCustomerId,
            customer_name: state.selectedCustomerName || null,
            notes: transactionNotes,
        }),
    })
        .then(res => res.json())
        .then(data => {
            if (btnProcessLabel) btnProcessLabel.innerText = 'Kembalian';
            calculateChange();
            if (data.success) {
                const trxEl = document.getElementById('modal-trx-number');
                const tagihanEl = document.getElementById('modal-tagihan');
                const tunaiEl = document.getElementById('modal-tunai');
                const kembaliEl = document.getElementById('modal-kembali');

                if (trxEl) trxEl.innerText = data.transaction.number;
                if (tagihanEl) tagihanEl.innerText = 'Rp ' + formatNumber(data.transaction.total);
                if (tunaiEl) tunaiEl.innerText = 'Rp ' + formatNumber(state.uangDiterima);
                if (kembaliEl) kembaliEl.innerText = 'Rp ' + formatNumber(data.transaction.change);

                const receiptItems = state.checkoutItems.length > 0 ? state.checkoutItems : state.cart.map(item => ({
                    product_id: item.id,
                    name: item.name,
                    quantity: item.qty,
                    unit_price: item.price,
                    original_price: item.price,
                    discount_amount: 0,
                    promotion_id: null,
                    is_free: false,
                    subtotal: item.price * item.qty,
                }));

                const btnNotes = document.getElementById('btn-text-notes');
                const btnCust = document.getElementById('btn-text-customer');

                state.lastTransactionData = {
                    storeName: state.cfg.merchantName,
                    storeAddress: state.cfg.merchantAddress,
                    transactionNumber: data.transaction.number,
                    date: new Date().toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                    }) + ' ' + new Date().toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                    }),
                    cashier: state.cfg.cashierName,
                    customer: state.selectedCustomerName || (btnCust ? btnCust.innerText : 'Umum'),
                    items: receiptItems.map(formatReceiptItem),
                    subtotal: state.checkoutSubtotal,
                    discount: state.checkoutDiscountTotal,
                    discounts: state.checkoutPromoLines.map(d => ({ desc: d.desc, amount: d.amount })),
                    total: data.transaction.total,
                    paymentMethod: state.paymentMethod,
                    amountReceived: state.uangDiterima,
                    change: data.transaction.change,
                    notes: btnNotes && btnNotes.innerText !== '-' ? btnNotes.innerText : null,
                };

                openModal('success-modal');
            } else {
                showError('Transaksi Gagal', data.message || 'Gagal memproses transaksi.');
            }
        })
        .catch(() => {
            if (btnProcessLabel) btnProcessLabel.innerText = 'Kembalian';
            calculateChange();
            showError('Gagal Terhubung', 'Gagal terhubung ke server.');
        });
};

export const resetPos = () => {
    closeModal('success-modal');
    state.cart = [];
    state.previewItems = [];
    state.previewTotals = { subtotal: 0, discount_total: 0, total: 0, discounts: [] };
    if (state.previewTimer) clearTimeout(state.previewTimer);
    if (state.previewAbort) {
        state.previewAbort.abort();
        state.previewAbort = null;
    }
    state.checkoutItems = [];
    state.checkoutPromoLines = [];
    state.checkoutDiscountTotal = 0;
    state.checkoutSubtotal = 0;
    state.grandTotal = 0;
    state.uangDiterima = 0;
    state.selectedCustomerId = null;
    state.selectedCustomerName = '';
    state.selectedNotes = '';

    const inpNotes = document.getElementById('input-notes');
    if (inpNotes) inpNotes.value = '';
    const btnNotes = document.getElementById('btn-text-notes');
    if (btnNotes) btnNotes.innerText = '-';

    state.lastTransactionData = null;
    state.activeIdempotencyKey = null;

    const viewCheckout = document.getElementById('view-checkout');
    const viewIndex = document.getElementById('view-index');
    if (viewCheckout) {
        viewCheckout.classList.add('hidden');
        viewCheckout.classList.remove('flex');
    }
    if (viewIndex) viewIndex.classList.remove('hidden');
    updateCartUI();
};
