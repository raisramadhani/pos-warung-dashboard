/**
 * Transaction history view, pagination, filters, detail modal, and reprint receipt logic.
 */

import { state } from "./state.js";
import { formatRupiah, formatNumber, formatDateTime } from "./utils.js";
import { openModal, closeModal, showError, showInfo, showToast } from "./ui.js";
import { formatReceiptItem } from "./receipt.js";
import { posBt } from "./printer.js";

export const openHistory = () => {
    closeModal("sidebar-nav-confirm-modal");
    const sidebar = document.getElementById("mobile-sidebar");
    const backdrop = document.getElementById("sidebar-backdrop");
    if (sidebar && !sidebar.classList.contains("-translate-x-full")) {
        backdrop?.classList.add("opacity-0");
        sidebar.classList.add("-translate-x-full");
        sidebar.classList.remove("translate-x-0");
        setTimeout(() => backdrop?.classList.add("hidden"), 300);
    }
    const viewIndex = document.getElementById("view-index");
    const viewCheckout = document.getElementById("view-checkout");
    const viewHistory = document.getElementById("view-history");

    if (viewIndex) viewIndex.classList.add("hidden");
    if (viewCheckout) {
        viewCheckout.classList.add("hidden");
        viewCheckout.classList.remove("flex");
    }
    if (viewHistory) {
        viewHistory.classList.remove("hidden");
        viewHistory.classList.add("flex");
    }

    // Update active state in sidebar
    const btnKasir = document.getElementById("nav-kasir");
    const btnHistory = document.getElementById("nav-history");

    const activeClasses = [
        "text-primary",
        "bg-blue-50",
        "dark:bg-gray-700",
        "dark:text-blue-400",
    ];
    const inactiveClasses = [
        "text-gray-600",
        "dark:text-gray-300",
        "hover:bg-gray-50",
        "dark:hover:bg-gray-700/50",
        "hover:text-primary",
    ];

    if (btnKasir) {
        btnKasir.classList.remove(...activeClasses);
        btnKasir.classList.add(...inactiveClasses);
    }
    if (btnHistory) {
        btnHistory.classList.remove(...inactiveClasses);
        btnHistory.classList.add(...activeClasses);
    }

    state.historyPage = 1;
    loadHistory();
};

export const closeHistory = () => {
    const sidebar = document.getElementById("mobile-sidebar");
    const backdrop = document.getElementById("sidebar-backdrop");
    if (sidebar && !sidebar.classList.contains("-translate-x-full")) {
        backdrop?.classList.add("opacity-0");
        sidebar.classList.add("-translate-x-full");
        sidebar.classList.remove("translate-x-0");
        setTimeout(() => backdrop?.classList.add("hidden"), 300);
    }

    const viewHistory = document.getElementById("view-history");
    const viewIndex = document.getElementById("view-index");
    const viewCheckout = document.getElementById("view-checkout");

    if (viewHistory) {
        viewHistory.classList.add("hidden");
        viewHistory.classList.remove("flex");
    }
    if (viewCheckout) {
        viewCheckout.classList.add("hidden");
        viewCheckout.classList.remove("flex");
    }
    if (viewIndex) {
        viewIndex.classList.remove("hidden");
    }

    // Update active state in sidebar
    const btnKasir = document.getElementById("nav-kasir");
    const btnHistory = document.getElementById("nav-history");

    const activeClasses = [
        "text-primary",
        "bg-blue-50",
        "dark:bg-gray-700",
        "dark:text-blue-400",
    ];
    const inactiveClasses = [
        "text-gray-600",
        "dark:text-gray-300",
        "hover:bg-gray-50",
        "dark:hover:bg-gray-700/50",
        "hover:text-primary",
    ];

    if (btnKasir) {
        btnKasir.classList.remove(...inactiveClasses);
        btnKasir.classList.add(...activeClasses);
    }
    if (btnHistory) {
        btnHistory.classList.remove(...activeClasses);
        btnHistory.classList.add(...inactiveClasses);
    }
};

export const resetHistoryFilters = () => {
    const searchEl = document.getElementById("history-search");
    const methodEl = document.getElementById("history-method");
    if (searchEl) searchEl.value = "";
    if (methodEl) methodEl.value = "";
    if (state.historyRangePicker) state.historyRangePicker.clear();
    state.historyPage = 1;
    loadHistory();
};

export const renderHistoryList = (items) => {
    const container = document.getElementById("history-list");
    if (!container) return;
    container.innerHTML = "";

    if (items.length === 0) {
        container.innerHTML = `<div class="flex flex-col items-center justify-center py-20 gap-3 text-gray-400 dark:text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    <span class="text-xs font-bold">Tidak ada transaksi ditemukan</span>
                </div>`;
        return;
    }

    items.forEach((item) => {
        const card = document.createElement("div");
        card.className =
            "bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl shadow-sm p-3 flex justify-between items-center gap-3 transition-all active:scale-95 cursor-pointer hover:shadow-md";
        card.onclick = () => openHistoryDetail(item.id);

        const methodBadge =
            item.payment_method === "qris"
                ? '<span class="px-2 py-0.5 bg-blue-50 dark:bg-gray-700 text-blue-600 dark:text-blue-400 rounded-full text-[9px] font-bold">QRIS</span>'
                : '<span class="px-2 py-0.5 bg-green-50 dark:bg-gray-700 text-green-600 dark:text-green-400 rounded-full text-[9px] font-bold">TUNAI</span>';

        card.innerHTML = `
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-xs md:text-sm text-gray-800 dark:text-white truncate">${item.transaction_number}</h4>
                            ${methodBadge}
                        </div>
                        <div class="text-[10px] text-gray-500 dark:text-gray-400 font-medium mt-1">
                            ${formatDateTime(item.transaction_at)} · ${item.line_items_count ?? item.items_count ?? 0} Produk (${item.items_count ?? 0} Item)
                            ${item.customer_name ? " · " + item.customer_name : ""}
                        </div>
                        <div class="text-[10px] text-gray-400 dark:text-gray-500 font-medium mt-0.5">Kasir: ${item.cashier || "-"}</div>
                    </div>
                    <div class="text-right shrink-0 pl-2">
                        <div class="font-black text-sm md:text-base text-primary dark:text-blue-400">${formatRupiah(item.total_amount)}</div>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-300 dark:text-gray-600 ml-auto mt-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </div>`;
        container.appendChild(card);
    });
};

export const loadHistory = (page = 1) => {
    state.historyPage = page;
    const container = document.getElementById("history-list");
    const pagination = document.getElementById("history-pagination");

    if (container) {
        container.innerHTML = `<div class="flex flex-col items-center justify-center py-20 gap-4 text-gray-400 dark:text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-primary animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                <span class="text-xs font-bold">Memuat riwayat...</span>
            </div>`;
    }
    if (pagination) pagination.innerHTML = "";

    const params = new URLSearchParams({
        page: state.historyPage,
        per_page: 5,
    });
    const searchEl = document.getElementById("history-search");
    const q = searchEl ? searchEl.value.trim() : "";
    if (q) params.set("q", q);
    const methodEl = document.getElementById("history-method");
    const method = methodEl ? methodEl.value : "";
    if (method) params.set("payment_method", method);
    if (state.historyRangePicker && state.historyRangePicker.range) {
        const range = state.historyRangePicker.range;
        params.set("from", range.start.format("YYYY-MM-DD"));
        params.set("to", range.end.format("YYYY-MM-DD"));
    }

    fetch(`${state.cfg.routes.history}?${params.toString()}`, {
        method: "GET",
        headers: { Accept: "application/json" },
    })
        .then((res) => res.json())
        .then((data) => {
            if (!data.success) {
                if (container) {
                    container.innerHTML = `<div class="flex flex-col items-center justify-center py-20 gap-3 text-gray-400 dark:text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span class="text-xs font-bold">${data.message || "Gagal memuat riwayat."}</span>
                        </div>`;
                }
                return;
            }
            renderHistoryList(data.data || [], data.meta || {});
        })
        .catch(() => {
            if (container) {
                container.innerHTML = `<div class="flex flex-col items-center justify-center py-20 gap-3 text-gray-400 dark:text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span class="text-xs font-bold">Gagal terhubung ke server.</span>
                    </div>`;
            }
        });
};

export const fillHistoryDetail = (t) => {
    const trxEl = document.getElementById("hd-trx-number");
    const dateEl = document.getElementById("hd-date");
    const cashierEl = document.getElementById("hd-cashier");
    const customerEl = document.getElementById("hd-customer");
    const methodEl = document.getElementById("hd-method");

    if (trxEl) trxEl.innerText = t.transactionNumber;
    if (dateEl) dateEl.innerText = t.date || "-";
    if (cashierEl) cashierEl.innerText = t.cashier || "-";
    if (customerEl) customerEl.innerText = t.customer || "Umum";
    if (methodEl) methodEl.innerText = t.paymentLabel || "-";

    const notesContainer = document.getElementById("hd-notes-container");
    const notesEl = document.getElementById("hd-notes");
    if (notesContainer && notesEl) {
        if (t.notes) {
            notesEl.innerText = t.notes;
            notesContainer.classList.remove("hidden");
        } else {
            notesEl.innerText = "";
            notesContainer.classList.add("hidden");
        }
    }

    const itemsContainer = document.getElementById("hd-items");
    if (itemsContainer) {
        itemsContainer.innerHTML = "";
        (t.items || []).forEach((item) => {
            const isFree = !!item.is_free;
            const label = isFree
                ? ' <span class="text-green-600 dark:text-green-400 font-bold">(Gratis)</span>'
                : "";
            const promoTag = item.promotionName
                ? `<span class="shrink-0 text-[9px] font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/40 border border-amber-200 dark:border-amber-700 px-1.5 py-0.5 rounded">${item.promotionName}</span>`
                : "";
            const originalPrice =
                item.original_price ?? item.originalPrice ?? item.price;
            const unitPrice = item.price;
            const qty = item.qty;
            const discountAmt =
                item.discount_amount ?? item.discountAmount ?? 0;
            const hasUnitPriceDiscount = !isFree && originalPrice > unitPrice;
            const hasDiscountAmount =
                !isFree && !hasUnitPriceDiscount && discountAmt > 0;
            const lineSubtotal =
                item.subtotal !== undefined ? item.subtotal : unitPrice * qty;
            let detailHtml = "";
            if (isFree) {
                detailHtml = `<span class="text-green-600 dark:text-green-400">${formatNumber(unitPrice)}</span> x ${qty} = <span class="text-green-600 dark:text-green-400 font-bold">${formatNumber(lineSubtotal)}</span>`;
            } else if (hasUnitPriceDiscount) {
                detailHtml = `<span class="line-through text-gray-400">${formatNumber(originalPrice)}</span> <span class="text-primary font-bold">${formatNumber(unitPrice)}</span> x ${qty} = <span class="text-primary font-bold">${formatNumber(lineSubtotal)}</span>`;
            } else if (hasDiscountAmount) {
                const originalGross = originalPrice * qty;
                const finalSubtotal =
                    item.subtotal !== undefined
                        ? item.subtotal
                        : Math.max(0, originalGross - discountAmt);
                detailHtml = `${formatNumber(originalPrice)} x ${qty} = <span class="text-primary font-bold">${formatNumber(originalGross)}</span><br><span class="text-red-500 font-bold">− ${formatNumber(discountAmt)}</span> = <span class="text-primary font-bold">${formatNumber(finalSubtotal)}</span>`;
            } else {
                detailHtml = `${formatNumber(unitPrice)} x ${qty} = <span class="text-primary font-bold">${formatNumber(lineSubtotal)}</span>`;
            }
            itemsContainer.innerHTML += `<div class="p-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-100 dark:border-gray-600 rounded-lg flex flex-col gap-1">
                            <div class="flex items-start justify-between gap-1.5"><h4 class="font-bold text-xs text-gray-800 dark:text-white leading-tight min-w-0 flex-1">${item.name}${label}</h4>${promoTag}</div>
                            <div class="text-gray-500 dark:text-gray-400 text-[10px] font-medium leading-relaxed">${detailHtml}</div>
                        </div>`;
        });
    }

    const subtotalEl = document.getElementById("hd-subtotal");
    if (subtotalEl) subtotalEl.innerText = "Rp " + formatNumber(t.subtotal);

    const discountSection = document.getElementById("hd-discount");
    const discountLines = document.getElementById("hd-discount-lines");
    if (discountLines) discountLines.innerHTML = "";
    if (t.discount > 0) {
        if (discountSection?.parentElement)
            discountSection.parentElement.style.display = "";
        (t.discounts || []).forEach((d) => {
            if (discountLines) {
                discountLines.innerHTML += `<div class="flex justify-between text-[10px] font-medium text-gray-500 dark:text-gray-400"><span class="truncate pr-2">${d.desc}</span><span class="text-red-500 font-bold shrink-0">- Rp ${formatNumber(d.amount)}</span></div>`;
            }
        });
        if (discountSection)
            discountSection.innerText = "- Rp " + formatNumber(t.discount);
    } else if (discountSection?.parentElement) {
        discountSection.parentElement.style.display = "none";
    }

    const totalEl = document.getElementById("hd-total");
    if (totalEl) totalEl.innerText = "Rp " + formatNumber(t.total);
};

export const openHistoryDetail = (id) => {
    const btn = document.getElementById("btn-history-reprint");
    if (btn) {
        btn.disabled = true;
        btn.classList.add("opacity-50", "cursor-not-allowed");
    }

    fetch(state.cfg.routes.historyDetail.replace("__ID__", id), {
        method: "GET",
        headers: { Accept: "application/json" },
    })
        .then((res) => res.json())
        .then((data) => {
            if (!data.success) {
                showError(
                    "Gagal Memuat Detail",
                    data.message || "Gagal memuat detail transaksi.",
                );
                return;
            }
            state.historyDetailData = data.transaction;
            fillHistoryDetail(state.historyDetailData);
            if (btn) {
                btn.disabled = false;
                btn.classList.remove("opacity-50", "cursor-not-allowed");
            }
            openModal("history-detail-modal");
        })
        .catch(() => {
            if (btn) {
                btn.disabled = false;
                btn.classList.remove("opacity-50", "cursor-not-allowed");
            }
            showError("Gagal Terhubung", "Gagal terhubung ke server.");
        });
};

export const printHistoryReceipt = async () => {
    if (!state.historyDetailData) {
        showInfo("Tidak Ada Data", "Tidak ada data transaksi untuk dicetak.");
        return;
    }
    if (!posBt || !posBt.isConnected()) {
        showError(
            "Printer Tidak Terhubung",
            "Hubungkan printer di header terlebih dahulu.",
        );
        return;
    }

    const btn = document.getElementById("btn-history-reprint");
    const originalText = btn ? btn.innerHTML : "";
    if (btn) {
        btn.disabled = true;
        btn.classList.add("opacity-50", "cursor-not-allowed");
    }

    try {
        const payload = {
            storeName:
                state.historyDetailData.storeName || state.cfg.merchantName,
            storeAddress:
                state.historyDetailData.storeAddress ||
                state.cfg.merchantAddress,
            transactionNumber: state.historyDetailData.transactionNumber,
            date: state.historyDetailData.date,
            cashier: state.historyDetailData.cashier || state.cfg.cashierName,
            customer: state.historyDetailData.customer || "Umum",
            notes: state.historyDetailData.notes || null,
            items: (state.historyDetailData.items || []).map(formatReceiptItem),
            subtotal: state.historyDetailData.subtotal,
            discount: state.historyDetailData.discount,
            discounts: state.historyDetailData.discounts || [],
            total: state.historyDetailData.total,
            paymentMethod: state.historyDetailData.paymentMethod,
            amountReceived:
                state.historyDetailData.amountReceived ||
                state.historyDetailData.total,
            change: state.historyDetailData.change || 0,
        };
        await posBt.printReceipt(payload);
        showToast("success", "Struk berhasil dicetak ulang.");
    } catch (e) {
        showError(
            "Gagal Mencetak",
            e.message || "Terjadi kesalahan saat mencetak.",
        );
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.classList.remove("opacity-50", "cursor-not-allowed");
            btn.innerHTML = originalText;
        }
    }
};

export async function initHistoryRangePicker() {
    if (!window.PosDateRangePicker) {
        await new Promise((resolve) => {
            const check = () =>
                window.PosDateRangePicker ? resolve() : setTimeout(check, 50);
            check();
        });
    }
    const input = document.getElementById("history-range");
    if (!input || state.historyRangePicker) return;

    state.historyRangePicker = new window.PosDateRangePicker(input, {
        maxDate: moment().endOf("day"),
        opens: "left",
        drops: "down",
    });
    state.historyRangePicker.setPlaceholder("Semua tanggal");
}
