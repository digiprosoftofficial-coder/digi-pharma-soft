<template>
    <MarketingLayout :support="support">
        <SeoHead
            :title="t('platform.seo_demo_title')"
            :description="t('platform.seo_demo_description')"
            path="/demo"
            :support-email="support.email || ''"
            :support-phone="support.phone || ''"
        />

        <section class="sp-page-hero">
            <div class="sp-container" style="max-width: 720px;">
                <p class="sp-eyebrow">SoftoPharma</p>
                <h1 class="sp-display">{{ t('platform.demo_section_title') }}</h1>
                <p class="sp-lead" style="margin-top: 0.85rem;">{{ t('platform.demo_section_sub') }}</p>
                <p class="sp-bn" style="margin-top: 0.75rem;">{{ t('platform.brand_presents_bn') }}</p>
            </div>
        </section>

        <section class="sp-section" style="padding-top: 0;">
            <div class="sp-container" style="max-width: 720px;">
                <DemoLeadForm />
                <p v-if="support.email || support.phone" class="sp-lead" style="margin-top: 1.25rem; font-size: 0.92rem;">
                    {{ t('platform.demo_support_hint') }}
                    <template v-if="support.phone">
                        <a :href="`tel:${support.phone}`" style="color: var(--sp-brand); font-weight: 700;">{{ support.phone }}</a>
                    </template>
                    <template v-if="support.email">
                        <span v-if="support.phone"> · </span>
                        <a :href="`mailto:${support.email}`" style="color: var(--sp-brand); font-weight: 700;">{{ support.email }}</a>
                    </template>
                </p>
            </div>
        </section>
    </MarketingLayout>
</template>

<script setup>
import DemoLeadForm from '@/Components/Marketing/DemoLeadForm.vue';
import SeoHead from '@/Components/Marketing/SeoHead.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useLocale } from '@/composables/useLocale';

defineProps({
    support: { type: Object, default: () => ({ email: null, phone: null }) },
});

const { t } = useLocale();
</script>
