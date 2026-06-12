<template>
    <TenantShellLayout page-title="Supplier bills">
        <Head title="Supplier bills" />
        <h1 class="h4 mb-3">{{ t('purchases.supplier_bills') }}</h1>
        <p class="text-muted small">{{ t('purchases.supplier_bills_hint') }}</p>

        <form
            v-if="branchLedgerEnabled && viewAllBranches && branches.length"
            class="card border-0 shadow-sm card-body mb-3"
            @submit.prevent="applyBranchFilter"
        >
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-0">{{ t('purchases.filter_by_branch') }}</label>
                    <select v-model="branchId" class="form-select form-select-sm">
                        <option value="">{{ t('purchases.all_branches') }}</option>
                        <option v-for="b in branches" :key="b.id" :value="String(b.id)">
                            {{ b.name }} ({{ b.code }})
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary">{{ t('purchases.filter') }}</button>
                </div>
            </div>
        </form>

        <div class="card border-0 shadow-sm">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('purchases.supplier') }}</th>
                        <th class="text-end">{{ branchLedgerEnabled && !viewAllBranches ? t('purchases.branch_due') : t('purchases.open_due') }}</th>
                        <th class="text-end" style="width: 6rem">{{ t('purchases.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in suppliers.data" :key="s.id">
                        <td>{{ s.name }}</td>
                        <td class="text-end text-danger fw-medium">{{ formatMoney(s.purchases_sum_due) }}</td>
                        <td class="text-end">
                            <Link :href="billShowUrl(s.id)" class="btn btn-sm btn-outline-primary">
                                {{ t('purchases.view') }}
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!suppliers.data?.length">
                        <td colspan="3" class="text-muted text-center py-4">{{ t('purchases.no_supplier_due') }}</td>
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
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    suppliers: { type: Object, required: true },
    branchLedgerEnabled: { type: Boolean, default: false },
    viewAllBranches: { type: Boolean, default: false },
    branches: { type: Array, default: () => [] },
    branchFilter: { type: Number, default: null },
});

const { t } = useLocale();
const { formatMoney } = useMoney();
const branchId = ref(props.branchFilter ? String(props.branchFilter) : '');

function applyBranchFilter() {
    router.get('/purchases/supplier-bills', branchId.value ? { branch_id: branchId.value } : {}, { preserveState: true });
}

function billShowUrl(supplierId) {
    if (props.branchFilter) {
        return `/purchases/supplier-bills/${supplierId}?branch_id=${props.branchFilter}`;
    }
    return `/purchases/supplier-bills/${supplierId}`;
}
</script>
