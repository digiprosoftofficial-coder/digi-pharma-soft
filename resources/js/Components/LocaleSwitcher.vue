<template>
    <select
        class="form-select form-select-sm"
        :class="{ 'locale-switcher--fluid': fluid }"
        :value="locale()"
        :aria-label="t('common.language')"
        @change="onChange"
    >
        <option v-for="loc in locales()" :key="loc.code" :value="loc.code">
            {{ loc.label }}
        </option>
    </select>
</template>

<script setup>
import { useLocale } from '@/composables/useLocale';

defineProps({
    fluid: { type: Boolean, default: false },
});

const { locale, locales, t, setLocale } = useLocale();

function onChange(event) {
    setLocale(event.target.value);
}
</script>

<style scoped>
.form-select {
    width: auto;
    min-width: 7rem;
}

.locale-switcher--fluid {
    width: 100%;
}
</style>
