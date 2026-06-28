<template>
    <TenantShellLayout :page-title="t('reports.quick_customer_title')">
        <Head :title="t('reports.quick_customer_title')" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">{{ t('reports.quick_customer_title') }}</h1>
                <p class="small text-muted mb-0">{{ t('reports.quick_customer_help') }}</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">{{ t('reports.hub') }}</Link>
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
                            <th>{{ t('sales.customer') }}</th>
                            <th>{{ t('customers.phone_label') }}</th>
                            <th class="text-end">{{ t('reports.invoices') }}</th>
                            <th class="text-end">{{ t('reports.sales') }}</th>
                            <th class="text-end">{{ t('sales.paid') }}</th>
                            <th class="text-end">{{ t('sales.due') }}</th>
                            <th class="text-end">{{ t('reports.loyalty') }}</th>
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
                            <td colspan="7" class="text-center text-muted py-4">{{ t('reports.no_customer_records') }}</td>
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
    { label: t('reports.customers'), value: props.summary.customerCount, money: false },
    { label: t('reports.sales_total'), value: props.summary.salesTotal, money: true },
    { label: t('sales.paid'), value: props.summary.paid, money: true },
    { label: t('sales.due'), value: props.summary.due, money: true },
]);

function number(value) {
    return new Intl.NumberFormat().format(Number(value || 0));
}
</script>
