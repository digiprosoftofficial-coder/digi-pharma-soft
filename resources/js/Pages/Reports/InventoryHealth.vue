<template>
    <TenantShellLayout :page-title="t('reports.quick_inventory_title')">
        <Head :title="t('reports.quick_inventory_title')" />
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-0">{{ t('reports.quick_inventory_title') }}</h1>
                <p class="small text-muted mb-0">{{ t('reports.quick_inventory_help') }}</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">{{ t('reports.hub') }}</Link>
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
                        <h2 class="h6 mb-0">{{ t('reports.low_stock') }}</h2>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li v-for="batch in lowStock" :key="batch.id" class="list-group-item d-flex justify-content-between gap-2">
                            <span>
                                {{ batch.product?.name }}
                                <span class="d-block small text-muted">{{ batch.branch?.name ?? '—' }} | {{ t('purchases.batch') }} {{ batch.batch_no }}</span>
                            </span>
                            <span class="text-danger fw-medium">{{ formatNumber(batch.quantity_on_hand) }}</span>
                        </li>
                        <li v-if="!lowStock.length" class="list-group-item small text-muted">{{ t('reports.no_low_stock_batches') }}</li>
                    </ul>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h2 class="h6 mb-0">{{ t('reports.expiry_risk') }}</h2>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li v-for="batch in expiryRisk" :key="batch.id" class="list-group-item d-flex justify-content-between gap-2">
                            <span>
                                {{ batch.product?.name }}
                                <span class="d-block small text-muted">{{ batch.branch?.name ?? '—' }} | {{ t('purchases.batch') }} {{ batch.batch_no }}</span>
                            </span>
                            <span class="text-danger fw-medium">{{ formatHumanDate(batch.expiry_date) }}</span>
                        </li>
                        <li v-if="!expiryRisk.length" class="list-group-item small text-muted">{{ t('reports.no_expiring_batches') }}</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h2 class="h6 mb-0">{{ t('reports.batch_stock') }}</h2>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('sales.product') }}</th>
                            <th>{{ t('purchases.batch') }}</th>
                            <th>{{ t('branches.title') }}</th>
                            <th>{{ t('reports.location') }}</th>
                            <th class="text-end">{{ t('sales.qty') }}</th>
                            <th class="text-end">{{ t('reports.stock_value') }} ({{ currencyCode() }})</th>
                            <th>{{ t('purchases.expiry') }}</th>
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
                            <td>{{ formatHumanDate(batch.expiry_date) }}</td>
                        </tr>
                        <tr v-if="!batches.data?.length">
                            <td colspan="7" class="text-center text-muted py-4">{{ t('reports.no_stock_found') }}</td>
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
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import PaginationLinks from '@/Pages/Reports/Partials/PaginationLinks.vue';
import ReportControls from '@/Pages/Reports/Partials/ReportControls.vue';
import SummaryCards from '@/Pages/Reports/Partials/SummaryCards.vue';
import { formatHumanDate } from '@/utils/dates';
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
const { t } = useLocale();

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
