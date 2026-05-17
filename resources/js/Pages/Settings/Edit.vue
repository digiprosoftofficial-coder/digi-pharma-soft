<template>
    <TenantShellLayout page-title="Settings">
        <Head title="Settings" />
        <h1 class="h4 mb-3">Pharmacy settings</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">Business name</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Slug</label>
                <input :value="tenant.slug" class="form-control" disabled />
                <div class="form-text">Read-only. Contact support to change subdomain.</div>
            </div>
            <div class="mb-2">
                <label class="form-label">Phone</label>
                <input v-model="form.settings.phone" class="form-control" />
            </div>
            <div class="mb-2">
                <label class="form-label">Address</label>
                <textarea v-model="form.settings.address" class="form-control" rows="2"></textarea>
            </div>
            <button v-if="can('settings.manage')" type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
            <p v-else class="small text-muted mb-0">View only — you need settings.manage to update.</p>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps({
    tenant: { type: Object, required: true },
});

const { can } = usePermissions();

const form = useForm({
    name: props.tenant.name,
    settings: {
        phone: props.tenant.settings?.phone ?? '',
        address: props.tenant.settings?.address ?? '',
    },
});

function submit() {
    if (!can('settings.manage')) {
        return;
    }
    form.put('/settings');
}
</script>
