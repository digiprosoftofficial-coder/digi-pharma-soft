<template>
    <div class="platform-shell d-flex min-vh-100 bg-light">
        <div
            v-if="mobileNavOpen"
            class="platform-mobile-backdrop d-lg-none"
            aria-hidden="true"
            @click="closeMobileNav"
        ></div>
        <aside
            id="platform-mobile-navigation"
            class="platform-sidebar platform-mobile-drawer border-end bg-white d-lg-none flex-column"
            :class="{ 'platform-mobile-drawer--open': mobileNavOpen }"
            :aria-hidden="mobileNavOpen ? 'false' : 'true'"
            :aria-label="t('common.menu', 'Menu')"
        >
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between gap-2">
                <Link href="/platform/dashboard" class="text-decoration-none text-dark min-w-0" @click="closeMobileNav">
                    <div class="fw-bold">{{ t('platform.brand') }}</div>
                    <div class="small text-muted">{{ t('platform.brand_sub') }}</div>
                </Link>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    :aria-label="t('common.close', 'Close')"
                    @click="closeMobileNav"
                >
                    &times;
                </button>
            </div>
            <nav class="flex-grow-1 overflow-auto small p-2">
                <ul class="nav flex-column gap-1">
                    <li v-for="item in navItems" :key="item.href" class="nav-item">
                        <Link :href="item.href" class="nav-link rounded py-2" :class="navActive(item.href)" @click="closeMobileNav">
                            {{ item.label }}
                        </Link>
                    </li>
                </ul>
            </nav>
        </aside>
        <aside class="platform-sidebar platform-sidebar--desktop border-end bg-white d-none d-lg-flex flex-column flex-shrink-0">
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
        <div class="platform-content flex-grow-1 d-flex flex-column min-vh-100 min-w-0">
            <header class="platform-topbar border-bottom bg-white px-3 py-2 d-flex flex-wrap align-items-center gap-2">
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary d-lg-none platform-menu-btn"
                    :aria-label="t('common.menu', 'Menu')"
                    :aria-expanded="mobileNavOpen ? 'true' : 'false'"
                    aria-controls="platform-mobile-navigation"
                    @click="openMobileNav"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="platform-mobile-brand d-lg-none me-auto min-w-0">
                    <span class="platform-mobile-brand__name">{{ t('platform.brand') }}</span>
                    <span class="platform-mobile-brand__page">{{ pageTitle }}</span>
                </div>
                <h1 class="h5 mb-0 text-primary me-auto d-none d-lg-block">{{ pageTitle }}</h1>
                <span class="small text-muted d-none d-md-inline">{{ userName }}</span>
                <Link href="/logout" method="post" as="button" class="btn btn-sm btn-outline-secondary">
                    {{ t('common.logout') }}
                </Link>
            </header>
            <main class="platform-main flex-grow-1 p-3 p-md-4 overflow-auto">
                <div v-if="flashSuccess" class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ flashSuccess }}
                    <button type="button" class="btn-close" @click="flashSuccess = null"></button>
                </div>
                <slot />
            </main>
        </div>
        <AppMessageModal />
    </div>
</template>

<script setup>
import AppMessageModal from '@/Components/AppMessageModal.vue';
import { useLocale } from '@/composables/useLocale';
import { useTheme } from '@/composables/useTheme';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

defineProps({
    pageTitle: { type: String, default: 'Overview' },
});

const { t } = useLocale();
useTheme();
const page = usePage();

const userName = computed(() => page.props.auth?.user?.name ?? 'Admin');
const flashSuccess = ref(page.props.flash?.success ?? null);
const mobileNavOpen = ref(false);

watch(
    () => page.props.flash?.success,
    (v) => {
        if (v) {
            flashSuccess.value = v;
        }
    },
);

watch(
    () => page.url,
    () => {
        closeMobileNav();
    },
);

watch(mobileNavOpen, (open) => {
    document.body.classList.toggle('overflow-hidden', open);
});

function onEscape(event) {
    if (event.key === 'Escape') {
        closeMobileNav();
    }
}

onMounted(() => {
    window.addEventListener('keydown', onEscape);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onEscape);
    document.body.classList.remove('overflow-hidden');
});

const navItems = computed(() => [
    { href: '/platform/dashboard', label: t('platform.nav_overview') },
    { href: '/platform/tenants', label: t('platform.nav_pharmacies') },
    { href: '/platform/plans', label: t('platform.nav_plans') },
    { href: '/platform/resellers', label: t('platform.nav_resellers') },
    { href: '/platform/master-catalog', label: t('platform.nav_master_catalog') },
    { href: '/platform/master-catalog/suggestions', label: t('platform.nav_master_suggestions') },
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

    // Keep master-catalog and suggestions nav items independent.
    if (prefix === '/platform/master-catalog') {
        return u === '/platform/master-catalog' || (u.startsWith('/platform/master-catalog/') && !u.startsWith('/platform/master-catalog/suggestions'))
            ? 'active bg-primary text-white'
            : '';
    }

    return u === prefix || u.startsWith(prefix + '/') ? 'active bg-primary text-white' : '';
}

function openMobileNav() {
    mobileNavOpen.value = true;
}

function closeMobileNav() {
    mobileNavOpen.value = false;
}
</script>

<style scoped>
.platform-shell {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: clip;
}

.platform-sidebar--desktop {
    width: 260px;
}

.platform-content,
.platform-main {
    width: 100%;
    max-width: 100%;
    min-width: 0;
}

.platform-main {
    overflow-x: clip;
}

.platform-sidebar .nav-link.active {
    color: #fff !important;
}

.platform-mobile-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1040;
    background: rgba(15, 23, 42, 0.42);
}

.platform-mobile-drawer {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 1045;
    display: flex;
    width: min(272px, 86vw);
    max-width: 86vw;
    visibility: hidden;
    pointer-events: none;
    transform: translateX(-100%);
    transition: transform 0.2s ease, visibility 0.2s ease;
}

.platform-mobile-drawer--open {
    visibility: visible;
    pointer-events: auto;
    transform: translateX(0);
}

.platform-menu-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.25rem;
    min-height: 2.25rem;
    padding: 0.3rem;
}

.platform-mobile-brand {
    display: flex;
    min-width: 0;
    flex-direction: column;
    line-height: 1.15;
}

.platform-mobile-brand__name,
.platform-mobile-brand__page {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.platform-mobile-brand__name {
    font-size: 0.92rem;
    font-weight: 700;
}

.platform-mobile-brand__page {
    color: var(--bs-secondary-color);
    font-size: 0.72rem;
}

@media (max-width: 991.98px) {
    .platform-topbar {
        position: sticky;
        top: 0;
        z-index: 1030;
    }
}

@media (max-width: 575.98px) {
    .platform-topbar {
        padding-right: 0.5rem !important;
        padding-left: 0.5rem !important;
    }

    .platform-main {
        padding: 0.75rem !important;
    }
}
</style>
