<template>
    <TenantShellLayout page-title="Suppliers">
        <Head title="Suppliers" />
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">Suppliers</h1>
            <Link href="/suppliers/create" class="btn btn-primary btn-sm">Add supplier</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in suppliers.data" :key="s.id">
                        <td>{{ s.name }}</td>
                        <td>{{ s.phone || '—' }}</td>
                        <td class="text-end">
                            <Link :href="`/suppliers/${s.id}/edit`" class="btn btn-sm btn-outline-secondary">Edit</Link>
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

defineProps({ suppliers: { type: Object, required: true } });
</script>
