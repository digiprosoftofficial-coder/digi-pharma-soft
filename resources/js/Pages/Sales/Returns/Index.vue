<template>
    <TenantShellLayout page-title="Returns">
        <Head title="Returns" />
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">Returns</h1>
            <Link href="/sales/returns/create" class="btn btn-primary btn-sm">New return</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Reference</th>
                        <th>Date</th>
                        <th class="text-end">Refund ({{ currencyCode() }})</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in saleReturns.data" :key="r.id">
                        <td>{{ r.reference_no }}</td>
                        <td>{{ r.returned_at }}</td>
                        <td class="text-end">{{ formatMoney(r.total_refund) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useMoney } from '@/composables/useMoney';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ saleReturns: { type: Object, required: true } });

const { formatMoney, currencyCode } = useMoney();
</script>
