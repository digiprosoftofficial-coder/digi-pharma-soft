<template>
    <GuestLayout>
        <Head :title="t('auth.verify_title')" />
        <div class="text-center mb-4">
            <h1 class="h4 mb-1">{{ t('auth.verify_title') }}</h1>
            <p class="text-muted small mb-0">{{ t('auth.verify_subtitle') }}</p>
        </div>
        <form @submit.prevent="submit">
            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">{{ t('auth.resend_email') }}</button>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, useForm } from '@inertiajs/vue3';

const { t } = useLocale();
const form = useForm({});

function submit() {
    form.post('/email/verification-notification');
}
</script>
