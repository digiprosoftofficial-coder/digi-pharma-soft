<template>
    <TenantShellLayout :page-title="isEdit ? 'Edit package template' : 'New package template'">
        <Head :title="isEdit ? 'Edit package template' : 'New package template'" />
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">{{ isEdit ? 'Edit package template' : 'New package template' }}</h1>
                <p class="text-muted small mb-0">Choose products, units, and quantities that should be added together during package sale.</p>
            </div>
            <Link href="/sales/packages" class="btn btn-sm btn-outline-secondary">Back to packages</Link>
        </div>

        <form class="row g-3" @submit.prevent="submit">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm card-body mb-3 package-details-card">
                    <div class="mb-3 package-form-field">
                        <label class="form-label">Package name</label>
                        <input v-model="form.name" class="form-control" :class="{ 'is-invalid': form.errors.name }" required />
                        <div v-if="form.errors.name" class="invalid-feedback d-block">{{ form.errors.name }}</div>
                    </div>
                    <div class="mb-3 package-form-field">
                        <label class="form-label">Description</label>
                        <textarea v-model="form.description" rows="3" class="form-control package-description-input"></textarea>
                    </div>
                    <div class="package-pricing-grid">
                        <div class="package-form-field">
                            <label class="form-label">Discount %</label>
                            <input v-model.number="form.discount_percent" type="number" min="0" max="100" step="0.01" class="form-control" />
                            <div class="form-text">Optional. Applied when this package is selected.</div>
                        </div>
                        <div class="package-form-field">
                            <label class="form-label">Fixed price</label>
                            <input v-model.number="form.fixed_price" type="number" min="0" step="0.0001" class="form-control" />
                            <div class="form-text">Optional. Overrides discount by matching the cart to this price.</div>
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input id="packageActive" v-model="form.is_active" type="checkbox" class="form-check-input" />
                        <label class="form-check-label" for="packageActive">Active for package sale</label>
                    </div>
                </div>

                <div class="card border-0 shadow-sm card-body package-search-card">
                    <label class="form-label">Add product</label>
                    <input
                        v-model="q"
                        type="search"
                        class="form-control"
                        placeholder="Search by name, SKU, or barcode"
                        autocomplete="off"
                        @input="debouncedSearch"
                    />
                    <ul v-if="results.length" class="list-group mt-2 small">
                        <li
                            v-for="item in results"
                            :key="item.id"
                            class="list-group-item list-group-item-action d-flex justify-content-between gap-2"
                            @click="addItem(item)"
                        >
                            <span class="min-w-0">
                                <span class="fw-semibold d-block text-truncate">{{ item.name }}</span>
                                <span class="text-muted">{{ item.sku || item.generic_name }}</span>
                            </span>
                            <span class="text-muted">{{ formatMoney(item.sale_price) }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm card-body">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <h2 class="h6 mb-0">Package items</h2>
                        <span class="small text-muted">{{ form.items.length }} items</span>
                    </div>
                    <div v-if="form.errors.items" class="alert alert-danger small">{{ form.errors.items }}</div>
                    <p v-if="!form.items.length" class="text-muted small mb-0">Search and add products to prepare this package.</p>
                    <div v-else class="package-item-list">
                        <div v-for="(item, index) in form.items" :key="`${item.product_id}-${index}`" class="package-item-card border rounded p-2 mb-2">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div class="min-w-0">
                                    <strong class="d-block text-truncate">{{ item.product?.name }}</strong>
                                    <span class="small text-muted">{{ item.product?.sku }}</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" @click="form.items.splice(index, 1)">×</button>
                            </div>
                            <div class="package-item-card__price-summary mb-2">
                                <div>
                                    <span class="text-muted">Default price</span>
                                    <strong>{{ formatMoney(itemDefaultPrice(item)) }}</strong>
                                </div>
                                <div>
                                    <span class="text-muted">Line total</span>
                                    <strong>{{ formatMoney(itemLineTotal(item)) }}</strong>
                                </div>
                            </div>
                            <div class="package-item-card__fields">
                                <div>
                                    <label class="form-label small mb-1">Unit</label>
                                    <select v-model="item.sell_unit" class="form-select form-select-sm">
                                        <option v-for="unit in itemUnitOptions(item)" :key="unit.sell_unit" :value="unit.sell_unit">
                                            {{ unit.sell_unit }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors[`items.${index}.sell_unit`]" class="text-danger small mt-1">
                                        {{ form.errors[`items.${index}.sell_unit`] }}
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label small mb-1">Qty</label>
                                    <input v-model.number="item.quantity" type="number" min="0.0001" step="0.0001" class="form-control form-control-sm" />
                                </div>
                                <div>
                                    <label class="form-label small mb-1">Price override</label>
                                    <input
                                        v-model.number="item.unit_price_override"
                                        type="number"
                                        min="0"
                                        step="0.0001"
                                        class="form-control form-control-sm"
                                        :placeholder="formatPricePlaceholder(item)"
                                    />
                                    <div class="form-text small mb-0">Blank = {{ formatMoney(itemDefaultPrice(item)) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-top pt-3 mt-2">
                        <div class="small text-muted">Estimated base total: <strong>{{ formatMoney(estimatedTotal) }}</strong></div>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing || !form.items.length">
                            {{ form.processing ? 'Saving...' : 'Save package' }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useMoney } from '@/composables/useMoney';
import { defaultSellUnit, unitSalePrice } from '@/composables/useProductUnits';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    template: { type: Object, default: null },
});

const isEdit = computed(() => Boolean(props.template?.id));
const { formatMoney } = useMoney();
const q = ref('');
const results = ref([]);
let timer;

const form = useForm({
    name: props.template?.name ?? '',
    description: props.template?.description ?? '',
    is_active: props.template?.is_active ?? true,
    discount_percent: props.template?.discount_percent ?? null,
    fixed_price: props.template?.fixed_price ?? null,
    items: (props.template?.items ?? []).map((item) => ({
        product_id: item.product_id,
        product: item.product,
        sell_unit: item.sell_unit,
        quantity: Number(item.quantity || 1),
        unit_price_override: item.unit_price_override ?? null,
    })),
});

const estimatedTotal = computed(() =>
    form.items.reduce((sum, item) => {
        return sum + itemLineTotal(item);
    }, 0),
);

function debouncedSearch() {
    clearTimeout(timer);
    timer = setTimeout(runSearch, 250);
}

async function runSearch() {
    if (q.value.length < 1) {
        results.value = [];
        return;
    }

    const { data } = await window.axios.get('/catalog/product-search', { params: { q: q.value } });
    results.value = data.data;
}

function itemUnitOptions(item) {
    if (item.product?.units?.length) {
        return item.product.units;
    }

    const sellUnit = defaultSellUnit(item.product ?? {});
    return [{ sell_unit: sellUnit, sale_price: item.product?.sale_price ?? 0 }];
}

function addItem(product) {
    const sellUnit = defaultSellUnit(product);
    form.items.push({
        product_id: product.id,
        product,
        sell_unit: sellUnit,
        quantity: 1,
        unit_price_override: null,
    });
    q.value = '';
    results.value = [];
}

function itemDefaultPrice(item) {
    return Number(unitSalePrice(item.product, item.sell_unit) || 0);
}

function itemEffectivePrice(item) {
    return item.unit_price_override !== null && item.unit_price_override !== ''
        ? Number(item.unit_price_override)
        : itemDefaultPrice(item);
}

function itemLineTotal(item) {
    return Number(item.quantity || 0) * Number(itemEffectivePrice(item) || 0);
}

function formatPricePlaceholder(item) {
    return String(itemDefaultPrice(item) || '');
}

function submit() {
    const url = isEdit.value ? `/sales/packages/${props.template.id}` : '/sales/packages';
    const options = { preserveScroll: true };

    if (isEdit.value) {
        form.put(url, options);
        return;
    }

    form.post(url, options);
}
</script>

<style scoped>
.package-pricing-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}

.package-item-card__fields {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1fr);
    gap: 0.5rem;
}

.package-item-card__price-summary {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}

.package-item-card__price-summary > div {
    align-items: center;
    background: #f8f9fa;
    border: 1px solid #eef0f2;
    border-radius: 0.5rem;
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0.45rem 0.6rem;
}

@media (max-width: 575.98px) {
    .package-details-card,
    .package-search-card {
        padding: 0.85rem;
    }

    .package-details-card .mb-3,
    .package-search-card .mb-3 {
        margin-bottom: 0.75rem !important;
    }

    .package-form-field .form-label,
    .package-search-card .form-label {
        font-size: 0.82rem;
        font-weight: 600;
        margin-bottom: 0.3rem;
    }

    .package-details-card .form-control,
    .package-search-card .form-control {
        font-size: 0.9rem;
        min-height: 2.15rem;
        padding: 0.42rem 0.6rem;
    }

    .package-description-input {
        min-height: 4.5rem !important;
    }

    .package-pricing-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .package-pricing-grid .form-text {
        font-size: 0.7rem;
        line-height: 1.25;
        margin-top: 0.25rem;
    }

    .package-details-card .form-check {
        align-items: center;
        display: flex;
        gap: 0.35rem;
        margin-top: 0.75rem !important;
    }

    .package-details-card .form-check-input {
        flex: 0 0 auto;
        margin-top: 0;
    }

    .package-details-card .form-check-label {
        font-size: 0.88rem;
    }

    .package-item-card__fields {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.45rem;
    }

    .package-item-card__fields > div:nth-child(3) {
        grid-column: 1 / -1;
    }

    .package-item-card__price-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.4rem;
    }

    .package-item-card__price-summary > div {
        align-items: flex-start;
        flex-direction: column;
        font-size: 0.9rem;
        gap: 0.1rem;
        padding: 0.4rem 0.5rem;
    }

    .package-item-card {
        padding: 0.65rem !important;
    }

    .package-item-card .mb-2 {
        margin-bottom: 0.45rem !important;
    }

    .package-item-card .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        margin-bottom: 0.2rem !important;
    }

    .package-item-card .form-control,
    .package-item-card .form-select {
        font-size: 0.86rem;
        min-height: 2.1rem;
        padding: 0.35rem 0.5rem;
    }

    .package-item-card .form-text {
        font-size: 0.72rem;
        line-height: 1.2;
        margin-top: 0.2rem;
    }

    .package-item-card strong {
        font-size: 0.92rem;
    }
}
</style>
