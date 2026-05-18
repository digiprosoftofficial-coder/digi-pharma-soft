<template>
    <PlatformShellLayout :page-title="reseller ? t('common.edit') : t('platform.new_reseller')">
        <Head :title="reseller ? t('common.edit') : t('platform.new_reseller')" />
        <Link href="/platform/resellers" class="small text-decoration-none">← {{ t('platform.nav_resellers') }}</Link>
        <h1 class="h4 mt-2 mb-3">{{ reseller ? t('common.edit') : t('platform.new_reseller') }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">{{ t('platform.reseller_name') }}</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Slug</label>
                <input v-model="form.slug" class="form-control" required :disabled="!!reseller" />
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.reseller_contact_name') }}</label>
                    <input v-model="form.contact_name" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.reseller_contact_email') }}</label>
                    <input v-model="form.contact_email" type="email" class="form-control" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('platform.reseller_contact_phone') }}</label>
                    <input v-model="form.contact_phone" class="form-control" />
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.reseller_commission') }}</label>
                <input v-model.number="form.commission_percent" type="number" min="0" max="100" step="0.01" class="form-control" />
            </div>
            <div class="form-check mb-3">
                <input id="active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                <label class="form-check-label" for="active">{{ t('common.active') }}</label>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
            <Link href="/platform/resellers" class="btn btn-link">{{ t('common.cancel') }}</Link>
        </form>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    reseller: { type: Object, default: null },
});

const { t } = useLocale();

const form = useForm({
    name: props.reseller?.name ?? '',
    slug: props.reseller?.slug ?? '',
    contact_name: props.reseller?.contact_name ?? '',
    contact_email: props.reseller?.contact_email ?? '',
    contact_phone: props.reseller?.contact_phone ?? '',
    commission_percent: props.reseller?.commission_percent ?? null,
    is_active: props.reseller?.is_active ?? true,
});

function submit() {
    if (props.reseller) {
        form.put(`/platform/resellers/${props.reseller.id}`);
    } else {
        form.post('/platform/resellers');
    }
}
</script>
