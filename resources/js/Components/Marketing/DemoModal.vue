<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="sp-modal-root"
            role="dialog"
            aria-modal="true"
            :aria-label="t('platform.book_demo')"
            @keydown.esc.prevent="closeDemoModal"
        >
            <button type="button" class="sp-modal-backdrop" tabindex="-1" :aria-label="t('common.close') || 'Close'" @click="closeDemoModal" />
            <div class="sp-modal-panel">
                <div class="sp-modal-header">
                    <div>
                        <p class="sp-eyebrow" style="margin-bottom: 0.35rem;">SoftoPharma</p>
                        <h2 class="sp-modal-title">{{ t('platform.book_demo') }}</h2>
                    </div>
                    <button type="button" class="sp-modal-close" :aria-label="t('common.close') || 'Close'" @click="closeDemoModal">
                        ×
                    </button>
                </div>
                <DemoLeadForm compact />
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import DemoLeadForm from '@/Components/Marketing/DemoLeadForm.vue';
import { useDemoModal } from '@/composables/useDemoModal';
import { useLocale } from '@/composables/useLocale';
import { onBeforeUnmount, watch } from 'vue';

const { t } = useLocale();
const { isOpen, closeDemoModal } = useDemoModal();

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
