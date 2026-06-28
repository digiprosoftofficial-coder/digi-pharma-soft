<template>
    <form class="card border-0 shadow-sm card-body mb-3" @submit.prevent="applyFilters">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">{{ t('purchases.date_from') }}</label>
                <input v-model="filterForm.date_from" type="date" class="form-control form-control-sm" />
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">{{ t('purchases.date_to') }}</label>
                <input v-model="filterForm.date_to" type="date" class="form-control form-control-sm" />
            </div>
            <ReportFilterSelect
                v-if="canViewAllBranches"
                v-model="filterForm.branch_id"
                :label="t('branches.title')"
                :placeholder="t('purchases.all_branches')"
                :options="branchOptions"
                column-class="col-md-2"
            />
            <ReportFilterSelect v-if="has('supplier')" v-model="filterForm.supplier_id" :label="t('purchases.supplier')" :options="options.suppliers" />
            <ReportFilterSelect v-if="has('customer')" v-model="filterForm.customer_id" :label="t('sales.customer')" :options="options.customers" />
            <ReportFilterSelect v-if="has('product')" v-model="filterForm.product_id" :label="t('sales.product')" :options="options.products" />
            <ReportFilterSelect v-if="has('category')" v-model="filterForm.category_id" :label="t('catalog.category')" :options="options.categories" />
            <ReportFilterSelect v-if="has('manufacturer')" v-model="filterForm.manufacturer_id" :label="t('catalog.manufacturer')" :options="options.manufacturers" />
            <ReportFilterSelect v-if="has('user')" v-model="filterForm.user_id" :label="t('reports.user')" :options="options.users" />
            <ReportFilterSelect v-if="has('account')" v-model="filterForm.account_id" :label="t('reports.account')" :options="options.accounts" />
            <ReportFilterSelect v-if="has('paymentStatus')" v-model="filterForm.payment_status" :label="t('reports.payment_status')" :options="paymentStatusOptions" />
            <ReportFilterSelect v-if="has('paymentMethod')" v-model="filterForm.payment_method" :label="t('purchases.payment_method')" :options="paymentMethodOptions" />
            <ReportFilterSelect v-if="has('expiryStatus')" v-model="filterForm.expiry_status" :label="t('reports.expiry_status')" :options="expiryStatusOptions" />
            <ReportFilterSelect v-if="has('dueStatus')" v-model="filterForm.due_status" :label="t('reports.due_status')" :options="dueStatusOptions" />
            <ReportFilterSelect v-if="has('eventType')" v-model="filterForm.event_type" :label="t('dashboard.event')" :options="options.events" />
            <ReportFilterSelect v-if="has('direction')" v-model="filterForm.direction" :label="t('reports.direction')" :options="directionOptions" />
            <div v-if="has('batch')" class="col-md-2">
                <label class="form-label small mb-0">{{ t('purchases.batch') }}</label>
                <input v-model="filterForm.batch" type="search" class="form-control form-control-sm" :placeholder="t('reports.batch_no')" />
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary">{{ t('reports.apply') }}</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="resetFilters">{{ t('purchases.reset') }}</button>
            </div>
            <div class="col-md text-md-end">
                <ReportOutputActions :export-path="exportPath" :params="cleanParams()" />
            </div>
        </div>
        <p class="small text-muted mb-0 mt-2">
            {{ t('reports.current_scope') }}: <strong>{{ branchLabel }}</strong>
        </p>
    </form>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { useLocale } from '@/composables/useLocale';
import ReportFilterSelect from './ReportFilterSelect.vue';
import ReportOutputActions from './ReportOutputActions.vue';

const props = defineProps({
    filters: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
    branchLabel: { type: String, required: true },
    canViewAllBranches: { type: Boolean, default: false },
    reportPath: { type: String, required: true },
    exportPath: { type: String, required: true },
    enabledFilters: { type: Array, default: () => [] },
    options: { type: Object, default: () => ({}) },
});

const { t } = useLocale();

const filterForm = reactive({
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    branch_id: props.filters.branch_id ?? 'all',
    supplier_id: props.filters.supplier_id ?? '',
    customer_id: props.filters.customer_id ?? '',
    product_id: props.filters.product_id ?? '',
    category_id: props.filters.category_id ?? '',
    manufacturer_id: props.filters.manufacturer_id ?? '',
    user_id: props.filters.user_id ?? '',
    account_id: props.filters.account_id ?? '',
    payment_status: props.filters.payment_status ?? '',
    payment_method: props.filters.payment_method ?? '',
    expiry_status: props.filters.expiry_status ?? '',
    due_status: props.filters.due_status ?? '',
    event_type: props.filters.event_type ?? '',
    direction: props.filters.direction ?? '',
    batch: props.filters.batch ?? '',
});

const branchOptions = computed(() =>
    props.branches.map((branch) => ({
        value: branch.id,
        label: branch.code ? `${branch.name} (${branch.code})` : branch.name,
    })),
);

const paymentStatusOptions = [
    { value: 'paid', label: t('sales.paid') },
    { value: 'partial', label: t('reports.partial') },
    { value: 'due', label: t('sales.due') },
];

const paymentMethodOptions = [
    { value: 'cash', label: t('purchases.payment_cash') },
    { value: 'card', label: t('purchases.payment_card') },
    { value: 'bkash', label: 'bKash' },
    { value: 'nagad', label: 'Nagad' },
    { value: 'bank', label: t('reports.bank') },
];

const expiryStatusOptions = [
    { value: 'expired', label: t('reports.expired') },
    { value: 'expiring_30', label: t('reports.expiring_30_days') },
    { value: 'expiring_60', label: t('reports.expiring_60_days') },
    { value: 'expiring_90', label: t('reports.expiring_90_days') },
    { value: 'valid', label: t('reports.valid') },
];

const dueStatusOptions = [
    { value: 'has_due', label: t('reports.has_due') },
    { value: 'clear', label: t('reports.clear') },
];

const directionOptions = [
    { value: 'debit', label: t('reports.debits') },
    { value: 'credit', label: t('reports.credits') },
];

function has(filter) {
    return props.enabledFilters.includes(filter);
}

function cleanParams(extra = {}) {
    const params = { ...filterForm, ...extra };

    return Object.fromEntries(Object.entries(params).filter(([, value]) => value !== null && value !== ''));
}

function applyFilters() {
    router.get(props.reportPath, cleanParams(), { preserveState: true, replace: true });
}

function resetFilters() {
    router.get(props.reportPath, {}, { preserveState: true, replace: true });
}
</script>
