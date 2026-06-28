<template>
    <TenantShellLayout :page-title="t('reports.quick_expiry_title')">
        <Head :title="t('reports.quick_expiry_title')" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">{{ t('reports.quick_expiry_title') }}</h1>
                <p class="small text-muted mb-0">{{ t('reports.quick_expiry_help') }}</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">{{ t('reports.hub') }}</Link>
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
                            <th>{{ t('sales.product') }}</th>
                            <th>{{ t('purchases.batch') }}</th>
                            <th>{{ t('branches.title') }}</th>
                            <th>{{ t('reports.location') }}</th>
                            <th class="text-end">{{ t('sales.qty') }}</th>
                            <th>{{ t('purchases.expiry') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows.data" :key="row.id">
                            <td>{{ row.product?.name }}</td>
                            <td>{{ row.batch_no }}</td>
                            <td>{{ row.branch?.name ?? '—' }}</td>
                            <td>{{ row.storage_location?.name ?? '—' }}</td>
                            <td class="text-end">{{ number(row.quantity_on_hand) }}</td>
                            <td>{{ formatHumanDate(row.expiry_date) }}</td>
                        </tr>
                        <tr v-if="!rows.data?.length">
                            <td colspan="6" class="text-center text-muted py-4">{{ t('reports.no_expiry_records') }}</td>
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
import { useLocale } from '@/composables/useLocale';
import PaginationLinks from '@/Pages/Reports/Partials/PaginationLinks.vue';
import SmartReportFilters from '@/Pages/Reports/Partials/SmartReportFilters.vue';
import SummaryCards from '@/Pages/Reports/Partials/SummaryCards.vue';
import { formatHumanDate } from '@/utils/dates';
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

const { t } = useLocale();

const summaryCards = computed(() => [
    { label: t('reports.batches'), value: props.summary.totalBatches, money: false },
    { label: t('reports.expired'), value: props.summary.expired, money: false },
    { label: t('reports.expiring_30_days'), value: props.summary.expiring30, money: false },
    { label: t('reports.stock_at_risk'), value: props.summary.stockAtRisk, money: false },
]);

function number(value) {
    return new Intl.NumberFormat().format(Number(value || 0));
}
</script>
