export function defaultSellUnit(product) {
    const row = product.units?.find((u) => u.is_default) ?? product.units?.[0];
    return row?.sell_unit ?? product.base_unit ?? 'strip';
}

export function unitSalePrice(product, sellUnit) {
    const row = product.units?.find((u) => u.sell_unit === sellUnit);
    return Number(row?.sale_price ?? product.sale_price ?? 0);
}

export function unitPurchasePrice(product, sellUnit) {
    const row = product.units?.find((u) => u.sell_unit === sellUnit);
    return Number(row?.purchase_price ?? product.purchase_price ?? 0);
}

export function unitLabel(sellUnit) {
    const key = `catalog.units.${sellUnit}`;
    if (typeof window !== 'undefined' && window.__translations?.[key]) {
        return window.__translations[key];
    }
    return sellUnit;
}
