const DB_NAME = 'pharmacy-offline';
const DB_VERSION = 1;

/**
 * @returns {Promise<IDBDatabase>}
 */
function openDb() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = () => reject(request.error ?? new Error('IndexedDB open failed'));
        request.onsuccess = () => resolve(request.result);
        request.onupgradeneeded = () => {
            const db = request.result;

            if (!db.objectStoreNames.contains('products')) {
                const products = db.createObjectStore('products', { keyPath: 'id' });
                products.createIndex('sku', 'sku', { unique: false });
                products.createIndex('barcode', 'barcode', { unique: false });
                products.createIndex('name', 'name', { unique: false });
            }

            if (!db.objectStoreNames.contains('meta')) {
                db.createObjectStore('meta', { keyPath: 'key' });
            }

            if (!db.objectStoreNames.contains('pending_sales')) {
                const pending = db.createObjectStore('pending_sales', { keyPath: 'client_id' });
                pending.createIndex('created_at', 'created_at', { unique: false });
            }
        };
    });
}

/**
 * @template T
 * @param {string} storeName
 * @param {IDBTransactionMode} mode
 * @param {(store: IDBObjectStore) => IDBRequest|Promise<T>|T} work
 * @returns {Promise<T>}
 */
async function withStore(storeName, mode, work) {
    const db = await openDb();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(storeName, mode);
        const store = tx.objectStore(storeName);
        let result;

        try {
            result = work(store);
        } catch (error) {
            reject(error);
            return;
        }

        tx.oncomplete = () => {
            if (result && typeof result === 'object' && 'onsuccess' in result) {
                resolve(result.result);
            } else {
                resolve(result);
            }
        };
        tx.onerror = () => reject(tx.error ?? new Error('IndexedDB transaction failed'));
        tx.onabort = () => reject(tx.error ?? new Error('IndexedDB transaction aborted'));

        if (result && typeof result === 'object' && 'onsuccess' in result) {
            result.onerror = () => reject(result.error);
        } else if (result && typeof result.then === 'function') {
            result.then((value) => {
                result = value;
            }).catch(reject);
        }
    });
}

/**
 * @param {Array<Record<string, any>>} products
 * @param {number|string|null} tenantId
 */
export async function upsertProducts(products, tenantId = null) {
    if (!Array.isArray(products) || products.length === 0) {
        return;
    }

    const db = await openDb();

    await new Promise((resolve, reject) => {
        const tx = db.transaction(['products', 'meta'], 'readwrite');
        const productStore = tx.objectStore('products');
        const metaStore = tx.objectStore('meta');

        for (const product of products) {
            if (product?.id == null) {
                continue;
            }
            productStore.put({
                ...product,
                _cached_at: Date.now(),
            });
        }

        metaStore.put({
            key: 'catalog',
            tenant_id: tenantId,
            cached_at: Date.now(),
            count: products.length,
        });

        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error ?? new Error('Failed to cache products'));
    });
}

/**
 * @returns {Promise<{tenant_id: any, cached_at: number, count: number}|null>}
 */
export async function getCatalogMeta() {
    return withStore('meta', 'readonly', (store) => store.get('catalog'));
}

/**
 * @param {string} term
 * @param {number} limit
 * @returns {Promise<Array<Record<string, any>>>}
 */
export async function searchCachedProducts(term, limit = 30) {
    const q = String(term || '').trim().toLowerCase();
    if (!q) {
        return [];
    }

    const db = await openDb();

    return new Promise((resolve, reject) => {
        const tx = db.transaction('products', 'readonly');
        const store = tx.objectStore('products');
        const request = store.getAll();

        request.onsuccess = () => {
            const all = request.result || [];
            const exactBarcode = all.filter((p) => String(p.barcode || '').toLowerCase() === q);
            if (exactBarcode.length) {
                resolve(exactBarcode.slice(0, limit));
                return;
            }

            const scored = all
                .map((p) => {
                    const name = String(p.name || '').toLowerCase();
                    const sku = String(p.sku || '').toLowerCase();
                    const generic = String(p.generic_name || '').toLowerCase();
                    const barcode = String(p.barcode || '').toLowerCase();
                    const strength = String(p.strength || '').toLowerCase();
                    const hay = `${name} ${sku} ${generic} ${barcode} ${strength}`;

                    let score = 0;
                    if (sku === q || barcode === q) {
                        score = 100;
                    } else if (name.startsWith(q) || sku.startsWith(q)) {
                        score = 80;
                    } else if (hay.includes(q)) {
                        score = 40;
                    }

                    return { product: p, score };
                })
                .filter((row) => row.score > 0)
                .sort((a, b) => b.score - a.score || String(a.product.name).localeCompare(String(b.product.name)))
                .slice(0, limit)
                .map((row) => row.product);

            resolve(scored);
        };
        request.onerror = () => reject(request.error);
    });
}

/**
 * @param {{client_id: string, payload: Record<string, any>, created_at?: number}} sale
 */
export async function enqueuePendingSale(sale) {
    const record = {
        client_id: sale.client_id,
        payload: sale.payload,
        created_at: sale.created_at ?? Date.now(),
        status: 'pending',
        last_error: null,
        attempts: 0,
    };

    await withStore('pending_sales', 'readwrite', (store) => store.put(record));

    return record;
}

/**
 * @returns {Promise<Array<Record<string, any>>>}
 */
export async function listPendingSales() {
    const db = await openDb();

    return new Promise((resolve, reject) => {
        const tx = db.transaction('pending_sales', 'readonly');
        const store = tx.objectStore('pending_sales');
        const request = store.index('created_at').getAll();

        request.onsuccess = () => resolve(request.result || []);
        request.onerror = () => reject(request.error);
    });
}

/**
 * @returns {Promise<number>}
 */
export async function countPendingSales() {
    const rows = await listPendingSales();

    return rows.filter((row) => row.status !== 'synced').length;
}

/**
 * @param {string} clientId
 */
export async function removePendingSale(clientId) {
    await withStore('pending_sales', 'readwrite', (store) => store.delete(clientId));
}

/**
 * @param {string} clientId
 * @param {string} errorMessage
 */
export async function markPendingSaleFailed(clientId, errorMessage) {
    const db = await openDb();

    await new Promise((resolve, reject) => {
        const tx = db.transaction('pending_sales', 'readwrite');
        const store = tx.objectStore('pending_sales');
        const getReq = store.get(clientId);

        getReq.onsuccess = () => {
            const row = getReq.result;
            if (!row) {
                return;
            }
            row.status = 'failed';
            row.last_error = errorMessage;
            row.attempts = Number(row.attempts || 0) + 1;
            store.put(row);
        };

        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error ?? new Error('Failed to update pending sale'));
    });
}

export function createClientId() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `offline-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
}
