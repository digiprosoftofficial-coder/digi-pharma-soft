<template>
    <TenantShellLayout :page-title="location ? t('common.edit') : t('catalog.new_storage_location')">
        <Head :title="location ? t('common.edit') : t('catalog.new_storage_location')" />
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label">{{ t('catalog.storage_location_name') }}</label>
                <input v-model="form.name" class="form-control" required />
                <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('catalog.storage_location_code') }}</label>
                <input v-model="form.code" class="form-control" :placeholder="t('catalog.storage_location_code_hint')" />
                <div v-if="form.errors.code" class="text-danger small">{{ form.errors.code }}</div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label">{{ t('catalog.storage_location_sort') }}</label>
                    <input v-model.number="form.sort_order" type="number" min="0" class="form-control" />
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input id="active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                        <label class="form-check-label" for="active">{{ t('common.active') }}</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('catalog.storage_location_notes') }}</label>
                <textarea v-model="form.notes" class="form-control" rows="2" maxlength="2000" />
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
            <Link href="/storage-locations" class="btn btn-link">{{ t('common.cancel') }}</Link>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ location: { type: Object, default: null } });

const { t } = useLocale();

const form = useForm({
    name: props.location?.name ?? '',
    code: props.location?.code ?? '',
    sort_order: props.location?.sort_order ?? 0,
    is_active: props.location?.is_active ?? true,
    notes: props.location?.notes ?? '',
});

function submit() {
    if (props.location) {
        form.put(`/storage-locations/${props.location.id}`);
    } else {
        form.post('/storage-locations');
    }
}
</script>
