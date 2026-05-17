<template>
    <TenantShellLayout page-title="Purchases">
        <Head title="Purchases" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Purchase list</h1>
            <Link v-if="$page.props.auth?.user?.permissions?.includes('purchases.manage')" href="/purchases/create" class="btn btn-primary btn-sm">New purchase</Link>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice</th>
                            <th>Supplier</th>
                            <th>Date</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in purchases.data" :key="p.id">
                            <td>{{ p.invoice_no }}</td>
                            <td>{{ p.supplier?.name }}</td>
                            <td>{{ p.purchased_at }}</td>
                            <td class="text-end">{{ p.total }}</td>
                            <td class="text-end">{{ p.due }}</td>
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

defineProps({ purchases: { type: Object, required: true } });
</script>
