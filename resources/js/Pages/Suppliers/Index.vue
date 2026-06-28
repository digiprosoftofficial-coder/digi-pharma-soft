<template>
    <TenantShellLayout :page-title="t('tenant_nav.suppliers')">
        <Head :title="t('tenant_nav.suppliers')" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div v-if="formError" class="alert alert-danger small">{{ formError }}</div>
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">{{ t('tenant_nav.suppliers') }}</h1>
            <Link v-if="can('suppliers.manage')" href="/suppliers/create" class="btn btn-primary btn-sm">{{ t('suppliers.add_supplier') }}</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('common.name') }}</th>
                        <th>{{ t('customers.phone_label') }}</th>
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
                        <td class="text-end text-nowrap">
                            <Link v-if="can('suppliers.manage')" :href="`/suppliers/${s.id}/edit`" class="btn btn-sm btn-outline-secondary me-1">
                                {{ t('common.edit') }}
                            </Link>
                            <button
                                v-if="can('suppliers.manage')"
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                @click="remove(s)"
                            >
                                {{ t('common.delete') }}
                            </button>
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
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    suppliers: { type: Object, required: true },
    branchLedgerEnabled: { type: Boolean, default: false },
    viewAllBranches: { type: Boolean, default: false },
});

const { t } = useLocale();
const { formatMoney } = useMoney();
const { can } = usePermissions();
const page = usePage();

const formError = computed(() => page.props.errors?.supplier);

function hasPurchaseHistory(supplier) {
    return Number(supplier.purchases_count) > 0 || Number(supplier.purchase_returns_count) > 0;
}

function remove(supplier) {
    if (hasPurchaseHistory(supplier)) {
        window.alert(t('suppliers.cannot_delete_has_purchases'));
        return;
    }
    if (!window.confirm(t('suppliers.delete_confirm', { name: supplier.name }))) {
        return;
    }
    router.delete(`/suppliers/${supplier.id}`);
}
</script>
