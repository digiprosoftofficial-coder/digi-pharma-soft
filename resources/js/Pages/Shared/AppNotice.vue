<template>
    <GuestLayout>
        <Head :title="dialog?.title || t('errors.server_title')" />
        <div class="text-center">
            <h1 class="page-heading mb-2">{{ dialog?.title || t('errors.server_title') }}</h1>
            <p class="text-muted mb-0">{{ dialog?.message || t('errors.server_body') }}</p>
        </div>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head } from '@inertiajs/vue3';

defineProps({
    dialog: { type: Object, default: null },
});

const { t } = useLocale();
</script>
