<template>
    <GuestLayout>
        <Head :title="t('auth.two_factor_setup_title')" />
        <div class="text-center mb-4">
            <h1 class="h4 mb-1">{{ t('auth.two_factor_setup_title') }}</h1>
            <p class="text-muted small mb-0">{{ t('auth.two_factor_setup_subtitle') }}</p>
        </div>

        <div v-if="enabled" class="alert alert-success small">
            {{ t('auth.two_factor_continue') }}
        </div>

        <div v-else class="d-flex flex-column gap-3">
            <button
                v-if="!confirmingLocal"
                type="button"
                class="btn btn-primary"
                :disabled="busy"
                @click="enable"
            >
                {{ t('auth.two_factor_enable') }}
            </button>

            <template v-if="confirmingLocal">
                <p class="small text-muted mb-0">{{ t('auth.two_factor_confirm_help') }}</p>
                <div v-if="qrSvg" class="text-center bg-white border rounded-3 p-3" v-html="qrSvg"></div>
                <p v-else class="small text-muted mb-0">{{ t('auth.two_factor_qr_loading') }}</p>

                <div>
                    <label class="form-label" for="code">{{ t('auth.two_factor_code') }}</label>
                    <input id="code" v-model="code" type="text" inputmode="numeric" class="form-control" autocomplete="one-time-code" />
                    <div v-if="error" class="text-danger small mt-1">{{ error }}</div>
                </div>

                <button type="button" class="btn btn-primary" :disabled="busy || !code" @click="confirm">
                    {{ t('auth.two_factor_confirm') }}
                </button>
            </template>

            <div v-if="recoveryCodes.length" class="border rounded-3 p-3 bg-light">
                <div class="fw-semibold mb-1">{{ t('auth.two_factor_recovery_codes') }}</div>
                <p class="small text-muted">{{ t('auth.two_factor_recovery_help') }}</p>
                <ul class="small mb-0 font-monospace">
                    <li v-for="item in recoveryCodes" :key="item">{{ item }}</li>
                </ul>
            </div>
        </div>

        <Link
            v-if="enabled"
            href="/platform/dashboard"
            class="btn btn-primary w-100 mt-3"
        >
            {{ t('auth.two_factor_continue') }}
        </Link>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    enabled: { type: Boolean, default: false },
    confirming: { type: Boolean, default: false },
});

const { t } = useLocale();
const busy = ref(false);
const confirmingLocal = ref(props.confirming);
const code = ref('');
const error = ref('');
const qrSvg = ref('');
const recoveryCodes = ref([]);
const enabled = ref(props.enabled);

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function api(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
            'Content-Type': 'application/json',
            ...(options.headers || {}),
        },
        credentials: 'same-origin',
    });

    if (! response.ok) {
        const payload = await response.json().catch(() => ({}));
        const message = payload.message
            || payload.errors?.code?.[0]
            || payload.errors?.recovery_code?.[0]
            || t('auth.two_factor_subtitle');
        throw new Error(message);
    }

    if (response.status === 204) {
        return null;
    }

    return response.json();
}

async function loadQr() {
    const data = await api('/user/two-factor-qr-code');
    qrSvg.value = data?.svg ?? '';
}

async function loadRecoveryCodes() {
    const data = await api('/user/two-factor-recovery-codes');
    recoveryCodes.value = Array.isArray(data) ? data : (data?.recovery_codes ?? []);
}

async function enable() {
    busy.value = true;
    error.value = '';
    try {
        await api('/user/two-factor-authentication', { method: 'POST', body: '{}' });
        confirmingLocal.value = true;
        await loadQr();
    } catch (e) {
        error.value = e.message;
    } finally {
        busy.value = false;
    }
}

async function confirm() {
    busy.value = true;
    error.value = '';
    try {
        await api('/user/confirmed-two-factor-authentication', {
            method: 'POST',
            body: JSON.stringify({ code: code.value }),
        });
        await loadRecoveryCodes();
        enabled.value = true;
        confirmingLocal.value = false;
        router.reload({ only: ['enabled', 'confirming'] });
    } catch (e) {
        error.value = e.message;
    } finally {
        busy.value = false;
    }
}

if (props.confirming) {
    loadQr().catch(() => {});
}
</script>
