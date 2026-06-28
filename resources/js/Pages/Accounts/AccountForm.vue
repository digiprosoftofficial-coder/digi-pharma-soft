<template>
    <TenantShellLayout :page-title="t('common.account')">
        <Head :title="account ? t('common.edit_account') : t('common.new_account')" />
        <h1 class="h4 mb-3">{{ account ? t('common.edit_account') : t('common.new_account') }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">{{ t('common.code') }}</label>
                <input v-model="form.code" class="form-control" required :disabled="!!account" />
            </div>
            <div class="mb-2">
                <label class="form-label">{{ t('common.name') }}</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">{{ t('catalog.product_type') }}</label>
                <select v-model="form.type" class="form-select" required>
                    <option value="asset">{{ t('common.account_asset') }}</option>
                    <option value="liability">{{ t('common.account_liability') }}</option>
                    <option value="income">{{ t('common.account_income') }}</option>
                    <option value="expense">{{ t('common.account_expense') }}</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
            <Link href="/accounts" class="btn btn-link">{{ t('common.back') }}</Link>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ account: { type: Object, default: null } });
const { t } = useLocale();

const form = useForm({
    code: props.account?.code ?? '',
    name: props.account?.name ?? '',
    type: props.account?.type ?? 'asset',
});

function submit() {
    if (props.account) {
        form.put('/accounts/' + props.account.id);
    } else {
        form.post('/accounts');
    }
}
</script>
