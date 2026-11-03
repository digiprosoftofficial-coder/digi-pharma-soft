<template>
    <div class="tenant-shell d-flex flex-column min-vh-100 bg-light">
        <div
            v-if="impersonation?.active"
            class="alert alert-warning border-0 rounded-0 mb-0 small py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2"
        >
            <span>
                {{ t('platform.impersonation_banner', { name: impersonation.tenant_name, user: impersonation.acting_as }) }}
            </span>
            <Link :href="impersonation.stop_url" method="post" as="button" class="btn btn-sm btn-dark">
                {{ t('platform.impersonation_stop') }}
            </Link>
        </div>
        <div
            v-if="networkAnnouncement"
            class="border-0 rounded-0 mb-0 small py-2 px-3"
            :class="announcementAlertClass"
            role="alert"
        >
            <strong>{{ networkAnnouncement.title }}</strong>
            <span class="ms-1">{{ networkAnnouncement.body }}</span>
        </div>
        <div
            v-if="mobileNavOpen"
            class="tenant-mobile-backdrop d-lg-none"
            aria-hidden="true"
            @click="closeMobileNav"
        ></div>
        <aside
            id="tenant-mobile-navigation"
            class="tenant-sidebar tenant-mobile-drawer border-end bg-white d-lg-none flex-column"
            :class="{ 'tenant-mobile-drawer--open': mobileNavOpen }"
            :aria-hidden="mobileNavOpen ? 'false' : 'true'"
            aria-label="Mobile navigation"
        >
            <div class="tenant-sidebar-brand p-3 border-bottom">
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <Link href="/dashboard" class="text-decoration-none text-dark d-flex align-items-center gap-2 min-w-0" @click="closeMobileNav">
                        <span class="tenant-sidebar-brand__icon text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path d="M12 3 4 7v6c0 4.4 3.6 8 8 8s8-3.6 8-8V7z" />
                                <path d="M12 11v4M12 8h.01" />
                            </svg>
                        </span>
                        <span class="min-w-0">
                            <div class="fw-bold lh-sm text-truncate">{{ tenantName }}</div>
                            <div class="small text-muted">{{ t('tenant_nav.pharmacy_mgmt') }}</div>
                        </span>
                    </Link>
                    <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" :aria-label="t('common.close', 'Close')" @click="closeMobileNav">
                        &times;
                    </button>
                </div>
                <form
                    v-if="showBranchSwitcher"
                    class="tenant-sidebar-branch mt-2"
                    @submit.prevent="switchBranch"
                >
                    <label class="tenant-sidebar-branch__label" :for="mobileBranchSelectId">{{ t('branches.switch_branch') }}</label>
                    <div class="tenant-sidebar-branch__control">
                        <span class="tenant-sidebar-branch__pin" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                                <circle cx="12" cy="10" r="3" />
                            </svg>
                        </span>
                        <select
                            :id="mobileBranchSelectId"
                            v-model="activeBranchId"
                            class="tenant-sidebar-branch__select"
                            @change="switchBranch"
                        >
                            <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <span class="tenant-sidebar-branch__caret" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </span>
                    </div>
                </form>
            </div>
            <TenantSidebarNav @navigate="closeMobileNav" />
        </aside>
        <div class="d-flex flex-grow-1 min-h-0 min-w-0">
            <aside class="tenant-sidebar tenant-sidebar--desktop border-end bg-white d-none d-lg-flex flex-column flex-shrink-0">
                <div class="tenant-sidebar-brand p-3 border-bottom">
                    <Link href="/dashboard" class="text-decoration-none text-dark d-flex align-items-center gap-2 min-w-0">
                        <span class="tenant-sidebar-brand__icon text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path d="M12 3 4 7v6c0 4.4 3.6 8 8 8s8-3.6 8-8V7z" />
                                <path d="M12 11v4M12 8h.01" />
                            </svg>
                        </span>
                        <span class="min-w-0">
                            <div class="fw-bold lh-sm text-truncate">{{ tenantName }}</div>
                            <div class="small text-muted">{{ t('tenant_nav.pharmacy_mgmt') }}</div>
                        </span>
                    </Link>
                </div>
                <TenantSidebarNav />
            </aside>
            <div class="tenant-content flex-grow-1 d-flex flex-column min-vh-100 min-w-0">
                <header class="tenant-topbar border-bottom bg-white px-3 py-2 d-flex flex-wrap align-items-center gap-2">
                    <Link href="/dashboard" class="tenant-mobile-brand d-lg-none text-decoration-none text-dark me-auto">
                        <span class="tenant-mobile-brand__icon text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path d="M12 3 4 7v6c0 4.4 3.6 8 8 8s8-3.6 8-8V7z" />
                                <path d="M12 11v4M12 8h.01" />
                            </svg>
                        </span>
                        <span class="tenant-mobile-brand__text">
                            <span class="tenant-mobile-brand__name">{{ tenantName }}</span>
                            <span class="tenant-mobile-brand__page">{{ pageTitle }}</span>
                        </span>
                    </Link>
                    <form
                        v-if="showBranchSwitcher"
                        class="tenant-topbar-branch me-auto d-none d-lg-flex"
                        @submit.prevent="switchBranch"
                    >
                        <label class="tenant-sidebar-branch__label" :for="desktopBranchSelectId">{{ t('branches.switch_branch') }}</label>
                        <div class="tenant-topbar-branch__row">
                            <span
                                class="tenant-topbar-branch__dot"
                                :title="t('branches.active')"
                                :aria-label="t('branches.active')"
                            ></span>
                            <div class="tenant-sidebar-branch__control">
                                <span class="tenant-sidebar-branch__pin" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" />
                                        <circle cx="12" cy="10" r="3" />
                                    </svg>
                                </span>
                                <select
                                    :id="desktopBranchSelectId"
                                    v-model="activeBranchId"
                                    class="tenant-sidebar-branch__select"
                                    @change="switchBranch"
                                >
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                                <span class="tenant-sidebar-branch__caret" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </form>
                    <h1
                        v-else
                        class="h5 mb-0 text-primary me-auto d-none d-lg-block"
                    >
                        {{ pageTitle }}
                    </h1>
                    <h1 v-if="showBranchSwitcher" class="visually-hidden">{{ pageTitle }}</h1>

                    <!-- Desktop: inline product search -->
                    <form
                        class="topbar-search d-none d-lg-block flex-grow-1 position-relative"
                        style="max-width: 390px"
                        @submit.prevent="onSearchEnter()"
                    >
                        <div class="input-group input-group-sm">
                            <span class="input-group-text topbar-search__scope">{{ t('common.search_scope_products') }}</span>
                            <input
                                v-model="searchQ"
                                type="search"
                                class="form-control"
                                :placeholder="t('common.search_products_placeholder')"
                                autocomplete="off"
                                @input="debouncedProductSearch"
                                @focus="openSearchSuggestions"
                                @blur="closeSearchSuggestionsSoon"
                                @keydown.down.prevent="moveSearchHighlight(1)"
                                @keydown.up.prevent="moveSearchHighlight(-1)"
                                @keydown.enter.prevent="onSearchEnter()"
                                @keydown.esc.prevent="closeSearchSuggestions"
                            />
                            <button class="btn btn-outline-secondary topbar-search__button" type="submit" :title="t('common.search')" :aria-label="t('common.search')">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="11" cy="11" r="7" />
                                    <path d="m20 20-3.5-3.5" />
                                </svg>
                            </button>
                        </div>
                        <div
                            v-if="showSearchDropdown"
                            class="topbar-search__dropdown bg-white border rounded-3 shadow-sm overflow-hidden"
                        >
                            <div v-if="searchLoading" class="px-3 py-2 small text-muted">{{ t('common.searching') }}</div>
                            <template v-else-if="searchResults.length">
                                <button
                                    v-for="(product, index) in searchResults"
                                    :key="product.id"
                                    type="button"
                                    class="topbar-search__item w-100 border-0 bg-white text-start px-3 py-2"
                                    :class="{ 'topbar-search__item--active': index === highlightedSearchIndex }"
                                    @mousedown.prevent="selectSearchResult(product)"
                                >
                                    <span class="d-block fw-semibold text-truncate">{{ product.name }}</span>
                                    <span class="d-flex flex-wrap gap-2 small text-muted">
                                        <span v-if="product.sku">{{ product.sku }}</span>
                                        <span v-if="product.barcode">{{ product.barcode }}</span>
                                        <span>{{ t('common.stock') }}: {{ product.stock_on_hand ?? '0' }}</span>
                                    </span>
                                    <span class="d-block small text-primary mt-1">{{ t('common.open_product') }}</span>
                                </button>
                                <button
                                    type="button"
                                    class="topbar-search__item w-100 border-0 bg-light text-start px-3 py-2 small fw-semibold text-primary"
                                    @mousedown.prevent="runSearch()"
                                >
                                    {{ t('common.view_all_matching_products') }}
                                </button>
                            </template>
                            <div v-else-if="searchQ.trim().length >= 1" class="px-3 py-2 small text-muted">{{ t('common.no_results') }}</div>
                        </div>
                    </form>

                    <!-- Mobile: search icon → overlay (when floating smart search is off) -->
                    <button
                        v-if="!smartSearchEnabled"
                        type="button"
                        class="btn btn-sm btn-outline-secondary tenant-topbar-search-btn d-lg-none"
                        :aria-label="t('common.smart_search_title')"
                        :title="t('common.smart_search_title')"
                        @click="mobileSearchOpen = true"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                    </button>
                    <Link
                        v-if="pharmacyNotesEnabled && can('notes.view')"
                        href="/notes"
                        class="btn btn-sm tenant-topbar-notes-btn d-lg-none"
                        :class="notesActive ? 'btn-primary' : 'btn-outline-secondary'"
                        :aria-label="t('tenant_nav.notes')"
                        :title="t('tenant_nav.notes')"
                    >
                        <TenantNavIcon name="notes" />
                    </Link>
                    <div class="tenant-topbar-actions d-flex align-items-center gap-2">
                        <Link
                            v-if="can('products.view')"
                            href="/products"
                            class="btn btn-sm btn-outline-primary tenant-topbar-action d-none d-lg-inline-flex"
                        >
                            {{ t('tenant_nav.product_list') }}
                        </Link>
                        <Link v-if="can('pos.access')" href="/pos" class="btn btn-sm btn-primary tenant-topbar-action d-none d-lg-inline-flex">{{ t('tenant_nav.new_sale') }}</Link>
                        <span class="small text-muted d-none d-md-inline">{{ userName }}</span>
                        <Link href="/logout" method="post" as="button" class="btn btn-sm btn-outline-secondary tenant-topbar-action">{{ t('common.logout') }}</Link>
                    </div>
                </header>
                <main class="tenant-main flex-grow-1 p-2 p-md-4 min-w-0">
                    <slot />
                </main>
            </div>
        </div>
        <TenantBottomNav @open-more="openMobileNav" />
        <ProductSearchOverlay v-model:open="mobileSearchOpen" />
        <SmartSearchFab />
        <AppMessageModal />
        <FlashToasts />
    </div>
</template>

<script setup>
import AppMessageModal from '@/Components/AppMessageModal.vue';
import FlashToasts from '@/Components/FlashToasts.vue';
import ProductSearchOverlay from '@/Components/Tenant/ProductSearchOverlay.vue';
import SmartSearchFab from '@/Components/Tenant/SmartSearchFab.vue';
import TenantBottomNav from '@/Components/Tenant/TenantBottomNav.vue';
import TenantNavIcon from '@/Components/Tenant/TenantNavIcon.vue';
import TenantSidebarNav from '@/Components/Tenant/TenantSidebarNav.vue';
import { useLocale } from '@/composables/useLocale';
import { usePermissions } from '@/composables/usePermissions';
import { useProductSearch } from '@/composables/useProductSearch';
import { useTheme } from '@/composables/useTheme';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const { t } = useLocale();
useTheme();

defineProps({
    pageTitle: { type: String, default: 'Dashboard' },
});

const page = usePage();
const { can } = usePermissions();

const tenantName = computed(() => page.props.tenant?.name ?? 'Pharmacy');
const userName = computed(() => page.props.auth?.user?.name ?? 'User');
const impersonation = computed(() => page.props.impersonation);
const networkAnnouncement = computed(() => page.props.networkAnnouncement);
const multiBranch = computed(() => page.props.features?.multi_branch ?? false);
const smartSearchEnabled = computed(() => page.props.features?.smart_search ?? true);
const pharmacyNotesEnabled = computed(() => page.props.features?.pharmacy_notes ?? false);
const notesActive = computed(() => {
    const path = (page.url || '').split('?')[0];
    return path === '/notes' || path.startsWith('/notes/');
});
const branches = computed(() => page.props.branches ?? []);
const showBranchSwitcher = computed(() => multiBranch.value && branches.value.length > 1);
const desktopBranchSelectId = 'tenant-desktop-branch-select';
const mobileBranchSelectId = 'tenant-mobile-branch-select';
const activeBranchId = ref(page.props.branch?.id ?? null);
const mobileNavOpen = ref(false);
const mobileSearchOpen = ref(false);

const {
    searchQ,
    searchResults,
    searchLoading,
    highlightedSearchIndex,
    showSearchDropdown,
    debouncedProductSearch,
    openSearchSuggestions,
    closeSearchSuggestionsSoon,
    closeSearchSuggestions,
    moveSearchHighlight,
    onSearchEnter,
    selectSearchResult,
    runSearch,
} = useProductSearch();

watch(
    () => page.props.branch?.id,
    (id) => {
        activeBranchId.value = id ?? null;
    },
);

watch(
    () => page.url,
    () => {
        closeMobileNav();
        mobileSearchOpen.value = false;
    },
);

const branchSwitchForm = useForm({ branch_id: null });

function switchBranch() {
    branchSwitchForm.branch_id = activeBranchId.value;
    branchSwitchForm.post('/branches/switch', { preserveScroll: true });
}

function openMobileNav() {
    mobileNavOpen.value = true;
}

function closeMobileNav() {
    mobileNavOpen.value = false;
}

const announcementAlertClass = computed(() => {
    const severity = networkAnnouncement.value?.severity ?? 'info';

    return {
        info: 'alert alert-info',
        warning: 'alert alert-warning',
        danger: 'alert alert-danger',
    }[severity] ?? 'alert alert-info';
});
</script>

<style scoped>
.tenant-shell {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: clip;
}

.tenant-sidebar {
    width: 272px;
}

.tenant-content {
    width: 100%;
    max-width: 100%;
    min-width: 0;
}

.tenant-main {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: clip;
    overflow-y: auto;
}

.tenant-mobile-backdrop {
    position: fixed;
    inset: 0;
    z-index: 1040;
    background: rgba(15, 23, 42, 0.42);
}

.tenant-mobile-drawer {
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

.tenant-mobile-drawer--open {
    visibility: visible;
    pointer-events: auto;
    transform: translateX(0);
}

.tenant-sidebar-brand__icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.5rem;
    background: rgba(var(--bs-primary-rgb), 0.1);
}

.tenant-mobile-brand {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    min-width: 0;
}

.tenant-mobile-brand__icon {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 0.5rem;
    background: rgba(var(--bs-primary-rgb), 0.1);
}

.tenant-mobile-brand__text {
    display: flex;
    min-width: 0;
    flex-direction: column;
    line-height: 1.1;
}

.tenant-mobile-brand__name,
.tenant-mobile-brand__page {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.tenant-mobile-brand__name {
    max-width: 42vw;
    font-size: 0.92rem;
    font-weight: 700;
}

.tenant-mobile-brand__page {
    max-width: 42vw;
    color: var(--bs-secondary-color);
    font-size: 0.72rem;
}

.topbar-search__scope {
    color: var(--bs-primary);
    background: rgba(var(--bs-primary-rgb), 0.08);
    border-color: var(--bs-border-color);
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}

.topbar-search__dropdown {
    position: absolute;
    top: calc(100% + 0.35rem);
    right: 0;
    left: 0;
    z-index: 1040;
    max-height: 22rem;
    overflow-y: auto;
}

.topbar-search :deep(.form-control:focus) {
    border-color: var(--bs-border-color);
    box-shadow: none;
    outline: 0;
}

.topbar-search :deep(.btn:focus),
.topbar-search :deep(.btn:active),
.topbar-search :deep(.btn:focus-visible) {
    box-shadow: none;
    outline: 0;
}

.topbar-search__button {
    color: var(--bs-secondary-color);
    border-color: var(--bs-border-color);
}

.topbar-search__button:hover {
    color: var(--bs-body-color);
    background: var(--bs-tertiary-bg);
    border-color: var(--bs-border-color);
}

.topbar-search__item {
    transition: background-color 0.12s ease;
}

.topbar-search__item:hover,
.topbar-search__item--active {
    background: var(--bs-primary-bg-subtle) !important;
}

.tenant-topbar-search-btn,
.tenant-topbar-notes-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2.25rem;
    min-height: 2.25rem;
    padding: 0.3rem;
}
.tenant-topbar-notes-btn :deep(.tenant-nav-icon) {
    width: 16px;
    height: 16px;
}

.tenant-topbar {
    width: 100%;
    max-width: 100%;
    min-width: 0;
}

.tenant-sidebar-branch,
.tenant-topbar-branch {
    min-width: 0;
}

.tenant-topbar-branch {
    flex: 0 1 15.5rem;
    flex-direction: column;
    justify-content: center;
    max-width: 15.5rem;
}

.tenant-topbar-branch__row {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    min-width: 0;
}

.tenant-topbar-branch__row .tenant-sidebar-branch__control {
    flex: 1 1 auto;
    min-width: 0;
}

.tenant-topbar-branch__dot {
    flex: 0 0 auto;
    width: 0.55rem;
    height: 0.55rem;
    border-radius: 999px;
    background: #22c55e;
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.45);
    animation: tenant-branch-dot-pulse 1.6s ease-out infinite;
}

@keyframes tenant-branch-dot-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.45);
        transform: scale(1);
    }

    70% {
        box-shadow: 0 0 0 0.45rem rgba(34, 197, 94, 0);
        transform: scale(1.05);
    }

    100% {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0);
        transform: scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .tenant-topbar-branch__dot {
        animation: none;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.18);
    }
}

.tenant-sidebar-branch__label {
    display: block;
    margin-bottom: 0.35rem;
    color: #94a3b8;
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    line-height: 1.1;
}

.tenant-topbar-branch .tenant-sidebar-branch__label {
    margin-bottom: 0.15rem;
}

.tenant-topbar-branch .tenant-sidebar-branch__select {
    min-height: 31px;
    height: 31px;
    padding: 0.2rem 1.75rem 0.2rem 1.85rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    line-height: 1.25;
}

.tenant-topbar-branch .tenant-sidebar-branch__pin {
    left: 0.55rem;
}

.tenant-topbar-branch .tenant-sidebar-branch__pin svg,
.tenant-topbar-branch .tenant-sidebar-branch__caret svg {
    width: 12px;
    height: 12px;
}

.tenant-topbar-branch .tenant-sidebar-branch__caret {
    right: 0.5rem;
}

.tenant-topbar-branch__dot {
    width: 0.45rem;
    height: 0.45rem;
}

.tenant-topbar-actions .tenant-topbar-action,
.topbar-search .input-group-sm > .form-control,
.topbar-search .input-group-sm > .input-group-text,
.topbar-search .input-group-sm > .btn {
    min-height: 31px;
}

@media (min-width: 992px) {
    .tenant-sidebar--desktop .tenant-sidebar-brand,
    .tenant-topbar {
        box-sizing: border-box;
        min-height: 4.75rem;
    }

    .tenant-sidebar--desktop .tenant-sidebar-brand {
        display: flex;
        align-items: center;
    }

    .tenant-topbar {
        padding-top: 0.65rem;
        padding-bottom: 0.65rem;
    }
}

.tenant-sidebar-branch__control {
    position: relative;
    display: flex;
    align-items: center;
    min-width: 0;
}

.tenant-sidebar-branch__pin,
.tenant-sidebar-branch__caret {
    position: absolute;
    top: 50%;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    pointer-events: none;
    transform: translateY(-50%);
}

.tenant-sidebar-branch__pin {
    left: 0.7rem;
}

.tenant-sidebar-branch__caret {
    right: 0.65rem;
}

.tenant-sidebar-branch__select {
    width: 100%;
    min-width: 0;
    appearance: none;
    -webkit-appearance: none;
    margin: 0;
    padding: 0.55rem 2rem 0.55rem 2.15rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.7rem;
    background-color: #fff;
    background-image: none;
    box-shadow: none;
    color: #0f172a;
    font-size: 0.88rem;
    font-weight: 500;
    line-height: 1.2;
    cursor: pointer;
}

.tenant-sidebar-branch__select:hover {
    border-color: #cbd5e1;
}

.tenant-sidebar-branch__select:focus {
    border-color: rgba(var(--bs-primary-rgb), 0.55);
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.12);
}

@media (max-width: 991.98px) {
    .tenant-main {
        padding-bottom: calc(4.75rem + env(safe-area-inset-bottom, 0px)) !important;
    }

    .tenant-topbar {
        position: sticky;
        top: 0;
        z-index: 1030;
    }

    .tenant-topbar .h5 {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}

@media (max-width: 575.98px) {
    .tenant-topbar {
        padding-right: 0.5rem !important;
        padding-left: 0.5rem !important;
    }

    .tenant-topbar-action {
        min-height: 2.25rem;
    }

    .tenant-topbar-action {
        padding-right: 0.55rem;
        padding-left: 0.55rem;
        font-size: 0.78rem;
        white-space: nowrap;
    }

    .tenant-mobile-brand__name,
    .tenant-mobile-brand__page {
        max-width: 36vw;
    }
}
</style>
