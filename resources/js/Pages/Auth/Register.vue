<template>
    <GuestLayout>
        <Head :title="t('auth.register')" />
        <div class="text-center mb-4">
            <h1 class="h4 mb-1">{{ t('auth.register_title') }}</h1>
            <p class="text-muted small mb-0">{{ t('auth.register_subtitle') }}</p>
        </div>
        <form @submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label" for="tenant_slug">{{ t('auth.tenant_slug') }}</label>
                <input id="tenant_slug" v-model="form.tenant_slug" type="text" class="form-control" required autocomplete="off" />
                <div v-if="form.errors.tenant_slug" class="text-danger small mt-1">{{ form.errors.tenant_slug }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="name">{{ t('auth.name') }}</label>
                <input id="name" v-model="form.name" type="text" class="form-control" required autocomplete="name" />
                <div v-if="form.errors.name" class="text-danger small mt-1">{{ form.errors.name }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">{{ t('auth.email') }}</label>
                <input id="email" v-model="form.email" type="email" class="form-control" required autocomplete="username" />
                <div v-if="form.errors.email" class="text-danger small mt-1">{{ form.errors.email }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">{{ t('auth.password') }}</label>
                <input id="password" v-model="form.password" type="password" class="form-control" required autocomplete="new-password" />
                <div v-if="form.errors.password" class="text-danger small mt-1">{{ form.errors.password }}</div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password_confirmation">{{ t('auth.password_confirmation') }}</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="form-control" required autocomplete="new-password" />
            </div>
            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">{{ t('auth.register') }}</button>
            <p class="text-center small text-muted mt-4 mb-0">
                {{ t('auth.have_account') }}
                <Link :href="withTenant('/login')" class="text-decoration-none fw-semibold">{{ t('auth.sign_in') }}</Link>
            </p>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useAuthLinks } from '@/composables/useAuthLinks';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const { t } = useLocale();
const { withTenant } = useAuthLinks();
const page = usePage();

const form = useForm({
    tenant_slug: page.props.tenant?.slug ?? '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register');
}
</script>
