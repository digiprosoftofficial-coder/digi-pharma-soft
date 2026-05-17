<template>
    <TenantShellLayout page-title="Customers">
        <Head title="Customers" />
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0 d-lg-none">Customers</h1>
            <Link href="/customers/create" class="btn btn-primary btn-sm">Add customer</Link>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th class="text-end">Due</th>
                            <th class="text-end">Points</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in customers.data" :key="c.id">
                            <td>{{ c.name }}</td>
                            <td>{{ c.phone || '—' }}</td>
                            <td class="text-end">{{ c.balance_due }}</td>
                            <td class="text-end">{{ c.loyalty_points }}</td>
                            <td class="text-end">
                                <Link :href="`/customers/${c.id}/edit`" class="btn btn-sm btn-outline-secondary">Edit</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ customers: { type: Object, required: true } });
</script>
