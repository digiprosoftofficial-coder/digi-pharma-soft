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
                        <th v-if="branchLedgerEnabled" class="text-end">
                            {{ viewAllBranches ? t('purchases.total_due') : t('purchases.branch_due') }}
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in suppliers.data" :key="s.id">
                        <td>
                            <Link :href="`/suppliers/${s.id}`" class="text-decoration-none fw-medium">{{ s.name }}</Link>
                        </td>
                        <td>{{ s.phone || '—' }}</td>
                        <td v-if="branchLedgerEnabled" class="text-end">
                            <span :class="Number(s.open_due) > 0 ? 'text-danger fw-medium' : 'text-muted'">
                                {{ formatMoney(s.open_due) }}
                            </span>
                        </td>
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
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    suppliers: { type: Object, required: true },
    branchLedgerEnabled: { type: Boolean, default: false },
    viewAllBranches: { type: Boolean, default: false },
});

const { t } = useLocale();
const { formatMoney } = useMoney();
</script>
