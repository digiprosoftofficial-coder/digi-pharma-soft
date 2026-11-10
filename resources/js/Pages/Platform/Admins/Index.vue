<template>
    <PlatformShellLayout :page-title="t('platform.admins_title')">
        <Head :title="t('platform.admins_title')" />

        <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
            <p class="text-muted small mb-0">{{ t('platform.admins_sub') }}</p>
            <button type="button" class="btn btn-primary btn-sm" @click="openCreate">
                {{ t('platform.new_admin') }}
            </button>
        </div>

        <div v-if="showForm" class="card border-0 shadow-sm card-body mb-3" style="max-width: 720px">
            <h2 class="h6 mb-3">{{ editing ? t('common.edit') : t('platform.new_admin') }}</h2>
            <form class="row g-3" @submit.prevent="submit">
                <div class="col-md-6">
                    <label class="form-label">{{ t('common.name') || 'Name' }}</label>
                    <input v-model="form.name" class="form-control" required />
                    <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('auth.email') }}</label>
                    <input v-model="form.email" type="email" class="form-control" required />
                    <div v-if="form.errors.email" class="text-danger small">{{ form.errors.email }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.admin_role') }}</label>
                    <select v-model="form.role" class="form-select" required>
                        <option value="sales">{{ t('platform.admin_role_sales') }}</option>
                        <option value="super admin">{{ t('platform.admin_role_super') }}</option>
                    </select>
                    <div v-if="form.errors.role" class="text-danger small">{{ form.errors.role }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('auth.password') }}</label>
                    <input v-model="form.password" type="password" class="form-control" :required="!editing" autocomplete="new-password" />
                    <div v-if="editing" class="form-text">{{ t('platform.admin_password_optional') }}</div>
                    <div v-if="form.errors.password" class="text-danger small">{{ form.errors.password }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('auth.password_confirmation') }}</label>
                    <input v-model="form.password_confirmation" type="password" class="form-control" :required="!editing || !!form.password" autocomplete="new-password" />
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
                    <button type="button" class="btn btn-outline-secondary" @click="closeForm">{{ t('common.cancel') }}</button>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('common.name') || 'Name' }}</th>
                        <th>{{ t('auth.email') }}</th>
                        <th>{{ t('platform.admin_role') }}</th>
                        <th>{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in admins" :key="a.id">
                        <td>{{ a.name }}</td>
                        <td>{{ a.email }}</td>
                        <td>
                            <span class="badge" :class="a.role === 'super admin' ? 'text-bg-primary' : 'text-bg-success'">
                                {{ roleLabel(a.role) }}
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" :title="t('common.edit')" @click="openEdit(a)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                </svg>
                                <span class="visually-hidden">{{ t('common.edit') }}</span>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                :title="t('common.delete')"
                                :disabled="a.id === currentUserId"
                                @click="destroyAdmin(a)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M3 6h18" />
                                    <path d="M8 6V4h8v2" />
                                    <path d="M19 6l-1 14H6L5 6" />
                                </svg>
                                <span class="visually-hidden">{{ t('common.delete') }}</span>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!admins.length">
                        <td colspan="4" class="text-muted text-center py-4">{{ t('common.no_results') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    admins: { type: Array, required: true },
    roles: { type: Array, default: () => ['sales', 'super admin'] },
});

const { t } = useLocale();
const page = usePage();
const showForm = ref(false);
const editing = ref(null);
const currentUserId = computed(() => page.props.auth?.user?.id);

const form = useForm({
    name: '',
    email: '',
    role: 'sales',
    password: '',
    password_confirmation: '',
});

function roleLabel(role) {
    if (role === 'super admin') {
        return t('platform.admin_role_super');
    }
    if (role === 'sales') {
        return t('platform.admin_role_sales');
    }
    return role;
}

function openCreate() {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.role = 'sales';
    showForm.value = true;
}

function openEdit(admin) {
    editing.value = admin;
    form.clearErrors();
    form.name = admin.name;
    form.email = admin.email;
    form.role = admin.role === 'super admin' ? 'super admin' : 'sales';
    form.password = '';
    form.password_confirmation = '';
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editing.value = null;
    form.reset();
    form.clearErrors();
}

function submit() {
    if (editing.value) {
        form.put(`/platform/admins/${editing.value.id}`, {
            onSuccess: () => closeForm(),
        });
        return;
    }

    form.post('/platform/admins', {
        onSuccess: () => closeForm(),
    });
}

function destroyAdmin(admin) {
    if (!confirm(t('platform.admin_delete_confirm', { name: admin.name }))) {
        return;
    }
    router.delete(`/platform/admins/${admin.id}`);
}
</script>
