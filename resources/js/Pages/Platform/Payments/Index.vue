<template>
    <PlatformShellLayout :page-title="t('platform.payments_title')">
        <Head :title="t('platform.nav_payments')" />
        <div class="d-flex gap-2 mb-3 flex-wrap">
            <span class="badge text-bg-warning">pending: {{ stats.pending }}</span>
            <span class="badge text-bg-success">verified: {{ stats.verified }}</span>
            <span class="badge text-bg-danger">rejected: {{ stats.rejected }}</span>
        </div>
        <form class="row g-2 mb-3" @submit.prevent="filter">
            <div class="col-md-3"><input v-model="local.search" class="form-control form-control-sm" :placeholder="t('common.search')" /></div>
            <div class="col-md-2">
                <select v-model="local.status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending">pending</option>
                    <option value="verified">verified</option>
                    <option value="rejected">rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <select v-model="local.method" class="form-select form-select-sm">
                    <option value="">All methods</option>
                    <option v-for="m in methods" :key="m" :value="m">{{ m }}</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Filter</button></div>
        </form>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Trx</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in payments.data" :key="p.id">
                        <td>{{ p.tenant?.name }}</td>
                        <td>{{ p.method }}</td>
                        <td>{{ (p.amount_cents / 100).toFixed(0) }} {{ p.currency }}</td>
                        <td><code>{{ p.transaction_reference || '—' }}</code></td>
                        <td>{{ p.status }}</td>
                        <td class="text-end" v-if="canManage && p.status === 'pending'">
                            <button class="btn btn-sm btn-success me-1" @click="verify(p)">Verify</button>
                            <button class="btn btn-sm btn-outline-danger" @click="reject(p)">Reject</button>
                        </td>
                        <td v-else></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    payments: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
    methods: { type: Array, required: true },
    canManage: { type: Boolean, default: false },
});

const { t } = useLocale();
const local = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    method: props.filters.method || '',
});

function filter() { router.get('/platform/payments', { ...local }, { preserveState: true }); }
function verify(p) { router.post(`/platform/payments/${p.id}/verify`); }
function reject(p) { router.post(`/platform/payments/${p.id}/reject`); }
</script>
