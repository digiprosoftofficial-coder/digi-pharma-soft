<template>
    <TenantShellLayout page-title="Expiry Management">
        <Head title="Expiry Management" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">Expiry Management Reports</h1>
                <p class="small text-muted mb-0">Expired, expiring soon, batch-wise stock risk, and exportable expiry list.</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">Report Hub</Link>
        </div>
        <SmartReportFilters
            :filters="filters"
            :branches="options.branches"
            :branch-label="branchLabel"
            :can-view-all-branches="canViewAllBranches"
            :options="options"
            :enabled-filters="['product', 'category', 'manufacturer', 'batch', 'expiryStatus']"
            report-path="/reports/expiry"
            export-path="/reports/expiry/export"
        />
        <SummaryCards :cards="summaryCards" />
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Batch</th>
                            <th>Branch</th>
                            <th>Location</th>
                            <th class="text-end">Qty</th>
                            <th>Expiry</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows.data" :key="row.id">
                            <td>{{ row.product?.name }}</td>
                            <td>{{ row.batch_no }}</td>
                            <td>{{ row.branch?.name ?? '—' }}</td>
                            <td>{{ row.storage_location?.name ?? '—' }}</td>
                            <td class="text-end">{{ number(row.quantity_on_hand) }}</td>
                            <td>{{ row.expiry_date ?? '—' }}</td>
                        </tr>
                        <tr v-if="!rows.data?.length">
                            <td colspan="6" class="text-center text-muted py-4">No expiry records found.</td>
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

const summaryCards = computed(() => [
    { label: 'Batches', value: props.summary.totalBatches, money: false },
    { label: 'Expired', value: props.summary.expired, money: false },
    { label: 'Expiring 30 days', value: props.summary.expiring30, money: false },
    { label: 'Stock at risk', value: props.summary.stockAtRisk, money: false },
]);

function number(value) {
    return new Intl.NumberFormat().format(Number(value || 0));
}
</script>
