const KNOWN_ICONS = new Set([
    'tablet',
    'capsule',
    'syrup',
    'injection',
    'cream',
    'drops',
    'bottle',
    'tube',
    'vial',
    'pack',
    'sachet',
    'other',
]);

/**
 * Map product type slug to a supported icon key.
 */
export function resolveProductTypeIcon(slug) {
    const normalized = String(slug ?? 'other')
        .trim()
        .toLowerCase()
        .replace(/\s+/g, '_');

    if (KNOWN_ICONS.has(normalized)) {
        return normalized;
    }

    return 'other';
}

/**
 * Translated display label for a product type slug.
 */
export function productTypeLabel(slug, t) {
    const key = String(slug ?? 'other').trim().toLowerCase();
    const translated = t(`catalog.types.${key}`);

    if (translated !== `catalog.types.${key}`) {
        return translated;
    }

    return key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function useProductType() {
    return {
        resolveProductTypeIcon,
        productTypeLabel,
    };
}
