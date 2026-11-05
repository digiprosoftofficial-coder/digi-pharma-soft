<template>
    <MarketingLayout>
        <Head :title="t('platform.registration_pending_title')" />
        <div class="container py-5" style="max-width: 560px">
            <div class="card border-0 shadow-sm card-body text-center">
                <h1 class="h4 mb-3">{{ t('platform.registration_pending_title') }}</h1>
                <p class="text-muted">{{ t('platform.registration_pending_body') }}</p>
                <p v-if="support?.phone || support?.email" class="small mb-0">
                    {{ support.phone }} <span v-if="support.email">· {{ support.email }}</span>
                </p>
                <Link href="/login" class="btn btn-primary mt-4">{{ t('auth.login') || 'Login' }}</Link>
            </div>
        </div>
    </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ support: { type: Object, default: () => ({}) } });
const { t } = useLocale();
</script>
