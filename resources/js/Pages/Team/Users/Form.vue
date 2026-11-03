<template>
    <TenantShellLayout page-title="User">
        <Head :title="user ? 'Edit user' : 'New user'" />
        <h1 class="page-heading mb-3">{{ user ? 'Edit user' : 'New user' }}</h1>
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
            <div v-if="showAssignedBranch" class="mb-2">
                <label class="form-label">{{ t('branches.assigned_branch') }}</label>
                <select
                    v-model="form.assigned_branch_id"
                    class="form-select"
                    :required="form.role === 'manager'"
                >
                    <option :value="null">—</option>
                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                        {{ branch.name }} ({{ branch.code }})
                    </option>
                </select>
                <div class="form-text">{{ t('branches.assigned_branch_help') }}</div>
                <div v-if="form.errors.assigned_branch_id" class="text-danger small mt-1">
                    {{ form.errors.assigned_branch_id }}
                </div>
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
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    user: { type: Object, default: null },
    roles: { type: Array, required: true },
    branches: { type: Array, default: () => [] },
    multiBranch: { type: Boolean, default: false },
});

const { t } = useLocale();

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    role: props.user?.roles?.[0]?.name ?? props.roles[0] ?? '',
    assigned_branch_id: props.user?.assigned_branch_id ?? null,
    password: '',
    password_confirmation: '',
});

const showAssignedBranch = computed(
    () => props.multiBranch && form.role !== 'pharmacy owner' && props.branches.length > 0,
);

watch(
    () => form.role,
    (role) => {
        if (role === 'pharmacy owner') {
            form.assigned_branch_id = null;
        }
    },
);

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
