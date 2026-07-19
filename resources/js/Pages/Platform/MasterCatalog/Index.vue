<template>
    <PlatformShellLayout :page-title="t('platform.master_title')">
        <Head :title="t('platform.master_title')" />

        <section class="master-hero rounded-4 mb-4 p-4 p-md-5 text-white">
            <div class="row align-items-end g-3">
                <div class="col-lg-7">
                    <p class="small text-white-50 text-uppercase mb-2 fw-semibold letter-space">{{ t('platform.master_eyebrow') }}</p>
                    <h1 class="display-6 fw-semibold mb-2">{{ t('platform.master_title') }}</h1>
                    <p class="mb-0 opacity-90" style="max-width: 36rem">{{ t('platform.master_sub') }}</p>
                </div>
                <div class="col-lg-5 d-flex flex-wrap justify-content-lg-end gap-2">
                    <Link href="/platform/master-catalog/suggestions" class="btn btn-light fw-semibold">
                        {{ t('platform.nav_master_suggestions') }}
                        <span v-if="pendingSuggestions > 0" class="badge text-bg-warning ms-1">{{ pendingSuggestions }}</span>
                    </Link>
                    <Link href="/platform/master-catalog/import" class="btn btn-outline-light fw-semibold">
                        {{ t('platform.master_import_btn') }}
                    </Link>
                    <Link href="/platform/master-catalog/create" class="btn btn-outline-light fw-semibold">
                        {{ t('platform.master_add_btn') }}
                    </Link>
                </div>
            </div>
        </section>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-tile h-100">
                    <div class="stat-tile__label">{{ t('platform.master_stat_total') }}</div>
                    <div class="stat-tile__value">{{ stats.total }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-tile h-100">
                    <div class="stat-tile__label">{{ t('platform.master_stat_active') }}</div>
                    <div class="stat-tile__value text-success">{{ stats.active }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-tile h-100">
                    <div class="stat-tile__label">{{ t('platform.master_stat_inactive') }}</div>
                    <div class="stat-tile__value text-secondary">{{ stats.inactive }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-tile h-100">
                    <div class="stat-tile__label">{{ t('platform.master_stat_makers') }}</div>
                    <div class="stat-tile__value">{{ stats.manufacturers }}</div>
                </div>
            </div>
        </div>

        <form class="master-filters card border-0 shadow-sm mb-3" @submit.prevent="applyFilters">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small mb-1">{{ t('platform.master_search') }}</label>
                        <input
                            v-model="local.q"
                            type="search"
                            class="form-control"
                            :placeholder="t('platform.master_search_placeholder')"
                            autocomplete="off"
                        />
                    </div>
                    <div class="col-sm-6 col-md-2">
                        <label class="form-label small mb-1">{{ t('platform.master_status') }}</label>
                        <select v-model="local.status" class="form-select">
                            <option value="all">{{ t('platform.master_status_all') }}</option>
                            <option value="active">{{ t('common.active') }}</option>
                            <option value="inactive">{{ t('common.inactive') }}</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <label class="form-label small mb-1">{{ t('catalog.product_type') }}</label>
                        <select v-model="local.product_type" class="form-select">
                            <option value="">{{ t('platform.master_type_all') }}</option>
                            <option v-for="type in productTypes" :key="type" :value="type">{{ typeLabel(type) }}</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">{{ t('common.search') }}</button>
                        <button type="button" class="btn btn-outline-secondary" @click="clearFilters">{{ t('platform.master_clear') }}</button>
                    </div>
                </div>
            </div>
        </form>

        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 master-table">
                    <thead>
                        <tr>
                            <th>{{ t('catalog.product_name') }}</th>
                            <th class="d-none d-md-table-cell">{{ t('platform.master_generic') }}</th>
                            <th class="d-none d-lg-table-cell">{{ t('platform.master_manufacturer') }}</th>
                            <th class="text-end">{{ t('platform.master_mrp') }}</th>
                            <th>{{ t('catalog.status') }}</th>
                            <th class="text-end">{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in products.data" :key="item.id">
                            <td>
                                <div class="fw-semibold">{{ item.name }}</div>
                                <div class="small text-muted">
                                    <span v-if="item.strength">{{ item.strength }}</span>
                                    <span v-if="item.strength && item.product_type"> · </span>
                                    <span>{{ typeLabel(item.product_type) }}</span>
                                    <span v-if="item.sku"> · <code class="small">{{ item.sku }}</code></span>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell">
                                <div>{{ item.generic_name || '—' }}</div>
                                <div v-if="item.drug_class" class="small text-muted">{{ item.drug_class }}</div>
                            </td>
                            <td class="d-none d-lg-table-cell">{{ item.manufacturer_name || '—' }}</td>
                            <td class="text-end fw-medium">{{ formatMoney(item.mrp) }}</td>
                            <td>
                                <button
                                    type="button"
                                    class="badge rounded-pill border status-toggle"
                                    :class="item.is_active
                                        ? 'text-bg-success-subtle text-success border-success-subtle'
                                        : 'text-bg-secondary-subtle text-secondary'"
                                    :title="item.is_active ? t('platform.master_deactivate') : t('platform.master_activate')"
                                    :disabled="togglingId === item.id"
                                    @click="toggleActive(item)"
                                >
                                    {{ item.is_active ? t('common.active') : t('common.inactive') }}
                                </button>
                            </td>
                            <td class="text-end text-nowrap">
                                <Link :href="`/platform/master-catalog/${item.id}/edit`" class="btn btn-sm btn-outline-primary me-1">
                                    {{ t('common.edit') }}
                                </Link>
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(item)">
                                    {{ t('common.delete') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!products.data?.length">
                            <td colspan="6" class="text-center py-5">
                                <div class="empty-state mx-auto">
                                    <div class="empty-state__icon mb-3">℞</div>
                                    <h2 class="h6 mb-1">{{ t('platform.master_empty_title') }}</h2>
                                    <p class="small text-muted mb-3">{{ t('platform.master_empty_sub') }}</p>
                                    <Link href="/platform/master-catalog/import" class="btn btn-primary btn-sm">
                                        {{ t('platform.master_import_btn') }}
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="products.last_page > 1" class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="small text-muted">
                    {{ t('platform.master_showing', { from: String(products.from ?? 0), to: String(products.to ?? 0), total: String(products.total ?? 0) }) }}
                </span>
                <div class="d-flex gap-1">
                    <Link
                        v-if="products.prev_page_url"
                        :href="products.prev_page_url"
                        class="btn btn-sm btn-outline-secondary"
                        preserve-scroll
                    >
                        ←
                    </Link>
                    <Link
                        v-if="products.next_page_url"
                        :href="products.next_page_url"
                        class="btn btn-sm btn-outline-secondary"
                        preserve-scroll
                    >
                        →
                    </Link>
                </div>
            </div>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
    productTypes: { type: Array, default: () => [] },
    pendingSuggestions: { type: Number, default: 0 },
});

const { t } = useLocale();
const { formatMoney } = useMoney();
const togglingId = ref(null);

const local = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? 'all',
    product_type: props.filters.product_type ?? '',
});

function typeLabel(type) {
    return t(`catalog.types.${type}`, type);
}

function applyFilters() {
    router.get('/platform/master-catalog', {
        q: local.q || undefined,
        status: local.status !== 'all' ? local.status : undefined,
        product_type: local.product_type || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    local.q = '';
    local.status = 'all';
    local.product_type = '';
    applyFilters();
}

function toggleActive(item) {
    if (togglingId.value) {
        return;
    }
    togglingId.value = item.id;
    // Optimistic UI so the badge flips immediately on click.
    item.is_active = !item.is_active;
    router.post(`/platform/master-catalog/${item.id}/toggle-active`, {}, {
        preserveScroll: true,
        onError: () => {
            item.is_active = !item.is_active;
        },
        onFinish: () => {
            togglingId.value = null;
        },
    });
}

function remove(item) {
    if (!window.confirm(t('platform.master_delete_confirm', { name: item.name }))) {
        return;
    }
    router.delete(`/platform/master-catalog/${item.id}`);
}
</script>

<style scoped>
.master-hero {
    background:
        radial-gradient(1200px 400px at 10% -20%, rgba(255, 255, 255, 0.18), transparent 55%),
        linear-gradient(135deg, #0f766e 0%, #115e59 45%, #134e4a 100%);
    box-shadow: 0 18px 40px rgba(15, 118, 110, 0.22);
}

.letter-space {
    letter-spacing: 0.08em;
}

.stat-tile {
    background: #fff;
    border-radius: 1rem;
    padding: 1rem 1.1rem;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 24px rgba(15, 23, 42, 0.04);
    border: 1px solid rgba(15, 23, 42, 0.04);
}

.stat-tile__label {
    font-size: 0.78rem;
    color: #64748b;
    margin-bottom: 0.25rem;
}

.stat-tile__value {
    font-size: 1.65rem;
    font-weight: 700;
    line-height: 1.1;
    color: #0f172a;
}

.master-table thead th {
    background: #f8fafc;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    border-bottom-width: 1px;
}

.empty-state {
    max-width: 22rem;
}

.empty-state__icon {
    width: 3.25rem;
    height: 3.25rem;
    margin-inline: auto;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background: #ecfdf5;
    color: #0f766e;
    font-size: 1.25rem;
    font-weight: 700;
}

.status-toggle {
    cursor: pointer;
    padding: 0.4em 0.75em;
    font-weight: 600;
    transition: transform 0.12s ease, box-shadow 0.12s ease, opacity 0.12s ease;
}

.status-toggle:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}

.status-toggle:disabled {
    opacity: 0.65;
    cursor: wait;
}
</style>
