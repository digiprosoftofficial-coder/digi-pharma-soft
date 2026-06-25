<template>
    <form class="card border-0 shadow-sm card-body mb-3" @submit.prevent="applyFilters">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">From</label>
                <input v-model="filterForm.date_from" type="date" class="form-control form-control-sm" />
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">To</label>
                <input v-model="filterForm.date_to" type="date" class="form-control form-control-sm" />
            </div>
            <ReportFilterSelect
                v-if="canViewAllBranches"
                v-model="filterForm.branch_id"
                label="Branch"
                placeholder="All branches"
                :options="branchOptions"
                column-class="col-md-2"
            />
            <ReportFilterSelect v-if="has('supplier')" v-model="filterForm.supplier_id" label="Supplier" :options="options.suppliers" />
            <ReportFilterSelect v-if="has('customer')" v-model="filterForm.customer_id" label="Customer" :options="options.customers" />
            <ReportFilterSelect v-if="has('product')" v-model="filterForm.product_id" label="Product" :options="options.products" />
            <ReportFilterSelect v-if="has('category')" v-model="filterForm.category_id" label="Category" :options="options.categories" />
            <ReportFilterSelect v-if="has('manufacturer')" v-model="filterForm.manufacturer_id" label="Manufacturer" :options="options.manufacturers" />
            <ReportFilterSelect v-if="has('user')" v-model="filterForm.user_id" label="User" :options="options.users" />
            <ReportFilterSelect v-if="has('account')" v-model="filterForm.account_id" label="Account" :options="options.accounts" />
            <ReportFilterSelect v-if="has('paymentStatus')" v-model="filterForm.payment_status" label="Payment status" :options="paymentStatusOptions" />
            <ReportFilterSelect v-if="has('paymentMethod')" v-model="filterForm.payment_method" label="Payment method" :options="paymentMethodOptions" />
            <ReportFilterSelect v-if="has('expiryStatus')" v-model="filterForm.expiry_status" label="Expiry status" :options="expiryStatusOptions" />
            <ReportFilterSelect v-if="has('dueStatus')" v-model="filterForm.due_status" label="Due status" :options="dueStatusOptions" />
            <ReportFilterSelect v-if="has('eventType')" v-model="filterForm.event_type" label="Event" :options="options.events" />
            <ReportFilterSelect v-if="has('direction')" v-model="filterForm.direction" label="Direction" :options="directionOptions" />
            <div v-if="has('batch')" class="col-md-2">
                <label class="form-label small mb-0">Batch</label>
                <input v-model="filterForm.batch" type="search" class="form-control form-control-sm" placeholder="Batch no" />
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="resetFilters">Reset</button>
            </div>
            <div class="col-md text-md-end">
                <ReportOutputActions :export-path="exportPath" :params="cleanParams()" />
            </div>
        </div>
        <p class="small text-muted mb-0 mt-2">
            Current scope: <strong>{{ branchLabel }}</strong>
        </p>
    </form>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
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
    { value: 'paid', label: 'Paid' },
    { value: 'partial', label: 'Partial' },
    { value: 'due', label: 'Due' },
];

const paymentMethodOptions = [
    { value: 'cash', label: 'Cash' },
    { value: 'card', label: 'Card' },
    { value: 'bkash', label: 'bKash' },
    { value: 'nagad', label: 'Nagad' },
    { value: 'bank', label: 'Bank' },
];

const expiryStatusOptions = [
    { value: 'expired', label: 'Expired' },
    { value: 'expiring_30', label: 'Expiring in 30 days' },
    { value: 'expiring_60', label: 'Expiring in 60 days' },
    { value: 'expiring_90', label: 'Expiring in 90 days' },
    { value: 'valid', label: 'Valid' },
];

const dueStatusOptions = [
    { value: 'has_due', label: 'Has due' },
    { value: 'clear', label: 'Clear' },
];

const directionOptions = [
    { value: 'debit', label: 'Debit' },
    { value: 'credit', label: 'Credit' },
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
