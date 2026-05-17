<template>
    <GuestLayout>
        <Head title="Forgot password" />
        <h1 class="h4 mb-4">Forgot password</h1>
        <p class="text-muted small">We will email you a reset link.</p>
        <form @submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input id="email" v-model="form.email" type="email" class="form-control" required />
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">Send link</button>
                <Link href="/login" class="btn btn-link">Back to login</Link>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({ email: '' });

function submit() {
    form.post('/forgot-password');
}
</script>
