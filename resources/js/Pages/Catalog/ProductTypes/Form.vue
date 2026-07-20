<template>
    <TenantShellLayout :page-title="productType ? 'Edit product type' : 'New product type'">
        <Head :title="productType ? 'Edit product type' : 'New product type'" />
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label">Name</label>
                <input v-model="form.name" class="form-control" required />
                <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Slug (optional)</label>
                <input v-model="form.slug" class="form-control" placeholder="auto-generated from name" />
                <div class="form-text">Stored on products as the type code (e.g. tablet, syrup).</div>
                <div v-if="form.errors.slug" class="text-danger small">{{ form.errors.slug }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Sort order</label>
                <input v-model.number="form.sort_order" type="number" min="0" class="form-control" />
                <div v-if="form.errors.sort_order" class="text-danger small">{{ form.errors.sort_order }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('catalog.product_type_icon') }}</label>
                <input type="file" accept="image/png,image/jpeg,image/webp" class="form-control" @change="onIconChange" />
                <p class="form-text small mb-2">{{ t('catalog.product_type_icon_hint') }}</p>
                <div v-if="previewUrl" class="mt-2">
                    <img :src="previewUrl" alt="" class="border rounded p-1 bg-white" width="64" height="64" style="object-fit: contain" />
                </div>
                <div v-else-if="productType?.icon_url" class="mt-2">
                    <img :src="productType.icon_url" alt="" class="border rounded p-1 bg-white" width="64" height="64" style="object-fit: contain" />
                    <p v-if="!productType?.uses_custom_icon" class="small text-muted mb-0 mt-1">
                        {{ t('catalog.product_type_using_platform_default') }}
                    </p>
                </div>
                <div v-if="productType?.uses_custom_icon || previewUrl" class="form-check mt-2">
                    <input id="remove_icon" v-model="form.remove_icon" type="checkbox" class="form-check-input" />
                    <label class="form-check-label small" for="remove_icon">{{ t('catalog.product_type_reset_icon') }}</label>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 product-type-form-actions">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
                <Link href="/product-types" class="btn btn-outline-secondary">{{ t('common.cancel') }}</Link>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onUnmounted, ref } from 'vue';

const props = defineProps({ productType: { type: Object, default: null } });

const { t } = useLocale();
const previewUrl = ref(null);

const form = useForm({
    name: props.productType?.name ?? '',
    slug: props.productType?.slug ?? '',
    sort_order: props.productType?.sort_order ?? 0,
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
        form.transform((data) => ({ ...data, _method: 'put' })).post(`/product-types/${props.productType.id}`, {
            forceFormData: true,
        });
    } else {
        form.post('/product-types', { forceFormData: true });
    }
}
</script>

<style scoped>
.product-type-form-actions .btn {
    min-width: 0;
}

@media (max-width: 575.98px) {
    .product-type-form-actions {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        width: 100%;
    }

    .product-type-form-actions .btn {
        width: 100%;
    }
}
</style>
