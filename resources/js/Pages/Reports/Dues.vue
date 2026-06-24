<template>
    <TenantShellLayout page-title="Customer and supplier dues">
        <Head title="Customer and supplier dues" />
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-0">Customer and supplier dues</h1>
                <p class="small text-muted mb-0">Receivables and payables for tenant-wide or selected branch scope.</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">Reports hub</Link>
        </div>

        <ReportControls
            :filters="filters"
            :branches="branches"
            :branch-label="branchLabel"
            :can-view-all-branches="canViewAllBranches"
            report-path="/reports/dues"
            export-path="/reports/dues/export"
        />

        <SummaryCards :cards="summaryCards" />

        <div class="row g-3">
            <div class="col-xl-6">
                <DueTable title="Customer receivables" empty-text="No customer dues found." :rows="customers" />
            </div>
            <div class="col-xl-6">
                <DueTable title="Supplier payables" empty-text="No supplier dues found." :rows="suppliers" />
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useMoney } from '@/composables/useMoney';
import ReportControls from '@/Pages/Reports/Partials/ReportControls.vue';
import SummaryCards from '@/Pages/Reports/Partials/SummaryCards.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, h } from 'vue';

const props = defineProps({
    summary: { type: Object, required: true },
    customers: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
    branchLabel: { type: String, required: true },
    canViewAllBranches: { type: Boolean, default: false },
});

const { formatMoney } = useMoney();

const summaryCards = computed(() => [
    { label: 'Customer due', value: props.summary.customerDue, money: true },
    { label: 'Supplier due', value: props.summary.supplierDue, money: true },
    { label: 'Customers', value: props.summary.customerCount, money: false },
    { label: 'Suppliers', value: props.summary.supplierCount, money: false },
]);

const DueTable = {
    props: {
        title: { type: String, required: true },
        rows: { type: Array, required: true },
        emptyText: { type: String, required: true },
    },
    setup(tableProps) {
        return () =>
            h('div', { class: 'card border-0 shadow-sm h-100' }, [
                h('div', { class: 'card-header bg-white' }, h('h2', { class: 'h6 mb-0' }, tableProps.title)),
                h('div', { class: 'table-responsive' }, [
                    h('table', { class: 'table table-sm mb-0' }, [
                        h('thead', { class: 'table-light' }, [
                            h('tr', [
                                h('th', 'Name'),
                                h('th', 'Phone'),
                                h('th', 'Scope'),
                                h('th', { class: 'text-end' }, 'Due'),
                            ]),
                        ]),
                        h('tbody', [
                            ...tableProps.rows.map((row) =>
                                h('tr', { key: row.id }, [
                                    h('td', row.name),
                                    h('td', row.phone || '—'),
                                    h('td', row.scope),
                                    h('td', { class: 'text-end text-danger fw-medium' }, formatMoney(row.due)),
                                ]),
                            ),
                            tableProps.rows.length
                                ? null
                                : h('tr', [h('td', { colspan: 4, class: 'text-center text-muted py-4' }, tableProps.emptyText)]),
                        ]),
                    ]),
                ]),
            ]);
    },
};
</script>
