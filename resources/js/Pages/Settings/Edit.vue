<template>
    <TenantShellLayout page-title="Settings">
        <Head title="Settings" />
        <h1 class="h4 mb-3">Pharmacy settings</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">Business name</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Slug</label>
                <input :value="tenant.slug" class="form-control" disabled />
                <div class="form-text">Read-only. Contact support to change subdomain.</div>
            </div>
            <div class="mb-2">
                <label class="form-label">Phone</label>
                <input v-model="form.settings.phone" class="form-control" />
            </div>
            <div class="mb-2">
                <label class="form-label">Address</label>
                <textarea v-model="form.settings.address" class="form-control" rows="2"></textarea>
            </div>
            <div class="mb-2">
                <label class="form-label">Currency</label>
                <select v-model="form.settings.currency" class="form-select">
                    <option v-for="code in currencies" :key="code" :value="code">
                        {{ currencyLabel(code) }}
                    </option>
                </select>
                <div class="form-text">
                    Platform default: <strong>{{ platformDefaultCurrency }}</strong>. Changing this updates every monetary input and display in your pharmacy.
                </div>
                <div v-if="form.errors['settings.currency']" class="text-danger small">{{ form.errors['settings.currency'] }}</div>
            </div>
            <div v-if="supplierBranchLedgerEnabled" class="border-top pt-3 mt-3 mb-3">
                <h2 class="h6 mb-3">{{ t('purchases.supplier_bills') }}</h2>
                <div class="form-check mb-2">
                    <input
                        id="crossBranch"
                        v-model="form.settings.supplier_payments.cross_branch"
                        type="checkbox"
                        class="form-check-input"
                        :disabled="!can('settings.manage')"
                    />
                    <label class="form-check-label" for="crossBranch">{{ t('purchases.cross_branch_payment') }}</label>
                </div>
                <p class="form-text small">{{ t('purchases.cross_branch_payment_hint') }}</p>
                <div class="form-check mb-2">
                    <input
                        id="managersPay"
                        v-model="form.settings.supplier_payments.managers_can_pay"
                        type="checkbox"
                        class="form-check-input"
                        :disabled="!can('settings.manage')"
                    />
                    <label class="form-check-label" for="managersPay">{{ t('purchases.managers_can_pay') }}</label>
                </div>
                <p class="form-text small mb-0">{{ t('purchases.managers_can_pay_hint') }}</p>
            </div>
            <div class="mb-2">
                <label class="form-label">{{ t('sales.pos_rounding_label') }}</label>
                <select v-model="form.settings.invoice_rounding" class="form-select">
                    <option v-for="opt in roundingOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </select>
                <div class="form-text">{{ t('sales.pos_rounding_hint') }}</div>
            </div>
            <button v-if="can('settings.manage')" type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
            <p v-else class="small text-muted mb-0">View only — you need settings.manage to update.</p>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { usePermissions } from '@/composables/usePermissions';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    tenant: { type: Object, required: true },
    currencies: { type: Array, default: () => ['BDT', 'USD', 'EUR', 'GBP', 'INR', 'SAR'] },
    platformDefaultCurrency: { type: String, default: 'BDT' },
    roundingOptions: { type: Array, default: () => [{ value: 'none', label: 'None' }] },
    supplierBranchLedgerEnabled: { type: Boolean, default: false },
});

const { t } = useLocale();
const { can } = usePermissions();

const form = useForm({
    name: props.tenant.name,
    settings: {
        phone: props.tenant.settings?.phone ?? '',
        address: props.tenant.settings?.address ?? '',
        currency: props.tenant.settings?.currency ?? props.tenant.currency ?? props.platformDefaultCurrency,
        invoice_rounding: props.tenant.settings?.invoice_rounding ?? 'none',
        supplier_payments: {
            cross_branch: props.tenant.settings?.supplier_payments?.cross_branch ?? true,
            managers_can_pay: props.tenant.settings?.supplier_payments?.managers_can_pay ?? false,
        },
    },
});

const currencyNames = {
    BDT: 'Bangladeshi Taka',
    USD: 'US Dollar',
    EUR: 'Euro',
    GBP: 'Pound Sterling',
    INR: 'Indian Rupee',
    SAR: 'Saudi Riyal',
};

function currencyLabel(code) {
    let symbol = code;
    try {
        const parts = new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency: code,
            currencyDisplay: 'narrowSymbol',
        }).formatToParts(0);
        symbol = parts.find((p) => p.type === 'currency')?.value ?? code;
    } catch (e) {
        symbol = code;
    }
    const name = currencyNames[code] ?? code;
    return `${code} — ${name} (${symbol})`;
}

function submit() {
    if (!can('settings.manage')) {
        return;
    }
    form.put('/settings');
}
</script>
