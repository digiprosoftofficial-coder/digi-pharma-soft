<template>
    <div :class="columnClass">
        <label class="form-label small mb-0">{{ label }}</label>
        <select :value="modelValue" class="form-select form-select-sm" @change="$emit('update:modelValue', $event.target.value)">
            <option value="">{{ placeholder ?? t('reports.all') }}</option>
            <option v-for="option in options" :key="option.value" :value="String(option.value)">
                {{ option.label }}
            </option>
        </select>
    </div>
</template>

<script setup>
import { useLocale } from '@/composables/useLocale';

defineEmits(['update:modelValue']);

defineProps({
    label: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: null },
    columnClass: { type: String, default: 'col-md-2' },
});

const { t } = useLocale();
</script>
