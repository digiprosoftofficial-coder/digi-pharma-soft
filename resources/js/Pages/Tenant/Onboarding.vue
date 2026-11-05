<template>
    <TenantShellLayout :page-title="t('platform.onboarding_title')">
        <Head :title="t('platform.onboarding_title')" />
        <h1 class="page-heading mb-2">{{ t('platform.onboarding_title') }}</h1>
        <p class="text-muted mb-3">{{ t('platform.onboarding_sub') }}</p>
        <div class="progress mb-4" style="height: 10px">
            <div class="progress-bar" :style="{ width: progress.percent + '%' }" />
        </div>
        <div class="list-group shadow-sm">
            <div v-for="step in steps" :key="step" class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">{{ t(`platform.onboarding_${step}`) }}</div>
                </div>
                <span v-if="progress.steps[step]" class="badge text-bg-success">Done</span>
                <Link v-else :href="stepLink(step)" class="btn btn-sm btn-outline-primary">{{ t('common.continue') || 'Continue' }}</Link>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    progress: { type: Object, required: true },
    steps: { type: Array, required: true },
});

const { t } = useLocale();

function stepLink(step) {
    const map = {
        profile: '/settings',
        branch: '/branches',
        staff: '/team/users',
        categories: '/catalog/categories',
        products: '/catalog/products/import',
        suppliers: '/suppliers',
        opening_stock: '/inventory',
        first_sale: '/pos',
    };
    return map[step] || '/dashboard';
}
</script>
