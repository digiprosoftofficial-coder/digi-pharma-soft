<template>
    <TenantShellLayout :page-title="t('reports.quick_purchase_title')">
        <Head :title="t('reports.quick_purchase_title')" />
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-0">{{ t('reports.quick_purchase_title') }}</h1>
                <p class="small text-muted mb-0">{{ t('reports.quick_purchase_help') }}</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">{{ t('reports.hub') }}</Link>
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
                        <h2 class="h6 mb-0">{{ t('reports.purchases_in_range') }}</h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ t('purchases.invoice') }}</th>
                                    <th>{{ t('purchases.date') }}</th>
                                    <th>{{ t('branches.title') }}</th>
                                    <th>{{ t('purchases.supplier') }}</th>
                                    <th class="text-end">{{ t('purchases.total') }} ({{ currencyCode() }})</th>
                                    <th class="text-end">{{ t('purchases.due') }} ({{ currencyCode() }})</th>
                                    <th>{{ t('sales.status') }}</th>
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
                                    <td colspan="7" class="text-center text-muted py-4">{{ t('reports.no_purchases_found') }}</td>
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
                        <h2 class="h6 mb-0">{{ t('reports.top_suppliers') }}</h2>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li v-for="supplier in topSuppliers" :key="supplier.supplier_id" class="list-group-item">
                            <div class="d-flex justify-content-between gap-2">
                                <span>{{ supplier.supplier_name }}</span>
                                <strong>{{ formatMoney(supplier.purchase_total) }}</strong>
                            </div>
                            <div class="small text-muted text-end">{{ t('sales.due') }} {{ formatMoney(supplier.due_total) }}</div>
                        </li>
                        <li v-if="!topSuppliers.length" class="list-group-item text-muted small">{{ t('reports.no_supplier_purchases') }}</li>
                    </ul>
                </div>
            </div>
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
const { t } = useLocale();

const summaryCards = computed(() => [
    { label: t('reports.purchase_total'), value: props.summary.purchaseTotal, money: true },
    { label: t('reports.return_credit'), value: props.summary.returnCredit, money: true },
    { label: t('reports.net_purchase'), value: props.summary.netPurchase, money: true },
    { label: t('purchases.due'), value: props.summary.due, money: true },
    { label: t('purchases.paid'), value: props.summary.paid, money: true },
    { label: t('reports.purchases'), value: props.summary.purchaseCount, money: false },
    { label: t('reports.returns'), value: props.summary.returnCount, money: false },
]);
</script>
