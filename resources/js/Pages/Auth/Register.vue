<template>
    <GuestLayout>
        <Head title="Register" />
        <h1 class="h4 mb-4">Register</h1>
        <form @submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label" for="tenant_slug">Pharmacy code</label>
                <input id="tenant_slug" v-model="form.tenant_slug" type="text" class="form-control" required autocomplete="off" />
                <div v-if="form.errors.tenant_slug" class="text-danger small mt-1">{{ form.errors.tenant_slug }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="name">Name</label>
                <input id="name" v-model="form.name" type="text" class="form-control" required autocomplete="name" />
            </div>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input id="email" v-model="form.email" type="email" class="form-control" required autocomplete="username" />
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input id="password" v-model="form.password" type="password" class="form-control" required autocomplete="new-password" />
            </div>
            <div class="mb-3">
                <label class="form-label" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" class="form-control" required autocomplete="new-password" />
            </div>
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">Register</button>
                <Link href="/login" class="btn btn-link">Already registered?</Link>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    tenant_slug: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register');
}
</script>
