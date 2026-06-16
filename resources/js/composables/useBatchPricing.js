import { unitConversionFactor } from '@/composables/useProductUnits';

export function costPerBaseUnit(batch) {
    let cost = Number(batch?.purchase_unit_cost ?? 0);
    const packFactor = Number(batch?.pack_conversion_factor ?? 0);
    if (batch?.pack_sell_unit && packFactor > 0) {
        cost /= packFactor;
    }

    return cost;
}

export function salePricePerBaseUnit(batch) {
    if (batch?.sale_price === null || batch?.sale_price === undefined || batch?.sale_price === '') {
        return null;
    }

    let price = Number(batch.sale_price);
    if (Number.isNaN(price)) {
        return null;
    }

    const packFactor = Number(batch?.pack_conversion_factor ?? 0);
    if (batch?.pack_sell_unit && packFactor > 0) {
        price /= packFactor;
    }

    return price;
}

/** Unit in which purchase_unit_cost / sale_price were recorded for this batch. */
export function batchStoredPriceUnit(batch, baseUnit = 'strip') {
    if (batch?.pack_sell_unit) {
        return batch.pack_sell_unit;
    }

    return baseUnit || 'strip';
}

export function batchBaseUnitCost(batch) {
    if (batch?.cost_per_base_unit != null && batch.cost_per_base_unit !== '') {
        const fromApi = Number(batch.cost_per_base_unit);
        if (!Number.isNaN(fromApi)) {
            return fromApi;
        }
    }

    return costPerBaseUnit(batch);
}

export function batchStoredPriceDiffersFromBase(batch, baseUnit = 'strip') {
    return batchStoredPriceUnit(batch, baseUnit) !== (baseUnit || 'strip');
}

export function conversionFactorForBatchLine(batch, sellUnit, unitOptions) {
    if (batch?.pack_sell_unit === sellUnit && batch?.pack_conversion_factor) {
        const packFactor = Number(batch.pack_conversion_factor);
        if (packFactor > 0) {
            return packFactor;
        }
    }

    return unitConversionFactor({ units: unitOptions }, sellUnit);
}

export function unitCostInSellUnit(batch, sellUnit, unitOptions) {
    return costPerBaseUnit(batch) * conversionFactorForBatchLine(batch, sellUnit, unitOptions);
}

export function batchSalePriceInSellUnit(batch, sellUnit, unitOptions) {
    const basePrice = salePricePerBaseUnit(batch);
    if (basePrice === null) {
        return null;
    }

    return Math.round(basePrice * conversionFactorForBatchLine(batch, sellUnit, unitOptions) * 10000) / 10000;
}

export function resolveMarkupPercent(batch, product) {
    if (batch?.markup_percent !== null && batch?.markup_percent !== undefined && batch?.markup_percent !== '') {
        return Number(batch.markup_percent);
    }
    if (product?.default_markup_percent !== null && product?.default_markup_percent !== undefined && product?.default_markup_percent !== '') {
        return Number(product.default_markup_percent);
    }

    return null;
}

export function suggestedUnitPrice(batch, product, sellUnit, unitOptions) {
    const markup = resolveMarkupPercent(batch, product);
    if (markup === null || Number.isNaN(markup)) {
        return null;
    }

    const cost = unitCostInSellUnit(batch, sellUnit, unitOptions);
    return Math.round(cost * (1 + markup / 100) * 10000) / 10000;
}

export function lineMarginAmount(quantity, unitPrice, unitCost) {
    return (Number(unitPrice || 0) - Number(unitCost || 0)) * Number(quantity || 0);
}

export function lineMarginPercent(unitPrice, unitCost) {
    const price = Number(unitPrice || 0);
    const cost = Number(unitCost || 0);
    if (cost <= 0) {
        return null;
    }

    return ((price - cost) / cost) * 100;
}

export function applyPricingToCartLine(line, productMeta) {
    const batch = line.batches?.find((b) => b.id === line.product_batch_id) ?? {
        purchase_unit_cost: line.batch_purchase_cost,
        sale_price: line.batch_sale_price,
        pack_sell_unit: line.batch_pack_sell_unit,
        pack_conversion_factor: line.batch_pack_conversion_factor,
        markup_percent: line.batch_markup_percent,
    };
    const product = productMeta ?? {
        default_markup_percent: line.default_markup_percent,
    };

    const unitCost = unitCostInSellUnit(batch, line.sell_unit, line.unit_options);
    line.unit_cost = unitCost;

    const batchPrice = batchSalePriceInSellUnit(batch, line.sell_unit, line.unit_options);
    if (batchPrice !== null) {
        line.unit_price = batchPrice;
        line.price_from_batch = true;
        line.uses_markup_pricing = false;
        return;
    }

    const suggested = suggestedUnitPrice(batch, product, line.sell_unit, line.unit_options);
    if (suggested !== null) {
        line.unit_price = suggested;
        line.price_from_batch = false;
        line.uses_markup_pricing = true;
        return;
    }

    line.price_from_batch = false;
    line.uses_markup_pricing = false;
}
