<template>
    <TenantShellLayout page-title="Inventory health">
        <Head title="Inventory health" />
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-0">Inventory health</h1>
                <p class="small text-muted mb-0">Branch stock, valuation, low stock, expired, and expiring batches.</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">Reports hub</Link>
        </div>

        <ReportControls
            :filters="filters"
            :branches="branches"
            :branch-label="branchLabel"
            :can-view-all-branches="canViewAllBranches"
            report-path="/reports/inventory/health"
            export-path="/reports/inventory/export"
        />

        <SummaryCards :cards="summaryCards" />

        <div class="row g-3 mb-3">
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h2 class="h6 mb-0">Low stock</h2>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li v-for="batch in lowStock" :key="batch.id" class="list-group-item d-flex justify-content-between gap-2">
                            <span>
                                {{ batch.product?.name }}
                                <span class="d-block small text-muted">{{ batch.branch?.name ?? '—' }} | Batch {{ batch.batch_no }}</span>
                            </span>
                            <span class="text-danger fw-medium">{{ formatNumber(batch.quantity_on_hand) }}</span>
                        </li>
                        <li v-if="!lowStock.length" class="list-group-item small text-muted">No low stock batches.</li>
                    </ul>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h2 class="h6 mb-0">Expiry risk</h2>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li v-for="batch in expiryRisk" :key="batch.id" class="list-group-item d-flex justify-content-between gap-2">
                            <span>
                                {{ batch.product?.name }}
                                <span class="d-block small text-muted">{{ batch.branch?.name ?? '—' }} | Batch {{ batch.batch_no }}</span>
                            </span>
                            <span class="text-danger fw-medium">{{ batch.expiry_date }}</span>
                        </li>
                        <li v-if="!expiryRisk.length" class="list-group-item small text-muted">No batches expiring within 30 days.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0">Batch stock</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Batch</th>
                            <th>Branch</th>
                            <th>Location</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">Stock value ({{ currencyCode() }})</th>
                            <th>Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="batch in batches.data" :key="batch.id">
                            <td>{{ batch.product?.name }}</td>
                            <td>{{ batch.batch_no }}</td>
                            <td>{{ batch.branch?.name ?? '—' }}</td>
                            <td>{{ batch.storage_location?.name ?? '—' }}</td>
                            <td class="text-end">{{ formatNumber(batch.quantity_on_hand) }}</td>
                            <td class="text-end">{{ formatMoney(Number(batch.quantity_on_hand || 0) * Number(batch.purchase_unit_cost || 0)) }}</td>
                            <td>{{ batch.expiry_date ?? '—' }}</td>
                        </tr>
                        <tr v-if="!batches.data?.length">
                            <td colspan="7" class="text-center text-muted py-4">No stock found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <PaginationLinks :links="batches.links" />
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
    batches: { type: Object, required: true },
    lowStock: { type: Array, default: () => [] },
    expiryRisk: { type: Array, default: () => [] },
    filters: { type: Object, required: true },
    branches: { type: Array, default: () => [] },
    branchLabel: { type: String, required: true },
    canViewAllBranches: { type: Boolean, default: false },
});

const { formatMoney, currencyCode } = useMoney();

const summaryCards = computed(() => [
    { label: 'Stock value', value: props.summary.stockValue, money: true },
    { label: 'Stock quantity', value: props.summary.stockQuantity, money: false },
    { label: 'Low stock', value: props.summary.lowStockCount, money: false },
    { label: 'Expired', value: props.summary.expiredCount, money: false },
    { label: 'Expiring soon', value: props.summary.expiringSoonCount, money: false },
    { label: 'Batches', value: props.summary.batchCount, money: false },
]);

function formatNumber(value) {
    return new Intl.NumberFormat().format(Number(value || 0));
}
</script>
