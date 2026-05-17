<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            :aria-labelledby="titleId"
            aria-modal="true"
            @keydown.esc.prevent="onClose"
        >
            <div class="modal-dialog" role="document" @click.stop>
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 :id="titleId" class="modal-title h5 mb-0">{{ title }}</h2>
                        <button
                            type="button"
                            class="btn-close"
                            :aria-label="t('common.cancel')"
                            :disabled="processing"
                            @click="onClose"
                        />
                    </div>
                    <div class="modal-body">
                        <slot />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" :disabled="processing" @click="onClose">
                            {{ cancelLabel || t('common.cancel') }}
                        </button>
                        <button
                            type="button"
                            :class="['btn', confirmClass]"
                            :disabled="processing"
                            @click="emit('confirm')"
                        >
                            <span v-if="processing" class="spinner-border spinner-border-sm me-1" role="status" />
                            {{ confirmLabel || t('common.confirm') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="show" class="modal-backdrop fade show" />
    </Teleport>
</template>

<script setup>
import { useLocale } from '@/composables/useLocale';
import { onUnmounted, useId, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, required: true },
    confirmLabel: { type: String, default: '' },
    cancelLabel: { type: String, default: '' },
    confirmClass: { type: String, default: 'btn-primary' },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'confirm']);

const { t } = useLocale();
const titleId = useId();

function onClose() {
    if (props.processing) {
        return;
    }
    emit('close');
}

function setBodyScrollLock(visible) {
    document.body.classList.toggle('modal-open', visible);
    document.body.style.overflow = visible ? 'hidden' : '';
}

watch(
    () => props.show,
    (visible) => setBodyScrollLock(visible),
);

onUnmounted(() => setBodyScrollLock(false));
</script>
