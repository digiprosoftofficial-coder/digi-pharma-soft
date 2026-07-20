<template>
    <GuestLayout>
        <Head :title="t('auth.confirm_title')" />
        <div class="text-center mb-4">
            <h1 class="h4 mb-1">{{ t('auth.confirm_title') }}</h1>
            <p class="text-muted small mb-0">{{ t('auth.confirm_subtitle') }}</p>
        </div>
        <form @submit.prevent="submit">
            <div class="mb-4">
                <label class="form-label" for="password">{{ t('auth.password') }}</label>
                <input id="password" v-model="form.password" type="password" class="form-control" required autocomplete="current-password" />
                <div v-if="form.errors.password" class="text-danger small mt-1">{{ form.errors.password }}</div>
            </div>
            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">{{ t('auth.confirm_password') }}</button>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, useForm } from '@inertiajs/vue3';

const { t } = useLocale();
const form = useForm({ password: '' });

function submit() {
    form.post('/user/confirm-password');
}
</script>
