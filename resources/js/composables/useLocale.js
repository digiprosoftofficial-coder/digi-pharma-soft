import { router, usePage } from '@inertiajs/vue3';

export function useLocale() {
    const page = usePage();

    function t(key, replacementsOrFallback = null, fallback = null) {
        let value = page.props.translations?.[key];
        let replacements = null;
        let fb = fallback;

        if (
            replacementsOrFallback !== null
            && typeof replacementsOrFallback === 'object'
            && !Array.isArray(replacementsOrFallback)
        ) {
            replacements = replacementsOrFallback;
        } else {
            fb = replacementsOrFallback ?? fallback;
        }

        if (value === undefined || value === null) {
            return fb ?? key;
        }

        if (replacements) {
            for (const [name, replacement] of Object.entries(replacements)) {
                value = value.replaceAll(`:${name}`, String(replacement));
            }
        }

        return value;
    }

    function setLocale(code) {
        router.post(
            '/locale',
            { locale: code },
            { preserveScroll: true, preserveState: false },
        );
    }

    return {
        locale: () => page.props.locale ?? 'en',
        locales: () => page.props.locales ?? [],
        t,
        setLocale,
    };
}
