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
            class="platform-sidebar platform-mobile-drawer d-lg-none flex-column"
            :class="{ 'platform-mobile-drawer--open': mobileNavOpen }"
            :aria-hidden="mobileNavOpen ? 'false' : 'true'"
            :aria-label="t('common.menu', 'Menu')"
        >
            <div class="platform-sidebar-brand p-3 d-flex align-items-center justify-content-between gap-2">
                <Link href="/platform/dashboard" class="platform-brand-link platform-chrome-brand text-decoration-none min-w-0" @click="closeMobileNav">
                    <img class="platform-brand-logo" :src="brandLogo" :alt="brandName" />
                    <span class="platform-brand-text">
                        <span class="fw-bold d-block">{{ brandName }}</span>
                        <span class="platform-chrome-muted small">{{ brandSub }}</span>
                    </span>
                </Link>
                <button
                    type="button"
                    class="btn btn-sm platform-chrome-btn"
                    :aria-label="t('common.close', 'Close')"
                    @click="closeMobileNav"
                >
                    &times;
                </button>
            </div>
            <nav class="platform-nav flex-grow-1 overflow-auto p-2" :aria-label="t('common.menu', 'Menu')">
                <div v-for="section in navSections" :key="section.key" class="platform-nav-section">
                    <p class="platform-nav-section__label">{{ section.label }}</p>
                    <ul class="platform-nav-list">
                        <li v-for="item in section.items" :key="item.href">
                            <Link
                                :href="item.href"
                                class="platform-nav-link"
                                :class="{ 'is-active': isNavActive(item.href) }"
                                @click="closeMobileNav"
                            >
                                <span class="platform-nav-link__icon">
                                    <PlatformNavIcon :name="item.icon" />
                                </span>
                                <span class="platform-nav-link__label">{{ item.label }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>
        <aside class="platform-sidebar platform-sidebar--desktop d-none d-lg-flex flex-column flex-shrink-0">
            <div class="platform-sidebar-brand p-3">
                <Link href="/platform/dashboard" class="platform-brand-link platform-chrome-brand text-decoration-none">
                    <img class="platform-brand-logo" :src="brandLogo" :alt="brandName" />
                    <span class="platform-brand-text">
                        <span class="fw-bold d-block">{{ brandName }}</span>
                        <span class="platform-chrome-muted small">{{ brandSub }}</span>
                    </span>
                </Link>
            </div>
            <nav class="platform-nav flex-grow-1 overflow-auto p-2" :aria-label="t('common.menu', 'Menu')">
                <div v-for="section in navSections" :key="section.key" class="platform-nav-section">
                    <p class="platform-nav-section__label">{{ section.label }}</p>
                    <ul class="platform-nav-list">
                        <li v-for="item in section.items" :key="item.href">
                            <Link
                                :href="item.href"
                                class="platform-nav-link"
                                :class="{ 'is-active': isNavActive(item.href) }"
                            >
                                <span class="platform-nav-link__icon">
                                    <PlatformNavIcon :name="item.icon" />
                                </span>
                                <span class="platform-nav-link__label">{{ item.label }}</span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </nav>
        </aside>
        <div class="platform-content flex-grow-1 d-flex flex-column min-vh-100 min-w-0">
            <header class="platform-topbar px-3 py-2 d-flex flex-wrap align-items-center gap-2">
                <button
                    type="button"
                    class="btn btn-sm platform-chrome-btn d-lg-none platform-menu-btn"
                    :aria-label="t('common.menu', 'Menu')"
                    :aria-expanded="mobileNavOpen ? 'true' : 'false'"
                    aria-controls="platform-mobile-navigation"
                    @click="openMobileNav"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="platform-mobile-brand d-lg-none me-auto min-w-0 d-flex align-items-center gap-2">
                    <img class="platform-mobile-brand__icon" :src="brandIcon" alt="" />
                    <span class="min-w-0">
                        <span class="platform-mobile-brand__name">{{ brandName }}</span>
                        <span class="platform-mobile-brand__page">{{ pageTitle }}</span>
                    </span>
                </div>
                <h1 class="h5 mb-0 platform-topbar-title me-auto d-none d-lg-block">{{ pageTitle }}</h1>
                <div class="platform-bell dropdown">
                    <button
                        type="button"
                        class="btn btn-sm platform-chrome-btn platform-bell-btn position-relative"
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
                <span class="small platform-chrome-muted d-none d-md-inline">{{ userName }}</span>
                <Link href="/logout" method="post" as="button" class="btn btn-sm platform-chrome-btn">
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
import PlatformNavIcon from '@/Components/Platform/PlatformNavIcon.vue';
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

const branding = computed(() => page.props.branding || {});
const brandName = computed(() => branding.value.site_name || t('platform.brand'));
const brandSub = computed(() => branding.value.product_of || t('platform.brand_sub'));
const brandLogo = computed(() => branding.value.logo_url || '/brand/softopharma-logo.png');
const brandIcon = computed(() => branding.value.icon_url || '/brand/softopharma-icon.png');

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

const navSections = computed(() => {
    const sections = [
        {
            key: 'overview',
            label: t('platform.nav_group_overview'),
            items: [
                { href: '/platform/dashboard', label: t('platform.nav_overview'), icon: 'overview' },
            ],
        },
        {
            key: 'pipeline',
            label: t('platform.nav_group_pipeline'),
            items: [
                can('platform.leads') ? { href: '/platform/leads', label: t('platform.nav_leads'), icon: 'leads' } : null,
                can('platform.demos') ? { href: '/platform/demos', label: t('platform.nav_demos'), icon: 'demos' } : null,
                can('platform.payments.view') ? { href: '/platform/payments', label: t('platform.nav_payments'), icon: 'payments' } : null,
            ].filter(Boolean),
        },
        {
            key: 'pharmacies',
            label: t('platform.nav_group_pharmacies'),
            items: [
                (isSuper.value || can('platform.tenants.create'))
                    ? { href: '/platform/tenants', label: t('platform.nav_pharmacies'), icon: 'pharmacies' }
                    : null,
                isSuper.value ? { href: '/platform/plans', label: t('platform.nav_plans'), icon: 'plans' } : null,
                isSuper.value ? { href: '/platform/resellers', label: t('platform.nav_resellers'), icon: 'resellers' } : null,
            ].filter(Boolean),
        },
        {
            key: 'catalog',
            label: t('platform.nav_group_catalog'),
            items: [
                isSuper.value ? { href: '/platform/master-catalog', label: t('platform.nav_master_catalog'), icon: 'master' } : null,
                isSuper.value ? { href: '/platform/master-catalog/suggestions', label: t('platform.nav_master_suggestions'), icon: 'suggestions' } : null,
                isSuper.value ? { href: '/platform/catalog-templates', label: t('platform.nav_catalog'), icon: 'templates' } : null,
                isSuper.value ? { href: '/platform/product-types', label: t('platform.nav_product_types'), icon: 'product-types' } : null,
            ].filter(Boolean),
        },
        {
            key: 'ops',
            label: t('platform.nav_group_ops'),
            items: [
                isSuper.value ? { href: '/platform/announcements', label: t('platform.nav_announcements'), icon: 'announcements' } : null,
                can('platform.payments.manage') ? { href: '/platform/billing', label: t('platform.nav_billing'), icon: 'billing' } : null,
                { href: '/platform/notifications', label: t('platform.nav_notifications'), icon: 'notifications' },
            ].filter(Boolean),
        },
        {
            key: 'system',
            label: t('platform.nav_group_system'),
            items: [
                isSuper.value ? { href: '/platform/admins', label: t('platform.nav_admins'), icon: 'admins' } : null,
                isSuper.value ? { href: '/platform/audit', label: t('platform.nav_audit'), icon: 'audit' } : null,
                isSuper.value ? { href: '/platform/health', label: t('platform.nav_health'), icon: 'health' } : null,
                isSuper.value ? { href: '/platform/marketing-settings', label: t('platform.nav_marketing_settings'), icon: 'marketing' } : null,
                isSuper.value ? { href: '/platform/settings', label: t('platform.nav_settings'), icon: 'settings' } : null,
            ].filter(Boolean),
        },
    ];

    return sections.filter((section) => section.items.length > 0);
});

function pathNow() {
    return (page.url || '').split('?')[0];
}

function isNavActive(prefix) {
    const u = pathNow();
    if (prefix === '/platform/dashboard') {
        return u === '/platform/dashboard';
    }

    if (prefix === '/platform/settings') {
        return u === '/platform/settings';
    }

    // Keep master-catalog and suggestions nav items independent.
    if (prefix === '/platform/master-catalog') {
        return u === '/platform/master-catalog'
            || (u.startsWith('/platform/master-catalog/') && !u.startsWith('/platform/master-catalog/suggestions'));
    }

    return u === prefix || u.startsWith(`${prefix}/`);
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
    --platform-chrome-bg: #163f34;
    --platform-chrome-bg: color-mix(in srgb, var(--bs-primary) 34%, #0a1a16 66%);
    --platform-chrome-fg: rgba(255, 255, 255, 0.94);
    --platform-chrome-muted: rgba(255, 255, 255, 0.58);
    --platform-chrome-border: rgba(255, 255, 255, 0.12);
    --platform-chrome-surface: rgba(255, 255, 255, 0.1);
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: clip;
}

.platform-sidebar {
    background: var(--platform-chrome-bg);
    color: var(--platform-chrome-fg);
    border-right: 1px solid var(--platform-chrome-border);
}

.platform-sidebar--desktop {
    width: 276px;
}

.platform-sidebar-brand {
    border-bottom: 1px solid var(--platform-chrome-border);
}

.platform-chrome-brand {
    color: var(--platform-chrome-fg);
}

.platform-chrome-muted {
    color: var(--platform-chrome-muted) !important;
}

.platform-chrome-btn {
    color: var(--platform-chrome-fg) !important;
    background: transparent;
    border: 1px solid var(--platform-chrome-border);
}

.platform-chrome-btn:hover,
.platform-chrome-btn:focus {
    color: #fff !important;
    background: var(--platform-chrome-surface);
    border-color: rgba(255, 255, 255, 0.28);
}

.platform-topbar {
    background: var(--platform-chrome-bg);
    color: var(--platform-chrome-fg);
    border-bottom: 1px solid var(--platform-chrome-border);
}

.platform-topbar-title {
    color: var(--platform-chrome-fg);
}

.platform-nav {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
}

.platform-nav-section__label {
    margin: 0 0 0.35rem;
    padding: 0 0.65rem;
    color: var(--platform-chrome-muted);
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.platform-nav-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.platform-nav-link {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-height: 2.45rem;
    padding: 0.45rem 0.7rem;
    border-radius: 0.65rem;
    color: rgba(255, 255, 255, 0.78);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.25;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.platform-nav-link:hover {
    background: var(--platform-chrome-surface);
    color: #fff;
}

.platform-nav-link.is-active {
    background: color-mix(in srgb, var(--bs-primary) 72%, #ffffff 28%) !important;
    color: #fff !important;
}

.platform-nav-link__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.15rem;
    height: 1.15rem;
    flex: 0 0 auto;
    opacity: 0.92;
}

.platform-nav-link__icon :deep(svg) {
    width: 100%;
    height: 100%;
}

.platform-nav-link__label {
    min-width: 0;
}

.platform-brand-link {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    min-width: 0;
}

.platform-brand-logo {
    display: block;
    height: 2.35rem;
    width: auto;
    max-width: 8.5rem;
    object-fit: contain;
    flex-shrink: 0;
}

.platform-brand-text {
    min-width: 0;
    line-height: 1.2;
}

.platform-mobile-brand__icon {
    width: 1.75rem;
    height: 1.75rem;
    object-fit: contain;
    flex-shrink: 0;
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

.platform-mobile-brand {
    color: var(--platform-chrome-fg);
}

.platform-mobile-brand__page {
    color: var(--platform-chrome-muted);
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
