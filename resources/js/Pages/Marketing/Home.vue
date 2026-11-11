<template>
    <MarketingLayout :support="support">
        <SeoHead
            path="/"
            :support-email="support.email || ''"
            :support-phone="support.phone || ''"
        />

        <!-- HERO -->
        <section id="home" class="sp-hero">
            <div class="sp-container-wide sp-hero-grid">
                <div class="sp-hero-copy sp-reveal">
                    <p class="sp-presents">
                        DigiProSoft Presents <strong>{{ branding.site_name || 'SoftoPharma' }}</strong>
                    </p>
                    <p class="sp-eyebrow">{{ t('platform.hero_eyebrow') }}</p>
                    <h1 class="sp-display">{{ t('platform.marketing_hero_title') }}</h1>
                    <p class="sp-lead">{{ t('platform.marketing_hero_sub') }}</p>
                    <p class="sp-bn">{{ t('platform.marketing_hero_bn') }}</p>
                    <div class="sp-hero-ctas">
                        <button
                            v-if="showTryLiveDemo && selfServiceEnabled"
                            type="button"
                            class="sp-btn sp-btn-primary"
                            @click="openRegisterModal"
                        >
                            {{ t('platform.try_live_demo') }}
                        </button>
                        <a
                            v-else-if="showTryLiveDemo"
                            href="/#demo"
                            class="sp-btn sp-btn-primary"
                        >{{ t('platform.try_live_demo') }}</a>
                        <a href="/#demo" class="sp-btn sp-btn-secondary">{{ t('platform.book_demo') }}</a>
                    </div>
                    <p class="sp-trust-line">{{ t('platform.hero_trust') }}</p>
                </div>

                <div class="sp-reveal" style="animation-delay: 0.12s;">
                    <ProductPreview
                        :label="t('platform.module_pos')"
                        :src="shot('pos')"
                        :alt="t('platform.shot_pos_alt')"
                        variant="cards"
                    />
                </div>
            </div>
        </section>

        <!-- CHALLENGES -->
        <section class="sp-section">
            <div class="sp-container">
                <div class="sp-section-head">
                    <p class="sp-eyebrow">{{ t('platform.challenges_eyebrow') }}</p>
                    <h2 class="sp-display">{{ t('platform.challenges_title') }}</h2>
                    <p class="sp-lead">{{ t('platform.challenges_sub') }}</p>
                </div>
                <div class="sp-challenge-list">
                    <article v-for="(item, i) in challenges" :key="item.title" class="sp-challenge-item">
                        <div class="sp-challenge-num">{{ String(i + 1).padStart(2, '0') }}</div>
                        <h3 class="sp-challenge-title">{{ item.title }}</h3>
                        <p class="sp-challenge-body">{{ item.body }}</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- PRODUCT SHOWCASE -->
        <section id="features" class="sp-section sp-section-sage">
            <div class="sp-container">
                <div class="sp-section-head">
                    <p class="sp-eyebrow">{{ t('platform.features_eyebrow') }}</p>
                    <h2 class="sp-display">{{ t('platform.features_title') }}</h2>
                    <p class="sp-lead">{{ t('platform.features_sub') }}</p>
                </div>

                <div class="sp-showcase-tabs" role="tablist" :aria-label="t('platform.features_title')">
                    <button
                        v-for="(mod, idx) in primaryModules"
                        :key="mod.key"
                        type="button"
                        class="sp-showcase-tab"
                        :class="{ 'is-active': activeModule === idx }"
                        role="tab"
                        :aria-selected="activeModule === idx ? 'true' : 'false'"
                        @click="activeModule = idx"
                    >
                        {{ mod.title }}
                    </button>
                </div>

                <div class="sp-showcase-panel" :class="{ 'is-flip': activeModule % 2 === 1 }">
                    <ProductPreview
                        :label="primaryModules[activeModule].title"
                        :src="shot(primaryModules[activeModule].key)"
                        :alt="primaryModules[activeModule].alt"
                        :variant="primaryModules[activeModule].variant"
                    />
                    <div class="sp-showcase-copy">
                        <h3>{{ primaryModules[activeModule].title }}</h3>
                        <p>{{ primaryModules[activeModule].body }}</p>
                    </div>
                </div>

                <div class="sp-feature-alt">
                    <article
                        v-for="(mod, idx) in secondaryModules"
                        :key="mod.key"
                        class="sp-feature-row"
                        :class="{ 'is-flip': idx % 2 === 1 }"
                    >
                        <ProductPreview
                            :label="mod.title"
                            :src="shot(mod.key)"
                            :alt="mod.alt"
                            :variant="mod.variant"
                        />
                        <div class="sp-feature-copy">
                            <h3>{{ mod.title }}</h3>
                            <p>{{ mod.body }}</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- MULTI-BRANCH -->
        <section id="solutions" class="sp-section">
            <div class="sp-container">
                <div class="sp-showcase-panel">
                    <div>
                        <div class="sp-section-head" style="margin-bottom: 1.5rem;">
                            <p class="sp-eyebrow">{{ t('platform.branch_eyebrow') }}</p>
                            <h2 class="sp-display">{{ t('platform.branch_title') }}</h2>
                            <p class="sp-lead">{{ t('platform.branch_sub') }}</p>
                        </div>
                        <ul class="sp-pill-list">
                            <li v-for="item in branchPoints" :key="item.title">
                                <span class="sp-check" aria-hidden="true" />
                                <div>
                                    <strong>{{ item.title }}</strong>
                                    <span>{{ item.body }}</span>
                                </div>
                            </li>
                        </ul>
                        <p class="sp-lead" style="margin-top: 1.25rem; font-size: 0.92rem;">
                            {{ t('platform.branch_plan_note') }}
                        </p>
                    </div>
                    <ProductPreview
                        :label="t('platform.module_branches')"
                        :src="shot('branches')"
                        :alt="t('platform.shot_branches_alt')"
                        variant="cards"
                    />
                </div>
            </div>
        </section>

        <!-- RESPONSIVE -->
        <section class="sp-section sp-section-sage">
            <div class="sp-container">
                <div class="sp-section-head">
                    <p class="sp-eyebrow">{{ t('platform.responsive_eyebrow') }}</p>
                    <h2 class="sp-display">{{ t('platform.responsive_title') }}</h2>
                    <p class="sp-lead">{{ t('platform.responsive_sub') }}</p>
                </div>
                <div class="sp-device-grid">
                    <div class="sp-device">
                        <div class="sp-device-label">Desktop</div>
                        <div class="sp-device-screen">
                            <ProductPreview :label="t('platform.module_reports')" variant="cards" />
                        </div>
                    </div>
                    <div class="sp-device">
                        <div class="sp-device-label">Tablet</div>
                        <div class="sp-device-screen">
                            <ProductPreview :label="t('platform.module_inventory')" />
                        </div>
                    </div>
                    <div class="sp-device phone">
                        <div class="sp-device-label">Mobile</div>
                        <div class="sp-device-screen">
                            <ProductPreview :label="t('platform.module_pos')" variant="cards" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS -->
        <section class="sp-section">
            <div class="sp-container">
                <div class="sp-section-head">
                    <p class="sp-eyebrow">{{ t('platform.how_eyebrow') }}</p>
                    <h2 class="sp-display">{{ t('platform.how_title') }}</h2>
                </div>
                <div class="sp-steps">
                    <article v-for="(step, i) in steps" :key="step.title" class="sp-step">
                        <span class="sp-step-num">{{ String(i + 1).padStart(2, '0') }}</span>
                        <h3>{{ step.title }}</h3>
                        <p>{{ step.body }}</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- PRICING PREVIEW -->
        <section id="pricing" class="sp-section sp-section-sage">
            <div class="sp-container">
                <div class="sp-section-head">
                    <p class="sp-eyebrow">{{ t('platform.nav_pricing') }}</p>
                    <h2 class="sp-display">{{ t('platform.pricing_title') }}</h2>
                    <p class="sp-lead">{{ t('platform.pricing_sub') }}</p>
                </div>

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
                        <h3>{{ plan.name }}</h3>
                        <p class="sp-plan-price">
                            {{ formatCents(priceOf(plan)) }}
                            <small>/ {{ interval === 'yearly' ? t('platform.yr') : t('platform.mo') }}</small>
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
                            <li v-if="plan.features?.multi_branch">{{ t('platform.feature_multi_branch') }}</li>
                            <li v-if="plan.features?.reports">{{ t('platform.feature_reports') }}</li>
                            <li v-if="plan.features?.pos">{{ t('platform.feature_pos') }}</li>
                        </ul>
                        <a href="/#demo" class="sp-btn sp-btn-primary">
                            {{ t('platform.start_trial') }}
                        </a>
                    </article>
                </div>

                <div style="margin-top: 1.5rem;">
                    <Link href="/pricing" class="sp-btn sp-btn-ghost">{{ t('platform.view_full_pricing') }}</Link>
                </div>
            </div>
        </section>

        <!-- DEMO -->
        <section id="demo" class="sp-section">
            <div class="sp-container">
                <div class="sp-showcase-panel">
                    <div>
                        <div class="sp-section-head" style="margin-bottom: 1.25rem;">
                            <p class="sp-eyebrow">{{ t('platform.nav_demo') }}</p>
                            <h2 class="sp-display">{{ t('platform.demo_section_title') }}</h2>
                            <p class="sp-lead">{{ t('platform.demo_section_sub') }}</p>
                        </div>
                        <DemoLeadForm />
                    </div>
                    <div>
                        <p class="sp-bn" style="margin-bottom: 1rem;">{{ t('platform.brand_presents_bn') }}</p>
                        <ProductPreview
                            :label="t('platform.module_dashboard')"
                            :src="shot('dashboard')"
                            :alt="t('platform.shot_dashboard_alt')"
                            variant="cards"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- TRUST -->
        <section id="trust" class="sp-section sp-section-sage">
            <div class="sp-container">
                <div class="sp-section-head">
                    <p class="sp-eyebrow">{{ t('platform.trust_eyebrow') }}</p>
                    <h2 class="sp-display">{{ t('platform.trust_title') }}</h2>
                    <p class="sp-lead">{{ t('platform.company_blurb') }}</p>
                </div>
                <ul class="sp-pill-list">
                    <li v-for="item in trustPoints" :key="item.title">
                        <span class="sp-check" aria-hidden="true" />
                        <div>
                            <strong>{{ item.title }}</strong>
                            <span>{{ item.body }}</span>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" class="sp-section">
            <div class="sp-container" style="max-width: 760px;">
                <div class="sp-section-head">
                    <p class="sp-eyebrow">FAQ</p>
                    <h2 class="sp-display">{{ t('platform.faq_title') }}</h2>
                </div>
                <div class="sp-faq">
                    <details v-for="item in faqs" :key="item.q">
                        <summary>{{ item.q }}</summary>
                        <p>{{ item.a }}</p>
                    </details>
                </div>
            </div>
        </section>

        <!-- FINAL CTA -->
        <section class="sp-section sp-section-forest">
            <div class="sp-container sp-final-cta">
                <h2 class="sp-display">{{ t('platform.final_cta_title') }}</h2>
                <p class="sp-lead">{{ t('platform.final_cta_sub') }}</p>
                <div class="sp-hero-ctas">
                    <button
                        v-if="showTryLiveDemo && selfServiceEnabled"
                        type="button"
                        class="sp-btn sp-btn-primary"
                        @click="openRegisterModal"
                    >
                        {{ t('platform.try_live_demo') }}
                    </button>
                    <a
                        v-else-if="showTryLiveDemo"
                        href="/#demo"
                        class="sp-btn sp-btn-primary"
                    >{{ t('platform.try_live_demo') }}</a>
                    <a
                        href="/#demo"
                        class="sp-btn sp-btn-secondary"
                        style="border-color: #f8f8f4; color: #f8f8f4;"
                    >
                        {{ t('platform.book_demo') }}
                    </a>
                </div>
            </div>
        </section>

        <RegisterModal
            v-if="selfServiceEnabled"
            :plans="plans"
            :payment-methods="paymentMethods"
            :payment-info="paymentInfo"
        />
    </MarketingLayout>
</template>

<script setup>
import DemoLeadForm from '@/Components/Marketing/DemoLeadForm.vue';
import ProductPreview from '@/Components/Marketing/ProductPreview.vue';
import RegisterModal from '@/Components/Marketing/RegisterModal.vue';
import SeoHead from '@/Components/Marketing/SeoHead.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useRegisterModal } from '@/composables/useRegisterModal';
import { useLocale } from '@/composables/useLocale';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    plans: { type: Array, required: true },
    currency: { type: String, default: 'BDT' },
    selfServiceEnabled: { type: Boolean, default: true },
    showTryLiveDemo: { type: Boolean, default: true },
    support: { type: Object, default: () => ({ email: null, phone: null }) },
    screenshots: { type: Object, default: () => ({}) },
    paymentMethods: { type: Array, default: () => [] },
    paymentInfo: { type: Object, default: () => ({}) },
});

const { t } = useLocale();
const page = usePage();
const branding = computed(() => page.props.branding || {});
const { openRegisterModal } = useRegisterModal();
const interval = ref('monthly');
const activeModule = ref(0);

const challenges = computed(() => [
    { title: t('platform.challenge_1_title'), body: t('platform.challenge_1_body') },
    { title: t('platform.challenge_2_title'), body: t('platform.challenge_2_body') },
    { title: t('platform.challenge_3_title'), body: t('platform.challenge_3_body') },
    { title: t('platform.challenge_4_title'), body: t('platform.challenge_4_body') },
    { title: t('platform.challenge_5_title'), body: t('platform.challenge_5_body') },
    { title: t('platform.challenge_6_title'), body: t('platform.challenge_6_body') },
]);

const primaryModules = computed(() => [
    {
        key: 'pos',
        title: t('platform.module_pos'),
        body: t('platform.module_pos_body'),
        alt: t('platform.shot_pos_alt'),
        variant: 'cards',
    },
    {
        key: 'inventory',
        title: t('platform.module_inventory'),
        body: t('platform.module_inventory_body'),
        alt: t('platform.shot_inventory_alt'),
        variant: 'table',
    },
    {
        key: 'expiry',
        title: t('platform.module_expiry'),
        body: t('platform.module_expiry_body'),
        alt: t('platform.shot_expiry_alt'),
        variant: 'table',
    },
    {
        key: 'purchase',
        title: t('platform.module_purchase'),
        body: t('platform.module_purchase_body'),
        alt: t('platform.shot_purchase_alt'),
        variant: 'table',
    },
]);

const secondaryModules = computed(() => [
    {
        key: 'supplier',
        title: t('platform.module_supplier'),
        body: t('platform.module_supplier_body'),
        alt: t('platform.shot_supplier_alt'),
        variant: 'table',
    },
    {
        key: 'customer',
        title: t('platform.module_customer'),
        body: t('platform.module_customer_body'),
        alt: t('platform.shot_customer_alt'),
        variant: 'table',
    },
    {
        key: 'accounting',
        title: t('platform.module_accounting'),
        body: t('platform.module_accounting_body'),
        alt: t('platform.shot_accounting_alt'),
        variant: 'cards',
    },
    {
        key: 'reports',
        title: t('platform.module_reports'),
        body: t('platform.module_reports_body'),
        alt: t('platform.shot_reports_alt'),
        variant: 'cards',
    },
]);

const branchPoints = computed(() => [
    { title: t('platform.branch_point_1_title'), body: t('platform.branch_point_1_body') },
    { title: t('platform.branch_point_2_title'), body: t('platform.branch_point_2_body') },
    { title: t('platform.branch_point_3_title'), body: t('platform.branch_point_3_body') },
    { title: t('platform.branch_point_4_title'), body: t('platform.branch_point_4_body') },
    { title: t('platform.branch_point_5_title'), body: t('platform.branch_point_5_body') },
]);

const steps = computed(() => [
    { title: t('platform.step_1_title'), body: t('platform.step_1_body') },
    { title: t('platform.step_2_title'), body: t('platform.step_2_body') },
    { title: t('platform.step_3_title'), body: t('platform.step_3_body') },
    { title: t('platform.step_4_title'), body: t('platform.step_4_body') },
]);

const trustPoints = computed(() => [
    { title: t('platform.trust_1_title'), body: t('platform.trust_1_body') },
    { title: t('platform.trust_2_title'), body: t('platform.trust_2_body') },
    { title: t('platform.trust_3_title'), body: t('platform.trust_3_body') },
]);

const faqs = computed(() => [
    { q: t('platform.faq_1_q'), a: t('platform.faq_1_a') },
    { q: t('platform.faq_2_q'), a: t('platform.faq_2_a') },
    { q: t('platform.faq_3_q'), a: t('platform.faq_3_a') },
    { q: t('platform.faq_4_q'), a: t('platform.faq_4_a') },
    { q: t('platform.faq_5_q'), a: t('platform.faq_5_a') },
    { q: t('platform.faq_6_q'), a: t('platform.faq_6_a') },
    { q: t('platform.faq_7_q'), a: t('platform.faq_7_a') },
    { q: t('platform.faq_8_q'), a: t('platform.faq_8_a') },
]);

function shot(key) {
    return props.screenshots?.[key] || '';
}

function priceOf(plan) {
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
