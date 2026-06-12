<template>
    <TenantShellLayout :page-title="customer.name">
        <Head :title="customer.name" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <Link href="/sales/customer-bills" class="small text-decoration-none d-block mb-1">
                    ← {{ t('sales.back_to_customer_bills') }}
                </Link>
                <h1 class="h4 mb-1">{{ customer.name }}</h1>
                <p v-if="customer.phone" class="text-muted small mb-0">{{ customer.phone }}</p>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2 px-3 text-end">
                    <div class="text-muted small">{{ t('sales.open_due') }}</div>
                    <div class="fs-5 fw-semibold text-danger">{{ formatMoney(customer.open_due) }}</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">{{ t('sales.open_invoices') }}</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('sales.sales_invoice') }}</th>
                            <th>{{ t('sales.date') }}</th>
                            <th class="text-end">{{ t('sales.total') }}</th>
                            <th class="text-end">{{ t('sales.paid') }}</th>
                            <th class="text-end">{{ t('sales.due') }}</th>
                            <th v-if="canManage" style="min-width: 18rem">{{ t('sales.record_payment') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sale in openSales" :key="sale.id">
                            <td>
                                <a
                                    :href="`/sales/${sale.id}/print`"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-decoration-none fw-medium"
                                >
                                    {{ sale.invoice_no }}
                                </a>
                            </td>
                            <td>{{ formatDate(sale.sold_at) }}</td>
                            <td class="text-end">{{ formatMoney(sale.rounded_total ?? sale.total) }}</td>
                            <td class="text-end">{{ formatMoney(sale.paid) }}</td>
                            <td class="text-end text-danger fw-medium">{{ formatMoney(sale.due) }}</td>
                            <td v-if="canManage">
                                <form class="d-flex flex-wrap gap-1 align-items-center" @submit.prevent="submitPayment(sale)">
                                    <select v-model="paymentForms[sale.id].method" class="form-select form-select-sm" style="width: 6.5rem">
                                        <option v-for="m in paymentMethods" :key="m.value" :value="m.value">{{ m.label }}</option>
                                    </select>
                                    <input
                                        v-model.number="paymentForms[sale.id].amount"
                                        type="number"
                                        min="0.01"
                                        :max="Number(sale.due)"
                                        step="0.01"
                                        class="form-control form-control-sm"
                                        style="width: 5.5rem"
                                        required
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        @click="paymentForms[sale.id].amount = Number(sale.due)"
                                    >
                                        {{ t('sales.pay_full') }}
                                    </button>
                                    <button type="submit" class="btn btn-sm btn-primary" :disabled="paymentForms[sale.id].processing">
                                        {{ t('sales.pay') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <tr v-if="!openSales.length">
                            <td :colspan="canManage ? 6 : 5" class="text-muted text-center py-3">{{ t('sales.no_open_invoices') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">{{ t('sales.payment_history') }}</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('sales.date') }}</th>
                            <th>{{ t('sales.sales_invoice') }}</th>
                            <th>{{ t('sales.payment_method') }}</th>
                            <th class="text-end">{{ t('sales.amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="payment in paymentHistory" :key="payment.id">
                            <td>{{ formatDate(payment.created_at) }}</td>
                            <td>
                                <a
                                    v-if="payment.sale"
                                    :href="`/sales/${payment.sale.id}/print`"
                                    target="_blank"
                                    rel="noopener"
                                    class="text-decoration-none"
                                >
                                    {{ payment.sale.invoice_no }}
                                </a>
                            </td>
                            <td>{{ paymentMethodLabel(payment.method) }}</td>
                            <td class="text-end">{{ formatMoney(payment.amount) }}</td>
                        </tr>
                        <tr v-if="!paymentHistory.length">
                            <td colspan="4" class="text-muted text-center py-3">{{ t('sales.no_payments') }}</td>
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
    customer: { type: Object, required: true },
    openSales: { type: Array, default: () => [] },
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
    for (const sale of props.openSales) {
        if (!paymentForms[sale.id]) {
            paymentForms[sale.id] = {
                method: defaultMethod(),
                amount: Number(sale.due),
                processing: false,
            };
        }
    }
}

ensurePaymentForms();

watch(
    () => props.openSales,
    () => ensurePaymentForms(),
    { deep: true },
);

function formatDate(value) {
    if (!value) return '—';
    return String(value).slice(0, 10);
}

function paymentMethodLabel(method) {
    return props.paymentMethods.find((m) => m.value === method)?.label ?? method;
}

function submitPayment(sale) {
    const form = paymentForms[sale.id];
    form.processing = true;
    router.post(
        `/sales/${sale.id}/payments`,
        {
            method: form.method,
            amount: form.amount,
            redirect: 'customer_bill',
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
