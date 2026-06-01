const DEFAULT_STRIP_TYPES = ['tablet', 'capsule'];

const UNIT_ORDER = ['piece', 'strip', 'box', 'carton'];

export function usesStripProductType(productType, stripTypes = DEFAULT_STRIP_TYPES) {
    return stripTypes.includes(productType);
}

export function defaultBaseUnitForProductType(productType, stripTypes = DEFAULT_STRIP_TYPES) {
    return usesStripProductType(productType, stripTypes) ? 'strip' : 'piece';
}

/**
 * @param {string} productType
 * @param {string[]} allSellUnits
 * @param {string[]} includeUnits - keep these even if not normally allowed (edit legacy data)
 * @param {string[]} stripTypes
 */
export function sellUnitsForProductType(
    productType,
    allSellUnits,
    includeUnits = [],
    stripTypes = DEFAULT_STRIP_TYPES,
) {
    let allowed;
    if (usesStripProductType(productType, stripTypes)) {
        allowed = [...allSellUnits];
    } else {
        allowed = allSellUnits.filter((u) => u !== 'strip');
    }

    for (const unit of includeUnits) {
        if (unit && allSellUnits.includes(unit) && !allowed.includes(unit)) {
            allowed.push(unit);
        }
    }

    return allowed.sort((a, b) => UNIT_ORDER.indexOf(a) - UNIT_ORDER.indexOf(b));
}
