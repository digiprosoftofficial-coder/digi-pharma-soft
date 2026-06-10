<template>
    <TenantShellLayout :page-title="t('purchases.supplier_bill_detail')">
        <Head :title="supplier.name" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <Link href="/purchases/supplier-bills" class="small text-decoration-none d-block mb-1">
                    ← {{ t('purchases.back_to_supplier_bills') }}
                </Link>
                <h1 class="h4 mb-1">{{ supplier.name }}</h1>
                <p v-if="supplier.phone" class="text-muted small mb-0">{{ supplier.phone }}</p>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2 px-3 text-end">
                    <div class="text-muted small">{{ t('purchases.open_due') }}</div>
                    <div class="fs-5 fw-semibold text-danger">{{ formatMoney(supplier.balance_due) }}</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">{{ t('purchases.open_invoices') }}</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('purchases.invoice') }}</th>
                            <th>{{ t('purchases.date') }}</th>
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
                            <td>{{ purchase.purchased_at }}</td>
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
                                        :max="Number(purchase.due)"
                                        step="0.01"
                                        class="form-control form-control-sm"
                                        style="width: 5.5rem"
                                        required
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        @click="paymentForms[purchase.id].amount = Number(purchase.due)"
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
                            <td :colspan="canManage ? 6 : 5" class="text-muted text-center py-3">{{ t('purchases.no_open_invoices') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">{{ t('purchases.payment_history') }}</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('purchases.date') }}</th>
                            <th>{{ t('purchases.invoice') }}</th>
                            <th>{{ t('purchases.payment_method') }}</th>
                            <th class="text-end">{{ t('purchases.amount') }}</th>
                            <th>{{ t('purchases.reference') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payment in paymentHistory" :key="payment.id">
                            <td>{{ payment.paid_at }}</td>
                            <td>
                                <Link
                                    v-if="payment.purchase"
                                    :href="`/purchases/${payment.purchase.id}`"
                                    class="text-decoration-none"
                                >
                                    {{ payment.purchase.invoice_no }}
                                </Link>
                            </td>
                            <td>{{ paymentMethodLabel(payment.method) }}</td>
                            <td class="text-end">{{ formatMoney(payment.amount) }}</td>
                            <td class="small text-muted">{{ payment.reference || '—' }}</td>
                        </tr>
                        <tr v-if="!paymentHistory.length">
                            <td colspan="5" class="text-muted text-center py-3">{{ t('purchases.no_payments') }}</td>
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
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    supplier: { type: Object, required: true },
    openPurchases: { type: Array, default: () => [] },
    paymentHistory: { type: Array, default: () => [] },
    paymentMethods: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
});

const { t } = useLocale();
const { formatMoney } = useMoney();

const paymentForms = reactive({});

function defaultMethod() {
    return props.paymentMethods[0]?.value ?? 'cash';
}

function ensurePaymentForms() {
    for (const purchase of props.openPurchases) {
        if (!paymentForms[purchase.id]) {
            paymentForms[purchase.id] = {
                method: defaultMethod(),
                amount: Number(purchase.due),
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

function submitPayment(purchase) {
    const form = paymentForms[purchase.id];
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
