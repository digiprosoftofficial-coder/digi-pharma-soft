<template>
    <GuestLayout>
        <Head title="Verify email" />
        <h1 class="h4 mb-4">Verify your email</h1>
        <p class="text-muted">Please confirm your address before continuing.</p>
        <form @submit.prevent="submit" class="d-inline">
            <button type="submit" class="btn btn-primary" :disabled="form.processing">Resend email</button>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({});

function submit() {
    form.post('/email/verification-notification');
}
</script>
