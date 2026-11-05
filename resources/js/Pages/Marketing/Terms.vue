<template>
    <MarketingLayout :support="support">
        <SeoHead
            :title="t('platform.terms_conditions') + ' | SoftoPharma'"
            :description="t('platform.terms_intro')"
            path="/terms"
            :support-email="support.email || ''"
            :support-phone="support.phone || ''"
        />
        <section class="sp-page-hero">
            <div class="sp-container" style="max-width: 720px;">
                <h1 class="sp-display">{{ t('platform.terms_conditions') }}</h1>
                <p class="sp-lead" style="margin-top: 1rem;">{{ t('platform.terms_intro') }}</p>
                <p class="sp-lead" style="margin-top: 1rem;">{{ t('platform.terms_body') }}</p>
            </div>
        </section>
    </MarketingLayout>
</template>

<script setup>
import SeoHead from '@/Components/Marketing/SeoHead.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useLocale } from '@/composables/useLocale';

defineProps({
    support: { type: Object, default: () => ({ email: null, phone: null }) },
});

const { t } = useLocale();
</script>
