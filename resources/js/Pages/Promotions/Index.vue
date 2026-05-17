<template>
    <TenantShellLayout page-title="Promotions">
        <Head title="Promotions" />
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">Discount coupons</h1>
            <Link href="/promotions/create" class="btn btn-primary btn-sm">New coupon</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th class="text-end">% off</th>
                        <th>Expires</th>
                        <th>Active</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in coupons.data" :key="c.id">
                        <td>{{ c.code }}</td>
                        <td class="text-end">{{ c.percent_off }}</td>
                        <td>{{ c.expires_at || '—' }}</td>
                        <td>{{ c.is_active ? 'Yes' : 'No' }}</td>
                        <td class="text-end">
                            <Link :href="`/promotions/${c.id}/edit`" class="btn btn-sm btn-outline-secondary">Edit</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ coupons: { type: Object, required: true } });
</script>
