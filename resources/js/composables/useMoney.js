import { usePage } from '@inertiajs/vue3';

export const SUPPORTED_CURRENCIES = ['BDT', 'USD', 'EUR', 'GBP', 'INR', 'SAR'];

function defaultLocaleFor(currency, appLocale) {
    if (currency === 'BDT') {
        return appLocale === 'bn' ? 'bn-BD' : 'en-BD';
    }

    return 'en-US';
}

/**
 * @param {{ currency?: string, locale?: string } | null} [override]
 */
export function useMoney(override = null) {
    const page = usePage();
    const shared = page.props.money ?? null;
    const cfg = override ?? shared ?? null;

    function resolved(options = {}) {
        const currency = options.currency ?? cfg?.currency ?? 'BDT';
        const locale = options.locale ?? cfg?.locale ?? defaultLocaleFor(currency, page.props.locale);

        return { currency, locale };
    }

    function formatMoney(amount, options = {}) {
        const { currency, locale } = resolved(options);

        return new Intl.NumberFormat(locale, {
            style: 'currency',
            currency,
            // BDT defaults to the "BDT" code in many locales; narrowSymbol yields ৳.
            currencyDisplay: 'narrowSymbol',
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }).format(Number(amount || 0));
    }

    function formatCents(cents, currency = null) {
        return formatMoney(Number(cents || 0) / 100, currency ? { currency } : {});
    }

    function currencyCode() {
        return cfg?.currency ?? 'BDT';
    }

    function currencySymbol() {
        if (cfg?.symbol) {
            return cfg.symbol;
        }

        const { currency, locale } = resolved();

        if (currency === 'BDT') {
            return '৳';
        }

        try {
            const parts = new Intl.NumberFormat(locale, {
                style: 'currency',
                currency,
                currencyDisplay: 'narrowSymbol',
            }).formatToParts(0);

            const symbol = parts.find((p) => p.type === 'currency');

            return symbol?.value ?? currency;
        } catch (e) {
            return currency;
        }
    }

    return { formatMoney, formatCents, currencyCode, currencySymbol };
}
