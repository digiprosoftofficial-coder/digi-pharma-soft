<template>
    <TenantShellLayout page-title="Customer dues">
        <Head :title="t('sales.customer_bills')" />
        <h1 class="h4 mb-3">{{ t('sales.customer_bills') }}</h1>
        <p class="text-muted small">{{ t('sales.customer_bills_hint') }}</p>

        <div class="card border-0 shadow-sm">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('sales.customer') }}</th>
                        <th class="text-end">{{ t('sales.open_due') }}</th>
                        <th class="text-end" style="width: 6rem">{{ t('sales.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in customers.data" :key="c.id">
                        <td>{{ c.name }}</td>
                        <td class="text-end text-danger fw-medium">{{ formatMoney(c.open_due_sum) }}</td>
                        <td class="text-end">
                            <Link :href="`/sales/customer-bills/${c.id}`" class="btn btn-sm btn-outline-primary">
                                {{ t('sales.view') }}
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="!customers.data?.length">
                        <td colspan="3" class="text-muted text-center py-4">{{ t('sales.no_customer_due') }}</td>
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

defineProps({ customers: { type: Object, required: true } });

const { t } = useLocale();
const { formatMoney } = useMoney();
</script>
