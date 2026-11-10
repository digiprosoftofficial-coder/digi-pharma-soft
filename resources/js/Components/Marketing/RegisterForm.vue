<template>
    <form class="sp-form" :class="{ 'sp-form--modal': compact }" @submit.prevent="submit">
        <div v-if="page.props.flash?.success" class="sp-alert-success" role="status">{{ page.props.flash.success }}</div>

        <div class="sp-form-grid two">
            <div class="sp-field">
                <label for="reg-pharmacy">{{ t('platform.pharmacy_name') }}</label>
                <input id="reg-pharmacy" v-model="form.pharmacy_name" type="text" required autocomplete="organization" />
                <div v-if="form.errors.pharmacy_name" class="sp-field-error">{{ form.errors.pharmacy_name }}</div>
            </div>
            <div class="sp-field">
                <label for="reg-slug">Slug</label>
                <input id="reg-slug" v-model="form.slug" type="text" />
                <div v-if="form.errors.slug" class="sp-field-error">{{ form.errors.slug }}</div>
            </div>
            <div class="sp-field">
                <label for="reg-owner">{{ t('platform.owner_name') }}</label>
                <input id="reg-owner" v-model="form.owner_name" type="text" required autocomplete="name" />
                <div v-if="form.errors.owner_name" class="sp-field-error">{{ form.errors.owner_name }}</div>
            </div>
            <div class="sp-field">
                <label for="reg-phone">{{ t('platform.mobile') }}</label>
                <input
                    id="reg-phone"
                    v-model="form.phone"
                    type="tel"
                    required
                    autocomplete="tel"
                    inputmode="numeric"
                    maxlength="11"
                    pattern="01[3-9][0-9]{8}"
                    :placeholder="t('platform.mobile_placeholder')"
                    @input="normalizePhone"
                />
                <p class="sp-field-hint">{{ t('platform.phone_format_hint') }}</p>
                <div v-if="form.errors.phone" class="sp-field-error">{{ form.errors.phone }}</div>
            </div>
        </div>

        <div class="sp-form-grid two">
            <div class="sp-field">
                <label for="reg-email">{{ t('platform.email') }}</label>
                <div class="sp-input-row">
                    <input id="reg-email" v-model="form.owner_email" type="email" required autocomplete="email" />
                    <button
                        type="button"
                        class="sp-btn sp-btn-secondary sp-btn-compact"
                        :disabled="otpSending || !form.owner_email"
                        @click="sendOtp"
                    >
                        {{ t('platform.send_email_otp') }}
                    </button>
                </div>
                <p class="sp-field-hint">{{ t('platform.email_otp_hint') }}</p>
                <div v-if="form.errors.owner_email" class="sp-field-error">{{ form.errors.owner_email }}</div>
            </div>
            <div class="sp-field">
                <label for="reg-otp">{{ t('platform.otp_code') }}</label>
                <div class="sp-input-row">
                    <input id="reg-otp" v-model="otpCode" type="text" maxlength="6" inputmode="numeric" />
                    <button
                        type="button"
                        class="sp-btn sp-btn-secondary sp-btn-compact"
                        :disabled="!otpCode || !form.owner_email"
                        @click="verifyOtp"
                    >
                        {{ t('common.verify') || 'Verify' }}
                    </button>
                </div>
                <div v-if="page.props.flash?.otp_debug_code" class="sp-field-hint">Dev OTP: {{ page.props.flash.otp_debug_code }}</div>
            </div>
        </div>

        <div class="sp-form-grid two">
            <div class="sp-field">
                <label for="reg-password">{{ t('auth.password') }}</label>
                <input id="reg-password" v-model="form.owner_password" type="password" required autocomplete="new-password" />
                <div v-if="form.errors.owner_password" class="sp-field-error">{{ form.errors.owner_password }}</div>
            </div>
            <div class="sp-field">
                <label for="reg-password2">{{ t('auth.password_confirmation') }}</label>
                <input id="reg-password2" v-model="form.owner_password_confirmation" type="password" required autocomplete="new-password" />
            </div>
            <div class="sp-field">
                <label for="reg-plan">{{ t('platform.plan') }}</label>
                <select id="reg-plan" v-model="form.subscription_plan_id" required>
                    <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
            </div>
            <div class="sp-field">
                <label for="reg-interval">{{ t('platform.billing_interval') }}</label>
                <select id="reg-interval" v-model="form.billing_interval">
                    <option value="monthly">{{ t('platform.monthly') }}</option>
                    <option value="yearly">{{ t('platform.yearly') }}</option>
                </select>
            </div>
        </div>

        <label class="sp-check-row">
            <input v-model="form.start_trial" type="checkbox" />
            <span>{{ t('platform.start_free_trial') }}</span>
        </label>

        <template v-if="!form.start_trial">
            <div class="sp-form-grid two">
                <div class="sp-field">
                    <label for="reg-pay-method">{{ t('platform.payment_method') }}</label>
                    <select id="reg-pay-method" v-model="form.payment_method" required>
                        <option v-for="m in paymentMethods" :key="m" :value="m">{{ m }}</option>
                    </select>
                </div>
                <div class="sp-field">
                    <label for="reg-trx">{{ t('platform.transaction_reference') }}</label>
                    <input id="reg-trx" v-model="form.transaction_reference" type="text" required />
                </div>
            </div>
            <div class="sp-field-hint">
                <div v-if="paymentInfo.bkash">bKash: {{ paymentInfo.bkash }}</div>
                <div v-if="paymentInfo.nagad">Nagad: {{ paymentInfo.nagad }}</div>
                <div v-if="paymentInfo.bank">{{ paymentInfo.bank }}</div>
                <div v-if="paymentInfo.instructions">{{ paymentInfo.instructions }}</div>
            </div>
        </template>

        <button class="sp-btn sp-btn-primary" type="submit" :disabled="form.processing || !form.otp_session_token">
            {{ t('platform.create_account') }}
        </button>
        <div v-if="form.errors.otp_session_token" class="sp-field-error">{{ form.errors.otp_session_token }}</div>
    </form>
</template>

<script setup>
import { useLocale } from '@/composables/useLocale';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    plans: { type: Array, required: true },
    selectedPlanId: { type: [Number, String], default: null },
    selectedInterval: { type: String, default: 'monthly' },
    paymentMethods: { type: Array, required: true },
    paymentInfo: { type: Object, default: () => ({}) },
    compact: { type: Boolean, default: false },
});

const emit = defineEmits(['success']);

const { t } = useLocale();
const page = usePage();
const otpCode = ref('');
const otpSending = ref(false);

const form = useForm({
    pharmacy_name: '',
    slug: '',
    owner_name: '',
    owner_email: '',
    owner_password: '',
    owner_password_confirmation: '',
    phone: '',
    otp_session_token: '',
    subscription_plan_id: props.selectedPlanId || props.plans[0]?.id || null,
    billing_interval: props.selectedInterval || 'monthly',
    start_trial: true,
    payment_method: props.paymentMethods[0] || 'bkash',
    transaction_reference: '',
    branch_count: 1,
    address: '',
});

watch(() => page.props.flash?.otp_session_token, (token) => {
    if (token) {
        form.otp_session_token = token;
    }
});

function normalizePhone(event) {
    form.phone = String(event.target.value || '').replace(/\D/g, '').slice(0, 11);
}

function sendOtp() {
    otpSending.value = true;
    router.post('/get-started/otp', { owner_email: form.owner_email }, {
        preserveScroll: true,
        onFinish: () => { otpSending.value = false; },
    });
}

function verifyOtp() {
    router.post('/get-started/otp/verify', {
        owner_email: form.owner_email,
        code: otpCode.value,
    }, {
        preserveScroll: true,
    });
}

function submit() {
    if (!/^01[3-9]\d{8}$/.test(form.phone)) {
        form.setError('phone', t('platform.phone_invalid'));
        return;
    }
    form.clearErrors('phone');
    form.post('/get-started', {
        preserveScroll: true,
        onSuccess: () => emit('success'),
    });
}
</script>
