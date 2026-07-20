<template>
    <GuestLayout>
        <Head :title="t('auth.sign_in')" />

        <div class="text-center mb-4">
            <div class="auth-form-icon mx-auto mb-3 text-primary" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                    <path d="M12 3 4 7v6c0 4.4 3.6 8 8 8s8-3.6 8-8V7z" />
                    <path d="M12 11v4M12 8h.01" />
                </svg>
            </div>
            <h1 class="h4 mb-1">{{ title }}</h1>
            <p class="text-muted small mb-0">{{ subtitle }}</p>
        </div>

        <form @submit.prevent="submit">
            <div v-if="form.errors.email" class="alert alert-danger py-2 small">{{ form.errors.email }}</div>

            <div class="mb-3">
                <label class="form-label" for="email">{{ t('auth.email') }}</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M4 4h16v16H4z" />
                            <path d="m4 7 8 6 8-6" />
                        </svg>
                    </span>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="form-control"
                        :placeholder="t('auth.email_placeholder')"
                        required
                        autocomplete="username"
                    />
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">{{ t('auth.password') }}</label>
                <div class="input-group">
                    <span class="input-group-text bg-white text-muted">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="10" rx="2" />
                            <path d="M8 11V8a4 4 0 0 1 8 0v3" />
                        </svg>
                    </span>
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="form-control"
                        :placeholder="t('auth.password_placeholder')"
                        required
                        autocomplete="current-password"
                    />
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        :aria-label="showPassword ? t('auth.hide_password') : t('auth.show_password')"
                        @click="showPassword = !showPassword"
                    >
                        <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M3 3l18 18" />
                            <path d="M10.6 10.6A2 2 0 0 0 12 14a2 2 0 0 0 1.4-.6" />
                            <path d="M9.9 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.1" />
                            <path d="M6.1 6.1C3.7 7.9 2 12 2 12s3.5 7 10 7c1.4 0 2.7-.3 3.9-.8" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between gap-2 mb-4">
                <div class="form-check mb-0">
                    <input id="remember" v-model="form.remember" type="checkbox" class="form-check-input" />
                    <label class="form-check-label small" for="remember">{{ t('auth.remember') }}</label>
                </div>
                <Link v-if="!platformOnly" :href="withTenant('/forgot-password')" class="small text-decoration-none">
                    {{ t('auth.forgot_password') }}
                </Link>
            </div>

            <button type="submit" class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-2" :disabled="form.processing">
                <span>{{ t('auth.sign_in') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M5 12h14M13 5l7 7-7 7" />
                </svg>
            </button>

            <p v-if="!platformOnly" class="text-center small text-muted mt-4 mb-0">
                {{ t('auth.no_account') }}
                <Link :href="withTenant('/register')" class="text-decoration-none fw-semibold">{{ t('auth.create_account') }}</Link>
            </p>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useAuthLinks } from '@/composables/useAuthLinks';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    platformOnly: { type: Boolean, default: false },
});

const { t } = useLocale();
const { withTenant } = useAuthLinks();
const showPassword = ref(false);

const title = computed(() => (props.platformOnly ? t('auth.platform_welcome') : t('auth.welcome_back')));
const subtitle = computed(() => (props.platformOnly ? t('auth.platform_subtitle') : t('auth.sign_in_subtitle')));

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    const url = props.platformOnly ? '/platform/login' : '/login';
    form.post(url, {
        onFinish: () => form.reset('password'),
    });
}
</script>

<style scoped>
.auth-form-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 3.25rem;
    height: 3.25rem;
    border-radius: 999px;
    background: rgba(var(--bs-primary-rgb), 0.12);
}
</style>
