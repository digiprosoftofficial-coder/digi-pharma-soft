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
        <aside class="tenant-sidebar border-end bg-white d-flex flex-column flex-shrink-0" style="width: 260px">
            <div class="p-3 border-bottom">
                <Link href="/dashboard" class="text-decoration-none text-dark">
                    <div class="fw-bold">{{ tenantName }}</div>
                    <div class="small text-muted">Pharmacy management</div>
                </Link>
            </div>
            <nav class="flex-grow-1 overflow-auto small p-2">
                <ul class="nav flex-column gap-1">
                    <li class="nav-item">
                        <Link href="/dashboard" class="nav-link rounded py-2" :class="navActive('/dashboard')">Dashboard</Link>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            class="nav-link rounded py-2 w-100 text-start btn btn-link text-decoration-none text-body border-0"
                            @click="toggle('sales')"
                        >
                            Sales
                            <span class="float-end text-muted">{{ open.sales ? '−' : '+' }}</span>
                        </button>
                        <div v-show="open.sales" class="ps-3 pb-2">
                            <Link v-if="can('sales.view')" href="/sales" class="d-block py-1 text-decoration-none" :class="exactPath('/sales')">Sales list</Link>
                            <Link v-if="can('pos.access')" href="/pos" class="d-block py-1 text-decoration-none" :class="exactPath('/pos')">New POS sale</Link>
                            <Link v-if="can('pos.access')" href="/sales/package" class="d-block py-1 text-decoration-none" :class="pathStarts('/sales/package')">Package sell</Link>
                            <Link v-if="can('returns.manage')" href="/sales/returns" class="d-block py-1 text-decoration-none" :class="pathStarts('/sales/returns')">Returns</Link>
                        </div>
                    </li>
                    <li class="nav-item">
                        <button
                            type="button"
                            class="nav-link rounded py-2 w-100 text-start btn btn-link text-decoration-none text-body border-0"
                            @click="toggle('purchases')"
                        >
                            Purchases
                            <span class="float-end text-muted">{{ open.purchases ? '−' : '+' }}</span>
                        </button>
                        <div v-show="open.purchases" class="ps-3 pb-2">
                            <Link v-if="can('purchases.view')" href="/purchases" class="d-block py-1 text-decoration-none" :class="exactPath('/purchases')">Purchase list</Link>
                            <Link v-if="can('purchases.manage')" href="/purchases/create" class="d-block py-1 text-decoration-none" :class="exactPath('/purchases/create')">New purchase</Link>
                            <Link v-if="can('purchases.view')" href="/purchases/supplier-bills" class="d-block py-1 text-decoration-none" :class="pathStarts('/purchases/supplier-bills')">Supplier bills</Link>
                        </div>
                    </li>
                    <li class="nav-item">
                        <Link v-if="can('accounting.view')" href="/accounts" class="nav-link rounded py-2" :class="navActive('/accounts')">General accounts</Link>
                    </li>
                    <li class="nav-item">
                        <Link v-if="can('products.view')" href="/products" class="nav-link rounded py-2" :class="pathStarts('/products')">Products</Link>
                    </li>
                    <li class="nav-item">
                        <Link v-if="can('inventory.view')" href="/inventory" class="nav-link rounded py-2" :class="pathStarts('/inventory')">Inventory</Link>
                    </li>
                    <li class="nav-item">
                        <Link v-if="can('employees.view')" href="/employees" class="nav-link rounded py-2" :class="pathStarts('/employees')">Employees</Link>
                    </li>
                    <li class="nav-item">
                        <Link v-if="can('promotions.view')" href="/promotions" class="nav-link rounded py-2" :class="pathStarts('/promotions')">Promotions</Link>
                    </li>
                    <li class="nav-item">
                        <Link v-if="can('stock_transfers.view')" href="/stock-transfers" class="nav-link rounded py-2" :class="pathStarts('/stock-transfers')">Stock transfer</Link>
                    </li>
                    <li class="nav-item">
                        <Link v-if="can('suppliers.view')" href="/suppliers" class="nav-link rounded py-2" :class="pathStarts('/suppliers')">Suppliers</Link>
                    </li>
                    <li class="nav-item">
                        <Link v-if="can('customers.view')" href="/customers" class="nav-link rounded py-2" :class="pathStarts('/customers')">Customers</Link>
                    </li>
                    <li class="nav-item">
                        <Link v-if="can('reports.view')" href="/reports" class="nav-link rounded py-2" :class="pathStarts('/reports')">Reports</Link>
                    </li>
                    <li class="nav-item">
                        <Link v-if="can('settings.view')" href="/settings" class="nav-link rounded py-2" :class="pathStarts('/settings')">Settings</Link>
                    </li>
                    <li class="nav-item">
                        <Link v-if="can('sms.send')" href="/sms" class="nav-link rounded py-2" :class="pathStarts('/sms')">Send SMS</Link>
                    </li>
                    <li class="nav-item">
                        <Link v-if="can('team.users.view')" href="/team/users" class="nav-link rounded py-2" :class="pathStarts('/team/users')">Users</Link>
                    </li>
                </ul>
            </nav>
            <div class="p-3 border-top mt-auto small">
                <Link href="/support" class="d-block py-1 text-decoration-none" :class="navActive('/support')">Support</Link>
                <Link
                    v-if="usesPlatform"
                    href="/platform/dashboard"
                    class="d-block py-1 text-decoration-none"
                    :class="navActive('/platform')"
                >
                    Global settings
                </Link>
                <Link v-else href="/global-settings" class="d-block py-1 text-decoration-none text-muted">Global settings</Link>
            </div>
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
import LocaleSwitcher from '@/Components/LocaleSwitcher.vue';
import { useLocale } from '@/composables/useLocale';
import { usePermissions } from '@/composables/usePermissions';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const { t } = useLocale();

defineProps({
    pageTitle: { type: String, default: 'Dashboard' },
});

const page = usePage();
const { can } = usePermissions();

const tenantName = computed(() => page.props.tenant?.name ?? 'Pharmacy');
const userName = computed(() => page.props.auth?.user?.name ?? 'User');
const usesPlatform = computed(() => page.props.auth?.user?.uses_platform_dashboard === true);
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

const open = reactive({ sales: true, purchases: true });
function toggle(key) {
    open[key] = !open[key];
}

const url = computed(() => page.url || '');
function pathNow() {
    return (url.value || '').split('?')[0];
}

function navActive(prefix) {
    const u = pathNow();
    if (prefix === '/dashboard') {
        return u === '/dashboard' ? 'active bg-primary text-white' : '';
    }
    return u === prefix || u.startsWith(prefix + '/') ? 'active bg-primary text-white' : '';
}

function exactPath(path) {
    return pathNow() === path ? 'text-primary fw-semibold' : '';
}

function pathStarts(path) {
    const u = pathNow();
    return u === path || u.startsWith(path + '/') ? 'text-primary fw-semibold' : '';
}

const searchQ = ref('');
function runSearch() {
    router.visit('/products');
}
</script>

<style scoped>
.tenant-sidebar .nav-link.active {
    color: #fff !important;
}
.tenant-sidebar .btn-link.nav-link {
    color: inherit;
}
</style>
