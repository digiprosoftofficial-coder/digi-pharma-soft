<template>
    <div :class="columnClass">
        <label class="form-label small mb-0">{{ label }}</label>
        <select :value="modelValue" class="form-select form-select-sm" @change="$emit('update:modelValue', $event.target.value)">
            <option value="">{{ placeholder }}</option>
            <option v-for="option in options" :key="option.value" :value="String(option.value)">
                {{ option.label }}
            </option>
        </select>
    </div>
</template>

<script setup>
defineEmits(['update:modelValue']);

defineProps({
    label: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'All' },
    columnClass: { type: String, default: 'col-md-2' },
});
</script>
