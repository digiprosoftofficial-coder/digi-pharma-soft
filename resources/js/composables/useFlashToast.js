import { ref } from 'vue';

const toasts = ref([]);
let seq = 0;
const DEFAULT_DURATION = {
    success: 4200,
    info: 4500,
    warning: 5500,
    error: 6500,
};

function normalizeType(type) {
    if (type === 'danger') {
        return 'error';
    }
    if (['success', 'info', 'warning', 'error'].includes(type)) {
        return type;
    }
    return 'info';
}

export function pushFlashToast(payload) {
    if (!payload) {
        return null;
    }

    const message = typeof payload === 'string' ? payload : payload.message;
    if (!message || !String(message).trim()) {
        return null;
    }

    const type = normalizeType(typeof payload === 'string' ? 'info' : (payload.type || 'info'));
    const duration = typeof payload === 'object' && payload.duration != null
        ? Number(payload.duration)
        : DEFAULT_DURATION[type];

    const id = ++seq;
    const toast = {
        id,
        type,
        message: String(message).trim(),
        title: typeof payload === 'object' ? (payload.title || '') : '',
        actionHref: typeof payload === 'object' ? (payload.actionHref || payload.action_href || null) : null,
        actionLabel: typeof payload === 'object' ? (payload.actionLabel || payload.action_label || '') : '',
    };

    toasts.value = [...toasts.value, toast];

    if (duration > 0) {
        window.setTimeout(() => {
            dismissFlashToast(id);
        }, duration);
    }

    return id;
}

export function dismissFlashToast(id) {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
}

export function clearFlashToasts() {
    toasts.value = [];
}

export function notifySuccess(message, options = {}) {
    return pushFlashToast({ ...options, type: 'success', message });
}

export function notifyError(message, options = {}) {
    return pushFlashToast({ ...options, type: 'error', message });
}

export function notifyWarning(message, options = {}) {
    return pushFlashToast({ ...options, type: 'warning', message });
}

export function notifyInfo(message, options = {}) {
    return pushFlashToast({ ...options, type: 'info', message });
}

export function useFlashToast() {
    return {
        toasts,
        push: pushFlashToast,
        dismiss: dismissFlashToast,
        clear: clearFlashToasts,
        success: notifySuccess,
        error: notifyError,
        warning: notifyWarning,
        info: notifyInfo,
    };
}
