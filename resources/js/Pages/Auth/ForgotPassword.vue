<template>
    <GuestLayout>
        <Head :title="t('auth.forgot_title')" />
        <div class="text-center mb-4">
            <h1 class="h4 mb-1">{{ t('auth.forgot_title') }}</h1>
            <p class="text-muted small mb-0">{{ t('auth.forgot_subtitle') }}</p>
        </div>
        <form @submit.prevent="submit">
            <div class="mb-4">
                <label class="form-label" for="email">{{ t('auth.email') }}</label>
                <input id="email" v-model="form.email" type="email" class="form-control" required autocomplete="username" />
                <div v-if="form.errors.email" class="text-danger small mt-1">{{ form.errors.email }}</div>
            </div>
            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">{{ t('auth.send_reset_link') }}</button>
            <p class="text-center small mt-4 mb-0">
                <Link :href="withTenant('/login')" class="text-decoration-none">{{ t('auth.back_to_login') }}</Link>
            </p>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useAuthLinks } from '@/composables/useAuthLinks';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm } from '@inertiajs/vue3';

const { t } = useLocale();
const { withTenant } = useAuthLinks();
const form = useForm({ email: '' });

function submit() {
    form.post('/forgot-password');
}
</script>
