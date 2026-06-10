<template>
    <TenantShellLayout :page-title="t('purchases.purchase_returns')">
        <Head :title="t('purchases.purchase_returns')" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">{{ t('purchases.purchase_returns') }}</h1>
            <Link
                v-if="$page.props.auth?.user?.permissions?.includes('purchases.manage')"
                href="/purchases/returns/create"
                class="btn btn-primary btn-sm"
            >
                {{ t('purchases.new_purchase_return') }}
            </Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('purchases.return_reference') }}</th>
                        <th>{{ t('purchases.supplier') }}</th>
                        <th>{{ t('purchases.date') }}</th>
                        <th>{{ t('purchases.invoice') }}</th>
                        <th class="text-end">{{ t('purchases.return_credit') }} ({{ currencyCode() }})</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in returns.data" :key="r.id">
                        <td class="fw-medium">{{ r.reference_no }}</td>
                        <td>{{ r.supplier?.name }}</td>
                        <td>{{ r.returned_at }}</td>
                        <td>
                            <Link
                                v-if="r.purchase"
                                :href="`/purchases/${r.purchase.id}`"
                                class="text-decoration-none"
                            >
                                {{ r.purchase.invoice_no }}
                            </Link>
                            <span v-else class="text-muted">—</span>
                        </td>
                        <td class="text-end">{{ formatMoney(r.total_credit) }}</td>
                    </tr>
                    <tr v-if="!returns.data?.length">
                        <td colspan="5" class="text-muted text-center py-4">{{ t('purchases.no_returns') }}</td>
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

defineProps({ returns: { type: Object, required: true } });

const { t } = useLocale();
const { formatMoney, currencyCode } = useMoney();
</script>
