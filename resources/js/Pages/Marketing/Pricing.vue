<template>
    <MarketingLayout>
        <Head :title="t('platform.nav_pricing')" />
        <div class="container py-5">
            <h1 class="page-heading mb-2">{{ t('platform.pricing_title') }}</h1>
            <p class="text-muted mb-4">{{ t('platform.pricing_sub') }}</p>
            <div class="btn-group mb-4">
                <button type="button" class="btn" :class="interval === 'monthly' ? 'btn-primary' : 'btn-outline-primary'" @click="interval = 'monthly'">{{ t('platform.monthly') }}</button>
                <button type="button" class="btn" :class="interval === 'yearly' ? 'btn-primary' : 'btn-outline-primary'" @click="interval = 'yearly'">{{ t('platform.yearly') }}</button>
            </div>
            <div class="row g-3">
                <div v-for="plan in plans" :key="plan.id" class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <h2 class="h5">{{ plan.name }}</h2>
                            <p class="fs-3 fw-bold mb-1">{{ formatCents(price(plan)) }}</p>
                            <p class="small text-muted mb-3">{{ interval === 'yearly' ? t('platform.billed_yearly') : t('platform.billed_monthly') }}</p>
                            <ul class="small text-muted mb-4">
                                <li>{{ t('platform.trial_days_label', { days: plan.trial_days }) }}</li>
                                <li v-if="plan.limits?.max_branches">{{ t('platform.max_branches_label', { count: plan.limits.max_branches }) }}</li>
                            </ul>
                            <Link
                                v-if="selfServiceEnabled"
                                :href="`/get-started?plan=${plan.slug}&interval=${interval}`"
                                class="btn btn-primary mt-auto"
                            >{{ t('platform.choose_plan') }}</Link>
                            <Link v-else href="/demo" class="btn btn-primary mt-auto">{{ t('platform.book_demo') }}</Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    plans: { type: Array, required: true },
    currency: { type: String, default: 'BDT' },
    selfServiceEnabled: { type: Boolean, default: true },
});

const { t } = useLocale();
const interval = ref('monthly');

function price(plan) {
    return interval.value === 'yearly'
        ? (plan.yearly_price_cents || plan.price_cents)
        : (plan.monthly_price_cents || plan.price_cents);
}

function formatCents(cents) {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: props.currency, maximumFractionDigits: 0 }).format((Number(cents) || 0) / 100);
}
</script>
