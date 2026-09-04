/**
 * Helper to normalize and format receipt items for ESC/POS printer payload.
 */

export const formatReceiptItem = (item) => {
    const isFree = !!(item.is_free || item.isFree || item.unit_price === 0 || item.price === 0);
    const qty = item.quantity ?? item.qty ?? 1;
    const originalPrice = item.original_price ?? item.originalPrice ?? item.unit_price ?? item.price ?? 0;
    const unitPrice = item.unit_price ?? item.price ?? originalPrice;
    const subtotal = item.subtotal !== undefined ? item.subtotal : (unitPrice * qty);

    let lineDiscount = 0;
    if (!isFree) {
        if (item.discount_amount !== undefined && item.discount_amount !== null && item.discount_amount > 0) {
            lineDiscount = item.discount_amount;
        } else if (item.discountAmount !== undefined && item.discountAmount !== null && item.discountAmount > 0) {
            lineDiscount = item.discountAmount;
        } else {
            lineDiscount = Math.max(0, (originalPrice * qty) - subtotal);
        }
    }
    const cleanName = (item.name || 'Item').replace(/\s*\(GRATIS\)/i, '');
    return {
        name: cleanName + (isFree ? ' (GRATIS)' : ''),
        qty: qty,
        price: unitPrice,
        originalPrice: originalPrice,
        subtotal: subtotal,
        promotionName: item.promotion_name || item.promotionName || null,
        discountAmount: lineDiscount,
        is_free: isFree,
    };
};
