<template>
    <TenantShellLayout page-title="Customer Reports">
        <Head title="Customer Reports" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">Customer Reports</h1>
                <p class="small text-muted mb-0">Customer sales, receivables, invoice count, loyalty points, and exportable due view.</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">Report Hub</Link>
        </div>
        <SmartReportFilters
            :filters="filters"
            :branches="options.branches"
            :branch-label="branchLabel"
            :can-view-all-branches="canViewAllBranches"
            :options="options"
            :enabled-filters="['customer', 'paymentStatus', 'dueStatus']"
            report-path="/reports/customers"
            export-path="/reports/customers/export"
        />
        <SummaryCards :cards="summaryCards" />
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th class="text-end">Invoices</th>
                            <th class="text-end">Sales</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Due</th>
                            <th class="text-end">Loyalty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows.data" :key="row.customer_id">
                            <td>{{ row.customer_name }}</td>
                            <td>{{ row.phone ?? '—' }}</td>
                            <td class="text-end">{{ row.invoice_count }}</td>
                            <td class="text-end">{{ formatMoney(row.sales_total) }}</td>
                            <td class="text-end">{{ formatMoney(row.paid_total) }}</td>
                            <td class="text-end text-danger fw-medium">{{ formatMoney(row.due_total) }}</td>
                            <td class="text-end">{{ number(row.loyalty_points) }}</td>
                        </tr>
                        <tr v-if="!rows.data?.length">
                            <td colspan="7" class="text-center text-muted py-4">No customer records found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <PaginationLinks :links="rows.links" />
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useMoney } from '@/composables/useMoney';
import PaginationLinks from '@/Pages/Reports/Partials/PaginationLinks.vue';
import SmartReportFilters from '@/Pages/Reports/Partials/SmartReportFilters.vue';
import SummaryCards from '@/Pages/Reports/Partials/SummaryCards.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    summary: { type: Object, required: true },
    rows: { type: Object, required: true },
    filters: { type: Object, required: true },
    branchLabel: { type: String, required: true },
    canViewAllBranches: { type: Boolean, default: false },
    options: { type: Object, required: true },
});

const { formatMoney } = useMoney();

const summaryCards = computed(() => [
    { label: 'Customers', value: props.summary.customerCount, money: false },
    { label: 'Sales total', value: props.summary.salesTotal, money: true },
    { label: 'Paid', value: props.summary.paid, money: true },
    { label: 'Due', value: props.summary.due, money: true },
]);

function number(value) {
    return new Intl.NumberFormat().format(Number(value || 0));
}
</script>
