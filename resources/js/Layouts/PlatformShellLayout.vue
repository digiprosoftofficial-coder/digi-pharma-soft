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
                <div class="platform-bell dropdown">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary platform-bell-btn position-relative"
                        :aria-expanded="bellOpen ? 'true' : 'false'"
                        :aria-label="t('platform.nav_notifications')"
                        @click="toggleBell"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9" />
                            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0" />
                        </svg>
                        <span
                            v-if="unreadCount > 0"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger"
                        >
                            {{ unreadCount > 99 ? '99+' : unreadCount }}
                        </span>
                    </button>
                    <div v-if="bellOpen" class="platform-bell-menu dropdown-menu dropdown-menu-end show shadow">
                        <div class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>{{ t('platform.notifications_title') }}</span>
                            <button
                                v-if="unreadCount > 0"
                                type="button"
                                class="btn btn-link btn-sm p-0"
                                @click="markAllRead"
                            >
                                {{ t('platform.mark_all_read') }}
                            </button>
                        </div>
                        <div v-if="!recentNotifications.length" class="px-3 py-3 small text-muted">
                            {{ t('platform.no_notifications') }}
                        </div>
                        <button
                            v-for="n in recentNotifications"
                            :key="n.id"
                            type="button"
                            class="dropdown-item platform-bell-item"
                            :class="{ 'fw-semibold': !n.read_at }"
                            @click="openNotification(n)"
                        >
                            <div class="small">{{ n.title || t('platform.nav_notifications') }}</div>
                            <div class="small text-muted text-wrap">{{ n.body }}</div>
                            <div v-if="n.created_at" class="small text-muted">{{ formatWhen(n.created_at) }}</div>
                        </button>
                        <div class="dropdown-divider"></div>
                        <Link href="/platform/notifications" class="dropdown-item text-center small" @click="bellOpen = false">
                            {{ t('platform.view_all_notifications') }}
                        </Link>
                    </div>
                </div>
                <span class="small text-muted d-none d-md-inline">{{ userName }}</span>
                <Link href="/logout" method="post" as="button" class="btn btn-sm btn-outline-secondary">
                    {{ t('common.logout') }}
                </Link>
            </header>
            <main class="platform-main flex-grow-1 p-3 p-md-4 overflow-auto">
                <slot />
            </main>
        </div>
        <AppMessageModal />
        <FlashToasts />
    </div>
</template>

<script setup>
import AppMessageModal from '@/Components/AppMessageModal.vue';
import FlashToasts from '@/Components/FlashToasts.vue';
import { useLocale } from '@/composables/useLocale';
import { useTheme } from '@/composables/useTheme';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

defineProps({
    pageTitle: { type: String, default: 'Overview' },
});

const { t } = useLocale();
useTheme();
const page = usePage();

const userName = computed(() => page.props.auth?.user?.name ?? 'Admin');
const mobileNavOpen = ref(false);
const bellOpen = ref(false);

const unreadCount = computed(() => Number(page.props.platformNotifications?.unread_count || 0));
const recentNotifications = computed(() => page.props.platformNotifications?.recent || []);

watch(
    () => page.url,
    () => {
        closeMobileNav();
        bellOpen.value = false;
    },
);

watch(mobileNavOpen, (open) => {
    document.body.classList.toggle('overflow-hidden', open);
});

function onEscape(event) {
    if (event.key === 'Escape') {
        closeMobileNav();
        bellOpen.value = false;
    }
}

function onDocumentClick(event) {
    const root = event.target?.closest?.('.platform-bell');
    if (!root) {
        bellOpen.value = false;
    }
}

onMounted(() => {
    window.addEventListener('keydown', onEscape);
    document.addEventListener('click', onDocumentClick);
});

onUnmounted(() => {
    window.removeEventListener('keydown', onEscape);
    document.removeEventListener('click', onDocumentClick);
    document.body.classList.remove('overflow-hidden');
});

function toggleBell() {
    bellOpen.value = !bellOpen.value;
}

function formatWhen(value) {
    if (!value) {
        return '';
    }
    return new Date(value).toLocaleString();
}

function markAllRead() {
    router.post('/platform/notifications/read-all', {}, { preserveScroll: true });
}

function openNotification(notification) {
    const go = () => {
        bellOpen.value = false;
        if (notification.url) {
            router.visit(notification.url);
        } else {
            router.visit('/platform/notifications');
        }
    };

    if (!notification.read_at) {
        router.post(`/platform/notifications/${notification.id}/read`, {}, {
            preserveScroll: true,
            onFinish: go,
        });
        return;
    }

    go();
}

const permissions = computed(() => page.props.auth?.user?.permissions || []);
const isSuper = computed(() => (page.props.auth?.user?.roles || []).includes('super admin') || page.props.auth?.user?.is_platform_super_admin);

function can(perm) {
    return isSuper.value || permissions.value.includes(perm);
}

const navItems = computed(() => [
    { href: '/platform/dashboard', label: t('platform.nav_overview') },
    can('platform.leads') ? { href: '/platform/leads', label: t('platform.nav_leads') } : null,
    can('platform.demos') ? { href: '/platform/demos', label: t('platform.nav_demos') } : null,
    can('platform.payments.view') ? { href: '/platform/payments', label: t('platform.nav_payments') } : null,
    isSuper.value ? { href: '/platform/tenants', label: t('platform.nav_pharmacies') } : null,
    isSuper.value ? { href: '/platform/plans', label: t('platform.nav_plans') } : null,
    isSuper.value ? { href: '/platform/resellers', label: t('platform.nav_resellers') } : null,
    isSuper.value ? { href: '/platform/master-catalog', label: t('platform.nav_master_catalog') } : null,
    isSuper.value ? { href: '/platform/master-catalog/suggestions', label: t('platform.nav_master_suggestions') } : null,
    isSuper.value ? { href: '/platform/catalog-templates', label: t('platform.nav_catalog') } : null,
    isSuper.value ? { href: '/platform/product-types', label: t('platform.nav_product_types') } : null,
    isSuper.value ? { href: '/platform/announcements', label: t('platform.nav_announcements') } : null,
    isSuper.value ? { href: '/platform/billing', label: t('platform.nav_billing') } : null,
    { href: '/platform/notifications', label: t('platform.nav_notifications') },
    isSuper.value ? { href: '/platform/admins', label: t('platform.nav_admins') } : null,
    isSuper.value ? { href: '/platform/audit', label: t('platform.nav_audit') } : null,
    isSuper.value ? { href: '/platform/health', label: t('platform.nav_health') } : null,
    isSuper.value ? { href: '/platform/settings', label: t('platform.nav_settings') } : null,
].filter(Boolean));

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

.platform-bell {
    position: relative;
}

.platform-bell-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.25rem;
    min-height: 2.25rem;
    padding: 0.3rem;
}

.platform-bell-menu {
    position: absolute;
    top: calc(100% + 0.35rem);
    right: 0;
    z-index: 1050;
    width: min(360px, 86vw);
    max-height: min(70vh, 420px);
    overflow: auto;
    display: block;
}

.platform-bell-item {
    white-space: normal;
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
