<template>
    <TenantShellLayout :page-title="'Pharmacy dashboard'">
        <Head title="Dashboard" />
        <div v-if="$page.props.flash?.success" class="alert alert-success">{{ $page.props.flash.success }}</div>
        <div v-if="$page.props.flash?.info" class="alert alert-info">{{ $page.props.flash.info }}</div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">Today revenue</div>
                        <div class="display-6 fw-semibold">{{ formatMoney(kpis.revenueToday) }}</div>
                        <div class="small text-muted">Yesterday {{ formatMoney(kpis.revenueYesterday) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">Pending orders</div>
                        <div class="display-6 fw-semibold">{{ kpis.pendingOrders }}</div>
                        <div class="small text-muted">Sales with balance due</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">Low stock alerts</div>
                        <div class="display-6 fw-semibold">{{ kpis.lowStockCount }}</div>
                        <div class="small text-muted">Batches below minimum</div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">Customers</div>
                        <div class="display-6 fw-semibold">{{ kpis.customerCount }}</div>
                        <div class="small text-muted">Total records</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Revenue (last 7 days)</div>
                    <div class="card-body">
                        <div class="d-flex align-items-end gap-1" style="height: 140px">
                            <div
                                v-for="d in chartDays"
                                :key="d.label"
                                class="flex-fill bg-primary rounded-top opacity-75"
                                :style="{ height: barHeight(d.total) + '%', minHeight: '4px' }"
                                :title="d.label + ': ' + formatMoney(d.total)"
                            />
                        </div>
                        <div class="d-flex justify-content-between small text-muted mt-1">
                            <span v-for="d in chartDays" :key="'l-' + d.label">{{ d.label }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">Critical stock</div>
                    <ul class="list-group list-group-flush">
                        <li v-for="b in criticalStock" :key="b.id" class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ b.product?.name ?? 'Product' }}</span>
                            <span class="badge bg-danger-subtle text-danger">{{ b.quantity_on_hand }}</span>
                        </li>
                        <li v-if="!criticalStock?.length" class="list-group-item text-muted small">No low-stock batches.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">Recent activity</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Event</th>
                            <th>Description</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(a, idx) in activities" :key="idx">
                            <td><span class="badge bg-secondary-subtle text-secondary">{{ a.event }}</span></td>
                            <td>{{ a.description }}</td>
                            <td class="text-muted small">{{ formatDate(a.created_at) }}</td>
                        </tr>
                        <tr v-if="!activities?.length">
                            <td colspan="3" class="text-muted small">No activity yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    headline: { type: String, required: true },
    kpis: { type: Object, required: true },
    chartDays: { type: Array, required: true },
    criticalStock: { type: Array, required: true },
    activities: { type: Array, required: true },
});

function formatMoney(n) {
    return Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function barHeight(total) {
    const max = Math.max(...props.chartDays.map((d) => d.total), 1);
    return Math.min(100, (total / max) * 100);
}

function formatDate(iso) {
    if (!iso) {
        return '';
    }
    return new Date(iso).toLocaleString();
}
</script>
