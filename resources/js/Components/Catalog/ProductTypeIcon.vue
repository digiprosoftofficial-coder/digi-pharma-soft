<template>
    <svg
        class="product-type-icon"
        :class="sizeClass"
        xmlns="http://www.w3.org/2000/svg"
        :width="dimension"
        :height="dimension"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <!-- tablet: round pill -->
        <rect v-if="icon === 'tablet'" x="7" y="5" width="10" height="14" rx="5" />
        <!-- capsule -->
        <g v-else-if="icon === 'capsule'">
            <path d="M8 12a4 4 0 0 1 4-7.5 4 4 0 0 1 4 7.5 4 4 0 0 1-4 7.5 4 4 0 0 1-4-7.5z" />
            <path d="M12 4.5v15" opacity="0.35" />
        </g>
        <!-- syrup / bottle -->
        <g v-else-if="icon === 'syrup' || icon === 'bottle'">
            <path d="M9 3h6v3H9z" />
            <path d="M8 8h8v12a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2V8z" />
            <path d="M10 12h4" />
        </g>
        <!-- injection: syringe -->
        <g v-else-if="icon === 'injection'">
            <path d="M6 18l9-9" />
            <path d="M15 9l3-3" />
            <path d="M18 6l2 2" />
            <path d="M5 19l2 2" />
            <path d="M14 4l2 2" />
        </g>
        <!-- cream / tube -->
        <g v-else-if="icon === 'cream' || icon === 'tube'">
            <path d="M5 10c0-2 2-4 7-4s7 2 7 4v8H5v-8z" />
            <path d="M8 6V4h8v2" />
            <path d="M9 14h6" />
        </g>
        <!-- drops -->
        <g v-else-if="icon === 'drops'">
            <path d="M12 3c2 3 5 5.5 5 8.5a5 5 0 1 1-10 0C7 8.5 10 6 12 3z" />
        </g>
        <!-- vial -->
        <g v-else-if="icon === 'vial'">
            <path d="M10 3h4" />
            <path d="M9 6h6v2l-2 13H11L9 8V6z" />
        </g>
        <!-- pack -->
        <g v-else-if="icon === 'pack'">
            <path d="M12 3 3 8l9 5-9 5-9-5 9-5z" />
            <path d="M3 8v8l9 5 9-5V8" opacity="0.5" />
        </g>
        <!-- sachet -->
        <g v-else-if="icon === 'sachet'">
            <path d="M7 5h10l3 4v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9l3-4z" />
            <path d="M7 9h10" />
        </g>
        <!-- other / unknown -->
        <g v-else>
            <rect x="4" y="8" width="16" height="10" rx="2" />
            <path d="M8 8V6h8v2" />
            <path d="M12 12v2" />
        </g>
    </svg>
</template>

<script setup>
import { computed } from 'vue';
import { resolveProductTypeIcon } from '@/composables/useProductType';

const props = defineProps({
    type: { type: String, default: 'other' },
    size: { type: String, default: 'md' },
});

const icon = computed(() => resolveProductTypeIcon(props.type));

const dimension = computed(() => ({ sm: 20, md: 24, lg: 30 })[props.size] ?? 24);

const sizeClass = computed(() => `product-type-icon--${props.size}`);
</script>

<style scoped>
.product-type-icon {
    flex-shrink: 0;
    color: var(--bs-primary);
}
.product-type-icon--sm {
    opacity: 0.9;
}
</style>
