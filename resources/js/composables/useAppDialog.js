import { ref } from 'vue';

const dialog = ref(null);

export function showAppDialog(payload) {
    if (!payload || !payload.message) {
        return;
    }
    dialog.value = {
        type: payload.type || 'error',
        title: payload.title || '',
        message: payload.message,
        action_label: payload.action_label || '',
        action_href: payload.action_href || null,
        action_method: payload.action_method || null,
    };
}

export function closeAppDialog() {
    dialog.value = null;
}

export function useAppDialog() {
    return {
        dialog,
        show: showAppDialog,
        close: closeAppDialog,
    };
}
