<template>
    <TenantShellLayout page-title="Sales summary">
        <Head title="Sales summary" />
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-0">Sales summary</h1>
                <p class="small text-muted mb-0">Gross sales, returns, net sales, dues, and top products.</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">Reports hub</Link>
        </div>

        <ReportControls
            :filters="filters"
            :branches="branches"
            :branch-label="branchLabel"
            :can-view-all-branches="canViewAllBranches"
            report-path="/reports/sales/summary"
            export-path="/reports/sales/export"
        />

        <SummaryCards :cards="summaryCards" />

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="h6 mb-0">Sales in range</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Date</th>
                                    <th>Branch</th>
                                    <th>Customer</th>
                                    <th class="text-end">Total ({{ currencyCode() }})</th>
                                    <th class="text-end">Due ({{ currencyCode() }})</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="sale in sales.data" :key="sale.id">
                                    <td>{{ sale.invoice_no }}</td>
                                    <td>{{ formatDate(sale.sold_at) }}</td>
                                    <td>{{ sale.branch?.name ?? '—' }}</td>
                                    <td>{{ sale.customer?.name ?? 'Walk-in' }}</td>
                                    <td class="text-end">{{ formatMoney(sale.rounded_total ?? sale.total) }}</td>
                                    <td class="text-end" :class="{ 'text-danger fw-medium': Number(sale.due) > 0 }">
                                        {{ formatMoney(sale.due) }}
                                    </td>
                                    <td>{{ sale.status }}</td>
                                </tr>
                                <tr v-if="!sales.data?.length">
                                    <td colspan="7" class="text-center text-muted py-4">No sales found for this range.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <PaginationLinks :links="sales.links" />
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="h6 mb-0">Top products</h2>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li
                            v-for="product in topProducts"
                            :key="product.product_id"
                            class="list-group-item d-flex justify-content-between gap-2"
                        >
                            <span>{{ product.product_name }}</span>
                            <span class="text-end">
                                <span class="badge bg-primary rounded-pill">{{ Number(product.quantity || 0).toLocaleString() }}</span>
                                <span class="d-block small text-muted">{{ formatMoney(product.revenue) }}</span>
                            </span>
                        </li>
                        <li v-if="!topProducts.length" class="list-group-item text-muted small">No product lines found.</li>
                    </ul>
                </div>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useMoney } from '@/composables/useMoney';
import PaginationLinks from '@/Pages/Reports/Partials/PaginationLinks.vue';
import ReportControls from '@/Pages/Reports/Partials/ReportControls.vue';
import SummaryCards from '@/Pages/Reports/Partials/SummaryCards.vue';
import { formatHumanDateTime as formatDate } from '@/utils/dates';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    summary: { type: Object, required: true },
    sales: { type: Object, required: true },
    topProducts: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
    branchLabel: { type: String, required: true },
    canViewAllBranches: { type: Boolean, default: false },
});

const { formatMoney, currencyCode } = useMoney();

const summaryCards = computed(() => [
    { label: 'Gross sales', value: props.summary.grossSales, money: true },
    { label: 'Returns', value: props.summary.returnTotal, money: true },
    { label: 'Net sales', value: props.summary.netSales, money: true },
    { label: 'Due', value: props.summary.due, money: true },
    { label: 'Paid', value: props.summary.paid, money: true },
    { label: 'Invoices', value: props.summary.invoiceCount, money: false },
    { label: 'Return count', value: props.summary.returnCount, money: false },
]);

</script>
