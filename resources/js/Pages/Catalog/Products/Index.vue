<template>
    <TenantShellLayout :page-title="t('tenant_nav.products')">
        <Head :title="t('tenant_nav.products')" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h1 class="h4 mb-0 d-lg-none">{{ t('tenant_nav.products') }}</h1>
            <div class="d-flex flex-wrap gap-2">
                <Link v-if="can('products.view')" href="/catalog/master" class="btn btn-outline-primary">{{ t('catalog.master_catalog_add_from') }}</Link>
                <Link v-if="can('products.manage')" href="/products/create" class="btn btn-primary">{{ t('catalog.add_product') }}</Link>
            </div>
        </div>
        <form class="card border-0 shadow-sm card-body mb-3 product-filter-card" @submit.prevent="applyFilters">
            <div class="product-filters">
                <div class="product-filter-field product-filter-field--search">
                    <label class="form-label small mb-0">{{ t('common.search') }}</label>
                    <input
                        v-model="filterForm.q"
                        type="search"
                        class="form-control form-control-sm"
                        :placeholder="t('catalog.products_search_placeholder')"
                        @input="debouncedApplyFilters"
                    />
                </div>
                <div class="product-filter-field">
                    <label class="form-label small mb-0">{{ t('catalog.product_type') }}</label>
                    <select v-model="filterForm.product_type" class="form-select form-select-sm">
                        <option value="">{{ t('catalog.all_product_types') }}</option>
                        <option v-for="pt in productTypes" :key="pt" :value="pt">{{ labelForType(pt) }}</option>
                    </select>
                </div>
                <div class="product-filter-field">
                    <label class="form-label small mb-0">{{ t('catalog.storage_location_shelf') }}</label>
                    <select v-model="filterForm.storage_location_id" class="form-select form-select-sm">
                        <option value="">{{ t('catalog.storage_location_all') }}</option>
                        <option v-for="loc in storageLocations" :key="loc.id" :value="String(loc.id)">
                            {{ loc.code ? `${loc.name} (${loc.code})` : loc.name }}
                        </option>
                    </select>
                </div>
                <div class="product-filter-field">
                    <label class="form-label small mb-0">{{ t('catalog.status') }}</label>
                    <select v-model="filterForm.is_active" class="form-select form-select-sm">
                        <option value="">{{ t('reports.all') }}</option>
                        <option value="1">{{ t('common.active') }}</option>
                        <option value="0">{{ t('common.inactive') }}</option>
                    </select>
                </div>
                <div class="product-filter-actions">
                    <button type="submit" class="btn btn-sm btn-primary" :disabled="filterLoading">
                        {{ filterLoading ? t('common.searching') : t('purchases.filter') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" :disabled="filterLoading" @click="clearFilters">{{ t('purchases.reset') }}</button>
                </div>
            </div>
        </form>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <p class="small text-muted mb-0">
                <span>{{ resultsSummary }}</span>
                <span v-if="filterLoading" class="ms-2 text-primary">{{ t('common.searching') }}</span>
            </p>
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
                        :class="viewMode === 'dense' ? 'btn-primary' : 'btn-outline-secondary'"
                        @click="setViewMode('dense')"
                    >
                        {{ t('catalog.products_view_dense') }}
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
        <div v-if="viewMode === 'table'">
            <div class="product-mobile-list d-md-none">
                <div v-if="!products.data?.length" class="card border-0 shadow-sm card-body text-muted text-center py-4">
                    {{ t('catalog.products_showing_none') }}
                </div>
                <template v-else>
                    <div v-for="p in products.data" :key="p.id" class="card border-0 shadow-sm mb-2 product-mobile-card">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="min-w-0">
                                    <Link :href="`/products/${p.id}`" class="fw-semibold text-decoration-none product-mobile-card__title">
                                        {{ p.name }}
                                    </Link>
                                    <div class="small text-muted text-truncate">
                                        <span v-if="p.generic_name">{{ p.generic_name }}</span>
                                        <span v-if="p.generic_name && p.sku"> · </span>
                                        <span>{{ p.sku }}</span>
                                    </div>
                                </div>
                                <span class="badge flex-shrink-0" :class="p.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                    {{ p.is_active ? t('common.active') : t('common.inactive') }}
                                </span>
                            </div>

                            <div class="product-mobile-card__meta mb-2">
                                <div>
                                    <span class="text-muted">{{ t('catalog.product_type') }}</span>
                                    <span>
                                        <ProductTypeLabel
                                            v-if="p.product_type"
                                            :type="p.product_type"
                                            :icon-url="p.product_type_icon_url"
                                            size="sm"
                                        />
                                        <span v-else class="text-muted">—</span>
                                    </span>
                                </div>
                                <div>
                                    <span class="text-muted">{{ t('catalog.storage_location_shelf') }}</span>
                                    <span>{{ shelfLabel(p) }}</span>
                                </div>
                                <div>
                                    <span class="text-muted">{{ t('catalog.sale_price') }}</span>
                                    <strong>{{ formatMoney(p.sale_price) }}</strong>
                                </div>
                                <div>
                                    <span class="text-muted">{{ t('catalog.current_stock') }}</span>
                                    <strong>
                                        {{ formatQty(p.stock_on_hand) }}
                                        <span class="fw-normal text-muted">{{ unitLabel(p.base_unit || p.unit) }}</span>
                                    </strong>
                                </div>
                            </div>

                            <ProductRowActions
                                :product="p"
                                :can-manage="can('products.manage')"
                                :can-sell="can('pos.access')"
                                compact
                                @delete="confirmDelete"
                            />
                        </div>
                    </div>
                </template>
            </div>

            <div class="table-responsive card border-0 shadow-sm d-none d-md-block">
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
                            <ProductRowActions :product="p" :can-manage="can('products.manage')" :can-sell="can('pos.access')" @delete="confirmDelete" />
                        </td>
                    </tr>
                    <tr v-if="!products.data?.length">
                        <td colspan="13" class="text-muted text-center py-4">{{ t('catalog.products_showing_none') }}</td>
                    </tr>
                </tbody>
                </table>
            </div>
        </div>

        <!-- Grid view (4 across on xl) -->
        <div v-else-if="viewMode === 'grid'">
            <div v-if="!products.data?.length" class="card border-0 shadow-sm card-body text-muted text-center py-4">
                {{ t('catalog.products_showing_none') }}
            </div>
            <div v-else class="row row-cols-2 row-cols-lg-3 row-cols-xl-4 g-2 g-md-3 product-grid">
                <div v-for="p in products.data" :key="p.id" class="col">
                    <div class="card border-0 shadow-sm h-100 product-grid-card">
                        <div class="ratio ratio-4x3 border-bottom product-card-image-wrap">
                            <img
                                :src="cardImage(p)"
                                :alt="p.name"
                                class="product-card-image"
                                :class="{ 'product-card-image--placeholder': !p.image_url }"
                            />
                        </div>
                        <div class="card-body d-flex flex-column product-grid-card__body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <Link :href="`/products/${p.id}`" class="text-decoration-none fw-semibold stretched-link">
                                    {{ p.name }}
                                </Link>
                                <span class="badge flex-shrink-0" :class="p.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                    {{ p.is_active ? t('common.active') : t('common.inactive') }}
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
                                    <Link v-if="can('pos.access')" :href="`/pos?barcode=${encodeURIComponent(p.barcode || p.sku || '')}`" class="btn btn-sm btn-outline-success">
                                        {{ t('tenant_nav.new_sale') }}
                                    </Link>
                                    <Link :href="`/products/${p.id}`" class="btn btn-sm btn-outline-primary">{{ t('common.view') }}</Link>
                                    <Link v-if="can('products.manage')" :href="`/products/${p.id}/edit`" class="btn btn-sm btn-outline-secondary">{{ t('common.edit') }}</Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dense grid view (up to 8 across) -->
        <div v-else-if="viewMode === 'dense'">
            <div v-if="!products.data?.length" class="card border-0 shadow-sm card-body text-muted text-center py-4">
                {{ t('catalog.products_showing_none') }}
            </div>
            <div v-else class="row row-cols-2 row-cols-md-4 row-cols-xl-6 g-2 product-grid product-grid--dense">
                <div v-for="p in products.data" :key="p.id" class="col">
                    <div class="card border-0 shadow-sm h-100 product-grid-card product-grid-card--dense">
                        <div class="ratio ratio-1x1 border-bottom product-card-image-wrap">
                            <img
                                :src="cardImage(p)"
                                :alt="p.name"
                                class="product-card-image"
                                :class="{ 'product-card-image--placeholder': !p.image_url }"
                            />
                        </div>
                        <div class="card-body product-grid-card__body product-grid-card__body--dense">
                            <Link :href="`/products/${p.id}`" class="text-decoration-none fw-semibold stretched-link product-dense-title">
                                {{ p.name }}
                            </Link>
                            <div class="d-flex justify-content-between align-items-baseline gap-1 mt-1">
                                <span class="text-primary fw-semibold product-dense-price">{{ formatMoney(p.sale_price) }}</span>
                                <span
                                    class="badge product-dense-badge"
                                    :class="p.is_active ? 'text-bg-success' : 'text-bg-secondary'"
                                >
                                    {{ p.is_active ? t('common.active') : t('common.inactive') }}
                                </span>
                            </div>
                            <div class="small text-muted mt-1 product-dense-meta">
                                {{ formatQty(p.stock_on_hand) }} {{ unitLabel(p.base_unit || p.unit) }}
                            </div>
                            <div v-if="can('pos.access')" class="mt-2">
                                <Link
                                    :href="`/pos?barcode=${encodeURIComponent(p.barcode || p.sku || '')}`"
                                    class="btn btn-sm btn-outline-success w-100"
                                >
                                    {{ t('tenant_nav.new_sale') }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compact view -->
        <div v-else>
            <div class="product-compact-mobile-list d-md-none">
                <div v-if="!products.data?.length" class="card border-0 shadow-sm card-body text-muted text-center py-4">
                    {{ t('catalog.products_showing_none') }}
                </div>
                <template v-else>
                    <div v-for="p in products.data" :key="p.id" class="card border-0 shadow-sm mb-2 product-compact-mobile-card">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="min-w-0">
                                    <Link :href="`/products/${p.id}`" class="fw-semibold text-decoration-none product-compact-mobile-card__title">
                                        {{ p.name }}
                                    </Link>
                                    <div class="small text-muted text-truncate">
                                        <span>{{ p.sku }}</span>
                                        <span v-if="p.generic_name"> · {{ p.generic_name }}</span>
                                    </div>
                                </div>
                                <span class="badge flex-shrink-0" :class="p.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                    {{ p.is_active ? t('common.active') : t('common.inactive') }}
                                </span>
                            </div>

                            <div class="product-compact-mobile-card__stats my-2">
                                <div>
                                    <span class="text-muted">{{ t('catalog.sale_price') }}</span>
                                    <strong>{{ formatMoney(p.sale_price) }}</strong>
                                </div>
                                <div>
                                    <span class="text-muted">{{ t('catalog.current_stock') }}</span>
                                    <strong>{{ formatQty(p.stock_on_hand) }} <span class="fw-normal">{{ unitLabel(p.base_unit || p.unit) }}</span></strong>
                                </div>
                            </div>

                            <ProductRowActions
                                :product="p"
                                :can-manage="can('products.manage')"
                                :can-sell="can('pos.access')"
                                compact
                                @delete="confirmDelete"
                            />
                        </div>
                    </div>
                </template>
            </div>

            <div class="table-responsive card border-0 shadow-sm d-none d-md-block">
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
                            <ProductRowActions
                                :product="p"
                                :can-manage="can('products.manage')"
                                :can-sell="can('pos.access')"
                                compact
                                @delete="confirmDelete"
                            />
                        </td>
                    </tr>
                    <tr v-if="!products.data?.length">
                        <td colspan="6" class="text-muted text-center py-4">{{ t('catalog.products_showing_none') }}</td>
                    </tr>
                </tbody>
                </table>
            </div>
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
import { computed, defineComponent, h, onBeforeUnmount, reactive, ref } from 'vue';

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
const filterLoading = ref(false);
let filterTimer;

const ProductRowActions = defineComponent({
    name: 'ProductRowActions',
    props: {
        product: { type: Object, required: true },
        canManage: { type: Boolean, default: false },
        canSell: { type: Boolean, default: false },
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
                props.canSell
                    ? h(
                          Link,
                          {
                              href: `/pos?barcode=${encodeURIComponent(props.product.barcode || props.product.sku || '')}`,
                              class: `btn btn-sm btn-outline-success${props.compact ? '' : ' me-1'}`,
                          },
                          () => t('tenant_nav.new_sale'),
                      )
                    : null,
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
        if (saved === 'table' || saved === 'grid' || saved === 'dense' || saved === 'compact') {
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

function debouncedApplyFilters() {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => applyFilters(), 350);
}

function applyFilters() {
    clearTimeout(filterTimer);
    router.get('/products', { ...filterForm }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['products', 'filters'],
        onStart: () => {
            filterLoading.value = true;
        },
        onFinish: () => {
            filterLoading.value = false;
        },
    });
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

onBeforeUnmount(() => {
    clearTimeout(filterTimer);
});
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

.product-grid-card {
    overflow: hidden;
}

.product-grid-card--dense .product-card-image--placeholder {
    padding: 0.4rem;
}

.product-grid-card__body--dense {
    padding: 0.5rem 0.55rem 0.6rem;
}

.product-dense-title {
    display: -webkit-box;
    overflow: hidden;
    font-size: 0.78rem;
    line-height: 1.2;
    color: var(--bs-body-color);
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.product-dense-price {
    font-size: 0.82rem;
}

.product-dense-badge {
    font-size: 0.58rem;
    padding: 0.2em 0.4em;
}

.product-dense-meta {
    font-size: 0.68rem;
    line-height: 1.2;
}

@media (min-width: 1400px) {
    .product-grid--dense > .col {
        flex: 0 0 auto;
        width: 12.5%;
    }
}

.product-mobile-card,
.product-compact-mobile-card {
    overflow: hidden;
}

.product-mobile-card__title,
.product-compact-mobile-card__title {
    display: -webkit-box;
    overflow: hidden;
    color: var(--bs-body-color);
    line-height: 1.25;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
}

.product-mobile-card__meta,
.product-compact-mobile-card__stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.45rem;
    font-size: 0.82rem;
}

.product-mobile-card__meta > div,
.product-compact-mobile-card__stats > div {
    display: flex;
    min-width: 0;
    flex-direction: column;
    padding: 0.45rem;
    background: var(--bs-tertiary-bg);
    border-radius: 0.4rem;
}

.product-mobile-card :deep(.btn),
.product-compact-mobile-card :deep(.btn) {
    min-height: 2rem;
    padding: 0.22rem 0.45rem;
    font-size: 0.75rem;
}

.table-responsive table {
    min-width: 860px;
}

.product-filters {
    display: grid;
    gap: 0.5rem;
    align-items: end;
    /* Responsive: 2 lines — search on top, filters + actions below */
    grid-template-columns: repeat(4, minmax(0, 1fr));
}

.product-filter-field--search {
    grid-column: 1 / -1;
}

.product-filter-actions {
    display: flex;
    gap: 0.35rem;
    grid-column: 4 / 5;
}

.product-filter-actions .btn {
    flex: 1 1 0;
    min-width: 0;
    padding-right: 0.4rem;
    padding-left: 0.4rem;
}

@media (max-width: 575.98px) {
    .product-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .product-filter-actions {
        grid-column: 1 / -1;
    }
}

/* Desktop: all filters on one line */
@media (min-width: 992px) {
    .product-filters {
        grid-template-columns:
            minmax(14rem, 1.8fr)
            minmax(8rem, 0.85fr)
            minmax(8rem, 0.85fr)
            minmax(7rem, 0.7fr)
            auto;
    }

    .product-filter-field--search,
    .product-filter-actions {
        grid-column: auto;
    }

    .product-filter-actions .btn {
        flex: 0 0 auto;
        white-space: nowrap;
        padding-right: 0.65rem;
        padding-left: 0.65rem;
    }
}

@media (max-width: 575.98px) {
    .product-filter-card {
        padding: 0.75rem !important;
    }

    .product-filter-field .form-label {
        max-width: 100%;
        overflow: hidden;
        font-size: 0.72rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .product-filter-field .form-control,
    .product-filter-field .form-select,
    .product-filter-actions .btn {
        min-height: 2.15rem;
        font-size: 0.8rem;
    }

    .product-filter-actions .btn {
        padding-right: 0.35rem;
        padding-left: 0.35rem;
    }

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

    .product-grid {
        --bs-gutter-x: 0.5rem;
        --bs-gutter-y: 0.5rem;
    }

    .product-grid-card .ratio {
        --bs-aspect-ratio: 72%;
    }

    .product-grid-card__body {
        padding: 0.65rem;
    }

    .product-grid-card__body .badge {
        font-size: 0.62rem;
    }

    .product-grid-card__body a.fw-semibold {
        display: -webkit-box;
        overflow: hidden;
        font-size: 0.86rem;
        line-height: 1.2;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .product-grid-card__body .small {
        font-size: 0.72rem;
    }

    .product-grid-card__body .h6 {
        font-size: 0.9rem;
    }

    .product-grid-card__body .btn {
        flex: 1 1 auto;
        min-height: 1.95rem;
        padding: 0.2rem 0.35rem;
        font-size: 0.72rem;
    }
}
</style>
