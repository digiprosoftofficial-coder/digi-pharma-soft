<template>
    <TenantShellLayout :page-title="branch ? t('branches.edit_branch') : t('branches.new_branch')">
        <Head :title="branch ? t('branches.edit_branch') : t('branches.new_branch')" />
        <h1 class="h4 mb-3">{{ branch ? t('branches.edit_branch') : t('branches.new_branch') }}</h1>
        <div v-if="form.errors.name" class="alert alert-danger small">{{ form.errors.name }}</div>
        <form class="card border-0 shadow-sm card-body" style="max-width: 32rem" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">{{ t('branches.name') }}</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">{{ t('branches.code') }}</label>
                <input v-model="form.code" class="form-control" :placeholder="branch ? '' : 'AUTO'" />
            </div>
            <div class="mb-2">
                <label class="form-label">{{ t('branches.address') }}</label>
                <textarea v-model="form.address" class="form-control" rows="2" />
            </div>
            <div class="mb-2">
                <label class="form-label">{{ t('branches.phone') }}</label>
                <input v-model="form.phone" class="form-control" />
            </div>
            <div v-if="branch && !branch.is_default" class="form-check mb-3">
                <input id="active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                <label class="form-check-label" for="active">{{ t('branches.active') }}</label>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
            <Link href="/branches" class="btn btn-link">{{ t('common.cancel') }}</Link>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ branch: { type: Object, default: null } });

const { t } = useLocale();

const form = useForm({
    name: props.branch?.name ?? '',
    code: props.branch?.code ?? '',
    address: props.branch?.address ?? '',
    phone: props.branch?.phone ?? '',
    is_active: props.branch?.is_active ?? true,
});

function submit() {
    if (props.branch) {
        form.put(`/branches/${props.branch.id}`);
        return;
    }
    form.post('/branches');
}
</script>
