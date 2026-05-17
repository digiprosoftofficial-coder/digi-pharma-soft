<template>
    <GuestLayout>
        <Head title="Confirm password" />
        <h1 class="h4 mb-4">Confirm password</h1>
        <form @submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label" for="password">Password</label>
                <input id="password" v-model="form.password" type="password" class="form-control" required />
            </div>
            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">Confirm</button>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });

function submit() {
    form.post('/user/confirm-password');
}
</script>
