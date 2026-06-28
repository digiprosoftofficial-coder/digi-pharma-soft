<template>
    <TenantShellLayout :page-title="t('sales.sales_list')">
        <Head :title="t('sales.sales')" />
        <h1 class="h4 mb-3">{{ t('sales.sales_list') }}</h1>
        <div class="sales-mobile-list d-md-none">
            <div v-if="!sales.data?.length" class="card border-0 shadow-sm card-body text-center text-muted small">
                {{ t('common.no_results') }}
            </div>
            <div
                v-for="s in sales.data"
                :key="s.id"
                class="card border-0 shadow-sm mb-2"
                :class="{ 'opacity-75': s.status === 'voided' }"
            >
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate">{{ s.invoice_no }}</div>
                            <div class="small text-muted">{{ formatHumanDateTime(s.sold_at) }}</div>
                        </div>
                        <span v-if="s.status === 'voided'" class="badge text-bg-secondary">{{ t('sales.status_voided') }}</span>
                        <span v-else class="badge text-bg-success">{{ t('sales.status_posted') }}</span>
                    </div>

                    <div class="sales-mobile-card__amounts border rounded bg-light p-2 mb-2">
                        <div class="d-flex justify-content-between gap-2">
                            <span class="text-muted">{{ t('sales.total') }}</span>
                            <strong>{{ formatMoney(s.total) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between gap-2">
                            <span class="text-muted">{{ t('sales.due') }}</span>
                            <strong :class="{ 'text-danger': Number(s.due) > 0 }">{{ formatMoney(s.due) }}</strong>
                        </div>
                    </div>

                    <div v-if="expanded === s.id && s.lines?.length" class="sales-mobile-card__lines small mb-2">
                        <div v-for="line in s.lines" :key="line.id" class="sales-line-mobile-card">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate">{{ line.product?.name ?? t('sales.product') }}</div>
                                    <div class="text-muted text-truncate">
                                        <span>{{ formatQty(line.quantity) }} {{ line.sell_unit ?? '' }}</span>
                                        <template v-if="line.batch">
                                            <span> · {{ batchLineLabel(line.batch) }}</span>
                                        </template>
                                    </div>
                                </div>
                                <strong class="text-nowrap">{{ formatMoney(line.line_total) }}</strong>
                            </div>
                            <div v-if="line.unit_cost_at_sale != null" class="sales-line-mobile-card__profit">
                                {{ lineCostProfitLabel(line) }}
                            </div>
                        </div>
                    </div>

                    <div class="sales-mobile-card__actions">
                        <button
                            v-if="s.lines?.length"
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            :aria-expanded="expanded === s.id"
                            @click="toggle(s.id)"
                        >
                            {{ expanded === s.id ? t('common.hide', 'Hide') : t('common.view', 'View') }}
                        </button>
                        <a
                            :href="`/sales/${s.id}/print`"
                            target="_blank"
                            rel="noopener"
                            class="btn btn-sm btn-outline-secondary"
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
                    </div>
                </div>
            </div>
            <div v-if="sales.links?.length > 3" class="card border-0 shadow-sm overflow-hidden mt-2">
                <PaginationLinks :links="sales.links" />
            </div>
        </div>

        <div class="card border-0 shadow-sm table-responsive sales-table d-none d-md-block">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 2rem"></th>
                        <th>{{ t('sales.invoice') }}</th>
                        <th>{{ t('sales.date') }}</th>
                        <th>{{ t('sales.status') }}</th>
                        <th class="text-end">{{ t('sales.total') }} ({{ currencyCode() }})</th>
                        <th class="text-end">{{ t('sales.due') }} ({{ currencyCode() }})</th>
                        <th class="text-end" style="width: 10rem">{{ t('sales.actions') }}</th>
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
                        <tr v-if="expanded === s.id && s.lines?.length" class="table-light sales-expanded-row">
                            <td></td>
                            <td colspan="6" class="py-2">
                                <div class="sales-line-list">
                                    <div v-for="line in s.lines" :key="line.id" class="sales-line-row">
                                        <div class="sales-line-row__product min-w-0">
                                            <div class="fw-semibold text-truncate">{{ line.product?.name ?? t('sales.product') }}</div>
                                            <div v-if="line.batch" class="text-muted text-truncate">{{ batchLineLabel(line.batch) }}</div>
                                        </div>
                                        <div class="sales-line-row__qty text-muted">
                                            {{ formatQty(line.quantity) }} {{ line.sell_unit ?? '' }}
                                        </div>
                                        <div class="sales-line-row__amount text-end">
                                            <strong>{{ formatMoney(line.line_total) }}</strong>
                                            <div v-if="line.unit_cost_at_sale != null" class="text-muted">
                                                {{ lineCostProfitLabel(line) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <PaginationLinks :links="sales.links" />
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import PaginationLinks from '@/Pages/Reports/Partials/PaginationLinks.vue';
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

<style scoped>
.sales-table table {
    min-width: 720px;
}

.sales-mobile-card__amounts {
    font-size: 0.92rem;
}

.sales-line-mobile-card {
    border: 1px solid #e9ecef;
    border-radius: 0.65rem;
    background: #fff;
    padding: 0.65rem;
    margin-bottom: 0.5rem;
}

.sales-line-mobile-card__profit {
    border-top: 1px solid #f1f3f5;
    color: #6c757d;
    font-size: 0.82rem;
    margin-top: 0.45rem;
    padding-top: 0.45rem;
}

.sales-line-list {
    display: grid;
    gap: 0.4rem;
}

.sales-line-row {
    display: grid;
    grid-template-columns: minmax(14rem, 1fr) 8rem minmax(12rem, 16rem);
    align-items: center;
    gap: 0.75rem;
    border: 1px solid #e9ecef;
    border-radius: 0.65rem;
    background: #fff;
    padding: 0.6rem 0.75rem;
}

.sales-line-row__qty,
.sales-line-row__amount {
    font-size: 0.9rem;
}

.sales-line-row__amount .text-muted {
    font-size: 0.82rem;
}

.sales-mobile-card__actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.5rem;
}

.sales-mobile-card__actions .btn {
    min-height: 2.15rem;
    padding-right: 0.35rem;
    padding-left: 0.35rem;
    font-size: 0.78rem;
}

@media (max-width: 991.98px) {
    .sales-line-row {
        grid-template-columns: minmax(12rem, 1fr) 7rem minmax(10rem, 14rem);
    }
}
</style>
