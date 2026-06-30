<template>
    <TenantShellLayout :page-title="t('purchases.supplier_bill_detail')">
        <Head :title="supplier.name" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3 supplier-bill-header">
            <div>
                <Link href="/purchases/supplier-bills" class="small text-decoration-none d-block mb-1">
                    ← {{ t('purchases.back_to_supplier_bills') }}
                </Link>
                <h1 class="h4 mb-1">{{ supplier.name }}</h1>
                <p v-if="supplier.phone" class="text-muted small mb-0">{{ supplier.phone }}</p>
            </div>
            <div class="card border-0 shadow-sm supplier-bill-due-card">
                <div class="card-body py-2 px-3 text-end">
                    <div class="text-muted small">{{ t('purchases.open_due') }}</div>
                    <div class="fs-5 fw-semibold text-danger">{{ formatMoney(supplier.open_due) }}</div>
                </div>
            </div>
        </div>

        <form
            v-if="branchLedgerEnabled && viewAllBranches && branches.length"
            class="card border-0 shadow-sm card-body mb-3 supplier-branch-filter"
            @submit.prevent="applyBranchFilter"
        >
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small mb-0">{{ t('purchases.filter_by_branch') }}</label>
                    <select v-model="branchId" class="form-select form-select-sm">
                        <option value="">{{ t('purchases.all_branches') }}</option>
                        <option v-for="b in branches" :key="b.id" :value="String(b.id)">
                            {{ b.name }} ({{ b.code }})
                        </option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-sm btn-primary">{{ t('purchases.filter') }}</button>
                </div>
            </div>
        </form>

        <div v-if="branchLedgerEnabled && branchBreakdown.length && viewAllBranches && !branchFilter" class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">{{ t('purchases.branch_breakdown') }}</div>
            <table class="table table-sm mb-0 supplier-branch-breakdown-table">
                <tbody>
                    <tr v-for="row in branchBreakdown" :key="row.branch_id">
                        <td>{{ row.branch_name }} <code class="small">{{ row.branch_code }}</code></td>
                        <td class="text-end text-danger fw-medium">{{ formatMoney(row.due) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">{{ t('purchases.open_invoices') }}</div>
            <div class="supplier-open-mobile-list d-md-none p-2">
                <div v-if="!openPurchases.length" class="text-muted text-center small py-3">
                    {{ t('purchases.no_open_invoices') }}
                </div>
                <div v-for="purchase in openPurchases" :key="purchase.id" class="supplier-open-card">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="min-w-0">
                            <Link :href="`/purchases/${purchase.id}`" class="fw-semibold text-decoration-none text-truncate d-block">
                                {{ purchase.invoice_no }}
                            </Link>
                            <div class="small text-muted">{{ formatDate(purchase.purchased_at) }}</div>
                        </div>
                        <span v-if="branchLedgerEnabled" class="badge text-bg-light border flex-shrink-0">
                            {{ purchase.branch?.name ?? '—' }}
                        </span>
                    </div>

                    <div class="supplier-open-card__amounts">
                        <div>
                            <span>{{ t('purchases.total') }}</span>
                            <strong>{{ formatMoney(purchase.total) }}</strong>
                        </div>
                        <div>
                            <span>{{ t('purchases.paid') }}</span>
                            <strong>{{ formatMoney(purchase.paid) }}</strong>
                        </div>
                        <div>
                            <span>{{ t('purchases.due') }}</span>
                            <strong class="text-danger">{{ formatMoney(purchase.due) }}</strong>
                        </div>
                    </div>

                    <form v-if="canManage" class="supplier-mobile-payment-form" @submit.prevent="submitPayment(purchase)">
                        <select v-model="paymentForms[purchase.id].method" class="form-select form-select-sm">
                            <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                        <input
                            v-model.number="paymentForms[purchase.id].amount"
                            type="number"
                            min="0.01"
                            :max="payFullAmount(purchase)"
                            step="0.01"
                            class="form-control form-control-sm"
                            required
                        />
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            @click="paymentForms[purchase.id].amount = payFullAmount(purchase)"
                        >
                            {{ t('purchases.pay_full') }}
                        </button>
                        <button type="submit" class="btn btn-sm btn-primary" :disabled="paymentForms[purchase.id].processing">
                            {{ t('purchases.pay') }}
                        </button>
                    </form>
                </div>
            </div>
            <div class="table-responsive d-none d-md-block supplier-open-table">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('purchases.invoice') }}</th>
                            <th>{{ t('purchases.date') }}</th>
                            <th v-if="branchLedgerEnabled">{{ t('purchases.invoice_branch') }}</th>
                            <th class="text-end">{{ t('purchases.total') }}</th>
                            <th class="text-end">{{ t('purchases.paid') }}</th>
                            <th class="text-end">{{ t('purchases.due') }}</th>
                            <th v-if="canManage" style="min-width: 18rem">{{ t('purchases.record_payment') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="purchase in openPurchases" :key="purchase.id">
                            <td>
                                <Link :href="`/purchases/${purchase.id}`" class="text-decoration-none fw-medium">
                                    {{ purchase.invoice_no }}
                                </Link>
                            </td>
                            <td>{{ formatDate(purchase.purchased_at) }}</td>
                            <td v-if="branchLedgerEnabled">
                                <span v-if="purchase.branch">{{ purchase.branch.name }}</span>
                                <span v-else>—</span>
                            </td>
                            <td class="text-end">{{ formatMoney(purchase.total) }}</td>
                            <td class="text-end">{{ formatMoney(purchase.paid) }}</td>
                            <td class="text-end text-danger fw-medium">{{ formatMoney(purchase.due) }}</td>
                            <td v-if="canManage">
                                <form class="d-flex flex-wrap gap-1 align-items-center" @submit.prevent="submitPayment(purchase)">
                                    <select v-model="paymentForms[purchase.id].method" class="form-select form-select-sm" style="width: 6.5rem">
                                        <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                                    </select>
                                    <input
                                        v-model.number="paymentForms[purchase.id].amount"
                                        type="number"
                                        min="0.01"
                                        :max="payFullAmount(purchase)"
                                        step="0.01"
                                        class="form-control form-control-sm"
                                        style="width: 5.5rem"
                                        required
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        @click="paymentForms[purchase.id].amount = payFullAmount(purchase)"
                                    >
                                        {{ t('purchases.pay_full') }}
                                    </button>
                                    <button type="submit" class="btn btn-sm btn-primary" :disabled="paymentForms[purchase.id].processing">
                                        {{ t('purchases.pay') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr v-if="!openPurchases.length">
                            <td :colspan="canManage ? (branchLedgerEnabled ? 7 : 6) : (branchLedgerEnabled ? 6 : 5)" class="text-muted text-center py-3">
                                {{ t('purchases.no_open_invoices') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">{{ t('purchases.payment_history') }}</div>
            <div class="supplier-payment-mobile-list d-md-none p-2">
                <div v-if="!paymentHistory.length" class="text-muted text-center small py-3">
                    {{ t('purchases.no_payments') }}
                </div>
                <div v-for="payment in paymentHistory" :key="payment.id" class="supplier-payment-card">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="min-w-0">
                            <Link
                                v-if="payment.purchase"
                                :href="`/purchases/${payment.purchase.id}`"
                                class="fw-semibold text-decoration-none text-truncate d-block"
                            >
                                {{ payment.purchase.invoice_no }}
                            </Link>
                            <div class="small text-muted">{{ formatDateTime(payment.paid_at) }}</div>
                        </div>
                        <strong class="text-nowrap">{{ formatMoney(payment.amount) }}</strong>
                    </div>
                    <div class="supplier-payment-card__meta">
                        <div>
                            <span>{{ t('purchases.payment_method') }}</span>
                            <strong>{{ paymentMethodLabel(payment.method) }}</strong>
                        </div>
                        <div v-if="branchLedgerEnabled">
                            <span>{{ t('purchases.paying_branch') }}</span>
                            <strong>{{ payment.paying_branch?.name ?? '—' }}</strong>
                        </div>
                        <div>
                            <span>{{ t('purchases.reference') }}</span>
                            <strong>{{ payment.reference || '—' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive d-none d-md-block supplier-payment-table">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('purchases.date') }}</th>
                            <th>{{ t('purchases.invoice') }}</th>
                            <th v-if="branchLedgerEnabled">{{ t('purchases.paying_branch') }}</th>
                            <th>{{ t('purchases.payment_method') }}</th>
                            <th class="text-end">{{ t('purchases.amount') }}</th>
                            <th>{{ t('purchases.reference') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payment in paymentHistory" :key="payment.id">
                            <td>{{ formatDateTime(payment.paid_at) }}</td>
                            <td>
                                <Link
                                    v-if="payment.purchase"
                                    :href="`/purchases/${payment.purchase.id}`"
                                    class="text-decoration-none"
                                >
                                    {{ payment.purchase.invoice_no }}
                                </Link>
                            </td>
                            <td v-if="branchLedgerEnabled">
                                {{ payment.paying_branch?.name ?? '—' }}
                            </td>
                            <td>{{ paymentMethodLabel(payment.method) }}</td>
                            <td class="text-end">{{ formatMoney(payment.amount) }}</td>
                            <td class="small text-muted">{{ payment.reference || '—' }}</td>
                        </tr>
                        <tr v-if="!paymentHistory.length">
                            <td :colspan="branchLedgerEnabled ? 6 : 5" class="text-muted text-center py-3">{{ t('purchases.no_payments') }}</td>
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
import { formatHumanDate as formatDate, formatHumanDateTime as formatDateTime } from '@/utils/dates';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref, watch } from 'vue';

const props = defineProps({
    supplier: { type: Object, required: true },
    openPurchases: { type: Array, default: () => [] },
    paymentHistory: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    branchLedgerEnabled: { type: Boolean, default: false },
    viewAllBranches: { type: Boolean, default: false },
    branches: { type: Array, default: () => [] },
    branchFilter: { type: Number, default: null },
    branchBreakdown: { type: Array, default: () => [] },
    crossBranchEnabled: { type: Boolean, default: true },
});

const { t } = useLocale();
const { formatMoney } = useMoney();
const branchId = ref(props.branchFilter ? String(props.branchFilter) : '');

const paymentForms = reactive({});

function defaultMethod() {
    return props.paymentMethods[0]?.value ?? 'cash';
}

function roundMoney(value) {
    return Math.round((Number(value) + Number.EPSILON) * 100) / 100;
}

function payFullAmount(purchase) {
    return roundMoney(purchase.due);
}

function ensurePaymentForms() {
    for (const purchase of props.openPurchases) {
        if (!paymentForms[purchase.id]) {
            paymentForms[purchase.id] = {
                method: defaultMethod(),
                amount: payFullAmount(purchase),
                processing: false,
            };
        }
    }
}

ensurePaymentForms();

watch(
    () => props.openPurchases,
    () => ensurePaymentForms(),
    { deep: true },
);

function paymentMethodLabel(method) {
    return props.paymentMethods.find((m) => m.value === method)?.label ?? method;
}

function applyBranchFilter() {
    const params = branchId.value ? { branch_id: branchId.value } : {};
    router.get(`/purchases/supplier-bills/${props.supplier.id}`, params, { preserveState: true });
}

function submitPayment(purchase) {
    const form = paymentForms[purchase.id];
    form.amount = roundMoney(form.amount);
    form.processing = true;
    router.post(
        `/purchases/${purchase.id}/payments`,
        {
            method: form.method,
            amount: form.amount,
            redirect: 'supplier_bill',
        },
        {
            preserveScroll: true,
            onFinish: () => {
                form.processing = false;
            },
        },
    );
}
</script>

<style scoped>
.supplier-open-table table,
.supplier-payment-table table {
    min-width: 760px;
}

.supplier-open-mobile-list,
.supplier-payment-mobile-list {
    display: grid;
    gap: 0.65rem;
}

.supplier-open-card,
.supplier-payment-card {
    background: #fff;
    border: 1px solid #edf0f2;
    border-radius: 0.75rem;
    padding: 0.75rem;
}

.supplier-open-card__amounts {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.45rem;
}

.supplier-open-card__amounts > div,
.supplier-payment-card__meta > div {
    background: #f8f9fa;
    border: 1px solid #eef0f2;
    border-radius: 0.55rem;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    padding: 0.45rem 0.55rem;
}

.supplier-open-card__amounts span,
.supplier-payment-card__meta span {
    color: #6c757d;
    font-size: 0.74rem;
}

.supplier-open-card__amounts strong,
.supplier-payment-card__meta strong {
    font-size: 0.86rem;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.supplier-mobile-payment-form {
    border-top: 1px solid #eef0f2;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.45rem;
    margin-top: 0.65rem;
    padding-top: 0.65rem;
}

.supplier-payment-card__meta {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.45rem;
}

@media (max-width: 575.98px) {
    .supplier-bill-header {
        align-items: stretch !important;
    }

    .supplier-bill-due-card {
        width: 100%;
    }

    .supplier-bill-due-card .card-body {
        align-items: center;
        display: flex;
        justify-content: space-between;
        text-align: left !important;
    }

    .supplier-branch-filter {
        padding: 0.85rem;
    }

    .supplier-branch-filter .form-select,
    .supplier-branch-filter .btn,
    .supplier-mobile-payment-form .form-select,
    .supplier-mobile-payment-form .form-control,
    .supplier-mobile-payment-form .btn {
        font-size: 0.84rem;
        min-height: 2.1rem;
        padding: 0.35rem 0.5rem;
    }

    .supplier-branch-breakdown-table {
        font-size: 0.84rem;
    }

    .supplier-open-card,
    .supplier-payment-card {
        padding: 0.65rem;
    }

    .supplier-open-card__amounts {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.35rem;
    }

    .supplier-open-card__amounts > div,
    .supplier-payment-card__meta > div {
        padding: 0.4rem 0.45rem;
    }

    .supplier-mobile-payment-form,
    .supplier-payment-card__meta {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .supplier-mobile-payment-form .btn {
        white-space: nowrap;
    }
}

@media (max-width: 380px) {
    .supplier-open-card__amounts,
    .supplier-mobile-payment-form,
    .supplier-payment-card__meta {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
