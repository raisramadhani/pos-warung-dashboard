/**
 * Product catalog rendering, filtering, search, and live reload.
 */

import { state } from "./state.js";
import { formatRupiah, formatPlainNumber, getInitials } from "./utils.js";
import { showError } from "./ui.js";

export const renderCategories = (onCategoryChange) => {
    const container = document.getElementById("category-filters");
    if (!container) return;
    container.innerHTML = "";
    ["Semua Menu", ...state.categories].forEach((cat) => {
        const isActive = cat === state.activeCategory;
        const btn = document.createElement("button");
        btn.className = isActive
            ? "px-4 py-2 bg-primary text-white rounded-xl font-bold shadow-md shadow-blue-500/20 whitespace-nowrap transition-all text-xs"
            : "px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 rounded-xl font-bold hover:bg-blue-50 dark:hover:bg-gray-700 hover:text-primary whitespace-nowrap transition-all text-xs";
        btn.innerText = cat;
        btn.onclick = () => {
            state.activeCategory = cat;
            renderCategories(onCategoryChange);
            renderProducts();
            if (typeof onCategoryChange === "function") onCategoryChange(cat);
        };
        container.appendChild(btn);
    });
};

export const toggleClearSearchButton = () => {
    const btn = document.getElementById("btn-clear-search");
    const input = document.getElementById("search-input");
    if (!btn || !input) return;
    const hasValue = input.value.length > 0;
    btn.classList.toggle("hidden", !hasValue);
    btn.classList.toggle("flex", hasValue);
};

export const handleSearch = () => {
    const input = document.getElementById("search-input");
    state.searchQuery = input ? input.value.toLowerCase() : "";
    toggleClearSearchButton();
    renderProducts();
};

export const clearSearch = () => {
    const input = document.getElementById("search-input");
    if (input) input.value = "";
    state.searchQuery = "";
    toggleClearSearchButton();
    renderProducts();
};

export const setViewMode = (mode) => {
    state.viewMode = mode;
    const btnGrid = document.getElementById("btn-grid");
    const btnList = document.getElementById("btn-list");
    if (btnGrid) {
        btnGrid.className =
            mode === "grid"
                ? "p-2 bg-white dark:bg-gray-600 rounded-lg shadow-sm text-primary dark:text-blue-400 transition-all focus:outline-none"
                : "p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-all focus:outline-none rounded-lg";
    }
    if (btnList) {
        btnList.className =
            mode === "list"
                ? "p-2 bg-white dark:bg-gray-600 rounded-lg shadow-sm text-primary dark:text-blue-400 transition-all focus:outline-none"
                : "p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-all focus:outline-none rounded-lg";
    }
    renderProducts();
};

export const renderProducts = () => {
    const container = document.getElementById("product-container");
    if (!container) return;
    container.innerHTML = "";
    container.className =
        state.viewMode === "grid"
            ? "grid grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-2.5 lg:gap-3"
            : "flex flex-col gap-2";

    const filteredProducts = state.products.filter(
        (p) =>
            (state.activeCategory === "Semua Menu" ||
                p.category === state.activeCategory) &&
            p.name.toLowerCase().includes(state.searchQuery),
    );

    if (filteredProducts.length === 0) {
        container.innerHTML = `<div class="col-span-full flex flex-col items-center justify-center py-12 text-gray-400 dark:text-gray-500"><svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span class="font-bold text-sm">Produk tidak ditemukan</span></div>`;
        return;
    }

    filteredProducts.forEach((product) => {
        const card = document.createElement("div");
        card.onclick = () => window.addToCart(product.id);
        const cartItem = state.cart.find((item) => item.id === product.id);
        const isGrid = state.viewMode === "grid";
        const qtyBadgePos = isGrid ? "top-1.5 right-1.5" : "top-2.5 right-2.5";
        const qtyBadge = cartItem
            ? `<div class="absolute ${qtyBadgePos} bg-primary text-white font-black text-[11px] w-6 h-6 flex items-center justify-center rounded-full shadow-lg z-20 border-2 border-white dark:border-gray-800">${cartItem.qty}</div>`
            : "";
        const badges = state.promoBadges[product.id] || [];
        const promoBadgeHtml =
            badges.length > 0
                ? isGrid
                    ? `<div class="absolute top-1.5 left-1.5 bg-amber-400 text-amber-950 font-black text-[9px] px-1.5 py-0.5 rounded-full shadow-lg z-20 border border-amber-300 uppercase tracking-wide">${badges[0]}</div>`
                    : `<span class="inline-block bg-amber-400 text-amber-950 font-black text-[8px] px-1 py-0.5 rounded-full uppercase tracking-wide leading-none">${badges[0]}</span>`
                : "";

        const eff = state.effectivePrices[product.id];
        const priceHtml = eff
            ? `<span class="text-primary font-black ${isGrid ? "text-sm" : "text-base"}">${formatRupiah(eff.price)}</span> <span class="text-gray-400 dark:text-gray-500 line-through ${isGrid ? "text-[10px]" : "text-xs"} font-medium">${formatRupiah(product.price)}</span>`
            : `<span class="text-primary font-black ${isGrid ? "text-sm" : "text-base"}">${formatRupiah(product.price)}</span>`;

        if (isGrid) {
            card.className =
                "bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm hover:shadow-md active:scale-95 transition-all cursor-pointer flex flex-col group relative";
            card.innerHTML = `${qtyBadge}${promoBadgeHtml}<div class="aspect-square bg-gray-100 dark:bg-gray-700 relative overflow-hidden">${product.image ? `<img src="${product.image}" alt="${product.name}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">` : `<div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500 font-black text-4xl">${getInitials(product.name)}</div>`}</div><div class="p-2 flex flex-col flex-1 justify-between gap-1 text-center bg-white dark:bg-gray-800"><h3 class="font-bold text-gray-800 dark:text-gray-100 leading-tight text-[11px] line-clamp-2">${product.name}</h3><p class="flex items-center justify-center gap-1.5">${priceHtml}</p></div>`;
        } else {
            card.className =
                "bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm hover:shadow-md active:scale-95 transition-all cursor-pointer flex group p-2 gap-3 items-center relative";
            card.innerHTML = `${qtyBadge}<div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 relative overflow-hidden rounded-xl shrink-0">${product.image ? `<img src="${product.image}" alt="${product.name}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">` : `<div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-500 font-black text-lg">${getInitials(product.name)}</div>`}</div><div class="flex flex-col flex-1 min-w-0"><h3 class="font-bold text-gray-800 dark:text-gray-100 leading-tight text-sm line-clamp-1">${product.name}${promoBadgeHtml ? " " + promoBadgeHtml : ""}</h3><p class="flex items-center gap-1.5 mt-1">${priceHtml}</p></div><div class="text-right shrink-0 pr-2"><div class="bg-blue-50 text-primary dark:bg-gray-700 dark:text-blue-400 w-8 h-8 rounded-full flex items-center justify-center opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity ml-auto"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg></div></div>`;
        }
        container.appendChild(card);
    });
};

const showProductsLoader = () => {
    const container = document.getElementById("product-container");
    if (!container) return;
    container.innerHTML = `<div class="col-span-full flex flex-col items-center justify-center py-20 gap-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-primary animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                <span class="text-xs font-bold text-gray-400 dark:text-gray-500">Memuat ulang produk...</span>
            </div>`;
};

export const reloadProducts = (onComplete) => {
    const btn = document.getElementById("btn-reload-products");
    const icon = document.getElementById("icon-reload-products");
    if (!btn || btn.disabled) return;
    btn.disabled = true;
    if (icon) icon.classList.add("animate-spin");
    showProductsLoader();

    fetch(state.cfg.routes.products, {
        method: "GET",
        headers: { Accept: "application/json" },
    })
        .then((res) => {
            if (!res.ok) throw new Error("Gagal memuat ulang produk.");
            return res.json();
        })
        .then((data) => {
            if (!data.success)
                throw new Error(data.message || "Gagal memuat ulang produk.");
            state.products = data.products || [];
            state.categories = data.categories || [];
            state.promoBadges = data.promoBadges || {};
            state.effectivePrices = data.effectivePrices || {};
            if (typeof onComplete === "function") onComplete();
        })
        .catch((err) => {
            renderProducts();
            showError(
                "Gagal Memuat Ulang Produk",
                err.message || "Terjadi kesalahan saat memuat ulang produk.",
            );
        })
        .finally(() => {
            if (icon) icon.classList.remove("animate-spin");
            btn.disabled = false;
        });
};
