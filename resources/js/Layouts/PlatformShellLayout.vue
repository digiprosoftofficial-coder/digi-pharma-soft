<template>
    <div class="platform-shell d-flex min-vh-100 bg-light">
        <aside class="platform-sidebar border-end bg-white d-flex flex-column flex-shrink-0" style="width: 260px">
            <div class="p-3 border-bottom">
                <Link href="/platform/dashboard" class="text-decoration-none text-dark">
                    <div class="fw-bold">{{ t('platform.brand') }}</div>
                    <div class="small text-muted">{{ t('platform.brand_sub') }}</div>
                </Link>
            </div>
            <nav class="flex-grow-1 overflow-auto small p-2">
                <ul class="nav flex-column gap-1">
                    <li v-for="item in navItems" :key="item.href" class="nav-item">
                        <Link :href="item.href" class="nav-link rounded py-2" :class="navActive(item.href)">
                            {{ item.label }}
                        </Link>
                    </li>
                </ul>
            </nav>
        </aside>
        <div class="flex-grow-1 d-flex flex-column min-vh-100 min-w-0">
            <header class="border-bottom bg-white px-3 py-2 d-flex flex-wrap align-items-center gap-2">
                <h1 class="h5 mb-0 text-primary me-auto">{{ pageTitle }}</h1>
                <span class="small text-muted d-none d-md-inline">{{ userName }}</span>
                <Link href="/logout" method="post" as="button" class="btn btn-sm btn-outline-secondary">
                    {{ t('common.logout') }}
                </Link>
            </header>
            <main class="flex-grow-1 p-3 p-md-4 overflow-auto">
                <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ flashSuccess }}
                    <button type="button" class="btn-close" @click="flashSuccess = null"></button>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>

<script setup>
import { useLocale } from '@/composables/useLocale';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

defineProps({
    pageTitle: { type: String, default: 'Overview' },
});

const { t } = useLocale();
const page = usePage();

const userName = computed(() => page.props.auth?.user?.name ?? 'Admin');
const flashSuccess = ref(page.props.flash?.success ?? null);

watch(
    () => page.props.flash?.success,
    (v) => {
        if (v) {
            flashSuccess.value = v;
        }
    },
);

const navItems = computed(() => [
    { href: '/platform/dashboard', label: t('platform.nav_overview') },
    { href: '/platform/tenants', label: t('platform.nav_pharmacies') },
    { href: '/platform/plans', label: t('platform.nav_plans') },
    { href: '/platform/resellers', label: t('platform.nav_resellers') },
    { href: '/platform/master-catalog', label: t('platform.nav_master_catalog') },
    { href: '/platform/catalog-templates', label: t('platform.nav_catalog') },
    { href: '/platform/product-types', label: t('platform.nav_product_types') },
    { href: '/platform/announcements', label: t('platform.nav_announcements') },
    { href: '/platform/billing', label: t('platform.nav_billing') },
    { href: '/platform/admins', label: t('platform.nav_admins') },
    { href: '/platform/audit', label: t('platform.nav_audit') },
    { href: '/platform/health', label: t('platform.nav_health') },
    { href: '/platform/settings', label: t('platform.nav_settings') },
]);

function pathNow() {
    return (page.url || '').split('?')[0];
}

function navActive(prefix) {
    const u = pathNow();
    if (prefix === '/platform/dashboard') {
        return u === '/platform/dashboard' ? 'active bg-primary text-white' : '';
    }

    if (prefix === '/platform/settings') {
        return u === '/platform/settings' ? 'active bg-primary text-white' : '';
    }

    return u === prefix || u.startsWith(prefix + '/') ? 'active bg-primary text-white' : '';
}
</script>

<style scoped>
.platform-sidebar .nav-link.active {
    color: #fff !important;
}
</style>
