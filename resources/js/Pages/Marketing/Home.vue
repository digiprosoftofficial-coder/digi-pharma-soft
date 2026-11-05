<template>
    <MarketingLayout>
        <Head title="DigiProSoft" />
        <section class="py-5" style="background: linear-gradient(160deg, #e8f3ff 0%, #f8fafc 55%, #ffffff 100%);">
            <div class="container py-lg-4">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <p class="text-primary fw-semibold mb-2">DigiProSoft</p>
                        <h1 class="display-5 fw-bold mb-3">{{ t('platform.marketing_hero_title') }}</h1>
                        <p class="lead text-muted mb-4">{{ t('platform.marketing_hero_sub') }}</p>
                        <div class="d-flex flex-wrap gap-2">
                            <Link href="/demo" class="btn btn-primary btn-lg">{{ t('platform.book_demo') }}</Link>
                            <Link v-if="selfServiceEnabled" href="/get-started" class="btn btn-outline-primary btn-lg">{{ t('platform.start_trial') }}</Link>
                            <Link href="/pricing" class="btn btn-link btn-lg">{{ t('platform.view_pricing') }}</Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="container py-5">
            <h2 class="h4 mb-3">{{ t('platform.popular_plans') }}</h2>
            <div class="row g-3">
                <div v-for="plan in plans.slice(0, 3)" :key="plan.id" class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h3 class="h5">{{ plan.name }}</h3>
                            <p class="mb-1 fw-semibold">{{ formatCents(plan.monthly_price_cents || plan.price_cents) }}/mo</p>
                            <p class="small text-muted">{{ formatCents(plan.yearly_price_cents || plan.price_cents) }}/yr</p>
                            <Link :href="`/get-started?plan=${plan.slug}`" class="btn btn-sm btn-primary">{{ t('platform.choose_plan') }}</Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </MarketingLayout>
</template>

<script setup>
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    plans: { type: Array, required: true },
    currency: { type: String, default: 'BDT' },
    selfServiceEnabled: { type: Boolean, default: true },
});

const { t } = useLocale();

function formatCents(cents) {
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: props.currency, maximumFractionDigits: 0 }).format((Number(cents) || 0) / 100);
}
</script>
