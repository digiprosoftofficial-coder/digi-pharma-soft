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

export function unitConversionFactor(product, sellUnit) {
    const row = product.units?.find((u) => u.sell_unit === sellUnit);
    return Number(row?.conversion_factor ?? 1);
}

/** Stock on hand expressed in the selected sell unit. */
export function stockInSellUnit({ baseStock, baseUnit, sellUnit, units, piecesPerStrip }) {
    const stock = Number(baseStock ?? 0);
    if (Number.isNaN(stock)) {
        return 0;
    }

    if (sellUnit === baseUnit) {
        return stock;
    }

    if (sellUnit === 'piece' && baseUnit === 'strip' && piecesPerStrip) {
        return stock * Number(piecesPerStrip);
    }

    if (sellUnit === 'piece' && baseUnit === 'piece') {
        return stock;
    }

    const factor = unitConversionFactor({ units }, sellUnit);
    return stock / Math.max(0.0001, factor);
}

export function totalPiecesFromStock({ baseStock, baseUnit, piecesPerStrip }) {
    const stock = Number(baseStock ?? 0);
    const pps = Number(piecesPerStrip ?? 0);
    if (!pps || Number.isNaN(stock)) {
        return null;
    }
    if (baseUnit === 'piece') {
        return stock;
    }
    if (baseUnit === 'strip') {
        return stock * pps;
    }
    return null;
}
