<template>
    <TenantShellLayout page-title="Stock transfers">
        <Head title="Stock transfers" />
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">Transfers</h1>
            <Link href="/stock-transfers/create" class="btn btn-primary btn-sm">New transfer</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="t in transfers.data" :key="t.id">
                        <td>{{ t.transfer_no }}</td>
                        <td>{{ t.transferred_at }}</td>
                        <td>{{ t.status }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ transfers: { type: Object, required: true } });
</script>
