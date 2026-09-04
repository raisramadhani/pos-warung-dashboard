/**
 * Cart management, server-side preview synchronization, and cart UI rendering.
 */

import { state } from './state.js';
import { formatRupiah, formatPlainNumber, formatNumber } from './utils.js';
import { renderProducts } from './catalog.js';
import { closeModal } from './ui.js';

export const handleCartScroll = () => {
    const el = document.getElementById('cart-items');
    const topShadow = document.getElementById('scroll-top-shadow');
    const bottomShadow = document.getElementById('scroll-bottom-shadow');
    if (!el) return;
    if (el.scrollTop > 0) {
        topShadow?.classList.remove('opacity-0');
        topShadow?.classList.add('opacity-100');
    } else {
        topShadow?.classList.add('opacity-0');
        topShadow?.classList.remove('opacity-100');
    }
    if (el.scrollHeight - el.scrollTop > el.clientHeight + 1) {
        bottomShadow?.classList.remove('opacity-0');
        bottomShadow?.classList.add('opacity-100');
    } else {
        bottomShadow?.classList.add('opacity-0');
        bottomShadow?.classList.remove('opacity-100');
    }
};

export const syncCartWithProducts = () => {
    const productMap = new Map(state.products.map(p => [p.id, p]));
    state.cart = state.cart
        .filter(item => productMap.has(item.id))
        .map(item => {
            const fresh = productMap.get(item.id);
            const eff = state.effectivePrices[item.id];

            return {
                ...item,
                name: fresh.name,
                price: fresh.price,
                image: fresh.image,
                promoPrice: eff ? eff.price : null,
                promoName: eff ? eff.promotion_name : (state.promoBadges[item.id]?.[0] || null),
            };
        });
};

export const schedulePreview = (onPreviewSuccess) => {
    if (state.previewTimer) clearTimeout(state.previewTimer);
    if (state.previewAbort) {
        state.previewAbort.abort();
        state.previewAbort = null;
    }
    if (state.cart.length === 0) {
        state.previewItems = [];
        state.previewTotals = { subtotal: 0, discount_total: 0, total: 0, discounts: [], applied_promotions: [] };
        return;
    }
    state.previewTimer = setTimeout(() => {
        state.previewAbort = new AbortController();
        fetch(state.cfg.routes.preview, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': state.cfg.csrfToken },
            body: JSON.stringify({ items: state.cart.map(i => ({ product_id: i.id, quantity: i.qty })) }),
            signal: state.previewAbort.signal,
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                state.previewItems = data.items || [];
                state.previewTotals = {
                    subtotal: data.subtotal ?? 0,
                    discount_total: data.discount_total ?? 0,
                    total: data.total ?? 0,
                    discounts: data.discounts || [],
                    applied_promotions: data.applied_promotions || [],
                };
                state.checkoutItems = state.previewItems;
                state.checkoutPromoLines = state.previewTotals.discounts;
                state.checkoutAppliedPromos = state.previewTotals.applied_promotions;
                state.checkoutSubtotal = state.previewTotals.subtotal;
                state.checkoutDiscountTotal = state.previewTotals.discount_total;
                state.grandTotal = state.previewTotals.total;

                if (typeof onPreviewSuccess === 'function') {
                    onPreviewSuccess();
                }
                updateCartUI(false);
            })
            .catch(() => {})
            .finally(() => {
                state.previewAbort = null;
            });
    }, 300);
};

export const addToCart = (productId, onCartChange) => {
    const product = state.products.find(p => p.id === productId);
    if (!product) return;
    const existingItem = state.cart.find(item => item.id === productId);
    if (existingItem) {
        existingItem.qty += 1;
    } else {
        const eff = state.effectivePrices[product.id];
        state.cart.push({
            ...product,
            promoPrice: eff ? eff.price : null,
            promoName: eff ? eff.promotion_name : (state.promoBadges[product.id]?.[0] || null),
            qty: 1,
        });
    }
    updateCartUI();
    schedulePreview(onCartChange);
};

export const updateQty = (productId, delta, onCartChange) => {
    const itemIndex = state.cart.findIndex(item => item.id === productId);
    if (itemIndex > -1) {
        state.cart[itemIndex].qty += delta;
        if (state.cart[itemIndex].qty <= 0) state.cart.splice(itemIndex, 1);
    }
    if (state.cart.length === 0) {
        state.previewItems = [];
        state.previewTotals = { subtotal: 0, discount_total: 0, total: 0, discounts: [], applied_promotions: [] };
    }
    updateCartUI();
    schedulePreview(onCartChange);
};

export const clearCart = () => {
    if (state.cart.length > 0) {
        state.cart = [];
        state.previewItems = [];
        state.previewTotals = { subtotal: 0, discount_total: 0, total: 0, discounts: [], applied_promotions: [] };
        if (state.previewTimer) clearTimeout(state.previewTimer);
        if (state.previewAbort) {
            state.previewAbort.abort();
            state.previewAbort = null;
        }
        updateCartUI();
    }
};

export const confirmClearCart = () => {
    closeModal('clear-cart-modal');
    clearCart();
};

export const updateCartUI = (scroll = true) => {
    const cartContainer = document.getElementById('cart-items');
    const btnPay = document.getElementById('btn-pay');
    const btnPayItems = document.getElementById('btn-pay-items');
    const btnPayTotal = document.getElementById('btn-pay-total');
    if (!cartContainer || !btnPay || !btnPayItems || !btnPayTotal) return;

    cartContainer.innerHTML = '';
    let totalItems = 0;
    let cartTotal = 0;

    if (state.cart.length === 0) {
        cartContainer.innerHTML =
            `<div class="flex-1 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 min-h-[250px]"><div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-3"><svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg></div><p class="font-bold text-sm">Pesanan Masih Kosong</p></div>`;
        btnPayItems.innerText = '0 Produk (0 Item)';
        btnPayTotal.innerText = formatRupiah(0);
        btnPay.disabled = true;
        handleCartScroll();
        renderProducts();
        return;
    }

    btnPay.disabled = false;
    let lineProductCount = 0;

    if (state.previewItems.length > 0) {
        totalItems = state.previewItems.reduce((s, pi) => s + pi.quantity, 0);
        lineProductCount = state.previewItems.length;
        cartTotal = state.previewTotals.total;

        state.previewItems.forEach(pi => {
            const isFree = !!pi.is_free;
            const promoName = pi.promotion_name || state.promoBadges[pi.product_id]?.[0] || null;
            const promoLabel = promoName ? ` <span class="shrink-0 text-[9px] font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/40 border border-amber-200 dark:border-amber-700 px-1.5 py-0.5 rounded">${promoName}</span>` : '';
            const qty = pi.quantity;
            const unitPrice = pi.unit_price ?? 0;
            const originalPrice = pi.original_price ?? unitPrice;
            const discountAmt = pi.discount_amount ?? 0;
            const hasUnitPriceDiscount = !isFree && originalPrice > unitPrice;
            const hasDiscountAmount = !isFree && !hasUnitPriceDiscount && discountAmt > 0;
            const lineTotal = pi.subtotal !== undefined ? pi.subtotal : (unitPrice * qty);

            let detailHtml = '';
            let nameSuffix = '';
            if (isFree) {
                nameSuffix = ' <span class="text-green-600 dark:text-green-400 font-bold">(Gratis)</span>';
                detailHtml = `<span class="text-green-600 dark:text-green-400">${formatNumber(unitPrice)}</span> x ${qty} = <span class="text-green-600 dark:text-green-400 font-bold">${formatNumber(lineTotal)}</span>`;
            } else if (hasUnitPriceDiscount) {
                detailHtml = `<span class="line-through text-gray-400 dark:text-gray-500">${formatNumber(originalPrice)}</span> <span class="text-primary dark:text-blue-400 font-bold">${formatNumber(unitPrice)}</span> x ${qty} = <span class="text-primary dark:text-blue-400 font-bold">${formatNumber(lineTotal)}</span>`;
            } else if (hasDiscountAmount) {
                const originalGross = originalPrice * qty;
                const finalSubtotal = pi.subtotal !== undefined ? pi.subtotal : Math.max(0, originalGross - discountAmt);
                detailHtml = `${formatNumber(originalPrice)} x ${qty} = <span class="text-primary dark:text-blue-400 font-bold">${formatNumber(originalGross)}</span><br><span class="text-red-500 font-bold">− ${formatNumber(discountAmt)}</span> = <span class="text-primary dark:text-blue-400 font-bold">${formatNumber(finalSubtotal)}</span>`;
            } else {
                detailHtml = `${formatNumber(unitPrice)} x ${qty} = <span class="text-primary dark:text-blue-400 font-bold">${formatNumber(lineTotal)}</span>`;
            }

            const cartRow = document.createElement('div');
            cartRow.className = 'p-2 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-sm flex flex-col gap-1 transition-colors duration-200 shrink-0';
            const controls = isFree ? '' : `<div class="flex items-center bg-blue-50/50 dark:bg-gray-700 rounded-xl p-1 border border-blue-100 dark:border-gray-600 shrink-0 gap-1"><button onclick="updateQty(${pi.product_id}, -1)" class="w-10 h-10 flex items-center justify-center bg-white dark:bg-gray-800 dark:text-white rounded-lg shadow-sm text-gray-600 font-bold text-lg active:scale-95 focus:outline-none hover:bg-gray-50">-</button><span class="font-bold text-sm w-7 text-center dark:text-white">${qty}</span><button onclick="updateQty(${pi.product_id}, 1)" class="w-10 h-10 flex items-center justify-center bg-primary dark:bg-primary dark:text-white rounded-lg shadow-sm text-white font-bold text-lg active:scale-95 focus:outline-none hover:bg-primaryHover">+</button></div>`;
            const rowLayout = isFree
                ? `<div class="flex items-start justify-between gap-1.5"><div class="font-bold text-xs text-gray-800 dark:text-white leading-tight min-w-0 flex-1">${pi.name}${nameSuffix}</div>${promoLabel}</div><div class="text-gray-500 dark:text-gray-400 text-[10px] font-medium leading-relaxed">${detailHtml}</div>`
                : `<div class="flex justify-between items-center gap-2"><div class="flex-1 min-w-0"><div class="flex items-start gap-1 flex-wrap"><span class="font-bold text-gray-800 dark:text-white text-xs md:text-sm">${pi.name}${nameSuffix}</span>${promoLabel}</div><div class="text-gray-500 dark:text-gray-400 font-medium text-[11px] mt-1 leading-relaxed">${detailHtml}</div></div>${controls}</div>`;
            cartRow.innerHTML = rowLayout;
            cartContainer.appendChild(cartRow);
        });
    } else {
        lineProductCount = state.cart.length;
        state.cart.forEach(item => {
            const eff = state.effectivePrices[item.id];
            const promoPrice = eff ? eff.price : null;
            const displayPrice = promoPrice ?? item.price;
            const finalItemTotal = displayPrice * item.qty;
            totalItems += item.qty;
            cartTotal += finalItemTotal;
            const badgePromo = state.promoBadges[item.id]?.[0] || null;
            const promoName = item.promoName || badgePromo;
            const promoLabel = promoName ? ` <span class="shrink-0 text-[9px] font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/40 border border-amber-200 dark:border-amber-700 px-1.5 py-0.5 rounded">${promoName}</span>` : '';
            const priceHtml = promoPrice !== null ?
                `<span class="text-primary font-bold dark:text-blue-400">${formatPlainNumber(promoPrice)}</span> <span class="text-gray-400 line-through text-[9px]">${formatPlainNumber(item.price)}</span> x ${item.qty} = <span class="text-primary font-bold dark:text-blue-400">${formatPlainNumber(finalItemTotal)}</span>` :
                `${formatPlainNumber(item.price)} x ${item.qty} = <span class="text-primary font-bold dark:text-blue-400">${formatPlainNumber(finalItemTotal)}</span>`;
            const cartRow = document.createElement('div');
            cartRow.className =
                'p-2 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-sm flex justify-between items-center transition-colors duration-200 shrink-0';
            cartRow.innerHTML =
                `<div class="flex-1 min-w-0 pr-2"><h4 class="font-bold text-gray-800 dark:text-white text-xs md:text-sm flex items-center gap-1 flex-wrap">${item.name}${promoLabel}</h4><div class="text-gray-500 dark:text-gray-400 font-medium text-[11px] mt-1">${priceHtml}</div></div><div class="flex items-center bg-blue-50/50 dark:bg-gray-700 rounded-xl p-1 border border-blue-100 dark:border-gray-600 shrink-0 gap-1"><button onclick="updateQty(${item.id}, -1)" class="w-10 h-10 flex items-center justify-center bg-white dark:bg-gray-800 dark:text-white rounded-lg shadow-sm text-gray-600 font-bold text-lg active:scale-95 focus:outline-none hover:bg-gray-50">-</button><span class="font-bold text-sm w-7 text-center dark:text-white">${item.qty}</span><button onclick="updateQty(${item.id}, 1)" class="w-10 h-10 flex items-center justify-center bg-primary dark:bg-primary dark:text-white rounded-lg shadow-sm text-white font-bold text-lg active:scale-95 focus:outline-none hover:bg-primaryHover">+</button></div>`;
            cartContainer.appendChild(cartRow);
        });
    }

    btnPayItems.innerText = `${lineProductCount} Produk (${totalItems} Item)`;
    btnPayTotal.innerText = formatRupiah(cartTotal);
    if (scroll) cartContainer.scrollTop = cartContainer.scrollHeight;
    setTimeout(handleCartScroll, 50);
    renderProducts();
};
