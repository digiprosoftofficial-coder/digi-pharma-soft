<template>
    <TenantShellLayout :page-title="t('reports.quick_branch_title')">
        <Head :title="t('reports.quick_branch_title')" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">{{ t('reports.quick_branch_title') }}</h1>
                <p class="small text-muted mb-0">{{ t('reports.quick_branch_help') }}</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">{{ t('reports.hub') }}</Link>
        </div>
        <SmartReportFilters
            :filters="filters"
            :branches="options.branches"
            :branch-label="branchLabel"
            :can-view-all-branches="canViewAllBranches"
            :options="options"
            :enabled-filters="[]"
            report-path="/reports/branches"
            export-path="/reports/branches/export"
        />
        <SummaryCards :cards="summaryCards" />
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('branches.title') }}</th>
                            <th class="text-end">{{ t('reports.sales') }}</th>
                            <th class="text-end">{{ t('reports.purchases') }}</th>
                            <th class="text-end">{{ t('reports.sales_due') }}</th>
                            <th class="text-end">{{ t('reports.purchase_due') }}</th>
                            <th class="text-end">{{ t('reports.stock_value') }}</th>
                            <th class="text-end">{{ t('reports.expiry_risk') }}</th>
                            <th class="text-end">{{ t('reports.transfers') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows.data" :key="row.branch_id">
                            <td>{{ row.branch_name }} <span class="text-muted small">{{ row.branch_code }}</span></td>
                            <td class="text-end">{{ formatMoney(row.sales_total) }}</td>
                            <td class="text-end">{{ formatMoney(row.purchase_total) }}</td>
                            <td class="text-end text-danger">{{ formatMoney(row.sales_due) }}</td>
                            <td class="text-end text-danger">{{ formatMoney(row.purchase_due) }}</td>
                            <td class="text-end">{{ formatMoney(row.stock_value) }}</td>
                            <td class="text-end">{{ row.expiry_risk }}</td>
                            <td class="text-end">{{ t('reports.transfer_counts', { out: row.transfers_out, in: row.transfers_in }) }}</td>
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
const { t } = useLocale();

const summaryCards = computed(() => [
    { label: t('branches.title'), value: props.summary.branches, money: false },
    { label: t('reports.sales'), value: props.summary.sales, money: true },
    { label: t('reports.purchases'), value: props.summary.purchases, money: true },
    { label: t('reports.stock_value'), value: props.summary.stockValue, money: true },
]);
</script>
