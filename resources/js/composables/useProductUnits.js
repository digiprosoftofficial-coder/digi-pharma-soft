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

export function catalogPiecesPerBox(product) {
    if (product?.pieces_per_box != null && product.pieces_per_box !== '') {
        return Number(product.pieces_per_box);
    }
    if ((product?.base_unit ?? 'strip') === 'piece') {
        const factor = boxConversionFactor(product);
        return factor > 0 ? factor : null;
    }
    return null;
}

export function catalogPiecesPerStrip(product) {
    if (product?.pieces_per_strip != null && product.pieces_per_strip !== '') {
        return Number(product.pieces_per_strip);
    }
    return null;
}

/**
 * Units available when purchasing a product, based on type + base unit hierarchy.
 * Includes catalog units and synthesizes missing pack units.
 *
 * @returns {Array<{sell_unit:string,conversion_factor:number,purchase_price:number|string,sale_price:number|string,is_default:boolean}>}
 */
export function buildPurchaseUnitOptions(product, { stripTypes = ['tablet', 'capsule'] } = {}) {
    const existing = Array.isArray(product?.units)
        ? product.units
        : (product?.units?.data ?? []);
    const byUnit = Object.fromEntries(existing.map((u) => [u.sell_unit, u]));
    const base = product?.base_unit ?? product?.unit ?? 'strip';
    const type = product?.product_type ?? 'other';
    const allUnits = ['piece', 'strip', 'box', 'carton'];
    const usesStrip = stripTypes.includes(type);
    let allowed = usesStrip ? [...allUnits] : allUnits.filter((u) => u !== 'strip');

    for (const unit of [base, ...existing.map((u) => u.sell_unit)]) {
        if (unit && allUnits.includes(unit) && !allowed.includes(unit)) {
            allowed.push(unit);
        }
    }

    if (base === 'carton') {
        allowed = allowed.filter((u) => u === 'carton');
    } else if (base === 'box') {
        allowed = allowed.filter((u) => u === 'box' || u === 'carton');
    } else if (base === 'piece') {
        allowed = allowed.filter((u) => u === 'piece' || u === 'box' || u === 'carton' || (u === 'strip' && usesStrip));
    }

    allowed.sort((a, b) => allUnits.indexOf(a) - allUnits.indexOf(b));

    const basePurchase = Number(byUnit[base]?.purchase_price ?? product?.purchase_price ?? 0);
    const baseSale = Number(byUnit[base]?.sale_price ?? product?.sale_price ?? 0);
    const pps = catalogPiecesPerStrip(product);
    const spb = catalogStripsPerBox({ ...product, units: existing, base_unit: base });
    const ppb = catalogPiecesPerBox({ ...product, units: existing, base_unit: base });
    const bpc = catalogBoxesPerCarton({ ...product, units: existing });

    const boxFactor = (() => {
        if (base === 'box') {
            return 1;
        }
        if (base === 'strip' && spb) {
            return spb;
        }
        if (base === 'piece' && ppb) {
            return ppb;
        }
        return boxConversionFactor(existing);
    })();

    return allowed.map((sellUnit) => {
        if (byUnit[sellUnit]) {
            return {
                sell_unit: byUnit[sellUnit].sell_unit,
                conversion_factor: Number(byUnit[sellUnit].conversion_factor ?? 1),
                purchase_price: byUnit[sellUnit].purchase_price,
                sale_price: byUnit[sellUnit].sale_price,
                is_default: Boolean(byUnit[sellUnit].is_default),
            };
        }

        let conversionFactor = 1;
        if (sellUnit === base) {
            conversionFactor = 1;
        } else if (sellUnit === 'piece' && base === 'strip' && pps > 0) {
            conversionFactor = 1 / pps;
        } else if (sellUnit === 'strip' && base === 'piece' && pps > 0) {
            conversionFactor = pps;
        } else if (sellUnit === 'box' && base === 'strip' && spb > 0) {
            conversionFactor = spb;
        } else if (sellUnit === 'box' && base === 'piece' && ppb > 0) {
            conversionFactor = ppb;
        } else if (sellUnit === 'carton' && boxFactor > 0 && bpc > 0) {
            conversionFactor = boxFactor * bpc;
        } else if (sellUnit === 'carton' && base === 'box' && bpc > 0) {
            conversionFactor = bpc;
        }

        return {
            sell_unit: sellUnit,
            conversion_factor: conversionFactor > 0 ? conversionFactor : 1,
            purchase_price: conversionFactor > 0 ? basePurchase * conversionFactor : basePurchase,
            sale_price: conversionFactor > 0 ? baseSale * conversionFactor : baseSale,
            is_default: sellUnit === base,
        };
    });
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
