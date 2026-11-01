import axios from 'axios';
import { showAppDialog } from '@/composables/useAppDialog';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        if ([403, 404, 419, 429, 500, 503].includes(status) && !error.config?.headers?.['X-Inertia']) {
            const payload = error.response?.data?.dialog;
            showAppDialog(payload || {
                type: status === 404 ? 'not_found' : status === 403 ? 'forbidden' : 'error',
                title: payload?.title,
                message: error.response?.data?.message || '',
            });
        }
        return Promise.reject(error);
    },
);
