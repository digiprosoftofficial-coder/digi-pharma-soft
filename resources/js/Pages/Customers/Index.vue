<template>
    <TenantShellLayout :page-title="t('tenant_nav.customers')">
        <Head :title="t('tenant_nav.customers')" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div v-if="formError" class="alert alert-danger small">{{ formError }}</div>
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0 d-lg-none">{{ t('tenant_nav.customers') }}</h1>
            <Link v-if="can('customers.manage')" href="/customers/create" class="btn btn-primary btn-sm">{{ t('customers.add_customer') }}</Link>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('common.name') }}</th>
                            <th>{{ t('customers.phone_label') }}</th>
                            <th class="text-end">{{ t('sales.due') }}</th>
                            <th class="text-end">{{ t('customers.loyalty_points') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in customers.data" :key="c.id">
                            <td>{{ c.name }}</td>
                            <td>{{ c.phone || '—' }}</td>
                            <td class="text-end" :class="Number(c.balance_due) > 0 ? 'text-danger fw-medium' : ''">
                                {{ formatMoney(c.balance_due) }}
                            </td>
                            <td class="text-end">{{ c.loyalty_points }}</td>
                        <td class="text-end text-nowrap">
                            <Link
                                v-if="Number(c.balance_due) > 0"
                                :href="`/sales/customer-bills/${c.id}`"
                                class="btn btn-sm btn-outline-primary me-1"
                            >
                                {{ t('customers.collect_due') }}
                            </Link>
                            <Link
                                v-if="can('customers.manage')"
                                :href="`/customers/${c.id}/edit`"
                                class="btn btn-sm btn-outline-secondary me-1"
                            >
                                {{ t('common.edit') }}
                            </Link>
                                <button
                                    v-if="can('customers.manage')"
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    @click="remove(c)"
                                >
                                    {{ t('common.delete') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!customers.data?.length">
                            <td colspan="5" class="text-muted text-center py-3">{{ t('customers.no_customers') }}</td>
                        </tr>
                    </tbody>
                </table>
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

defineProps({ customers: { type: Object, required: true } });

const { t } = useLocale();
const { formatMoney } = useMoney();
const { can } = usePermissions();
const page = usePage();

const formError = computed(() => page.props.errors?.customer);

function hasSaleHistory(customer) {
    return Number(customer.sales_count) > 0 || Number(customer.balance_due) > 0;
}

function remove(customer) {
    if (hasSaleHistory(customer)) {
        window.alert(t('customers.cannot_delete_has_sales'));
        return;
    }
    if (!window.confirm(t('customers.delete_confirm', { name: customer.name }))) {
        return;
    }
    router.delete(`/customers/${customer.id}`);
}
</script>
