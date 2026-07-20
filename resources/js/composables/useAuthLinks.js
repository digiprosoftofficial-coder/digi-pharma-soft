import { usePage } from '@inertiajs/vue3';

/** Build auth URLs that preserve optional ?tenant= branding slug. */
export function useAuthLinks() {
    const page = usePage();

    function withTenant(path) {
        const slug = page.props.tenant?.slug;
        if (!slug) {
            return path;
        }

        const join = path.includes('?') ? '&' : '?';

        return `${path}${join}tenant=${encodeURIComponent(slug)}`;
    }

    return { withTenant };
}
