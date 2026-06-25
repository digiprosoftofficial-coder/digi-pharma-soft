<template>
    <TenantShellLayout page-title="Branch Reports">
        <Head title="Branch Reports" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">Branch Reports</h1>
                <p class="small text-muted mb-0">Compare branch sales, purchases, dues, stock value, expiry risk, and transfers.</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">Report Hub</Link>
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
                            <th>Branch</th>
                            <th class="text-end">Sales</th>
                            <th class="text-end">Purchases</th>
                            <th class="text-end">Sales due</th>
                            <th class="text-end">Purchase due</th>
                            <th class="text-end">Stock value</th>
                            <th class="text-end">Expiry risk</th>
                            <th class="text-end">Transfers</th>
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
                            <td class="text-end">{{ row.transfers_out }} out / {{ row.transfers_in }} in</td>
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
    { label: 'Branches', value: props.summary.branches, money: false },
    { label: 'Sales', value: props.summary.sales, money: true },
    { label: 'Purchases', value: props.summary.purchases, money: true },
    { label: 'Stock value', value: props.summary.stockValue, money: true },
]);
</script>
