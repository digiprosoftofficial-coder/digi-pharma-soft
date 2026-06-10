<template>
    <TenantShellLayout page-title="Supplier bills">
        <Head title="Supplier bills" />
        <h1 class="h4 mb-3">{{ t('purchases.supplier_bills') }}</h1>
        <p class="text-muted small">{{ t('purchases.supplier_bills_hint') }}</p>
        <div class="card border-0 shadow-sm">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('purchases.supplier') }}</th>
                        <th class="text-end">{{ t('purchases.open_due') }}</th>
                        <th class="text-end" style="width: 6rem">{{ t('purchases.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in suppliers.data" :key="s.id">
                        <td>{{ s.name }}</td>
                        <td class="text-end text-danger fw-medium">{{ formatMoney(s.purchases_sum_due) }}</td>
                        <td class="text-end">
                            <Link :href="`/purchases/supplier-bills/${s.id}`" class="btn btn-sm btn-outline-primary">
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
import { Head, Link } from '@inertiajs/vue3';

defineProps({ suppliers: { type: Object, required: true } });

const { t } = useLocale();
const { formatMoney } = useMoney();
</script>
