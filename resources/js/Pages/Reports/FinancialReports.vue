<template>
    <TenantShellLayout page-title="Financial Reports">
        <Head title="Financial Reports" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">Financial Reports</h1>
                <p class="small text-muted mb-0">Ledger entries, cash in/out, payment method breakdown, and account movement.</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">Report Hub</Link>
        </div>
        <SmartReportFilters
            :filters="filters"
            :branches="options.branches"
            :branch-label="branchLabel"
            :can-view-all-branches="canViewAllBranches"
            :options="options"
            :enabled-filters="['account', 'direction', 'paymentMethod']"
            report-path="/reports/finance"
            export-path="/reports/finance/export"
        />
        <SummaryCards :cards="summaryCards" />
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <BreakdownCard title="Sales payments" :rows="paymentBreakdown.sales" />
            </div>
            <div class="col-md-6">
                <BreakdownCard title="Purchase payments" :rows="paymentBreakdown.purchases" />
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Account</th>
                            <th>Type</th>
                            <th>Direction</th>
                            <th class="text-end">Amount</th>
                            <th>Memo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="entry in entries.data" :key="entry.id">
                            <td>{{ formatDate(entry.posted_at) }}</td>
                            <td>{{ entry.account?.code }} - {{ entry.account?.name }}</td>
                            <td>{{ entry.account?.type }}</td>
                            <td>{{ entry.direction }}</td>
                            <td class="text-end">{{ formatMoney(entry.amount) }}</td>
                            <td>{{ entry.memo }}</td>
                        </tr>
                        <tr v-if="!entries.data?.length">
                            <td colspan="6" class="text-center text-muted py-4">No ledger entries found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <PaginationLinks :links="entries.links" />
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
import { computed, h } from 'vue';

const props = defineProps({
    summary: { type: Object, required: true },
    entries: { type: Object, required: true },
    paymentBreakdown: { type: Object, required: true },
    filters: { type: Object, required: true },
    branchLabel: { type: String, required: true },
    canViewAllBranches: { type: Boolean, default: false },
    options: { type: Object, required: true },
});

const { formatMoney } = useMoney();

const summaryCards = computed(() => [
    { label: 'Credits', value: props.summary.credits, money: true },
    { label: 'Debits', value: props.summary.debits, money: true },
    { label: 'Net', value: props.summary.net, money: true },
    { label: 'Entries', value: props.summary.entryCount, money: false },
    { label: 'Sales payments', value: props.summary.salesPayments, money: true },
    { label: 'Purchase payments', value: props.summary.purchasePayments, money: true },
]);

const BreakdownCard = {
    props: { title: String, rows: Array },
    setup(cardProps) {
        return () => h('div', { class: 'card border-0 shadow-sm h-100' }, [
            h('div', { class: 'card-header bg-white' }, h('h2', { class: 'h6 mb-0' }, cardProps.title)),
            h('ul', { class: 'list-group list-group-flush' }, [
                ...(cardProps.rows || []).map((row) => h('li', { class: 'list-group-item d-flex justify-content-between' }, [
                    h('span', row.method || 'Unknown'),
                    h('strong', formatMoney(row.amount)),
                ])),
                (cardProps.rows || []).length ? null : h('li', { class: 'list-group-item text-muted small' }, 'No payments found.'),
            ]),
        ]);
    },
};

function formatDate(value) {
    return value ? String(value).slice(0, 16).replace('T', ' ') : '—';
}
</script>
