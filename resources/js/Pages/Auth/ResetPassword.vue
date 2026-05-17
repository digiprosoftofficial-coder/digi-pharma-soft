<template>
    <GuestLayout>
        <Head :title="pageTitle" />
        <h1 class="h4 mb-2">{{ pageTitle }}</h1>
        <p v-if="invite" class="text-muted small mb-4">{{ t('platform.owner_invite_set_password_subtitle') }}</p>
        <form @submit.prevent="submit">
            <input type="hidden" name="token" :value="token" />
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input id="email" v-model="form.email" type="email" class="form-control" required readonly />
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input id="password" v-model="form.password" type="password" class="form-control" required />
            </div>
            <div class="mb-3">
                <label class="form-label" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="form-control" required />
            </div>
            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">
                {{ invite ? t('platform.owner_invite_action') : 'Update password' }}
            </button>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    email: { type: String, default: '' },
    token: { type: String, required: true },
    invite: { type: Boolean, default: false },
});

const { t } = useLocale();

const pageTitle = computed(() =>
    props.invite ? t('platform.owner_invite_set_password_title') : 'Reset password',
);

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password');
}
</script>
