<template>
    <Teleport to="body">
        <div class="flash-toasts" aria-live="polite" aria-relevant="additions">
            <TransitionGroup name="flash-toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="flash-toast"
                    :class="`flash-toast--${toast.type}`"
                    role="status"
                >
                    <span class="flash-toast__icon" aria-hidden="true">
                        <svg v-if="toast.type === 'success'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <path d="m8 12 2.5 2.5L16 9" />
                        </svg>
                        <svg v-else-if="toast.type === 'warning'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 9v4" />
                            <path d="M12 17h.01" />
                            <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                        </svg>
                        <svg v-else-if="toast.type === 'error'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <path d="m15 9-6 6" />
                            <path d="m9 9 6 6" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M12 8v5" />
                            <path d="M12 16h.01" />
                        </svg>
                    </span>
                    <div class="flash-toast__body min-w-0">
                        <div v-if="toast.title" class="flash-toast__title">{{ toast.title }}</div>
                        <div class="flash-toast__message">{{ toast.message }}</div>
                        <a
                            v-if="toast.actionHref && toast.actionLabel"
                            :href="toast.actionHref"
                            class="flash-toast__action"
                        >
                            {{ toast.actionLabel }}
                        </a>
                    </div>
                    <button
                        type="button"
                        class="flash-toast__close"
                        :aria-label="t('common.close')"
                        @click="dismiss(toast.id)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<script setup>
import { useFlashToast } from '@/composables/useFlashToast';
import { useLocale } from '@/composables/useLocale';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

/** Pages that already show a richer success banner with actions (print, etc.). */
const SUPPRESS_FLASH_TOAST_COMPONENTS = new Set([
    'Pos/Index',
    'Sales/PackageSale',
]);

const { t } = useLocale();
const page = usePage();
const { toasts, push, dismiss } = useFlashToast();

const seenKeys = new Set();

function flashKey(type, message) {
    return `${type}:${message}`;
}

function pushFromFlash(type, message) {
    if (!message || SUPPRESS_FLASH_TOAST_COMPONENTS.has(page.component)) {
        return;
    }
    const key = flashKey(type, message);
    if (seenKeys.has(key)) {
        return;
    }
    seenKeys.add(key);
    push({ type, message });
    window.setTimeout(() => {
        seenKeys.delete(key);
    }, 1500);
}

watch(
    () => ({
        success: page.props.flash?.success,
        info: page.props.flash?.info,
        warning: page.props.flash?.warning,
        error: page.props.flash?.error,
        component: page.component,
    }),
    (flash) => {
        pushFromFlash('success', flash.success);
        pushFromFlash('info', flash.info);
        pushFromFlash('warning', flash.warning);
        pushFromFlash('error', flash.error);
    },
    { immediate: true, deep: true },
);
</script>

<style scoped>
.flash-toasts {
    position: fixed;
    top: calc(0.85rem + env(safe-area-inset-top, 0px));
    right: max(0.85rem, env(safe-area-inset-right, 0px));
    z-index: 1200;
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    width: min(22.5rem, calc(100vw - 1.5rem));
    pointer-events: none;
}

.flash-toast {
    pointer-events: auto;
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0.8rem 0.85rem;
    border-radius: 0.9rem;
    border: 1px solid transparent;
    background: #fff;
    box-shadow: 0 0.65rem 1.6rem rgba(15, 23, 42, 0.14);
    backdrop-filter: blur(8px);
}

.flash-toast__icon {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    width: 1.85rem;
    height: 1.85rem;
    border-radius: 999px;
}

.flash-toast__body {
    flex: 1 1 auto;
    padding-top: 0.1rem;
}

.flash-toast__title {
    font-size: 0.78rem;
    font-weight: 700;
    margin-bottom: 0.1rem;
    opacity: 0.85;
}

.flash-toast__message {
    font-size: 0.88rem;
    font-weight: 600;
    line-height: 1.35;
    color: #0f172a;
}

.flash-toast__action {
    display: inline-block;
    margin-top: 0.35rem;
    font-size: 0.78rem;
    font-weight: 700;
    text-decoration: none;
}

.flash-toast__action:hover {
    text-decoration: underline;
}

.flash-toast__close {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: center;
    width: 1.6rem;
    height: 1.6rem;
    margin: -0.15rem -0.2rem 0 0;
    padding: 0;
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: #94a3b8;
}

.flash-toast__close:hover {
    background: rgba(15, 23, 42, 0.06);
    color: #475569;
}

.flash-toast--success {
    border-color: #bbf7d0;
    background: linear-gradient(180deg, #f0fdf4 0%, #ffffff 70%);
}

.flash-toast--success .flash-toast__icon {
    color: #15803d;
    background: rgba(34, 197, 94, 0.14);
}

.flash-toast--success .flash-toast__action {
    color: #15803d;
}

.flash-toast--info {
    border-color: #bfdbfe;
    background: linear-gradient(180deg, #eff6ff 0%, #ffffff 70%);
}

.flash-toast--info .flash-toast__icon {
    color: #1d4ed8;
    background: rgba(59, 130, 246, 0.14);
}

.flash-toast--info .flash-toast__action {
    color: #1d4ed8;
}

.flash-toast--warning {
    border-color: #fde68a;
    background: linear-gradient(180deg, #fffbeb 0%, #ffffff 70%);
}

.flash-toast--warning .flash-toast__icon {
    color: #b45309;
    background: rgba(245, 158, 11, 0.16);
}

.flash-toast--warning .flash-toast__action {
    color: #b45309;
}

.flash-toast--error {
    border-color: #fecaca;
    background: linear-gradient(180deg, #fef2f2 0%, #ffffff 70%);
}

.flash-toast--error .flash-toast__icon {
    color: #b91c1c;
    background: rgba(239, 68, 68, 0.14);
}

.flash-toast--error .flash-toast__action {
    color: #b91c1c;
}

.flash-toast-enter-active,
.flash-toast-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.flash-toast-enter-from,
.flash-toast-leave-to {
    opacity: 0;
    transform: translateX(0.6rem);
}

.flash-toast-move {
    transition: transform 0.2s ease;
}

@media (max-width: 575.98px) {
    .flash-toasts {
        left: 50%;
        right: auto;
        width: min(22.5rem, calc(100vw - 1.25rem));
        transform: translateX(-50%);
    }

    .flash-toast-enter-from,
    .flash-toast-leave-to {
        transform: translateY(-0.4rem);
    }
}
</style>
