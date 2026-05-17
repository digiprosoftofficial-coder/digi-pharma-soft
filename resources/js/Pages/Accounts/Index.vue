<template>
    <TenantShellLayout page-title="General accounts">
        <Head title="Accounts" />
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">Ledger accounts</h1>
            <Link href="/accounts/create" class="btn btn-primary btn-sm">New account</Link>
        </div>
        <div class="card border-0 shadow-sm">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in accounts" :key="a.id">
                        <td>{{ a.code }}</td>
                        <td>{{ a.name }}</td>
                        <td>{{ a.type }}</td>
                        <td><Link :href="'/accounts/' + a.id" class="btn btn-sm btn-outline-primary">Entries</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ accounts: { type: Array, required: true } });
</script>
