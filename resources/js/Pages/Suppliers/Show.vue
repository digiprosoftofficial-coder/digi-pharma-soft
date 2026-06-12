<template>
    <TenantShellLayout :page-title="supplier.name">
        <Head :title="supplier.name" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div v-if="formError" class="alert alert-danger small">{{ formError }}</div>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <Link href="/suppliers" class="small text-decoration-none d-block mb-1">← Suppliers</Link>
                <h1 class="h4 mb-1">{{ supplier.name }}</h1>
                <p v-if="supplier.phone" class="text-muted small mb-0">{{ supplier.phone }}</p>
                <p v-if="supplier.email" class="text-muted small mb-0">{{ supplier.email }}</p>
            </div>
            <div class="d-flex gap-2">
                <Link :href="`/purchases/supplier-bills/${supplier.id}`" class="btn btn-sm btn-primary">
                    {{ t('purchases.view_bills') }}
                </Link>
                <Link v-if="can('suppliers.manage')" :href="`/suppliers/${supplier.id}/edit`" class="btn btn-sm btn-outline-secondary">
                    Edit
                </Link>
                <button
                    v-if="can('suppliers.manage')"
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    @click="remove"
                >
                    Delete
                </button>
            </div>
        </div>

        <div v-if="branchLedgerEnabled" class="row g-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm card-body">
                    <div class="text-muted small">{{ t('purchases.total_due') }}</div>
                    <div class="fs-4 fw-semibold" :class="Number(totalDue) > 0 ? 'text-danger' : ''">
                        {{ formatMoney(totalDue) }}
                    </div>
                </div>
            </div>
            <div v-if="branchBreakdown.length" class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white fw-semibold">{{ t('purchases.branch_breakdown') }}</div>
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ t('branches.name') }}</th>
                                <th class="text-end">{{ t('purchases.open_due') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in branchBreakdown" :key="row.branch_id">
                                <td>{{ row.branch_name }} <code class="small">{{ row.branch_code }}</code></td>
                                <td class="text-end text-danger fw-medium">{{ formatMoney(row.due) }}</td>
                                <td class="text-end">
                                    <Link
                                        :href="`/purchases/supplier-bills/${supplier.id}?branch_id=${row.branch_id}`"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        {{ t('purchases.view') }}
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    supplier: { type: Object, required: true },
    totalDue: { type: [Number, String], default: 0 },
    branchBreakdown: { type: Array, default: () => [] },
    branchLedgerEnabled: { type: Boolean, default: false },
    viewAllBranches: { type: Boolean, default: false },
});

const { t } = useLocale();
const { formatMoney } = useMoney();
const { can } = usePermissions();
const page = usePage();

const formError = computed(() => page.props.errors?.supplier);

function hasPurchaseHistory() {
    return Number(props.supplier.purchases_count) > 0 || Number(props.supplier.purchase_returns_count) > 0;
}

function remove() {
    if (hasPurchaseHistory()) {
        window.alert(t('suppliers.cannot_delete_has_purchases'));
        return;
    }
    if (!window.confirm(t('suppliers.delete_confirm', { name: props.supplier.name }))) {
        return;
    }
    router.delete(`/suppliers/${props.supplier.id}`);
}
</script>
