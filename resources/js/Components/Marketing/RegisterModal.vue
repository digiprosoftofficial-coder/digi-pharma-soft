<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="sp-modal-root"
            role="dialog"
            aria-modal="true"
            :aria-label="t('platform.try_live_demo')"
            @keydown.esc.prevent="closeRegisterModal"
        >
            <button
                type="button"
                class="sp-modal-backdrop"
                tabindex="-1"
                :aria-label="t('common.close') || 'Close'"
                @click="closeRegisterModal"
            />
            <div class="sp-modal-panel sp-modal-panel--wide">
                <div class="sp-modal-header">
                    <div>
                        <p class="sp-eyebrow" style="margin-bottom: 0.35rem;">SoftoPharma</p>
                        <h2 class="sp-modal-title">{{ t('platform.get_started') }}</h2>
                        <p class="small text-muted mb-0 mt-1">{{ t('platform.register_sub') }}</p>
                    </div>
                    <button
                        type="button"
                        class="sp-modal-close"
                        :aria-label="t('common.close') || 'Close'"
                        @click="closeRegisterModal"
                    >
                        ×
                    </button>
                </div>
                <RegisterForm
                    v-if="plans.length"
                    compact
                    :plans="plans"
                    :selected-plan-id="selectedPlanId"
                    :selected-interval="selectedInterval"
                    :payment-methods="paymentMethods"
                    :payment-info="paymentInfo"
                    @success="closeRegisterModal"
                />
                <p v-else class="text-muted small mb-0">{{ t('common.no_results') }}</p>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import RegisterForm from '@/Components/Marketing/RegisterForm.vue';
import { useRegisterModal } from '@/composables/useRegisterModal';
import { useLocale } from '@/composables/useLocale';
import { onBeforeUnmount, watch } from 'vue';

defineProps({
    plans: { type: Array, default: () => [] },
    selectedPlanId: { type: [Number, String], default: null },
    selectedInterval: { type: String, default: 'monthly' },
    paymentMethods: { type: Array, default: () => [] },
    paymentInfo: { type: Object, default: () => ({}) },
});

const { t } = useLocale();
const { isOpen, closeRegisterModal } = useRegisterModal();

watch(isOpen, (open) => {
    if (typeof document === 'undefined') {
        return;
    }
    document.body.classList.toggle('overflow-hidden', open);
});

onBeforeUnmount(() => {
    if (typeof document !== 'undefined') {
        document.body.classList.remove('overflow-hidden');
    }
});
</script>
