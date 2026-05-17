import { usePage } from '@inertiajs/vue3';

export function usePermissions() {
    const page = usePage();

    function can(permission) {
        return page.props.auth?.user?.permissions?.includes(permission) ?? false;
    }

    return { can };
}
