<template>
    <MarketingLayout>
        <Head :title="t('platform.get_started')" />
        <div class="container py-5" style="max-width: 720px">
            <h1 class="page-heading mb-2">{{ t('platform.get_started') }}</h1>
            <p class="text-muted mb-4">{{ t('platform.register_sub') }}</p>

            <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ t('platform.pharmacy_name') }}</label>
                        <input v-model="form.pharmacy_name" class="form-control" required />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Slug</label>
                        <input v-model="form.slug" class="form-control" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ t('platform.owner_name') }}</label>
                        <input v-model="form.owner_name" class="form-control" required />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input v-model="form.owner_email" type="email" class="form-control" required />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input v-model="form.owner_password" type="password" class="form-control" required />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Confirm password</label>
                        <input v-model="form.owner_password_confirmation" type="password" class="form-control" required />
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">{{ t('platform.mobile') }}</label>
                        <div class="input-group">
                            <input v-model="form.phone" class="form-control" required />
                            <button type="button" class="btn btn-outline-secondary" :disabled="otpSending" @click="sendOtp">{{ t('platform.send_otp') }}</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">OTP</label>
                        <div class="input-group">
                            <input v-model="otpCode" class="form-control" maxlength="6" />
                            <button type="button" class="btn btn-outline-secondary" @click="verifyOtp">{{ t('common.verify') || 'Verify' }}</button>
                        </div>
                        <div v-if="page.props.flash?.otp_debug_code" class="form-text">Dev OTP: {{ page.props.flash.otp_debug_code }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ t('platform.plan') }}</label>
                        <select v-model="form.subscription_plan_id" class="form-select" required>
                            <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ t('platform.billing_interval') }}</label>
                        <select v-model="form.billing_interval" class="form-select">
                            <option value="monthly">{{ t('platform.monthly') }}</option>
                            <option value="yearly">{{ t('platform.yearly') }}</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input id="start_trial" v-model="form.start_trial" type="checkbox" class="form-check-input" />
                            <label class="form-check-label" for="start_trial">{{ t('platform.start_free_trial') }}</label>
                        </div>
                    </div>
                    <template v-if="!form.start_trial">
                        <div class="col-md-6">
                            <label class="form-label">{{ t('platform.payment_method') }}</label>
                            <select v-model="form.payment_method" class="form-select" required>
                                <option v-for="m in paymentMethods" :key="m" :value="m">{{ m }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ t('platform.transaction_reference') }}</label>
                            <input v-model="form.transaction_reference" class="form-control" required />
                        </div>
                        <div class="col-12 small text-muted">
                            <div v-if="paymentInfo.bkash">bKash: {{ paymentInfo.bkash }}</div>
                            <div v-if="paymentInfo.nagad">Nagad: {{ paymentInfo.nagad }}</div>
                            <div v-if="paymentInfo.bank">{{ paymentInfo.bank }}</div>
                            <div v-if="paymentInfo.instructions">{{ paymentInfo.instructions }}</div>
                        </div>
                    </template>
                </div>
                <button class="btn btn-primary mt-4" :disabled="form.processing || !form.otp_session_token">{{ t('platform.create_account') }}</button>
                <div v-if="form.errors.otp_session_token" class="text-danger small mt-2">{{ form.errors.otp_session_token }}</div>
            </form>
        </div>
    </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    plans: { type: Array, required: true },
    selectedPlanId: { type: Number, default: null },
    selectedInterval: { type: String, default: 'monthly' },
    currency: { type: String, default: 'BDT' },
    paymentMethods: { type: Array, required: true },
    paymentInfo: { type: Object, default: () => ({}) },
});

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
    if (token) form.otp_session_token = token;
});

function sendOtp() {
    otpSending.value = true;
    router.post('/get-started/otp', { phone: form.phone }, {
        preserveScroll: true,
        onFinish: () => { otpSending.value = false; },
    });
}

function verifyOtp() {
    router.post('/get-started/otp/verify', { phone: form.phone, code: otpCode.value }, {
        preserveScroll: true,
    });
}

function submit() {
    form.post('/get-started');
}
</script>
