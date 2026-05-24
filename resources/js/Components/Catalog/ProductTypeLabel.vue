<template>
    <span class="product-type-label d-inline-flex align-items-center gap-2" :class="wrapperClass">
        <img
            v-if="resolvedIconUrl"
            :src="resolvedIconUrl"
            alt=""
            class="product-type-label__img"
            :width="imgSize"
            :height="imgSize"
        />
        <ProductTypeIcon v-else :type="type" :size="size" />
        <span :class="labelClass">{{ displayLabel }}</span>
    </span>
</template>

<script setup>
import ProductTypeIcon from '@/Components/Catalog/ProductTypeIcon.vue';
import { useLocale } from '@/composables/useLocale';
import { productTypeLabel } from '@/composables/useProductType';
import { computed } from 'vue';

const props = defineProps({
    type: { type: String, default: 'other' },
    size: { type: String, default: 'md' },
    label: { type: String, default: '' },
    iconUrl: { type: String, default: '' },
    muted: { type: Boolean, default: false },
});

const { t } = useLocale();

const displayLabel = computed(() => props.label || productTypeLabel(props.type, t));

const resolvedIconUrl = computed(() => props.iconUrl || null);

const imgSize = computed(() => ({ sm: 20, md: 24, lg: 30 })[props.size] ?? 24);

const wrapperClass = computed(() => ({
    'text-muted': props.muted,
}));

const labelClass = computed(() => ({
    'text-capitalize': !props.label,
}));
</script>

<style scoped>
.product-type-label__img {
    flex-shrink: 0;
    object-fit: contain;
}
</style>
