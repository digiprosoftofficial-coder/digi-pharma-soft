<template>
    <PlatformShellLayout :page-title="productType ? t('common.edit') : t('platform.product_type_new')">
        <Head :title="productType ? t('common.edit') : t('platform.product_type_new')" />
        <Link href="/platform/product-types" class="small text-decoration-none">← {{ t('platform.product_types_title') }}</Link>
        <h1 class="h4 mt-2 mb-3">{{ productType ? t('common.edit') : t('platform.product_type_new') }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label">{{ t('platform.product_type_name') }}</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-3">
                <label class="form-label">Slug</label>
                <input v-model="form.slug" class="form-control" :placeholder="t('platform.product_type_slug_hint')" />
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.product_type_sort') }}</label>
                <input v-model.number="form.sort_order" type="number" min="0" class="form-control" />
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.product_type_icon') }}</label>
                <input type="file" accept="image/png,image/jpeg,image/webp" class="form-control" @change="onIconChange" />
                <p class="form-text small mb-2">{{ t('platform.product_type_icon_hint') }}</p>
                <div v-if="previewUrl" class="mt-2">
                    <img :src="previewUrl" alt="" class="border rounded p-1 bg-white" width="64" height="64" style="object-fit: contain" />
                </div>
                <div v-else-if="productType?.icon_url" class="mt-2">
                    <img :src="productType.icon_url" alt="" class="border rounded p-1 bg-white" width="64" height="64" style="object-fit: contain" />
                </div>
                <div v-if="productType?.icon_url" class="form-check mt-2">
                    <input id="remove_icon" v-model="form.remove_icon" type="checkbox" class="form-check-input" />
                    <label class="form-check-label small" for="remove_icon">{{ t('platform.product_type_remove_icon') }}</label>
                </div>
            </div>
            <div class="form-check mb-3">
                <input id="active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                <label class="form-check-label" for="active">{{ t('common.active') }}</label>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
            <Link href="/platform/product-types" class="btn btn-link">{{ t('common.cancel') }}</Link>
        </form>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onUnmounted, ref } from 'vue';

const props = defineProps({
    productType: { type: Object, default: null },
});

const { t } = useLocale();

const previewUrl = ref(null);

const form = useForm({
    name: props.productType?.name ?? '',
    slug: props.productType?.slug ?? '',
    sort_order: props.productType?.sort_order ?? 0,
    is_active: props.productType?.is_active ?? true,
    icon: null,
    remove_icon: false,
});

function onIconChange(event) {
    const file = event.target.files?.[0] ?? null;
    form.icon = file;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = file ? URL.createObjectURL(file) : null;
}

onUnmounted(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});

function submit() {
    if (props.productType) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(`/platform/product-types/${props.productType.id}`, {
            forceFormData: true,
        });
    } else {
        form.post('/platform/product-types', { forceFormData: true });
    }
}
</script>
