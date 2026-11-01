<template>
    <Teleport to="body">
        <div
            v-if="visible"
            class="modal fade show d-block"
            tabindex="-1"
            role="dialog"
            :aria-labelledby="titleId"
            aria-modal="true"
            @keydown.esc.prevent="onClose"
        >
            <div class="modal-dialog modal-dialog-centered" role="document" @click.stop>
                <div class="modal-content border-0 shadow">
                    <div class="modal-header" :class="headerClass">
                        <h2 :id="titleId" class="modal-title h5 mb-0">{{ title }}</h2>
                        <button
                            type="button"
                            class="btn-close"
                            :class="{ 'btn-close-white': isDarkHeader }"
                            :aria-label="t('common.close')"
                            @click="onClose"
                        />
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">{{ message }}</p>
                    </div>
                    <div class="modal-footer">
                        <Link
                            v-if="href && method === 'post'"
                            :href="href"
                            method="post"
                            as="button"
                            class="btn btn-primary"
                        >
                            {{ actionLabel }}
                        </Link>
                        <Link
                            v-else-if="href"
                            :href="href"
                            class="btn btn-primary"
                        >
                            {{ actionLabel }}
                        </Link>
                        <button v-else type="button" class="btn btn-primary" @click="onClose">
                            {{ actionLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="visible" class="modal-backdrop fade show" />
    </Teleport>
</template>

<script setup>
import { closeAppDialog, useAppDialog } from '@/composables/useAppDialog';
import { useLocale } from '@/composables/useLocale';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, useId, watch } from 'vue';

const props = defineProps({
    pageDialog: { type: Object, default: null },
});

const { t } = useLocale();
const page = usePage();
const { dialog, show } = useAppDialog();
const titleId = useId();

watch(
    () => props.pageDialog ?? page.props.dialog ?? page.props.flash?.app_dialog,
    (value) => {
        if (value) {
            show(value);
        }
    },
    { immediate: true },
);

const visible = computed(() => !!dialog.value);
const title = computed(() => dialog.value?.title || t('errors.server_title'));
const message = computed(() => dialog.value?.message || t('errors.server_body'));
const actionLabel = computed(() => dialog.value?.action_label || t('errors.got_it'));
const href = computed(() => dialog.value?.action_href || null);
const method = computed(() => (dialog.value?.action_method || 'get').toLowerCase());
const type = computed(() => dialog.value?.type || 'error');

const headerClass = computed(() => {
    if (type.value === 'forbidden') {
        return 'bg-warning-subtle';
    }
    if (type.value === 'not_found') {
        return 'bg-light';
    }
    if (type.value === 'expired' || type.value === 'too_many') {
        return 'bg-info-subtle';
    }
    return 'bg-danger text-white';
});

const isDarkHeader = computed(() => type.value === 'error');

function onClose() {
    closeAppDialog();
}

function setBodyScrollLock(open) {
    document.body.classList.toggle('modal-open', open);
    document.body.style.overflow = open ? 'hidden' : '';
}

watch(visible, (open) => setBodyScrollLock(open));
onUnmounted(() => setBodyScrollLock(false));
</script>
