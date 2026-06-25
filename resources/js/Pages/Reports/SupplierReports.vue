<template>
    <TenantShellLayout page-title="Supplier Reports">
        <Head title="Supplier Reports" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">Supplier Reports</h1>
                <p class="small text-muted mb-0">Supplier purchase, paid, due, return credit, and exportable performance view.</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">Report Hub</Link>
        </div>
        <SmartReportFilters
            :filters="filters"
            :branches="options.branches"
            :branch-label="branchLabel"
            :can-view-all-branches="canViewAllBranches"
            :options="options"
            :enabled-filters="['supplier', 'paymentStatus', 'dueStatus']"
            report-path="/reports/suppliers"
            export-path="/reports/suppliers/export"
        />
        <SummaryCards :cards="summaryCards" />
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Supplier</th>
                            <th>Phone</th>
                            <th class="text-end">Purchases</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Due</th>
                            <th class="text-end">Return credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows.data" :key="row.supplier_id">
                            <td>{{ row.supplier_name }}</td>
                            <td>{{ row.phone ?? '—' }}</td>
                            <td class="text-end">{{ row.purchase_count }}</td>
                            <td class="text-end">{{ formatMoney(row.purchase_total) }}</td>
                            <td class="text-end">{{ formatMoney(row.paid_total) }}</td>
                            <td class="text-end text-danger fw-medium">{{ formatMoney(row.due_total) }}</td>
                            <td class="text-end">{{ formatMoney(row.return_credit) }}</td>
                        </tr>
                        <tr v-if="!rows.data?.length">
                            <td colspan="7" class="text-center text-muted py-4">No supplier records found.</td>
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
    { label: 'Suppliers', value: props.summary.supplierCount, money: false },
    { label: 'Purchase total', value: props.summary.purchaseTotal, money: true },
    { label: 'Paid', value: props.summary.paid, money: true },
    { label: 'Due', value: props.summary.due, money: true },
]);
</script>
