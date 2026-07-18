<template>
    <PlatformShellLayout :page-title="isEdit ? t('platform.master_edit') : t('platform.master_add_btn')">
        <Head :title="isEdit ? t('platform.master_edit') : t('platform.master_add_btn')" />

        <Link href="/platform/master-catalog" class="small text-decoration-none text-teal">← {{ t('platform.master_title') }}</Link>
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mt-2 mb-4">
            <div>
                <h1 class="h3 mb-1">{{ isEdit ? t('platform.master_edit') : t('platform.master_add_btn') }}</h1>
                <p class="text-muted small mb-0">{{ t('platform.master_form_hint') }}</p>
            </div>
            <span v-if="isEdit && activatedCount > 0" class="badge text-bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-2">
                {{ t('platform.master_activated_count', { count: String(activatedCount) }) }}
            </span>
        </div>

        <form class="row g-4" @submit.prevent="submit">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h6 text-uppercase text-muted mb-3">{{ t('platform.master_section_identity') }}</h2>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">{{ t('catalog.product_name') }}</label>
                                <input v-model="form.name" class="form-control form-control-lg" required />
                                <div v-if="form.errors.name" class="invalid-feedback d-block">{{ form.errors.name }}</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ t('catalog.strength') }}</label>
                                <input v-model="form.strength" class="form-control" :placeholder="t('catalog.strength_placeholder')" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ t('platform.master_generic') }}</label>
                                <input v-model="form.generic_name" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ t('platform.master_manufacturer') }}</label>
                                <input v-model="form.manufacturer_name" class="form-control" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ t('catalog.product_type') }}</label>
                                <select v-model="form.product_type" class="form-select" required>
                                    <option v-for="type in productTypes" :key="type" :value="type">{{ typeLabel(type) }}</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ t('platform.master_drug_class') }}</label>
                                <input v-model="form.drug_class" class="form-control" :placeholder="t('platform.master_drug_class_hint')" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body p-4">
                        <h2 class="h6 text-uppercase text-muted mb-3">{{ t('platform.master_section_packaging') }}</h2>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">{{ t('catalog.base_unit') }}</label>
                                <select v-model="form.base_unit" class="form-select">
                                    <option v-for="u in sellUnits" :key="u" :value="u">{{ unitLabel(u) }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ t('catalog.pieces_per_strip') }}</label>
                                <input v-model="form.pieces_per_strip" type="number" step="0.0001" min="0" class="form-control" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ t('catalog.strips_per_box') }}</label>
                                <input v-model="form.strips_per_box" type="number" step="0.0001" min="0" class="form-control" />
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ t('catalog.boxes_per_carton') }}</label>
                                <input v-model="form.boxes_per_carton" type="number" step="0.0001" min="0" class="form-control" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-lg-top" style="top: 1rem">
                    <div class="card-body p-4">
                        <h2 class="h6 text-uppercase text-muted mb-3">{{ t('platform.master_section_codes') }}</h2>
                        <div class="mb-3">
                            <label class="form-label">SKU</label>
                            <input v-model="form.sku" class="form-control" :placeholder="t('platform.master_sku_hint')" />
                            <div v-if="form.errors.sku" class="invalid-feedback d-block">{{ form.errors.sku }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ t('catalog.barcode') }}</label>
                            <input v-model="form.barcode" class="form-control" />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ t('platform.master_mrp') }}</label>
                            <input v-model="form.mrp" type="number" step="0.01" min="0" class="form-control" required />
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ t('platform.master_purchase') }}</label>
                            <input v-model="form.default_purchase_price" type="number" step="0.01" min="0" class="form-control" />
                            <p class="form-text small mb-0">{{ t('platform.master_purchase_hint') }}</p>
                        </div>
                        <div class="form-check form-switch mb-4">
                            <input id="is_active" v-model="form.is_active" class="form-check-input" type="checkbox" />
                            <label class="form-check-label" for="is_active">{{ t('common.active') }}</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-2" :disabled="form.processing">
                            {{ form.processing ? t('common.saving') : t('common.save') }}
                        </button>
                        <Link href="/platform/master-catalog" class="btn btn-outline-secondary w-100">{{ t('common.cancel') }}</Link>
                    </div>
                </div>
            </div>
        </form>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    product: { type: Object, default: null },
    productTypes: { type: Array, default: () => [] },
    sellUnits: { type: Array, default: () => [] },
    activatedCount: { type: Number, default: 0 },
});

const { t } = useLocale();
const isEdit = computed(() => !!props.product);

const form = useForm({
    name: props.product?.name ?? '',
    generic_name: props.product?.generic_name ?? '',
    strength: props.product?.strength ?? '',
    manufacturer_name: props.product?.manufacturer_name ?? '',
    product_type: props.product?.product_type ?? 'tablet',
    drug_class: props.product?.drug_class ?? '',
    base_unit: props.product?.base_unit ?? 'strip',
    pieces_per_strip: props.product?.pieces_per_strip ?? '',
    strips_per_box: props.product?.strips_per_box ?? '',
    boxes_per_carton: props.product?.boxes_per_carton ?? '',
    sku: props.product?.sku ?? '',
    barcode: props.product?.barcode ?? '',
    mrp: props.product?.mrp ?? '0',
    default_purchase_price: props.product?.default_purchase_price ?? '',
    is_active: props.product?.is_active ?? true,
});

function typeLabel(type) {
    return t(`catalog.types.${type}`, type);
}

function unitLabel(unit) {
    return t(`catalog.units.${unit}`, unit);
}

function submit() {
    if (isEdit.value) {
        form.put(`/platform/master-catalog/${props.product.id}`);
    } else {
        form.post('/platform/master-catalog');
    }
}
</script>

<style scoped>
.text-teal {
    color: #0f766e !important;
}
</style>
