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
        <div class="d-flex flex-grow-1 min-h-0">
            <aside class="tenant-sidebar border-end bg-white d-flex flex-column flex-shrink-0">
                <div class="tenant-sidebar-brand p-3 border-bottom">
                    <Link href="/dashboard" class="text-decoration-none text-dark d-flex align-items-center gap-2">
                        <span class="tenant-sidebar-brand__icon text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                <path d="M12 3 4 7v6c0 4.4 3.6 8 8 8s8-3.6 8-8V7z" />
                                <path d="M12 11v4M12 8h.01" />
                            </svg>
                        </span>
                        <span>
                            <div class="fw-bold lh-sm">{{ tenantName }}</div>
                            <div class="small text-muted">{{ t('tenant_nav.pharmacy_mgmt') }}</div>
                        </span>
                    </Link>
                </div>
                <TenantSidebarNav />
            </aside>
            <div class="flex-grow-1 d-flex flex-column min-vh-100 min-w-0">
                <header class="tenant-topbar border-bottom bg-white px-3 py-2 d-flex flex-wrap align-items-center gap-2">
                    <h1 class="h5 mb-0 text-primary me-auto">{{ pageTitle }}</h1>
                    <form class="flex-grow-1" style="max-width: 320px" @submit.prevent="runSearch">
                        <input v-model="searchQ" type="search" class="form-control form-control-sm" placeholder="Search products…" />
                    </form>
                    <Link v-if="can('pos.access')" href="/pos" class="btn btn-sm btn-primary">{{ t('tenant_nav.new_sale') }}</Link>
                    <LocaleSwitcher />
                    <span class="small text-muted d-none d-md-inline">{{ userName }}</span>
                    <Link href="/logout" method="post" as="button" class="btn btn-sm btn-outline-secondary">{{ t('common.logout') }}</Link>
                </header>
                <main class="flex-grow-1 p-3 p-md-4 overflow-auto">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import TenantSidebarNav from '@/Components/Tenant/TenantSidebarNav.vue';
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import { useLocale } from '@/composables/useLocale';
import { usePermissions } from '@/composables/usePermissions';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const { t } = useLocale();

defineProps({
    pageTitle: { type: String, default: 'Dashboard' },
});

const page = usePage();
const { can } = usePermissions();

const tenantName = computed(() => page.props.tenant?.name ?? 'Pharmacy');
const userName = computed(() => page.props.auth?.user?.name ?? 'User');
const impersonation = computed(() => page.props.impersonation);
const networkAnnouncement = computed(() => page.props.networkAnnouncement);

const announcementAlertClass = computed(() => {
    const severity = networkAnnouncement.value?.severity ?? 'info';

    return {
        info: 'alert alert-info',
        warning: 'alert alert-warning',
        danger: 'alert alert-danger',
    }[severity] ?? 'alert alert-info';
});

const searchQ = ref('');
function runSearch() {
    const q = searchQ.value?.trim();
    if (!q) {
        router.visit('/products');
        return;
    }
    router.visit('/products', { data: { q }, preserveState: true });
}
</script>

<style scoped>
.tenant-sidebar {
    width: 272px;
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
</style>
