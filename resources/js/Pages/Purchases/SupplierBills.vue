<template>
    <TenantShellLayout page-title="Supplier bills">
        <Head title="Supplier bills" />
        <h1 class="h4 mb-3">Open payables by supplier</h1>
        <div class="card border-0 shadow-sm">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Supplier</th>
                        <th class="text-end">Open due (sum)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in suppliers.data" :key="s.id">
                        <td>{{ s.name }}</td>
                        <td class="text-end">{{ s.purchases_sum_due ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ suppliers: { type: Object, required: true } });
</script>
