<template>
    <select
        class="form-select form-select-sm"
        style="width: auto; min-width: 7rem"
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

const { locale, locales, t, setLocale } = useLocale();

function onChange(event) {
    setLocale(event.target.value);
}
</script>
