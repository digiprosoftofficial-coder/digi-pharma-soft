<template>
    <MarketingLayout :support="support">
        <SeoHead
            :title="t('platform.privacy_policy') + ' | SoftoPharma'"
            :description="t('platform.privacy_intro')"
            path="/privacy"
            :support-email="support.email || ''"
            :support-phone="support.phone || ''"
        />
        <section class="sp-page-hero">
            <div class="sp-container" style="max-width: 720px;">
                <h1 class="sp-display">{{ t('platform.privacy_policy') }}</h1>
                <p class="sp-lead" style="margin-top: 1rem;">{{ t('platform.privacy_intro') }}</p>
                <p class="sp-lead" style="margin-top: 1rem;">{{ t('platform.privacy_body') }}</p>
                <p v-if="support.email" class="sp-lead" style="margin-top: 1rem;">
                    {{ t('platform.privacy_contact') }}
                    <a :href="`mailto:${support.email}`" style="color: var(--sp-brand); font-weight: 700;">{{ support.email }}</a>
                </p>
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
