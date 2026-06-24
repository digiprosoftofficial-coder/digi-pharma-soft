<template>
    <TenantShellLayout page-title="Purchase summary">
        <Head title="Purchase summary" />
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-0">Purchase summary</h1>
                <p class="small text-muted mb-0">Branch-scoped purchase totals, supplier dues, and return credits.</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">Reports hub</Link>
        </div>

        <ReportControls
            :filters="filters"
            :branches="branches"
            :branch-label="branchLabel"
            :can-view-all-branches="canViewAllBranches"
            report-path="/reports/purchases/summary"
            export-path="/reports/purchases/export"
        />

        <SummaryCards :cards="summaryCards" />

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="h6 mb-0">Purchases in range</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Date</th>
                                    <th>Branch</th>
                                    <th>Supplier</th>
                                    <th class="text-end">Total ({{ currencyCode() }})</th>
                                    <th class="text-end">Due ({{ currencyCode() }})</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="purchase in purchases.data" :key="purchase.id">
                                    <td>
                                        <Link :href="`/purchases/${purchase.id}`" class="fw-medium text-decoration-none">
                                            {{ purchase.invoice_no }}
                                        </Link>
                                    </td>
                                    <td>{{ purchase.purchased_at }}</td>
                                    <td>{{ purchase.branch?.name ?? '—' }}</td>
                                    <td>{{ purchase.supplier?.name ?? '—' }}</td>
                                    <td class="text-end">{{ formatMoney(purchase.total) }}</td>
                                    <td class="text-end" :class="{ 'text-danger fw-medium': Number(purchase.due) > 0 }">
                                        {{ formatMoney(purchase.due) }}
                                    </td>
                                    <td>{{ purchase.status }}</td>
                                </tr>
                                <tr v-if="!purchases.data?.length">
                                    <td colspan="7" class="text-center text-muted py-4">No purchases found for this range.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <PaginationLinks :links="purchases.links" />
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="h6 mb-0">Top suppliers</h2>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li v-for="supplier in topSuppliers" :key="supplier.supplier_id" class="list-group-item">
                            <div class="d-flex justify-content-between gap-2">
                                <span>{{ supplier.supplier_name }}</span>
                                <strong>{{ formatMoney(supplier.purchase_total) }}</strong>
                            </div>
                            <div class="small text-muted text-end">Due {{ formatMoney(supplier.due_total) }}</div>
                        </li>
                        <li v-if="!topSuppliers.length" class="list-group-item text-muted small">No supplier purchases found.</li>
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
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    summary: { type: Object, required: true },
    purchases: { type: Object, required: true },
    topSuppliers: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
    branchLabel: { type: String, required: true },
    canViewAllBranches: { type: Boolean, default: false },
});

const { formatMoney, currencyCode } = useMoney();

const summaryCards = computed(() => [
    { label: 'Purchase total', value: props.summary.purchaseTotal, money: true },
    { label: 'Return credit', value: props.summary.returnCredit, money: true },
    { label: 'Net purchase', value: props.summary.netPurchase, money: true },
    { label: 'Due', value: props.summary.due, money: true },
    { label: 'Paid', value: props.summary.paid, money: true },
    { label: 'Purchases', value: props.summary.purchaseCount, money: false },
    { label: 'Returns', value: props.summary.returnCount, money: false },
]);
</script>
