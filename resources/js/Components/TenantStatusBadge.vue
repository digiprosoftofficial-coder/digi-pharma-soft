<template>
    <span class="badge rounded-pill" :class="badgeClass">{{ label }}</span>
</template>

<script setup>
import { useLocale } from '@/composables/useLocale';
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, required: true },
});

const { t } = useLocale();

const label = computed(() => {
    const key = `platform.status_${props.status}`;
    const translated = t(key);
    return translated === key ? props.status : translated;
});

const badgeClass = computed(() => {
    const map = {
        running: 'text-bg-success',
        trial: 'text-bg-info',
        expiring: 'text-bg-warning',
        expired: 'text-bg-danger',
        suspended: 'text-bg-danger',
        inactive: 'text-bg-secondary',
    };

    return map[props.status] ?? 'text-bg-secondary';
});
</script>
