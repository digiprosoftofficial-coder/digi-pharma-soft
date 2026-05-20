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

export function boxConversionFactor(productOrUnits) {
    const units = productOrUnits?.units ?? productOrUnits;
    if (!Array.isArray(units)) {
        return 0;
    }
    const row = units.find((u) => u.sell_unit === 'box');
    return Number(row?.conversion_factor ?? 0);
}

export function catalogStripsPerBox(product) {
    if (product?.strips_per_box != null && product.strips_per_box !== '') {
        return Number(product.strips_per_box);
    }
    if ((product?.base_unit ?? 'strip') === 'strip') {
        const factor = boxConversionFactor(product);
        return factor > 0 ? factor : null;
    }
    return null;
}

export function catalogBoxesPerCarton(product) {
    if (product?.boxes_per_carton != null && product.boxes_per_carton !== '') {
        return Number(product.boxes_per_carton);
    }
    const boxFactor = boxConversionFactor(product);
    const cartonFactor = unitConversionFactor(product, 'carton');
    if (boxFactor > 0 && cartonFactor > 0) {
        return cartonFactor / boxFactor;
    }
    return null;
}

export function hasBoxAndCartonUnits(units) {
    if (!Array.isArray(units)) {
        return false;
    }
    return units.some((u) => u.sell_unit === 'box') && units.some((u) => u.sell_unit === 'carton');
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
