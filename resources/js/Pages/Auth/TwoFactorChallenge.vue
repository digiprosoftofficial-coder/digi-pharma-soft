<template>
    <GuestLayout>
        <Head :title="t('auth.two_factor_title')" />
        <div class="text-center mb-4">
            <h1 class="h4 mb-1">{{ t('auth.two_factor_title') }}</h1>
            <p class="text-muted small mb-0">{{ t('auth.two_factor_subtitle') }}</p>
        </div>
        <form @submit.prevent="submit">
            <div v-if="form.errors.code" class="alert alert-danger py-2 small">{{ form.errors.code }}</div>
            <div v-if="form.errors.recovery_code" class="alert alert-danger py-2 small">{{ form.errors.recovery_code }}</div>

            <div v-if="!recovery" class="mb-4">
                <label class="form-label" for="code">{{ t('auth.two_factor_code') }}</label>
                <input id="code" v-model="form.code" type="text" inputmode="numeric" class="form-control" autofocus autocomplete="one-time-code" />
            </div>
            <div v-else class="mb-4">
                <label class="form-label" for="recovery_code">{{ t('auth.two_factor_recovery') }}</label>
                <input id="recovery_code" v-model="form.recovery_code" type="text" class="form-control" autofocus autocomplete="one-time-code" />
            </div>

            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">{{ t('auth.two_factor_submit') }}</button>
            <button type="button" class="btn btn-link w-100 mt-2" @click="toggleRecovery">
                {{ recovery ? t('auth.two_factor_use_code') : t('auth.two_factor_use_recovery') }}
            </button>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const { t } = useLocale();
const recovery = ref(false);
const form = useForm({
    code: '',
    recovery_code: '',
});

function toggleRecovery() {
    recovery.value = !recovery.value;
    form.reset('code', 'recovery_code');
    form.clearErrors();
}

function submit() {
    form.post('/two-factor-challenge', {
        onFinish: () => form.reset('code', 'recovery_code'),
    });
}
</script>
