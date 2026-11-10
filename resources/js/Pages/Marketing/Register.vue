<template>
    <MarketingLayout>
        <Head :title="t('platform.get_started')" />
        <section class="sp-section">
            <div class="sp-container" style="max-width: 720px">
                <p class="sp-eyebrow">SoftoPharma</p>
                <h1 class="sp-display">{{ t('platform.get_started') }}</h1>
                <p class="sp-lead" style="margin: 0.75rem 0 1.5rem;">{{ t('platform.register_sub') }}</p>
                <RegisterForm
                    :plans="plans"
                    :selected-plan-id="selectedPlanId"
                    :selected-interval="selectedInterval"
                    :payment-methods="paymentMethods"
                    :payment-info="paymentInfo"
                />
            </div>
        </section>
    </MarketingLayout>
</template>

<script setup>
import RegisterForm from '@/Components/Marketing/RegisterForm.vue';
import MarketingLayout from '@/Layouts/MarketingLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head } from '@inertiajs/vue3';

defineProps({
    plans: { type: Array, required: true },
    selectedPlanId: { type: Number, default: null },
    selectedInterval: { type: String, default: 'monthly' },
    currency: { type: String, default: 'BDT' },
    paymentMethods: { type: Array, required: true },
    paymentInfo: { type: Object, default: () => ({}) },
});

const { t } = useLocale();
</script>
