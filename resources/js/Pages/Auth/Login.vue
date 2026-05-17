<template>
    <GuestLayout>
        <Head title="Log in" />
        <h1 class="h4 mb-4">Log in</h1>
        <form @submit.prevent="submit">
            <div v-if="form.errors.email" class="alert alert-danger">{{ form.errors.email }}</div>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input id="email" v-model="form.email" type="email" class="form-control" required autocomplete="username" />
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input id="password" v-model="form.password" type="password" class="form-control" required autocomplete="current-password" />
            </div>
            <div class="mb-3 form-check">
                <input id="remember" v-model="form.remember" type="checkbox" class="form-check-input" />
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">Log in</button>
                <Link href="/forgot-password" class="btn btn-link">Forgot password?</Link>
                <Link href="/register" class="btn btn-link">Create account</Link>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login');
}
</script>
