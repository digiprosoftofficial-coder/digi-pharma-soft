<template>
    <PlatformShellLayout :page-title="template ? t('common.edit') : t('platform.new_catalog')">
        <Head :title="template ? t('common.edit') : t('platform.new_catalog')" />
        <Link href="/platform/catalog-templates" class="small text-decoration-none">← {{ t('platform.nav_catalog') }}</Link>
        <h1 class="h4 mt-2 mb-3">{{ template ? t('common.edit') : t('platform.new_catalog') }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">{{ t('platform.catalog_name') }}</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Slug</label>
                <input v-model="form.slug" class="form-control" required :disabled="!!template" />
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.catalog_description') }}</label>
                <textarea v-model="form.description" class="form-control" rows="3" />
            </div>
            <div class="form-check mb-3">
                <input id="published" v-model="form.is_published" type="checkbox" class="form-check-input" />
                <label class="form-check-label" for="published">{{ t('platform.catalog_published') }}</label>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
            <Link href="/platform/catalog-templates" class="btn btn-link">{{ t('common.cancel') }}</Link>
        </form>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    template: { type: Object, default: null },
});

const { t } = useLocale();

const form = useForm({
    name: props.template?.name ?? '',
    slug: props.template?.slug ?? '',
    description: props.template?.description ?? '',
    is_published: props.template?.is_published ?? false,
});

function submit() {
    if (props.template) {
        form.put(`/platform/catalog-templates/${props.template.id}`);
    } else {
        form.post('/platform/catalog-templates');
    }
}
</script>
