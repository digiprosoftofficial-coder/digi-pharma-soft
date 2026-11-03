<template>
    <TenantShellLayout page-title="Users">
        <Head title="Team users" />
        <div class="d-flex justify-content-between mb-3">
            <h1 class="page-heading mb-0">Team users</h1>
            <Link href="/team/users/create" class="btn btn-primary btn-sm">Invite user</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th v-if="multiBranch">{{ t('branches.assigned_branch') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="u in users.data" :key="u.id">
                        <td>{{ u.name }}</td>
                        <td>{{ u.email }}</td>
                        <td class="small">{{ (u.roles || []).map((r) => r.name).join(', ') }}</td>
                        <td v-if="multiBranch" class="small">{{ u.assigned_branch?.name || '—' }}</td>
                        <td class="text-end">
                            <Link :href="`/team/users/${u.id}/edit`" class="btn btn-sm btn-outline-secondary">Edit</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    users: { type: Object, required: true },
    roles: { type: Array, required: true },
    multiBranch: { type: Boolean, default: false },
});

const { t } = useLocale();
</script>
