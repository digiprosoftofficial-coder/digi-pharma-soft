<template>
    <PlatformShellLayout :page-title="t('platform.settings_title')">
        <Head :title="t('platform.settings_title')" />
        <p class="text-muted small mb-3">{{ t('platform.settings_sub') }}</p>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div v-if="Object.keys(form.errors).length" class="alert alert-danger small" role="alert">
                {{ t('platform.form_has_errors') }}
            </div>

            <h2 class="h6">{{ t('platform.settings_section_defaults') }}</h2>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.settings_default_trial_days') }}</label>
                <input
                    v-model.number="form.default_trial_days"
                    type="number"
                    min="1"
                    max="365"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.default_trial_days }"
                    required
                />
                <div class="form-text">{{ t('platform.settings_default_trial_help') }}</div>
                <div v-if="form.errors.default_trial_days" class="invalid-feedback d-block">
                    {{ form.errors.default_trial_days }}
                </div>
            </div>

            <h2 class="h6 mt-4">{{ t('platform.settings_section_support') }}</h2>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.settings_support_email') }}</label>
                    <input
                        v-model="form.support_email"
                        type="email"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.support_email }"
                    />
                    <div v-if="form.errors.support_email" class="invalid-feedback d-block">
                        {{ form.errors.support_email }}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.settings_support_phone') }}</label>
                    <input v-model="form.support_phone" type="text" class="form-control" maxlength="64" />
                </div>
            </div>

            <h2 class="h6 mt-2">{{ t('platform.settings_section_sms') }}</h2>
            <p class="small text-muted">{{ t('platform.settings_sms_help') }}</p>
            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.settings_sms_provider') }}</label>
                    <select v-model="form.sms_provider" class="form-select">
                        <option value="">{{ t('platform.settings_sms_none') }}</option>
                        <option value="twilio">Twilio</option>
                        <option value="ssl_wireless">SSL Wireless</option>
                        <option value="other">{{ t('platform.settings_sms_other') }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.settings_sms_api_key') }}</label>
                    <input
                        v-model="form.sms_api_key"
                        type="password"
                        class="form-control"
                        :placeholder="settings.sms_api_key_set ? '••••••••' : ''"
                        autocomplete="new-password"
                    />
                    <div v-if="settings.sms_api_key_set" class="form-check mt-2">
                        <input id="clear-sms" v-model="form.clear_sms_api_key" type="checkbox" class="form-check-input" />
                        <label class="form-check-label small" for="clear-sms">{{ t('platform.settings_clear_sms_key') }}</label>
                    </div>
                </div>
            </div>

            <h2 class="h6 mt-4">{{ t('platform.settings_section_region') }}</h2>
            <p class="small text-muted">{{ t('platform.settings_region_help') }}</p>
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.settings_default_locale') }}</label>
                    <select v-model="form.default_locale" class="form-select" required>
                        <option value="en">English</option>
                        <option value="bn">বাংলা</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.settings_default_timezone') }}</label>
                    <input v-model="form.default_timezone" class="form-control" required />
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.settings_default_country') }}</label>
                    <input v-model="form.default_country_code" class="form-control" maxlength="2" required />
                </div>
            </div>

            <h2 class="h6 mt-4">{{ t('platform.settings_section_compliance') }}</h2>
            <p class="small text-muted">{{ t('platform.settings_compliance_help') }}</p>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.settings_audit_retention_days') }}</label>
                    <input
                        v-model.number="form.audit_log_retention_days"
                        type="number"
                        min="30"
                        max="3650"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.audit_log_retention_days }"
                        required
                    />
                    <div v-if="form.errors.audit_log_retention_days" class="invalid-feedback d-block">
                        {{ form.errors.audit_log_retention_days }}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.settings_export_retention_days') }}</label>
                    <input
                        v-model.number="form.compliance_export_retention_days"
                        type="number"
                        min="1"
                        max="90"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.compliance_export_retention_days }"
                        required
                    />
                </div>
            </div>

            <h2 class="h6 mt-4">{{ t('platform.settings_section_billing') }}</h2>
            <p class="small text-muted">{{ t('platform.settings_billing_help') }}</p>
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.settings_billing_grace_days') }}</label>
                    <input
                        v-model.number="form.billing_grace_days"
                        type="number"
                        min="0"
                        max="90"
                        class="form-control"
                        required
                    />
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.settings_default_currency') }}</label>
                    <select
                        v-model="form.default_currency"
                        class="form-select"
                        :class="{ 'is-invalid': form.errors.default_currency }"
                        required
                    >
                        <option v-for="code in currencies" :key="code" :value="code">
                            {{ currencyLabel(code) }}
                        </option>
                    </select>
                    <div class="form-text">{{ t('platform.settings_default_currency_help') }}</div>
                    <div v-if="form.errors.default_currency" class="invalid-feedback d-block">
                        {{ form.errors.default_currency }}
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input
                            id="auto-suspend-billing"
                            v-model="form.auto_suspend_on_payment_failure"
                            type="checkbox"
                            class="form-check-input"
                        />
                        <label class="form-check-label small" for="auto-suspend-billing">
                            {{ t('platform.settings_auto_suspend_payment') }}
                        </label>
                    </div>
                </div>
            </div>

            <h2 class="h6 mt-4">{{ t('platform.settings_section_features') }}</h2>
            <p class="small text-muted">{{ t('platform.settings_features_help') }}</p>
            <div class="mb-3">
                <div class="form-check">
                    <input id="f-pos" v-model="form.feature_flags.pos" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="f-pos">POS</label>
                </div>
                <div class="form-check">
                    <input id="f-reports" v-model="form.feature_flags.reports" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="f-reports">Reports</label>
                </div>
                <div class="form-check">
                    <input id="f-stock" v-model="form.feature_flags.stock_transfers" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="f-stock">Stock transfers</label>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
        </form>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: { type: Object, required: true },
    currencies: { type: Array, default: () => ['BDT', 'USD', 'EUR', 'GBP', 'INR', 'SAR'] },
});

function currencyLabel(code) {
    return t(`platform.currency_${String(code).toLowerCase()}`, code);
}

const { t } = useLocale();

const form = useForm({
    default_trial_days: props.settings.default_trial_days,
    support_email: props.settings.support_email ?? '',
    support_phone: props.settings.support_phone ?? '',
    sms_provider: props.settings.sms_provider ?? '',
    sms_api_key: '',
    clear_sms_api_key: false,
    feature_flags: {
        pos: props.settings.feature_flags?.pos ?? true,
        reports: props.settings.feature_flags?.reports ?? true,
        stock_transfers: props.settings.feature_flags?.stock_transfers ?? true,
    },
    audit_log_retention_days: props.settings.audit_log_retention_days ?? 365,
    compliance_export_retention_days: props.settings.compliance_export_retention_days ?? 7,
    billing_grace_days: props.settings.billing_grace_days ?? 7,
    auto_suspend_on_payment_failure: props.settings.auto_suspend_on_payment_failure ?? true,
    default_currency: props.settings.default_currency ?? 'BDT',
    default_locale: props.settings.default_locale ?? 'en',
    default_timezone: props.settings.default_timezone ?? 'Asia/Dhaka',
    default_country_code: props.settings.default_country_code ?? 'BD',
});

function submit() {
    form.put('/platform/settings', { preserveScroll: true });
}
</script>
