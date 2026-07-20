import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

const DEFAULT_PRIMARY = '#0d6efd';
const DEFAULT_PRIMARY_RGB = '13, 110, 253';

/**
 * @param {string} hex
 * @returns {[number, number, number] | null}
 */
function parseHex(hex) {
    const clean = String(hex || '').replace('#', '').trim();
    if (!/^[0-9a-fA-F]{6}$/.test(clean)) {
        return null;
    }

    const int = Number.parseInt(clean, 16);

    return [(int >> 16) & 255, (int >> 8) & 255, int & 255];
}

/**
 * Mix color with white (tint) or black (shade). weight 0..100 = % of white/black.
 * @param {[number, number, number]} rgb
 * @param {number} weight
 * @param {'tint' | 'shade'} mode
 */
function mixWith(rgb, weight, mode) {
    const w = Math.min(100, Math.max(0, weight)) / 100;
    const base = mode === 'tint' ? [255, 255, 255] : [0, 0, 0];

    return rgb.map((channel, i) => Math.round(channel * (1 - w) + base[i] * w));
}

function toHex(rgb) {
    return `#${rgb.map((n) => n.toString(16).padStart(2, '0')).join('')}`;
}

function toRgbString(rgb) {
    return rgb.join(', ');
}

/**
 * Apply brand theme CSS variables used across Bootstrap + custom UI.
 * @param {{ primary?: string, primary_rgb?: string } | null | undefined} theme
 */
export function applyTheme(theme) {
    if (typeof document === 'undefined') {
        return;
    }

    const root = document.documentElement;
    const primary = theme?.primary || DEFAULT_PRIMARY;
    const parsed = parseHex(primary);
    const primaryRgb = parsed ? toRgbString(parsed) : (theme?.primary_rgb || DEFAULT_PRIMARY_RGB);
    const rgb = parsed || parseHex(DEFAULT_PRIMARY);

    const textEmphasis = toHex(mixWith(rgb, 60, 'shade'));
    const bgSubtle = toHex(mixWith(rgb, 80, 'tint'));
    const borderSubtle = toHex(mixWith(rgb, 60, 'tint'));
    const hover = toHex(mixWith(rgb, 15, 'shade'));
    const active = toHex(mixWith(rgb, 20, 'shade'));

    root.style.setProperty('--bs-primary', primary);
    root.style.setProperty('--bs-primary-rgb', primaryRgb);
    root.style.setProperty('--bs-primary-text-emphasis', textEmphasis);
    root.style.setProperty('--bs-primary-bg-subtle', bgSubtle);
    root.style.setProperty('--bs-primary-border-subtle', borderSubtle);

    root.style.setProperty('--bs-link-color', primary);
    root.style.setProperty('--bs-link-color-rgb', primaryRgb);
    root.style.setProperty('--bs-link-hover-color', hover);
    root.style.setProperty('--bs-link-hover-color-rgb', toRgbString(mixWith(rgb, 15, 'shade')));
    root.style.setProperty('--bs-focus-ring-color', `rgba(${primaryRgb}, 0.25)`);

    // Extra tokens for custom CSS that should follow brand color
    root.style.setProperty('--app-primary', primary);
    root.style.setProperty('--app-primary-rgb', primaryRgb);
    root.style.setProperty('--app-primary-hover', hover);
    root.style.setProperty('--app-primary-active', active);

    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
        meta.setAttribute('content', primary);
    }
}

/** Watch Inertia shared `theme` and keep CSS variables in sync. */
export function useTheme() {
    const page = usePage();

    watch(
        () => page.props.theme,
        (theme) => {
            applyTheme(theme);
        },
        { immediate: true, deep: true },
    );
}
