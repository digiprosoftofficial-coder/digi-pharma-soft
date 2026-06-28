<template>
    <TenantShellLayout :page-title="t('tenant_nav.products')">
        <Head :title="t('tenant_nav.products')" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h1 class="h4 mb-0 d-lg-none">{{ t('tenant_nav.products') }}</h1>
            <Link v-if="can('products.manage')" href="/products/create" class="btn btn-primary">{{ t('catalog.add_product') }}</Link>
        </div>
        <form class="card border-0 shadow-sm card-body mb-3" @submit.prevent="applyFilters">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-0">{{ t('common.search') }}</label>
                    <input v-model="filterForm.q" type="search" class="form-control form-control-sm" :placeholder="t('catalog.products_search_placeholder')" />
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">{{ t('catalog.product_type') }}</label>
                    <select v-model="filterForm.product_type" class="form-select form-select-sm">
                        <option value="">{{ t('catalog.all_product_types') }}</option>
                        <option v-for="pt in productTypes" :key="pt" :value="pt">{{ labelForType(pt) }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">{{ t('catalog.storage_location_shelf') }}</label>
                    <select v-model="filterForm.storage_location_id" class="form-select form-select-sm">
                        <option value="">{{ t('catalog.storage_location_all') }}</option>
                        <option v-for="loc in storageLocations" :key="loc.id" :value="String(loc.id)">
                            {{ loc.code ? `${loc.name} (${loc.code})` : loc.name }}
                        </option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-0">{{ t('catalog.status') }}</label>
                    <select v-model="filterForm.is_active" class="form-select form-select-sm">
                        <option value="">{{ t('reports.all') }}</option>
                        <option value="1">{{ t('common.active') }}</option>
                        <option value="0">{{ t('common.inactive') }}</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid d-sm-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-primary">{{ t('purchases.filter') }}</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="clearFilters">{{ t('purchases.reset') }}</button>
                </div>
            </div>
        </form>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <p class="small text-muted mb-0">{{ resultsSummary }}</p>
            <div class="product-toolbar d-flex flex-wrap align-items-center gap-2">
                <select
                    v-model="filterForm.category_id"
                    class="form-select form-select-sm product-category-filter"
                    style="width: auto; min-width: 11rem"
                    @change="applyFilters"
                >
                    <option value="">{{ t('catalog.products_all_categories') }}</option>
                    <option v-for="category in categories" :key="category.id" :value="String(category.id)">
                        {{ category.name }}
                    </option>
                </select>
                <div class="btn-group btn-group-sm product-view-toggle" role="group" :aria-label="t('catalog.products_view_mode')">
                    <button
                        type="button"
                        class="btn"
                        :class="viewMode === 'table' ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="setViewMode('table')"
                    >
                        {{ t('catalog.products_view_table') }}
                    </button>
                    <button
                        type="button"
                        class="btn"
                        :class="viewMode === 'grid' ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="setViewMode('grid')"
                    >
                        {{ t('catalog.products_view_grid') }}
                    </button>
                    <button
                        type="button"
                        class="btn"
                        :class="viewMode === 'compact' ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="setViewMode('compact')"
                    >
                        {{ t('catalog.products_view_compact') }}
                    </button>
                </div>
                <div class="product-per-page d-flex align-items-center gap-2">
                    <label class="small text-muted mb-0" for="per-page">{{ t('catalog.products_per_page') }}</label>
                    <select
                        id="per-page"
                        v-model.number="filterForm.per_page"
                        class="form-select form-select-sm"
                        style="width: auto"
                        @change="applyFilters"
                    >
                        <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table view -->
        <div v-if="viewMode === 'table'" class="table-responsive card border-0 shadow-sm">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>{{ t('catalog.product_name') }}</th>
                        <th>{{ t('catalog.generic_name') }}</th>
                        <th>{{ t('catalog.strength') }}</th>
                        <th>{{ t('catalog.product_type') }}</th>
                        <th>{{ t('catalog.category') }}</th>
                        <th>{{ t('catalog.storage_location_shelf') }}</th>
                        <th>SKU</th>
                        <th>{{ t('catalog.sell_unit') }}</th>
                        <th class="text-end">{{ t('catalog.sale_price') }} ({{ currencyCode() }})</th>
                        <th class="text-end">{{ t('catalog.current_stock') }}</th>
                        <th class="text-end">{{ t('reports.purchases') }}</th>
                        <th>{{ t('catalog.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in products.data" :key="p.id">
                        <td>
                            <Link :href="`/products/${p.id}`" class="text-decoration-none fw-medium">{{ p.name }}</Link>
                        </td>
                        <td class="text-muted small">{{ p.generic_name || '—' }}</td>
                        <td class="small">{{ p.strength || '—' }}</td>
                        <td>
                            <ProductTypeLabel
                                v-if="p.product_type"
                                :type="p.product_type"
                                :icon-url="p.product_type_icon_url"
                                size="sm"
                            />
                            <span v-else class="text-muted">—</span>
                        </td>
                        <td>{{ p.category?.name || '—' }}</td>
                        <td class="small">{{ shelfLabel(p) }}</td>
                        <td>{{ p.sku }}</td>
                        <td class="text-capitalize">{{ p.unit || p.base_unit }}</td>
                        <td class="text-end">{{ formatMoney(p.sale_price) }}</td>
                        <td class="text-end">
                            <span class="fw-semibold">{{ formatQty(p.stock_on_hand) }}</span>
                            <span class="text-muted small ms-1">{{ unitLabel(p.base_unit || p.unit) }}</span>
                            <div v-if="p.stock_pieces" class="small text-muted">{{ formatQty(p.stock_pieces) }} pcs</div>
                        </td>
                        <td class="text-end">
                            <span>{{ formatQty(p.purchased_quantity) }}</span>
                            <span class="text-muted small ms-1">{{ unitLabel(p.base_unit || p.unit) }}</span>
                        </td>
                        <td>
                            <span class="badge" :class="p.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                {{ p.is_active ? t('common.active') : t('common.inactive') }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <ProductRowActions :product="p" :can-manage="can('products.manage')" @delete="confirmDelete" />
                        </td>
                    </tr>
                    <tr v-if="!products.data?.length">
                        <td colspan="13" class="text-muted text-center py-4">{{ t('catalog.products_showing_none') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Grid view -->
        <div v-else-if="viewMode === 'grid'">
            <div v-if="!products.data?.length" class="card border-0 shadow-sm card-body text-muted text-center py-4">
                {{ t('catalog.products_showing_none') }}
            </div>
            <div v-else class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3">
                <div v-for="p in products.data" :key="p.id" class="col">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="ratio ratio-4x3 border-bottom product-card-image-wrap">
                            <img
                                :src="cardImage(p)"
                                :alt="p.name"
                                class="product-card-image"
                                :class="{ 'product-card-image--placeholder': !p.image_url }"
                            />
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <Link :href="`/products/${p.id}`" class="text-decoration-none fw-semibold stretched-link">
                                    {{ p.name }}
                                </Link>
                                <span class="badge flex-shrink-0" :class="p.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                    {{ p.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <p v-if="p.generic_name" class="small text-muted mb-1">{{ p.generic_name }}</p>
                            <p v-if="p.strength" class="small text-muted mb-2">{{ p.strength }}</p>
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-baseline mb-2">
                                    <span class="h6 mb-0 text-primary">{{ formatMoney(p.sale_price) }}</span>
                                    <span class="small text-muted">{{ p.sku }}</span>
                                </div>
                                <div class="small text-muted mb-2">
                                    <span class="fw-semibold text-body">{{ formatQty(p.stock_on_hand) }}</span>
                                    {{ unitLabel(p.base_unit || p.unit) }} {{ t('catalog.on_hand') }}
                                    <span v-if="p.stock_pieces"> · {{ formatQty(p.stock_pieces) }} pcs</span>
                                </div>
                                <div class="d-flex flex-wrap gap-1 position-relative" style="z-index: 2">
                                    <Link :href="`/products/${p.id}`" class="btn btn-sm btn-outline-primary">{{ t('common.view') }}</Link>
                                    <a :href="`/barcodes/${p.id}`" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">{{ t('catalog.barcode') }}</a>
                                    <Link v-if="can('products.manage')" :href="`/products/${p.id}/edit`" class="btn btn-sm btn-outline-secondary">{{ t('common.edit') }}</Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compact view -->
        <div v-else class="table-responsive card border-0 shadow-sm">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('catalog.product_name') }}</th>
                        <th>{{ t('catalog.product_type') }}</th>
                        <th class="text-end">{{ t('catalog.sale_price') }} ({{ currencyCode() }})</th>
                        <th class="text-end">{{ t('catalog.current_stock') }}</th>
                        <th>{{ t('catalog.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in products.data" :key="p.id">
                        <td>
                            <Link :href="`/products/${p.id}`" class="text-decoration-none fw-medium">{{ p.name }}</Link>
                            <div class="small text-muted">
                                <span v-if="p.generic_name">{{ p.generic_name }}</span>
                                <span v-if="p.generic_name && p.sku"> · </span>
                                <span>{{ p.sku }}</span>
                            </div>
                        </td>
                        <td>
                            <ProductTypeLabel
                                v-if="p.product_type"
                                :type="p.product_type"
                                :icon-url="p.product_type_icon_url"
                                size="sm"
                            />
                            <span v-else class="text-muted">—</span>
                        </td>
                        <td class="text-end">{{ formatMoney(p.sale_price) }}</td>
                        <td class="text-end">
                            {{ formatQty(p.stock_on_hand) }}
                            <span class="text-muted small">{{ unitLabel(p.base_unit || p.unit) }}</span>
                        </td>
                        <td>
                            <span class="badge" :class="p.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                {{ p.is_active ? t('common.active') : t('common.inactive') }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <ProductRowActions :product="p" :can-manage="can('products.manage')" compact @delete="confirmDelete" />
                        </td>
                    </tr>
                    <tr v-if="!products.data?.length">
                        <td colspan="6" class="text-muted text-center py-4">{{ t('catalog.products_showing_none') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav v-if="products.links?.length > 3" class="mt-3">
            <ul class="pagination">
                <li v-for="l in products.links" :key="l.label" class="page-item" :class="{ active: l.active, disabled: !l.url }">
                    <Link v-if="l.url" class="page-link" :href="l.url" v-html="l.label" />
                    <span v-else class="page-link" v-html="l.label" />
                </li>
            </ul>
        </nav>
    </TenantShellLayout>
</template>

<script setup>
import ProductTypeLabel from '@/Components/Catalog/ProductTypeLabel.vue';
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { productTypeLabel } from '@/composables/useProductType';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { useQuantity } from '@/composables/useQuantity';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, defineComponent, h, reactive, ref } from 'vue';

const VIEW_MODE_KEY = 'catalog.products.viewMode';
const PRODUCT_PLACEHOLDER_URL = '/images/product-placeholder.png';

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    productTypes: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    storageLocations: { type: Array, default: () => [] },
    perPageOptions: { type: Array, default: () => [15, 25, 50, 100] },
});

const { t } = useLocale();
const { formatMoney, currencyCode } = useMoney();
const { formatQty } = useQuantity();
const { can } = usePermissions();

const viewMode = ref(loadViewMode());

const ProductRowActions = defineComponent({
    name: 'ProductRowActions',
    props: {
        product: { type: Object, required: true },
        canManage: { type: Boolean, default: false },
        compact: { type: Boolean, default: false },
    },
    emits: ['delete'],
    setup(props, { emit }) {
        return () =>
            h('div', { class: 'd-inline-flex flex-wrap gap-1' }, [
                h(
                    Link,
                    { href: `/products/${props.product.id}`, class: `btn btn-sm btn-outline-primary${props.compact ? '' : ' me-1'}` },
                    () => t('common.view'),
                ),
                h(
                    'a',
                    {
                        href: `/barcodes/${props.product.id}`,
                        target: '_blank',
                        rel: 'noopener',
                        class: `btn btn-sm btn-outline-secondary${props.compact ? '' : ' me-1'}`,
                    },
                    t('catalog.barcode'),
                ),
                props.canManage
                    ? h(
                          Link,
                          {
                              href: `/products/${props.product.id}/edit`,
                              class: `btn btn-sm btn-outline-secondary${props.compact ? '' : ' me-1'}`,
                          },
                          () => t('common.edit'),
                      )
                    : null,
                props.canManage
                    ? h(
                          'button',
                          {
                              type: 'button',
                              class: 'btn btn-sm btn-outline-danger',
                              onClick: () => emit('delete', props.product),
                          },
                          t('common.delete'),
                      )
                    : null,
            ]);
    },
});

function loadViewMode() {
    try {
        const saved = localStorage.getItem(VIEW_MODE_KEY);
        if (saved === 'table' || saved === 'grid' || saved === 'compact') {
            return saved;
        }
    } catch {
        // ignore
    }
    if (typeof window !== 'undefined' && window.innerWidth < 768) {
        return 'grid';
    }
    return 'table';
}

function setViewMode(mode) {
    viewMode.value = mode;
    try {
        localStorage.setItem(VIEW_MODE_KEY, mode);
    } catch {
        // ignore
    }
}

function labelForType(slug) {
    return productTypeLabel(slug, t);
}

function unitLabel(unit) {
    if (!unit) {
        return '';
    }
    return unit.charAt(0).toUpperCase() + unit.slice(1);
}

function shelfLabel(product) {
    const loc = product.effective_storage_location ?? product.storage_location;

    if (!loc) {
        return '—';
    }

    return loc.code ? `${loc.name} (${loc.code})` : loc.name;
}

function cardImage(product) {
    return product.image_url || PRODUCT_PLACEHOLDER_URL;
}

const filterForm = reactive({
    q: props.filters.q ?? '',
    product_type: props.filters.product_type ?? '',
    category_id: props.filters.category_id ?? '',
    is_active: props.filters.is_active ?? '',
    storage_location_id: props.filters.storage_location_id ?? '',
    per_page: Number(props.filters.per_page) || 25,
});

const resultsSummary = computed(() => {
    const total = props.products.total ?? 0;
    if (total === 0) {
        return t('catalog.products_showing_none');
    }

    const from = props.products.from ?? 0;
    const to = props.products.to ?? 0;

    return t('catalog.products_showing_range', { from, to, total });
});

function applyFilters() {
    router.get('/products', { ...filterForm }, { preserveState: true, replace: true });
}

function clearFilters() {
    filterForm.q = '';
    filterForm.product_type = '';
    filterForm.category_id = '';
    filterForm.is_active = '';
    filterForm.storage_location_id = '';
    filterForm.per_page = 25;
    applyFilters();
}

function confirmDelete(product) {
    if (!window.confirm(`Delete product "${product.name}"? This cannot be undone.`)) {
        return;
    }
    router.delete(`/products/${product.id}`, { preserveScroll: true });
}
</script>

<style scoped>
.product-card-image-wrap {
    background: #111;
}

.product-card-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-card-image--placeholder {
    object-fit: contain;
    padding: 0.75rem;
}

.table-responsive table {
    min-width: 860px;
}

@media (max-width: 575.98px) {
    .product-toolbar,
    .product-category-filter,
    .product-view-toggle,
    .product-per-page {
        width: 100% !important;
    }

    .product-view-toggle .btn {
        flex: 1 1 0;
    }

    .product-per-page {
        justify-content: space-between;
    }
}
</style>
