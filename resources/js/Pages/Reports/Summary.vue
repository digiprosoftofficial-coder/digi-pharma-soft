<template>
    <TenantShellLayout page-title="Sales summary">
        <Head title="Sales summary" />
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h1 class="h4 mb-0">Sales summary</h1>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">Reports hub</Link>
        </div>
        <form class="card border-0 shadow-sm card-body mb-3" @submit.prevent="applyRange">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-0">From</label>
                    <input v-model="range.date_from" type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">To</label>
                    <input v-model="range.date_to" type="date" class="form-control form-control-sm" />
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                </div>
                <div class="col-md-3 text-md-end">
                    <a :href="exportUrl" class="btn btn-sm btn-outline-primary">Export CSV</a>
                </div>
            </div>
        </form>
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm card-body">
                    <div class="text-muted small">Total sales (range)</div>
                    <div class="h3 mb-0">{{ salesTotal }}</div>
                </div>
            </div>
        </div>
        <h2 class="h6">Sales in range</h2>
        <div class="card border-0 shadow-sm table-responsive mb-4">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Due</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in salesInRange.data" :key="s.id">
                        <td>{{ s.invoice_no }}</td>
                        <td>{{ s.sold_at }}</td>
                        <td class="text-end">{{ s.total }}</td>
                        <td class="text-end">{{ s.due }}</td>
                        <td>{{ s.status }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <h2 class="h6">Top products (quantity)</h2>
        <ul class="list-group shadow-sm">
            <li v-for="row in topProducts" :key="row.product_id" class="list-group-item d-flex justify-content-between align-items-center">
                <span>Product #{{ row.product_id }}</span>
                <span>
                    <span class="badge bg-primary rounded-pill me-1">{{ row.qty }}</span>
                    <span class="text-muted small">rev {{ row.revenue }}</span>
                </span>
            </li>
            <li v-if="!topProducts.length" class="list-group-item text-muted small">No lines in this range.</li>
        </ul>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
    dateFrom: { type: String, required: true },
    dateTo: { type: String, required: true },
    salesTotal: { type: [Number, String], required: true },
    salesInRange: { type: Object, required: true },
    topProducts: { type: Array, required: true },
});

const range = reactive({
    date_from: props.dateFrom,
    date_to: props.dateTo,
});

const exportUrl = computed(() => {
    const q = new URLSearchParams({ date_from: range.date_from, date_to: range.date_to }).toString();

    return `/reports/export?${q}`;
});

function applyRange() {
    router.get('/reports/summary', { date_from: range.date_from, date_to: range.date_to }, { preserveState: true });
}
</script>
