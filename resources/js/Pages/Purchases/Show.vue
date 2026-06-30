<template>
    <TenantShellLayout :page-title="t('purchases.purchase_detail')">
        <Head :title="purchase.invoice_no" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div v-if="$page.props.errors?.void" class="alert alert-danger small">{{ $page.props.errors.void }}</div>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3 purchase-detail-header">
            <div>
                <Link href="/purchases" class="small text-decoration-none d-block mb-1">← {{ t('purchases.back_to_purchases') }}</Link>
                <h1 class="h4 mb-1">
                    {{ purchase.invoice_no }}
                    <span v-if="purchase.status === 'voided'" class="badge text-bg-secondary ms-1">{{ t('purchases.status_voided') }}</span>
                </h1>
                <p class="text-muted small mb-0">
                    {{ purchase.supplier?.name }} · {{ formatHumanDate(purchase.purchased_at) }}
                </p>
                <p v-if="purchase.notes" class="small text-muted mt-2 mb-0">
                    <span class="fw-medium">{{ t('purchases.notes') }}:</span> {{ purchase.notes }}
                </p>
            </div>
            <div class="d-flex gap-2 purchase-detail-actions">
                <button
                    v-if="canVoid"
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    @click="voidPurchase"
                >
                    {{ t('purchases.void_purchase') }}
                </button>
                <a
                    :href="`/purchases/${purchase.id}/print`"
                    target="_blank"
                    rel="noopener"
                    class="btn btn-sm btn-outline-secondary"
                >
                    {{ t('purchases.print') }}
                </a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small text-uppercase">{{ t('purchases.total') }}</div>
                        <div class="fs-5 fw-semibold">{{ formatMoney(purchase.total) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small text-uppercase">{{ t('purchases.paid') }}</div>
                        <div class="fs-5 fw-semibold">{{ formatMoney(purchase.paid) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small text-uppercase">{{ t('purchases.due') }}</div>
                        <div class="fs-5 fw-semibold" :class="{ 'text-danger': Number(purchase.due) > 0 }">
                            {{ formatMoney(purchase.due) }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small text-uppercase">{{ t('purchases.lines_count') }}</div>
                        <div class="fs-5 fw-semibold">{{ purchase.lines?.length ?? 0 }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">{{ t('purchases.purchase_lines') }}</div>
            <div class="purchase-lines-mobile d-lg-none p-2">
                <div v-if="!purchase.lines?.length" class="text-muted text-center small py-3">{{ t('purchases.no_lines') }}</div>
                <div v-for="line in purchase.lines" :key="line.id" class="purchase-line-card">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate">{{ line.product?.name ?? '—' }}</div>
                            <div v-if="line.product?.sku || line.product?.generic_name" class="small text-muted text-truncate">
                                <span v-if="line.product?.sku">{{ line.product.sku }}</span>
                                <span v-if="line.product?.sku && line.product?.generic_name"> · </span>
                                <span v-if="line.product?.generic_name">{{ line.product.generic_name }}</span>
                            </div>
                        </div>
                        <strong class="text-nowrap">{{ formatMoney(line.line_total) }}</strong>
                    </div>

                    <div class="purchase-line-card__meta">
                        <div>
                            <span>{{ t('purchases.batch') }}</span>
                            <strong>{{ line.batch_no || '—' }}</strong>
                        </div>
                        <div>
                            <span>{{ t('purchases.expiry') }}</span>
                            <strong>{{ formatHumanDate(line.expiry_date) }}</strong>
                        </div>
                        <div>
                            <span>{{ t('purchases.qty') }}</span>
                            <strong>{{ formatQty(line.quantity) }} {{ unitLabel(line.sell_unit) }}</strong>
                        </div>
                        <div>
                            <span>{{ t('purchases.stock_added') }}</span>
                            <strong class="text-success">+{{ formatQty(line.quantity_base) }} {{ unitLabel(line.product?.base_unit) }}</strong>
                        </div>
                        <div>
                            <span>{{ t('purchases.unit_cost') }}</span>
                            <strong>{{ formatMoney(line.unit_cost) }}</strong>
                        </div>
                        <div>
                            <span>{{ t('purchases.sale_price_mrp') }}</span>
                            <strong>{{ line.sale_price != null ? formatMoney(line.sale_price) : '—' }}</strong>
                        </div>
                    </div>

                    <div v-if="line.manufactured_at" class="small text-muted mt-2">
                        {{ t('purchases.manufactured_at') }}: {{ formatHumanDate(line.manufactured_at) }}
                    </div>
                </div>
            </div>
            <div class="table-responsive d-none d-lg-block purchase-lines-table">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('purchases.item') }}</th>
                            <th>{{ t('purchases.batch') }}</th>
                            <th>{{ t('purchases.expiry') }}</th>
                            <th>{{ t('purchases.manufactured_at') }}</th>
                            <th class="text-end">{{ t('purchases.qty') }}</th>
                            <th class="text-end">{{ t('purchases.sale_price_mrp') }}</th>
                            <th class="text-end">{{ t('purchases.stock_added') }}</th>
                            <th class="text-end">{{ t('purchases.unit_cost') }}</th>
                            <th class="text-end">{{ t('purchases.line_total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="line in purchase.lines" :key="line.id">
                            <td>
                                <div class="fw-medium">{{ line.product?.name ?? '—' }}</div>
                                <div v-if="line.product?.sku || line.product?.generic_name" class="small text-muted">
                                    <span v-if="line.product?.sku">{{ line.product.sku }}</span>
                                    <span v-if="line.product?.sku && line.product?.generic_name"> · </span>
                                    <span v-if="line.product?.generic_name">{{ line.product.generic_name }}</span>
                                </div>
                            </td>
                            <td>{{ line.batch_no }}</td>
                            <td>{{ formatHumanDate(line.expiry_date) }}</td>
                            <td>{{ formatHumanDate(line.manufactured_at) }}</td>
                            <td class="text-end text-nowrap">{{ formatQty(line.quantity) }} {{ unitLabel(line.sell_unit) }}</td>
                            <td class="text-end">{{ line.sale_price != null ? formatMoney(line.sale_price) : '—' }}</td>
                            <td class="text-end text-nowrap">
                                <span class="fw-medium text-success">+{{ formatQty(line.quantity_base) }}</span>
                                <span class="text-muted small"> {{ unitLabel(line.product?.base_unit) }}</span>
                            </td>
                            <td class="text-end">{{ formatMoney(line.unit_cost) }}</td>
                            <td class="text-end">{{ formatMoney(line.line_total) }}</td>
                        </tr>
                        <tr v-if="!purchase.lines?.length">
                            <td colspan="9" class="text-muted text-center py-3">{{ t('purchases.no_lines') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body purchase-summary-card">
                <div class="row justify-content-end">
                    <div class="col-12 col-md-5">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td class="text-muted">{{ t('purchases.subtotal') }}</td>
                                    <td class="text-end">{{ formatMoney(purchase.subtotal) }}</td>
                                </tr>
                                <tr v-if="Number(purchase.discount) > 0">
                                    <td class="text-muted">{{ t('purchases.discount') }}</td>
                                    <td class="text-end">−{{ formatMoney(purchase.discount) }}</td>
                                </tr>
                                <tr v-if="Number(purchase.tax) > 0">
                                    <td class="text-muted">{{ t('purchases.tax') }}</td>
                                    <td class="text-end">{{ formatMoney(purchase.tax) }}</td>
                                </tr>
                                <tr class="fw-semibold border-top">
                                    <td>{{ t('purchases.total') }}</td>
                                    <td class="text-end">{{ formatMoney(purchase.total) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">{{ t('purchases.paid') }}</td>
                                    <td class="text-end">{{ formatMoney(purchase.paid) }}</td>
                                </tr>
                                <tr v-if="Number(purchase.due) > 0">
                                    <td class="text-muted">{{ t('purchases.due') }}</td>
                                    <td class="text-end text-danger">{{ formatMoney(purchase.due) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="canManage && purchase.status !== 'voided' && Number(purchase.due) > 0" class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">{{ t('purchases.record_payment') }}</div>
            <form class="card-body" @submit.prevent="submitPayment">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-0">{{ t('purchases.payment_method') }}</label>
                        <select v-model="paymentForm.method" class="form-select form-select-sm">
                            <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label small mb-0">{{ t('purchases.amount') }}</label>
                        <input v-model.number="paymentForm.amount" type="number" min="0.01" :max="payFullAmount()" step="0.01" class="form-control form-control-sm" required />
                        <div v-if="paymentForm.errors.amount" class="text-danger small">{{ paymentForm.errors.amount }}</div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-2">
                        <label class="form-label small mb-0">{{ t('purchases.date') }}</label>
                        <input v-model="paymentForm.paid_at" type="date" class="form-control form-control-sm" />
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label small mb-0">{{ t('purchases.reference') }}</label>
                        <input v-model="paymentForm.reference" type="text" class="form-control form-control-sm" :placeholder="t('purchases.reference_placeholder')" />
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-1 purchase-payment-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="paymentForm.amount = payFullAmount()">
                            {{ t('purchases.pay_full') }}
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary" :disabled="paymentForm.processing">
                            {{ t('purchases.pay') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">{{ t('purchases.payment_history') }}</div>
            <div class="purchase-payments-mobile d-md-none p-2">
                <div v-if="!purchase.payments?.length" class="text-muted text-center small py-3">{{ t('purchases.no_payments') }}</div>
                <div v-for="payment in purchase.payments" :key="payment.id" class="purchase-payment-card">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div>
                            <div class="fw-semibold">{{ paymentMethodLabel(payment.method) }}</div>
                            <div class="small text-muted">{{ formatHumanDate(payment.paid_at) }}</div>
                        </div>
                        <strong class="text-nowrap">{{ formatMoney(payment.amount) }}</strong>
                    </div>
                    <div class="small text-muted">
                        {{ t('purchases.reference') }}: {{ payment.reference || '—' }}
                    </div>
                </div>
            </div>
            <div class="table-responsive d-none d-md-block purchase-payments-table">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('purchases.date') }}</th>
                            <th>{{ t('purchases.payment_method') }}</th>
                            <th class="text-end">{{ t('purchases.amount') }}</th>
                            <th>{{ t('purchases.reference') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payment in purchase.payments" :key="payment.id">
                            <td>{{ formatHumanDate(payment.paid_at) }}</td>
                            <td>{{ paymentMethodLabel(payment.method) }}</td>
                            <td class="text-end">{{ formatMoney(payment.amount) }}</td>
                            <td class="small text-muted">{{ payment.reference || '—' }}</td>
                        </tr>
                        <tr v-if="!purchase.payments?.length">
                            <td colspan="4" class="text-muted text-center py-3">{{ t('purchases.no_payments') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { unitLabel } from '@/composables/useProductUnits';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { useQuantity } from '@/composables/useQuantity';
import { formatHumanDate } from '@/utils/dates';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    purchase: { type: Object, required: true },
    paymentMethods: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    canVoid: { type: Boolean, default: false },
});

const { t } = useLocale();
const { formatMoney } = useMoney();
const { formatQty } = useQuantity();

const paymentForm = useForm({
    method: props.paymentMethods[0]?.value ?? 'cash',
    amount: payFullAmount(),
    paid_at: new Date().toISOString().slice(0, 10),
    reference: '',
    notes: '',
});

function paymentMethodLabel(method) {
    return props.paymentMethods.find((m) => m.value === method)?.label ?? method;
}

function roundMoney(value) {
    return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
}

function payFullAmount() {
    return roundMoney(props.purchase.due);
}

function submitPayment() {
    paymentForm.amount = roundMoney(paymentForm.amount);
    paymentForm.post(`/purchases/${props.purchase.id}/payments`, { preserveScroll: true });
}

function voidPurchase() {
    if (!window.confirm(t('purchases.void_confirm'))) {
        return;
    }
    router.post(`/purchases/${props.purchase.id}/void`, {}, { preserveScroll: true });
}

</script>

<style scoped>
.purchase-lines-table table {
    min-width: 980px;
}

.purchase-lines-mobile,
.purchase-payments-mobile {
    display: grid;
    gap: 0.65rem;
}

.purchase-line-card,
.purchase-payment-card {
    background: #fff;
    border: 1px solid #edf0f2;
    border-radius: 0.75rem;
    padding: 0.75rem;
}

.purchase-line-card__meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.45rem;
}

.purchase-line-card__meta > div {
    background: #f8f9fa;
    border: 1px solid #eef0f2;
    border-radius: 0.55rem;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    min-width: 0;
    padding: 0.45rem 0.55rem;
}

.purchase-line-card__meta span {
    color: #6c757d;
    font-size: 0.74rem;
}

.purchase-line-card__meta strong {
    font-size: 0.86rem;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (max-width: 767.98px) {
    .purchase-detail-header {
        align-items: stretch !important;
    }

    .purchase-detail-header > div:first-child,
    .purchase-detail-actions {
        width: 100%;
    }

    .purchase-detail-actions {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .purchase-detail-actions .btn {
        min-height: 2.15rem;
    }

    .purchase-summary-card {
        padding: 0.75rem;
    }

    .purchase-payment-actions {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .purchase-payment-actions .btn,
    form .form-control-sm,
    form .form-select-sm {
        font-size: 0.86rem;
        min-height: 2.2rem;
    }
}

@media (max-width: 575.98px) {
    .purchase-line-card,
    .purchase-payment-card {
        padding: 0.65rem;
    }

    .purchase-line-card__meta {
        gap: 0.35rem;
    }

    .purchase-line-card__meta > div {
        padding: 0.4rem 0.45rem;
    }

    .row.g-3 > [class*='col-'] .card-body {
        padding: 0.75rem;
    }

    .row.g-3 .fs-5 {
        font-size: 1rem !important;
    }
}

@media (max-width: 380px) {
    .purchase-line-card__meta,
    .purchase-detail-actions,
    .purchase-payment-actions {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
