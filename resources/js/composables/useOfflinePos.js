import {
    countPendingSales,
    createClientId,
    enqueuePendingSale,
    getCatalogMeta,
    listPendingSales,
    markPendingSaleFailed,
    removePendingSale,
    searchCachedProducts,
    upsertProducts,
} from '@/offline/db';
import { onMounted, onUnmounted, ref } from 'vue';

export function useOfflinePos({ tenantId } = {}) {
    const isOnline = ref(typeof navigator === 'undefined' ? true : navigator.onLine);
    const pendingCount = ref(0);
    const syncing = ref(false);
    const catalogCachedAt = ref(null);
    const catalogCount = ref(0);
    const lastSyncError = ref('');
    const offlineNotice = ref('');

    let syncTimer = null;

    async function refreshPendingCount() {
        pendingCount.value = await countPendingSales();
    }

    async function refreshCatalogMeta() {
        const meta = await getCatalogMeta();
        catalogCachedAt.value = meta?.cached_at ?? null;
        catalogCount.value = meta?.count ?? 0;
    }

    function setOnline(value) {
        isOnline.value = value;
        if (value) {
            offlineNotice.value = '';
            void syncPendingSales();
            void prefetchCatalog();
        }
    }

    function onOnline() {
        setOnline(true);
    }

    function onOffline() {
        setOnline(false);
    }

    async function cacheProducts(products) {
        await upsertProducts(products, tenantId?.value ?? tenantId ?? null);
        await refreshCatalogMeta();
    }

    async function prefetchCatalog() {
        if (!isOnline.value) {
            return;
        }

        try {
            const { data } = await window.axios.get('/pos/offline-catalog');
            if (Array.isArray(data?.data)) {
                await cacheProducts(data.data);
            }
        } catch {
            // Prefetch is best-effort; local cache may still help.
        }
    }

    async function searchProducts(term) {
        const q = String(term || '').trim();
        if (!q) {
            return [];
        }

        if (isOnline.value) {
            try {
                const { data } = await window.axios.get('/catalog/product-search', { params: { q } });
                const products = data?.data ?? [];
                if (products.length) {
                    await cacheProducts(products);
                }

                return products;
            } catch {
                // Fall through to local cache.
            }
        }

        return searchCachedProducts(q);
    }

    async function queueSale(payload) {
        const clientId = createClientId();
        const record = await enqueuePendingSale({
            client_id: clientId,
            payload: {
                ...payload,
                offline_client_id: clientId,
            },
        });
        await refreshPendingCount();
        offlineNotice.value = 'queued';

        return record;
    }

    async function syncPendingSales() {
        if (!isOnline.value || syncing.value) {
            return { synced: 0, failed: 0 };
        }

        syncing.value = true;
        lastSyncError.value = '';
        let synced = 0;
        let failed = 0;

        try {
            const pending = await listPendingSales();
            for (const row of pending) {
                if (row.status === 'synced') {
                    continue;
                }

                try {
                    const { data } = await window.axios.post('/pos/sales/sync', row.payload);
                    if (data?.ok) {
                        await removePendingSale(row.client_id);
                        synced += 1;
                    } else {
                        const message = data?.message || 'Sync failed';
                        await markPendingSaleFailed(row.client_id, message);
                        lastSyncError.value = message;
                        failed += 1;
                    }
                } catch (error) {
                    const message =
                        error?.response?.data?.message
                        || error?.response?.data?.errors?.checkout?.[0]
                        || error?.message
                        || 'Sync failed';
                    await markPendingSaleFailed(row.client_id, message);
                    lastSyncError.value = message;
                    failed += 1;

                    // Stop on auth / network hard failures after first error if offline again.
                    if (!navigator.onLine) {
                        break;
                    }
                }
            }
        } finally {
            syncing.value = false;
            await refreshPendingCount();
        }

        return { synced, failed };
    }

    onMounted(() => {
        window.addEventListener('online', onOnline);
        window.addEventListener('offline', onOffline);
        void refreshPendingCount();
        void refreshCatalogMeta();
        void prefetchCatalog();
        if (isOnline.value && pendingCount.value > 0) {
            void syncPendingSales();
        }
        syncTimer = window.setInterval(() => {
            if (navigator.onLine) {
                void syncPendingSales();
            }
        }, 30000);
    });

    onUnmounted(() => {
        window.removeEventListener('online', onOnline);
        window.removeEventListener('offline', onOffline);
        if (syncTimer) {
            window.clearInterval(syncTimer);
        }
    });

    return {
        isOnline,
        pendingCount,
        syncing,
        catalogCachedAt,
        catalogCount,
        lastSyncError,
        offlineNotice,
        cacheProducts,
        prefetchCatalog,
        searchProducts,
        queueSale,
        syncPendingSales,
        refreshPendingCount,
    };
}
