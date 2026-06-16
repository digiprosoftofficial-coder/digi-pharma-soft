/**
 * Display quantity or decimal: integers without decimals, otherwise max 2 fraction digits.
 */
export function formatQty(value) {
    const n = Number(value ?? 0);

    if (Number.isNaN(n)) {
        return '0';
    }

    const rounded = Math.round(n * 100) / 100;

    if (Math.abs(rounded - Math.round(rounded)) < 1e-9) {
        return String(Math.round(rounded));
    }

    return rounded.toFixed(2);
}

/** Display money amount: max 2 fraction digits, same rules as quantities. */
export function formatPrice(value) {
    return formatQty(value);
}

/** Keep up to 4 fraction digits for internal storage and calculations. */
export function precisionDecimal(value, decimals = 4) {
    const n = Number(value ?? 0);

    if (Number.isNaN(n)) {
        return 0;
    }

    const scale = 10 ** decimals;

    return Math.round(n * scale) / scale;
}
