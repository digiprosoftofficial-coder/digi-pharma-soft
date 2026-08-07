<template>
    <nav class="tenant-sidebar-nav flex-grow-1 overflow-auto p-2">
        <ul class="list-unstyled mb-0 d-flex flex-column gap-1">
            <!-- Dashboard -->
            <li>
                <Link
                    href="/dashboard"
                    class="tenant-nav-item"
                    :class="{ 'tenant-nav-item--active': isExact('/dashboard') }"
                    @click="notifyNavigate"
                >
                    <TenantNavIcon name="dashboard" />
                    <span class="tenant-nav-label">{{ t('tenant_nav.dashboard') }}</span>
                </Link>
            </li>

            <li v-if="pharmacyNotesEnabled && can('notes.view')">
                <Link
                    href="/notes"
                    class="tenant-nav-item"
                    :class="{
                        'tenant-nav-item--active': pathStarts('/notes'),
                        'tenant-nav-item--inactive': !pathStarts('/notes'),
                    }"
                    @click="notifyNavigate"
                >
                    <TenantNavIcon name="notes" />
                    <span class="tenant-nav-label">{{ t('tenant_nav.notes') }}</span>
                </Link>
            </li>

            <template v-for="section in visibleSections" :key="section.id">
                <li class="tenant-nav-group">
                    <button
                        type="button"
                        class="tenant-nav-item tenant-nav-item--toggle"
                        :class="{
                            'tenant-nav-item--active': isSectionActive(section),
                            'tenant-nav-item--inactive': !isSectionActive(section),
                        }"
                        :aria-expanded="open[section.id]"
                        @click="toggle(section.id)"
                    >
                        <TenantNavIcon :name="section.icon" />
                        <span class="tenant-nav-label">{{ section.label }}</span>
                        <svg
                            class="tenant-nav-chevron"
                            :class="{ 'tenant-nav-chevron--open': open[section.id] }"
                            xmlns="http://www.w3.org/2000/svg"
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            aria-hidden="true"
                        >
                            <path d="M6 9l6 6 6-6" />
                        </svg>
                    </button>
                    <ul v-show="open[section.id]" class="tenant-nav-children list-unstyled mb-0">
                        <li v-for="child in section.children" :key="child.href">
                            <Link
                                :href="child.href"
                                class="tenant-nav-child"
                                :class="{
                                    'tenant-nav-child--active': isChildActive(child),
                                    'tenant-nav-child--inactive': !isChildActive(child),
                                }"
                                @click="notifyNavigate"
                            >
                                <TenantNavIcon :name="child.icon" />
                                <span>{{ child.label }}</span>
                            </Link>
                        </li>
                    </ul>
                </li>

                <!-- Report Hub -->
                <li v-if="section.id === 'inventory' && can('reports.view')">
                    <Link
                        href="/reports"
                        class="tenant-nav-item"
                        :class="{
                            'tenant-nav-item--active': pathStarts('/reports'),
                            'tenant-nav-item--inactive': !pathStarts('/reports'),
                        }"
                        @click="notifyNavigate"
                    >
                        <TenantNavIcon name="report" />
                        <span class="tenant-nav-label">{{ t('reports.hub') }}</span>
                    </Link>
                </li>
            </template>
        </ul>

        <div class="tenant-nav-footer border-top pt-2 mt-2">
            <Link
                href="/support"
                class="tenant-nav-child"
                :class="{
                    'tenant-nav-child--active': isExact('/support') || pathStarts('/support'),
                    'tenant-nav-child--inactive': !(isExact('/support') || pathStarts('/support')),
                }"
                @click="notifyNavigate"
            >
                <TenantNavIcon name="support" />
                <span>{{ t('tenant_nav.support') }}</span>
            </Link>
            <Link
                v-if="usesPlatform"
                href="/platform/dashboard"
                class="tenant-nav-child"
                :class="{
                    'tenant-nav-child--active': pathStarts('/platform'),
                    'tenant-nav-child--inactive': !pathStarts('/platform'),
                }"
                @click="notifyNavigate"
            >
                <TenantNavIcon name="globe" />
                <span>{{ t('tenant_nav.global_settings') }}</span>
            </Link>
            <Link
                v-else-if="can('settings.view')"
                href="/settings"
                class="tenant-nav-child"
                :class="{
                    'tenant-nav-child--active': pathStarts('/settings'),
                    'tenant-nav-child--inactive': !pathStarts('/settings'),
                }"
                @click="notifyNavigate"
            >
                <TenantNavIcon name="settings" />
                <span>{{ t('tenant_nav.settings') }}</span>
            </Link>
        </div>
    </nav>
</template>

<script setup>
import TenantNavIcon from '@/Components/Tenant/TenantNavIcon.vue';
import { useLocale } from '@/composables/useLocale';
import { usePermissions } from '@/composables/usePermissions';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

const { t } = useLocale();
const { can } = usePermissions();
const page = usePage();
const emit = defineEmits(['navigate']);

const usesPlatform = computed(() => page.props.auth?.user?.uses_platform_dashboard === true);
const bulkImportEnabled = computed(() => page.props.features?.bulk_import ?? true);
const multiBranchEnabled = computed(() => page.props.features?.multi_branch ?? false);
const employeeManagementEnabled = computed(() => page.props.features?.employee_management ?? true);
const attendanceEnabled = computed(() => page.props.features?.attendance ?? false);
const hrPayrollEnabled = computed(() => page.props.features?.hr_payroll ?? false);
const packageSalesEnabled = computed(() => page.props.features?.package_sales ?? false);
const pharmacyNotesEnabled = computed(() => page.props.features?.pharmacy_notes ?? false);

const url = computed(() => page.url || '');
function pathNow() {
    return (url.value || '').split('?')[0];
}

function isExact(path) {
    return pathNow() === path;
}

function pathStarts(path) {
    const u = pathNow();
    return u === path || u.startsWith(path + '/');
}

function isChildActive(child) {
    if (child.match === 'exact') {
        return isExact(child.href);
    }
    if (child.match === 'products-list') {
        const u = pathNow();
        return u === '/products' || (u.startsWith('/products/') && u !== '/products/create');
    }
    return pathStarts(child.href);
}

function notifyNavigate() {
    emit('navigate');
}

const sectionsConfig = computed(() => [
    {
        id: 'sales',
        icon: 'sales',
        label: t('tenant_nav.sales'),
        show: can('sales.view') || can('pos.access') || can('returns.manage') || packageSalesEnabled.value,
        paths: ['/sales', '/pos', '/sales/package', '/sales/packages', '/sales/returns'],
        children: [
            { href: '/sales', icon: 'list', label: t('tenant_nav.sales_list'), show: can('sales.view'), match: 'exact' },
            { href: '/pos', icon: 'plus', label: t('tenant_nav.pos'), show: can('pos.access'), match: 'exact' },
            { href: '/sales/package', icon: 'package', label: t('tenant_nav.package_sell'), show: can('pos.access') && packageSalesEnabled.value, match: 'exact' },
            { href: '/sales/packages', icon: 'package', label: 'Package templates', show: can('pos.access') && packageSalesEnabled.value, match: 'prefix' },
            { href: '/sales/returns', icon: 'return', label: t('tenant_nav.returns'), show: can('returns.manage'), match: 'prefix' },
            { href: '/sales/customer-bills', icon: 'bill', label: t('tenant_nav.customer_bills'), show: can('customers.view'), match: 'prefix' },
        ],
    },
    {
        id: 'purchases',
        icon: 'purchases',
        label: t('tenant_nav.purchases'),
        show: can('purchases.view') || can('purchases.manage'),
        paths: ['/purchases'],
        children: [
            { href: '/purchases', icon: 'list', label: t('tenant_nav.purchase_list'), show: can('purchases.view'), match: 'exact' },
            { href: '/purchases/create', icon: 'plus', label: t('tenant_nav.new_purchase'), show: can('purchases.manage'), match: 'exact' },
            { href: '/purchases/supplier-bills', icon: 'bill', label: t('tenant_nav.supplier_bills'), show: can('purchases.view'), match: 'prefix' },
            { href: '/purchases/returns', icon: 'return', label: t('tenant_nav.purchase_returns'), show: can('purchases.manage'), match: 'prefix' },
        ],
    },
    {
        id: 'catalog',
        icon: 'catalog',
        label: t('tenant_nav.catalog'),
        show: can('products.view') || can('categories.view') || can('product_types.view') || can('manufacturers.view') || can('storage_locations.view'),
        paths: ['/products', '/categories', '/product-types', '/manufacturers', '/storage-locations', '/catalog/import'],
        children: [
            { href: '/products', icon: 'list', label: t('tenant_nav.product_list'), show: can('products.view'), match: 'products-list' },
            { href: '/products/create', icon: 'plus', label: t('tenant_nav.new_product'), show: can('products.manage'), match: 'exact' },
            { href: '/categories', icon: 'category', label: t('tenant_nav.categories'), show: can('categories.view'), match: 'prefix' },
            { href: '/product-types', icon: 'tag', label: t('tenant_nav.product_types'), show: can('product_types.view'), match: 'prefix' },
            { href: '/manufacturers', icon: 'factory', label: t('tenant_nav.manufacturers'), show: can('manufacturers.view'), match: 'prefix' },
            { href: '/storage-locations', icon: 'inventory', label: t('tenant_nav.storage_locations'), show: can('storage_locations.view'), match: 'prefix' },
            { href: '/catalog/import', icon: 'upload', label: t('tenant_nav.bulk_import'), show: can('products.manage') && bulkImportEnabled.value, match: 'prefix' },
        ],
    },
    {
        id: 'inventory',
        icon: 'inventory',
        label: t('tenant_nav.inventory_group'),
        show: can('inventory.view') || (can('stock_transfers.view') && multiBranchEnabled.value),
        paths: ['/inventory', '/stock-transfers'],
        children: [
            { href: '/inventory', icon: 'inventory', label: t('tenant_nav.inventory'), show: can('inventory.view'), match: 'prefix' },
            {
                href: '/stock-transfers',
                icon: 'transfer',
                label: t('tenant_nav.stock_transfer'),
                show: can('stock_transfers.view') && multiBranchEnabled.value,
                match: 'prefix',
            },
        ],
    },
    {
        id: 'people',
        icon: 'people',
        label: t('tenant_nav.people'),
        show: can('suppliers.view') || can('customers.view') || (employeeManagementEnabled.value && can('employees.view')) || attendanceEnabled.value || (hrPayrollEnabled.value && can('employees.manage')),
        paths: ['/suppliers', '/customers', '/employees', '/attendance', '/hr'],
        children: [
            { href: '/suppliers', icon: 'supplier', label: t('tenant_nav.suppliers'), show: can('suppliers.view'), match: 'prefix' },
            { href: '/customers', icon: 'customer', label: t('tenant_nav.customers'), show: can('customers.view'), match: 'prefix' },
            { href: '/employees', icon: 'employee', label: t('tenant_nav.employees'), show: employeeManagementEnabled.value && can('employees.view'), match: 'prefix' },
            { href: '/attendance', icon: 'employee', label: t('tenant_nav.attendance'), show: attendanceEnabled.value, match: 'prefix' },
            { href: '/hr/payroll', icon: 'accounts', label: t('tenant_nav.hr_payroll'), show: hrPayrollEnabled.value && can('employees.manage'), match: 'prefix' },
        ],
    },
    {
        id: 'finance',
        icon: 'accounts',
        label: t('tenant_nav.finance'),
        show: can('accounting.view'),
        paths: ['/accounts'],
        children: [
            { href: '/accounts', icon: 'accounts', label: t('tenant_nav.accounts'), show: can('accounting.view'), match: 'prefix' },
        ],
    },
    {
        id: 'marketing',
        icon: 'marketing',
        label: t('tenant_nav.marketing'),
        show: can('promotions.view') || can('sms.send'),
        paths: ['/promotions', '/sms'],
        children: [
            { href: '/promotions', icon: 'promo', label: t('tenant_nav.promotions'), show: can('promotions.view'), match: 'prefix' },
            { href: '/sms', icon: 'sms', label: t('tenant_nav.sms'), show: can('sms.send'), match: 'prefix' },
        ],
    },
    {
        id: 'admin',
        icon: 'settings',
        label: t('tenant_nav.administration'),
        show: can('team.users.view') || (can('branches.view') && multiBranchEnabled.value),
        paths: ['/team/users', '/branches'],
        children: [
            { href: '/branches', icon: 'inventory', label: t('tenant_nav.branches'), show: can('branches.view') && multiBranchEnabled.value, match: 'prefix' },
            { href: '/team/users', icon: 'users', label: t('tenant_nav.users'), show: can('team.users.view'), match: 'prefix' },
        ],
    },
]);

const visibleSections = computed(() =>
    sectionsConfig.value
        .filter((s) => s.show)
        .map((s) => ({
            ...s,
            children: s.children.filter((c) => c.show),
        }))
        .filter((s) => s.children.length > 0),
);

function isSectionActive(section) {
    return section.paths.some((p) => pathStarts(p));
}

const open = reactive({});

function initOpenState() {
    for (const section of visibleSections.value) {
        if (open[section.id] === undefined) {
            open[section.id] = isSectionActive(section);
        }
    }
}

initOpenState();

watch(url, () => {
    for (const section of visibleSections.value) {
        if (isSectionActive(section)) {
            open[section.id] = true;
        }
    }
});

function toggle(id) {
    open[id] = !open[id];
}
</script>

<style scoped>
.tenant-sidebar-nav {
    --nav-active-bg: var(--bs-primary);
    --nav-active-fg: #fff;
    --nav-hover-bg: rgba(var(--bs-primary-rgb), 0.08);
    --nav-child-active-bg: rgba(var(--bs-primary-rgb), 0.12);
}

.tenant-nav-item {
    display: flex;
    align-items: center;
    gap: 0.65rem;
    width: 100%;
    min-height: 2.75rem;
    padding: 0.55rem 0.75rem;
    border: 0;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    color: #374151;
    background: transparent;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.tenant-nav-item--toggle {
    cursor: pointer;
    text-align: left;
}

.tenant-nav-item--inactive:hover,
.tenant-nav-child--inactive:hover {
    background: var(--nav-hover-bg);
    color: var(--bs-primary);
}

.tenant-nav-item--active {
    background: var(--nav-active-bg);
    color: var(--nav-active-fg) !important;
}

.tenant-nav-item--active :deep(.tenant-nav-icon) {
    color: inherit;
}

.tenant-nav-label {
    flex: 1;
    min-width: 0;
}

.tenant-nav-chevron {
    flex-shrink: 0;
    opacity: 0.65;
    transition: transform 0.2s ease;
}

.tenant-nav-item--active .tenant-nav-chevron {
    opacity: 0.9;
}

.tenant-nav-chevron--open {
    transform: rotate(180deg);
}

.tenant-nav-children {
    padding: 0.15rem 0 0.35rem 0.35rem;
}

.tenant-nav-child {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    min-height: 2.5rem;
    padding: 0.4rem 0.65rem 0.4rem 2rem;
    margin: 0.1rem 0;
    border-radius: 0.4rem;
    font-size: 0.8125rem;
    text-decoration: none;
    color: #6b7280;
    transition: background-color 0.15s ease, color 0.15s ease;
}

.tenant-nav-child :deep(.tenant-nav-icon) {
    width: 16px;
    height: 16px;
    opacity: 0.85;
}

.tenant-nav-child--active {
    background: var(--nav-child-active-bg);
    color: var(--bs-primary);
    font-weight: 600;
}

.tenant-nav-child--active :deep(.tenant-nav-icon) {
    color: var(--bs-primary);
    opacity: 1;
}

.tenant-nav-footer .tenant-nav-child {
    padding-left: 0.75rem;
}

@media (max-width: 991.98px) {
    .tenant-nav-item {
        min-height: 3rem;
        font-size: 0.95rem;
    }

    .tenant-nav-child {
        min-height: 2.75rem;
        font-size: 0.9rem;
    }
}
</style>
