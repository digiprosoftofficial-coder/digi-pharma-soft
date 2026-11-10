<template>
    <MarketingLayout :support="support">
        <SeoHead
            :title="t('platform.seo_pricing_title')"
            :description="t('platform.seo_pricing_description')"
            path="/pricing"
            :support-email="support.email || ''"
            :support-phone="support.phone || ''"
        />

        <section class="sp-page-hero">
            <div class="sp-container">
                <p class="sp-eyebrow">SoftoPharma</p>
                <h1 class="sp-display">{{ t('platform.pricing_title') }}</h1>
                <p class="sp-lead" style="margin-top: 0.85rem; max-width: 36rem;">{{ t('platform.pricing_sub') }}</p>
            </div>
        </section>

        <section class="sp-section" style="padding-top: 0;">
            <div class="sp-container">
                <div class="sp-billing-toggle" role="group" :aria-label="t('platform.billing_interval')">
                    <button type="button" :class="{ 'is-active': interval === 'monthly' }" @click="interval = 'monthly'">
                        {{ t('platform.monthly') }}
                    </button>
                    <button type="button" :class="{ 'is-active': interval === 'yearly' }" @click="interval = 'yearly'">
                        {{ t('platform.yearly') }}
                    </button>
                </div>

                <div class="sp-plan-grid">
                    <article
                        v-for="plan in plans"
                        :key="plan.id"
                        class="sp-plan"
                        :class="{ 'is-featured': isFeatured(plan) }"
                    >
                        <span v-if="isFeatured(plan)" class="sp-plan-badge">{{ t('platform.recommended') }}</span>
                        <h2>{{ plan.name }}</h2>
                        <p class="sp-plan-price">
                            {{ formatCents(price(plan)) }}
                            <small>/ {{ interval === 'yearly' ? t('platform.yr') : t('platform.mo') }}</small>
                        </p>
                        <p class="sp-lead" style="font-size: 0.9rem;">
                            {{ interval === 'yearly' ? t('platform.billed_yearly') : t('platform.billed_monthly') }}
                        </p>
                        <ul>
                            <li>{{ t('platform.trial_days_label', { days: plan.trial_days }) }}</li>
                            <li v-if="plan.limits?.max_branches">
                                {{ t('platform.max_branches_label', { count: plan.limits.max_branches }) }}
                            </li>
                            <li v-if="plan.limits?.max_users">
                                {{ t('platform.max_users_label', { count: plan.limits.max_users }) }}
                            </li>
                            <li v-else-if="plan.limits && plan.limits.max_users === null">
                                {{ t('platform.unlimited_users') }}
                            </li>
                            <li v-if="plan.limits?.max_products">
                                {{ t('platform.max_products_label', { count: plan.limits.max_products }) }}
                            </li>
                            <li v-else-if="plan.limits && plan.limits.max_products === null">
                                {{ t('platform.unlimited_products') }}
                            </li>
                            <li v-if="plan.features?.pos">{{ t('platform.feature_pos') }}</li>
                            <li v-if="plan.features?.reports">{{ t('platform.feature_reports') }}</li>
                            <li v-if="plan.features?.multi_branch">{{ t('platform.feature_multi_branch') }}</li>
                            <li v-if="plan.features?.barcode_camera_scan">{{ t('platform.feature_barcode') }}</li>
                            <li v-if="plan.features?.hr_payroll">{{ t('platform.feature_payroll') }}</li>
                        </ul>
                        <a href="/#demo" class="sp-btn sp-btn-primary">
                            {{ plan.slug === 'enterprise' ? t('platform.contact_sales') : t('platform.start_trial') }}
                        </a>
                    </article>
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>

<script setup>
import SeoHead from '@/Components/Marketing/SeoHead.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { ref } from 'vue';

const props = defineProps({
    plans: { type: Array, required: true },
    currency: { type: String, default: 'BDT' },
    selfServiceEnabled: { type: Boolean, default: true },
    support: { type: Object, default: () => ({ email: null, phone: null }) },
});

const { t } = useLocale();
const interval = ref('monthly');

function price(plan) {
    return interval.value === 'yearly'
        ? (plan.yearly_price_cents || plan.price_cents)
        : (plan.monthly_price_cents || plan.price_cents);
}

function formatCents(cents) {
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: props.currency,
        maximumFractionDigits: 0,
    }).format((Number(cents) || 0) / 100);
}

function isFeatured(plan) {
    return Boolean(plan.is_featured || plan.slug === 'professional');
}
</script>
