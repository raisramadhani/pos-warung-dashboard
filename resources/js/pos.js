/**
 * POS Application Entry Point (Modular Architecture).
 *
 * Exposes all required functions to `window` for Blade inline handlers (`onclick`),
 * initializes state, event listeners, Bluetooth, and date range picker.
 */

import { state } from './pos/state.js';
import {
    toggleMobileMenu,
    toggleDarkMode,
    toggleFullscreen,
    updateFullscreenIcon,
    confirmSidebarNav,
    confirmSidebarNavProceed,
    applyStockOpnameLock,
    openModal,
    closeModal,
} from './pos/ui.js';
import {
    renderCategories,
    handleSearch,
    clearSearch,
    setViewMode,
    renderProducts,
    reloadProducts,
    toggleClearSearchButton,
} from './pos/catalog.js';
import {
    addToCart,
    updateQty,
    clearCart,
    confirmClearCart,
    syncCartWithProducts,
    schedulePreview,
    updateCartUI,
    handleCartScroll,
} from './pos/cart.js';
import {
    goToCheckout,
    confirmGoBackToCart,
    saveCustomer,
    saveNotes,
    clearNotes,
    renderPromoState,
    updateOrderModalItems,
    calculateTotals,
} from './pos/checkout.js';
import {
    setPaymentMethod,
    setExactAmount,
    clearAmount,
    appendDigit,
    backspaceDigit,
    updateUangPasState,
    calculateChange,
    processPayment,
    resetPos,
} from './pos/payment.js';
import {
    openHistory,
    closeHistory,
    resetHistoryFilters,
    loadHistory,
    openHistoryDetail,
    printHistoryReceipt,
    initHistoryRangePicker,
} from './pos/history.js';
import {
    initBluetooth,
    btScanAndConnect,
    btDisconnect,
    printReceipt,
} from './pos/printer.js';

// ================= CALLBACK BRIDGES =================
const onCartOrPreviewUpdate = () => {
    const inlineCountEl = document.getElementById('inline-order-count');
    const viewCheckout = document.getElementById('view-checkout');
    if (inlineCountEl && viewCheckout && !viewCheckout.classList.contains('hidden')) {
        const totalItems = state.previewItems.reduce((s, x) => s + x.quantity, 0);
        const totalProducts = state.previewItems.length;
        inlineCountEl.innerText = `${totalProducts} Produk (${totalItems} Item)`;
        renderPromoState();
        updateOrderModalItems();
        calculateTotals();
    }
};

const handleAddToCart = (productId) => addToCart(productId, onCartOrPreviewUpdate);
const handleUpdateQty = (productId, delta) => updateQty(productId, delta, onCartOrPreviewUpdate);

const handleReloadProducts = () => {
    reloadProducts(() => {
        syncCartWithProducts();
        renderCategories();
        updateCartUI();
        schedulePreview(onCartOrPreviewUpdate);
    });
};

const handlePrintReceipt = () => printReceipt(resetPos);
const handleBtScanAndConnect = () => btScanAndConnect(handlePrintReceipt);

// ================= AUTO-REFRESH PROMO =================
let promoRefreshTimer = null;

const autoRefreshPromo = () => {
    if (document.hidden) return;

    fetch(state.cfg.routes.products, {
        method: 'GET',
        headers: { Accept: 'application/json' },
    })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            const oldBadges = JSON.stringify(state.promoBadges);
            const oldPrices = JSON.stringify(state.effectivePrices);
            state.promoBadges = data.promoBadges || {};
            state.effectivePrices = data.effectivePrices || {};
            state.products = data.products || [];
            state.categories = data.categories || [];

            if (JSON.stringify(state.promoBadges) !== oldBadges || JSON.stringify(state.effectivePrices) !== oldPrices) {
                syncCartWithProducts();
                renderCategories();
                updateCartUI();
                schedulePreview(onCartOrPreviewUpdate);
            }
        })
        .catch(() => {});
};

// ================= INITIALIZATION =================
applyStockOpnameLock(state.cfg.isTransactionLocked);
renderCategories();
renderProducts();
updateCartUI();
toggleClearSearchButton();
initBluetooth();
initHistoryRangePicker();
updateFullscreenIcon();

window.addEventListener('resize', handleCartScroll);
window.addEventListener('fullscreenchange', updateFullscreenIcon);
const btnFullscreen = document.getElementById('btn-fullscreen');
if (btnFullscreen && !document.fullscreenEnabled) {
    btnFullscreen.classList.add('hidden');
}

window.addEventListener('beforeunload', () => {
    btDisconnect();
});

promoRefreshTimer = setInterval(autoRefreshPromo, 60_000);
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) autoRefreshPromo();
});

// ================= EXPOSE GLOBAL =================
Object.assign(window, {
    toggleMobileMenu,
    toggleDarkMode,
    toggleFullscreen,
    confirmSidebarNav,
    confirmSidebarNavProceed,
    reloadProducts: handleReloadProducts,
    handleSearch,
    clearSearch,
    setViewMode,
    addToCart: handleAddToCart,
    updateQty: handleUpdateQty,
    confirmClearCart,
    goToCheckout,
    confirmGoBackToCart,
    openHistory,
    closeHistory,
    resetHistoryFilters,
    loadHistory,
    openHistoryDetail,
    printHistoryReceipt,
    saveCustomer,
    saveNotes,
    clearNotes,
    setPaymentMethod,
    setExactAmount,
    clearAmount,
    appendDigit,
    backspaceDigit,
    updateUangPasState,
    calculateChange,
    processPayment,
    resetPos,
    initBluetooth,
    btScanAndConnect: handleBtScanAndConnect,
    btDisconnect,
    printReceipt: handlePrintReceipt,
    initHistoryRangePicker,
    openModal,
    closeModal,
    handleCartScroll,
});
