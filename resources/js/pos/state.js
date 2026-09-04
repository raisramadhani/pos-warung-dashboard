/**
 * Global reactive state store for POS session.
 */

const cfg = window.POS_CONFIG || {};

export const state = {
    cfg,
    products: cfg.products || [],
    categories: cfg.categories || [],
    promoBadges: cfg.promoBadges || {},
    effectivePrices: cfg.effectivePrices || {},
    activeCategory: 'Semua Menu',
    searchQuery: '',
    viewMode: 'grid',
    cart: [],

    // Preview promo server-side
    previewItems: [],
    previewTotals: { subtotal: 0, discount_total: 0, total: 0, discounts: [], applied_promotions: [] },
    previewTimer: null,
    previewAbort: null,

    // Checkout
    checkoutItems: [],
    checkoutPromoLines: [],
    checkoutAppliedPromos: [],
    checkoutSubtotal: 0,
    checkoutDiscountTotal: 0,
    grandTotal: 0,
    uangDiterima: 0,
    paymentMethod: 'cash',
    selectedCustomerId: null,
    selectedCustomerName: '',
    selectedNotes: '',
    lastTransactionData: null,
    activeIdempotencyKey: null,

    // History
    historyPage: 1,
    historyDetailData: null,
    historyRangePicker: null,
};
