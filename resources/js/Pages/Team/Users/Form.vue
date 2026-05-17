<template>
    <TenantShellLayout page-title="User">
        <Head :title="user ? 'Edit user' : 'New user'" />
        <h1 class="h4 mb-3">{{ user ? 'Edit user' : 'New user' }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">Name</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Email</label>
                <input v-model="form.email" type="email" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Role</label>
                <select v-model="form.role" class="form-select" required>
                    <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label">{{ user ? 'New password (optional)' : 'Password' }}</label>
                <input v-model="form.password" type="password" class="form-control" :required="!user" autocomplete="new-password" />
            </div>
            <div class="mb-2">
                <label class="form-label">Confirm password</label>
                <input v-model="form.password_confirmation" type="password" class="form-control" autocomplete="new-password" />
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
            <Link href="/team/users" class="btn btn-link">Cancel</Link>
            <button
                v-if="user"
                type="button"
                class="btn btn-outline-danger float-end"
                @click="destroyUser"
            >
                Remove user
            </button>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: { type: Object, default: null },
    roles: { type: Array, required: true },
});

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    role: props.user?.roles?.[0]?.name ?? props.roles[0] ?? '',
    password: '',
    password_confirmation: '',
});

function submit() {
    if (props.user) {
        form.put(`/team/users/${props.user.id}`);
    } else {
        form.post('/team/users');
    }
}

function destroyUser() {
    if (!props.user || !confirm('Remove this user?')) {
        return;
    }
    router.delete(`/team/users/${props.user.id}`);
}
</script>
