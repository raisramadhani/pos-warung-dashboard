/**
 * Checkout logic, promo evaluation, order list rendering, customer & notes handling.
 */

import { state } from './state.js';
import { formatNumber } from './utils.js';
import { closeModal } from './ui.js';
import { updateCartUI } from './cart.js';

export const renderPromoState = () => {
    const listContainer = document.getElementById('promo-list');
    const linesContainer = document.getElementById('promo-discount-lines');
    const btnText = document.getElementById('btn-text-promo');

    const promoItems = state.checkoutAppliedPromos.length > 0
        ? state.checkoutAppliedPromos
        : state.checkoutPromoLines.map(d => ({
            name: d.desc,
            type: 'discount',
            amount: d.amount,
            label: `- Rp ${formatNumber(d.amount)}`,
        }));

    if (listContainer) {
        listContainer.innerHTML = '';
        if (promoItems.length === 0) {
            listContainer.innerHTML =
                '<div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 text-center"><span class="text-xs font-medium text-gray-400">Tidak ada promo yang berlaku</span></div>';
        } else {
            promoItems.forEach((p, idx) => {
                const badgeColor = p.type === 'free_item' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500';
                listContainer.innerHTML +=
                    `<div class="flex justify-between items-center bg-gray-50 dark:bg-gray-800 p-2.5 rounded-lg border border-gray-200 dark:border-gray-600"><div class="flex flex-col min-w-0 pr-2"><span class="text-[10px] font-bold text-gray-400">PROMO ${idx + 1}</span><span class="text-xs font-bold text-gray-800 dark:text-white truncate">${p.name}</span></div><span class="text-xs font-black ${badgeColor}">${p.label}</span></div>`;
            });
        }
    }

    if (linesContainer) {
        linesContainer.innerHTML = '';
        state.checkoutPromoLines.forEach(d => {
            linesContainer.innerHTML +=
                `<div class="flex justify-between text-[10px] font-medium text-gray-500 dark:text-gray-400"><span class="truncate pr-2">${d.desc}</span><span class="text-red-500 font-bold shrink-0">- Rp ${formatNumber(d.amount)}</span></div>`;
        });
    }

    if (btnText) {
        btnText.innerText = promoItems.length > 0 ? `${promoItems.length} Promo` : 'Tidak ada';
    }
};

export const renderOrderItemsTo = (container) => {
    if (!container) return 0;
    container.innerHTML = '';
    let sub = 0;

    const items = (state.checkoutItems.length > 0) ? state.checkoutItems : (state.previewItems.length > 0 ? state.previewItems : state.cart);

    items.forEach(item => {
        const unitPrice = item.unit_price ?? item.price;
        const originalPrice = item.original_price ?? unitPrice;
        const qty = item.quantity ?? item.qty;
        const discountAmt = item.discount_amount ?? 0;
        const isFree = !!(item.is_free || item.isFree);
        const promoName = item.promotion_name || (!isFree ? (state.promoBadges[item.product_id]?.[0] || null) : null);

        const hasUnitPriceDiscount = !isFree && originalPrice > unitPrice;
        const hasDiscountAmount = !isFree && !hasUnitPriceDiscount && discountAmt > 0;

        const lineTotal = item.subtotal !== undefined ? item.subtotal : (unitPrice * qty);
        if (!isFree) sub += lineTotal;

        let nameSuffix = '';
        if (isFree) {
            nameSuffix = ' <span class="text-green-600 dark:text-green-400 font-bold">(Gratis)</span>';
        }

        const promoTag = promoName
            ? `<span class="shrink-0 text-[9px] font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/40 border border-amber-200 dark:border-amber-700 px-1.5 py-0.5 rounded">${promoName}</span>`
            : '';

        let detailHtml = '';

        if (isFree) {
            detailHtml = `<span class="text-green-600 dark:text-green-400">${formatNumber(unitPrice)}</span>`
                + ` x ${qty} = `
                + `<span class="text-green-600 dark:text-green-400 font-bold">${formatNumber(lineTotal)}</span>`;
        } else if (hasUnitPriceDiscount) {
            detailHtml = `<span class="line-through text-gray-400 dark:text-gray-500">${formatNumber(originalPrice)}</span> `
                + `<span class="text-primary dark:text-blue-400 font-bold">${formatNumber(unitPrice)}</span>`
                + ` x ${qty} = `
                + `<span class="text-primary dark:text-blue-400 font-bold">${formatNumber(lineTotal)}</span>`;
        } else if (hasDiscountAmount) {
            const originalGross = originalPrice * qty;
            const finalSubtotal = item.subtotal !== undefined ? item.subtotal : Math.max(0, originalGross - discountAmt);
            detailHtml = `${formatNumber(originalPrice)} x ${qty} = `
                + `<span class="text-primary dark:text-blue-400 font-bold">${formatNumber(originalGross)}</span>`
                + `<br><span class="text-red-500 font-bold">− ${formatNumber(discountAmt)}</span>`
                + ` = <span class="text-primary dark:text-blue-400 font-bold">${formatNumber(finalSubtotal)}</span>`;
        } else {
            detailHtml = `${formatNumber(unitPrice)} x ${qty} = `
                + `<span class="text-primary dark:text-blue-400 font-bold">${formatNumber(lineTotal)}</span>`;
        }

        container.innerHTML +=
            `<div class="px-2 py-1.5 bg-gray-50 dark:bg-gray-700/60 border border-gray-100 dark:border-gray-600 rounded-lg">
                <div class="flex items-start justify-between gap-1.5">
                    <div class="font-bold text-xs text-gray-800 dark:text-white leading-tight min-w-0 truncate">${item.name}${nameSuffix}</div>
                    ${promoTag}
                </div>
                <div class="text-gray-500 dark:text-gray-400 text-[10px] mt-0.5 font-medium leading-relaxed">${detailHtml}</div>
            </div>`;
    });

    return sub;
};

export const updateOrderModalItems = () => {
    const inlineContainer = document.getElementById('inline-order-items');
    if (inlineContainer) {
        renderOrderItemsTo(inlineContainer);
    }

    const modalContainer = document.getElementById('order-modal-items');
    if (modalContainer) {
        const sub = renderOrderItemsTo(modalContainer);
        const modalSubtotal = document.getElementById('modal-order-subtotal');
        if (modalSubtotal) modalSubtotal.innerText = 'Rp ' + formatNumber(sub);
    }
};

export const calculateTotals = () => {
    state.grandTotal = Math.max(0, state.checkoutSubtotal - state.checkoutDiscountTotal);
    const lblSub = document.getElementById('label-subtotal');
    const lblDisc = document.getElementById('label-discount');
    const lblGrand = document.getElementById('label-grandtotal');

    if (lblSub) lblSub.innerText = 'Rp ' + formatNumber(state.checkoutSubtotal);
    if (lblDisc) lblDisc.innerText = '- Rp ' + formatNumber(state.checkoutDiscountTotal);
    if (lblGrand) lblGrand.innerText = 'Rp ' + formatNumber(state.grandTotal);

    if (state.paymentMethod === 'qris') {
        state.uangDiterima = state.grandTotal;
        const inpUang = document.getElementById('input-uang');
        if (inpUang) inpUang.innerText = state.uangDiterima > 0 ? formatNumber(state.uangDiterima) : '0';
    }

    if (typeof window.updateUangPasState === 'function') window.updateUangPasState();
    if (typeof window.calculateChange === 'function') window.calculateChange();
};

export const initCheckoutFromCart = () => {
    state.uangDiterima = 0;
    state.selectedCustomerId = null;
    state.selectedCustomerName = '';
    const sc = document.getElementById('select-customer');
    if (sc) sc.value = '';
    const ic = document.getElementById('input-customer');
    if (ic) ic.value = '';
    const btnCust = document.getElementById('btn-text-customer');
    if (btnCust) btnCust.innerText = 'Umum';
    const inpNotes = document.getElementById('input-notes');
    if (inpNotes) inpNotes.value = '';
    const btnNotes = document.getElementById('btn-text-notes');
    if (btnNotes) btnNotes.innerText = '-';
    const inpUang = document.getElementById('input-uang');
    if (inpUang) inpUang.innerText = '0';

    if (state.previewItems.length > 0) {
        state.checkoutItems = state.previewItems;
        state.checkoutPromoLines = state.previewTotals.discounts || [];
        state.checkoutAppliedPromos = state.previewTotals.applied_promotions || [];
        state.checkoutSubtotal = state.previewTotals.subtotal;
        state.checkoutDiscountTotal = state.previewTotals.discount_total;
        state.grandTotal = state.previewTotals.total;
        const inlineCount = document.getElementById('inline-order-count');
        const t = state.checkoutItems.reduce((s, x) => s + x.quantity, 0);
        const p = state.checkoutItems.length;
        if (inlineCount) inlineCount.innerText = `${p} Produk (${t} Item)`;
        renderPromoState();
        updateOrderModalItems();
        if (typeof window.setPaymentMethod === 'function') window.setPaymentMethod('cash');
        calculateTotals();
        return;
    }

    state.checkoutSubtotal = state.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    state.checkoutDiscountTotal = 0;
    state.checkoutPromoLines = [];
    state.checkoutAppliedPromos = [];
    state.checkoutItems = [];
    state.grandTotal = state.checkoutSubtotal;

    const initTotalItems = state.cart.reduce((s, i) => s + i.qty, 0);
    const initLineProducts = state.cart.length;
    const inlineCount2 = document.getElementById('inline-order-count');
    if (inlineCount2) inlineCount2.innerText = `${initLineProducts} Produk (${initTotalItems} Item)`;
    renderPromoState();
    updateOrderModalItems();
    if (typeof window.setPaymentMethod === 'function') window.setPaymentMethod('cash');
    calculateTotals();

    fetch(state.cfg.routes.preview, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': state.cfg.csrfToken,
        },
        body: JSON.stringify({
            items: state.cart.map(item => ({
                product_id: item.id,
                quantity: item.qty,
            })),
        }),
    })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            state.checkoutItems = data.items || [];
            state.previewItems = state.checkoutItems;
            state.checkoutPromoLines = data.discounts || [];
            state.checkoutAppliedPromos = data.applied_promotions || [];
            state.previewTotals = {
                subtotal: data.subtotal ?? 0,
                discount_total: data.discount_total ?? 0,
                total: data.total ?? 0,
                discounts: state.checkoutPromoLines,
                applied_promotions: state.checkoutAppliedPromos,
            };
            state.checkoutSubtotal = data.subtotal ?? state.checkoutSubtotal;
            state.checkoutDiscountTotal = data.discount_total ?? 0;
            state.grandTotal = data.total ?? state.checkoutSubtotal;

            const totalItems = state.checkoutItems.reduce((s, item) => s + item.quantity, 0);
            const totalProducts = state.checkoutItems.length;
            const inlineCountEl = document.getElementById('inline-order-count');
            if (inlineCountEl) inlineCountEl.innerText = `${totalProducts} Produk (${totalItems} Item)`;
            updateCartUI(false);
            renderPromoState();
            updateOrderModalItems();
            calculateTotals();
        })
        .catch(() => {});
};

export const goToCheckout = () => {
    if (state.cart.length === 0) return;
    const viewIndex = document.getElementById('view-index');
    const viewCheckout = document.getElementById('view-checkout');
    if (viewIndex) viewIndex.classList.add('hidden');
    if (viewCheckout) {
        viewCheckout.classList.remove('hidden');
        viewCheckout.classList.add('flex');
    }
    initCheckoutFromCart();
};

export const confirmGoBackToCart = () => {
    closeModal('back-confirm-modal');
    const viewIndex = document.getElementById('view-index');
    const viewCheckout = document.getElementById('view-checkout');
    if (viewCheckout) {
        viewCheckout.classList.add('hidden');
        viewCheckout.classList.remove('flex');
    }
    if (viewIndex) viewIndex.classList.remove('hidden');
};

export const saveCustomer = () => {
    const dropdown = document.getElementById('select-customer');
    const input = document.getElementById('input-customer');
    const btnText = document.getElementById('btn-text-customer');
    const inputVal = input ? input.value.trim() : '';

    if (inputVal !== '') {
        state.selectedCustomerId = null;
        state.selectedCustomerName = inputVal;
        if (btnText) btnText.innerText = inputVal;
    } else if (dropdown && dropdown.value !== '') {
        state.selectedCustomerId = parseInt(dropdown.value, 10);
        state.selectedCustomerName = '';
        if (btnText) btnText.innerText = dropdown.options[dropdown.selectedIndex].text;
    } else {
        state.selectedCustomerId = null;
        state.selectedCustomerName = '';
        if (btnText) btnText.innerText = 'Umum';
    }
    closeModal('customer-modal');
};

export const saveNotes = () => {
    const input = document.getElementById('input-notes');
    const text = input ? input.value.trim() : '';
    state.selectedNotes = text;
    const btnText = document.getElementById('btn-text-notes');
    if (btnText) btnText.innerText = text !== '' ? text : '-';
    closeModal('notes-modal');
};

export const clearNotes = () => {
    const input = document.getElementById('input-notes');
    if (input) input.value = '';
    state.selectedNotes = '';
    const btnText = document.getElementById('btn-text-notes');
    if (btnText) btnText.innerText = '-';
    closeModal('notes-modal');
};
