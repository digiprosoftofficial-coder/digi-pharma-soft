<template>
    <TenantShellLayout page-title="Sales list">
        <Head title="Sales" />
        <h1 class="h4 mb-3">Sales list</h1>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 2rem"></th>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="text-end">Total ({{ currencyCode() }})</th>
                        <th class="text-end">Due ({{ currencyCode() }})</th>
                        <th class="text-end" style="width: 10rem">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="s in sales.data" :key="s.id">
                        <tr :class="{ 'table-secondary': s.status === 'voided' }">
                            <td>
                                <button
                                    v-if="s.lines?.length"
                                    type="button"
                                    class="btn btn-sm btn-link p-0 text-decoration-none"
                                    :aria-expanded="expanded === s.id"
                                    @click="toggle(s.id)"
                                >
                                    {{ expanded === s.id ? '−' : '+' }}
                                </button>
                            </td>
                            <td>{{ s.invoice_no }}</td>
                            <td>{{ formatHumanDateTime(s.sold_at) }}</td>
                            <td>
                                <span v-if="s.status === 'voided'" class="badge text-bg-secondary">{{ t('sales.status_voided') }}</span>
                                <span v-else class="badge text-bg-success">{{ t('sales.status_posted') }}</span>
                            </td>
                            <td class="text-end">{{ formatMoney(s.total) }}</td>
                            <td class="text-end">{{ formatMoney(s.due) }}</td>
                            <td class="text-end text-nowrap">
                                <a
                                    :href="`/sales/${s.id}/print`"
                                    target="_blank"
                                    rel="noopener"
                                    class="btn btn-sm btn-outline-secondary me-1"
                                >
                                    {{ t('sales.print_sale') }}
                                </a>
                                <button
                                    v-if="canVoid && s.status === 'posted'"
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    @click="confirmVoid(s)"
                                >
                                    {{ t('sales.void_sale') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="expanded === s.id && s.lines?.length" class="table-light">
                            <td></td>
                            <td colspan="6" class="py-2">
                                <ul class="list-unstyled small mb-0">
                                    <li v-for="line in s.lines" :key="line.id" class="mb-1">
                                        <span class="fw-medium">{{ line.product?.name ?? 'Product' }}</span>
                                        <span class="text-muted">
                                            — {{ formatQty(line.quantity) }} {{ line.sell_unit ?? '' }}
                                            <template v-if="line.batch">
                                                · {{ batchLineLabel(line.batch) }}
                                            </template>
                                        </span>
                                        <span class="float-end text-end">
                                            <span>{{ formatMoney(line.line_total) }}</span>
                                            <span
                                                v-if="line.unit_cost_at_sale != null"
                                                class="d-block text-muted"
                                                style="font-size: 0.85em"
                                            >
                                                {{ lineCostProfitLabel(line) }}
                                            </span>
                                        </span>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { lineMarginAmount } from '@/composables/useBatchPricing';
import { formatBatchLabel } from '@/composables/usePosBatches';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { useQuantity } from '@/composables/useQuantity';
import { formatHumanDateTime } from '@/utils/dates';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    sales: { type: Object, required: true },
    canVoid: { type: Boolean, default: false },
});

const { t } = useLocale();
const { formatMoney, currencyCode } = useMoney();
const { formatQty } = useQuantity();
const expanded = ref(null);

function toggle(id) {
    expanded.value = expanded.value === id ? null : id;
}

function batchLineLabel(batch) {
    return formatBatchLabel(batch);
}

function lineCostProfitLabel(line) {
    const cost = Number(line.unit_cost_at_sale ?? 0);
    const profit = lineMarginAmount(line.quantity, line.unit_price, cost);

    return `${t('catalog.sale_line_cost', { cost: formatMoney(cost) })} · ${t('catalog.sale_line_profit', { amount: formatMoney(profit) })}`;
}

function confirmVoid(sale) {
    if (!window.confirm(t('sales.void_confirm'))) {
        return;
    }
    router.post(`/sales/${sale.id}/void`, {}, { preserveScroll: true });
}
</script>
