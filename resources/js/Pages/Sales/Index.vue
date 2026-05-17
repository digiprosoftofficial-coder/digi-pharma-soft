<template>
    <TenantShellLayout page-title="Sales list">
        <Head title="Sales" />
        <h1 class="h4 mb-3">Sales list</h1>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Due</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in sales.data" :key="s.id">
                        <td>{{ s.invoice_no }}</td>
                        <td>{{ s.sold_at }}</td>
                        <td class="text-end">{{ s.total }}</td>
                        <td class="text-end">{{ s.due }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ sales: { type: Object, required: true } });
</script>
