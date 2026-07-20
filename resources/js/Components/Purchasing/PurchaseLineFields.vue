<template>
    <div class="purchase-line-fields">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small">Batch</label>
                <select
                    v-model="line.batch_pick"
                    class="form-select form-select-sm"
                    @change="$emit('batch-change', line)"
                >
                    <option value="__new__">+ New batch (from invoice)</option>
                    <option v-for="b in line.existing_batches" :key="b.id" :value="b.batch_no">
                        {{ b.batch_no }} · on hand {{ formatQty(b.quantity_on_hand) }}
                    </option>
                </select>
            </div>
            <div v-if="line.batch_pick === '__new__'" class="col-12 col-md-3">
                <label class="form-label small">New batch no</label>
                <input
                    v-model="line.batch_no"
                    type="text"
                    class="form-control form-control-sm"
                    :required="requireFields"
                    placeholder="e.g. LOT-2026-01"
                />
            </div>
            <div v-else class="col-12 col-md-3">
                <label class="form-label small">Batch no</label>
                <input :value="line.batch_no" type="text" class="form-control form-control-sm" disabled />
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">{{ t('purchases.expiry') }}</label>
                <input v-model="line.expiry_date" type="date" class="form-control form-control-sm" />
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">{{ t('purchases.manufactured_at') }}</label>
                <input v-model="line.manufactured_at" type="date" class="form-control form-control-sm" />
            </div>
            <div v-if="storageLocations.length" class="col-12 col-md-3">
                <label class="form-label small">{{ t('catalog.storage_location_shelf') }}</label>
                <select v-model="line.storage_location_id" class="form-select form-select-sm">
                    <option :value="null">{{ t('catalog.storage_location_use_default') }}</option>
                    <option v-for="loc in storageLocations" :key="loc.id" :value="loc.id">
                        {{ locationLabel(loc) }}
                    </option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">Unit</label>
                <select
                    v-model="line.sell_unit"
                    class="form-select form-select-sm"
                    @change="$emit('unit-change', line)"
                >
                    <option v-for="u in line.unit_options" :key="u.sell_unit" :value="u.sell_unit">
                        {{ unitLabel(u.sell_unit) }}
                    </option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">{{ t('purchases.qty') }}</label>
                <input
                    v-model.number="line.quantity"
                    type="number"
                    min="0.0001"
                    step="0.0001"
                    class="form-control form-control-sm"
                    :required="requireFields"
                />
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">{{ t('purchases.unit_cost') }} ({{ currencyCode() }})</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ currencySymbol() }}</span>
                    <input
                        v-model.number="line.unit_cost"
                        type="number"
                        min="0"
                        step="0.01"
                        class="form-control"
                        :required="requireFields"
                    />
                </div>
                <div v-if="priceComparisonLabel" class="form-text" :class="priceComparisonClass">
                    {{ priceComparisonLabel }}
                </div>
                <div v-if="Number(line.quantity) > 0 && Number(line.unit_cost) > 0" class="form-text">
                    = {{ formatMoney(Number(line.quantity) * Number(line.unit_cost)) }}
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small">{{ t('purchases.sale_price_mrp') }} ({{ currencyCode() }})</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ currencySymbol() }}</span>
                    <input v-model.number="line.sale_price" type="number" min="0" step="0.01" class="form-control" />
                </div>
            </div>
        </div>

        <div v-if="needsPackSize" class="row g-2 mt-1 pt-2 border-top">
            <div class="col-12 col-md-4">
                <label class="form-label small mb-0">{{ packSizeLabel }}</label>
                <input
                    v-if="usesStripsPerBox"
                    v-model.number="line.pack_strips_per_box"
                    type="number"
                    min="0.0001"
                    step="any"
                    class="form-control form-control-sm mt-1"
                    :required="requireFields"
                    @input="syncPackInput"
                />
                <input
                    v-else-if="usesBoxesPerCarton"
                    v-model.number="line.pack_boxes_per_carton"
                    type="number"
                    min="0.0001"
                    step="any"
                    class="form-control form-control-sm mt-1"
                    :required="requireFields"
                    @input="syncPackInput"
                />
                <input
                    v-else
                    v-model.number="line.conversion_factor"
                    type="number"
                    min="0.0001"
                    step="any"
                    class="form-control form-control-sm mt-1"
                    :required="requireFields"
                />
            </div>
            <div class="col-12 col-md-8 d-flex align-items-end">
                <div v-if="quantityBase > 0" class="small text-muted mb-1">
                    <p v-if="usesPackSizeFriendlyInput && packSizeBreakdown" class="mb-1">{{ packSizeBreakdown }}</p>
                    <p class="mb-0">
                        Adds <strong>{{ formatQty(quantityBase) }}</strong>
                        {{ unitLabel(line.base_unit) }}(s) to stock
                        <span v-if="packSizeDiffersFromDefault" class="text-warning">
                            ({{ packSizeDefaultHint }})
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { useQuantity } from '@/composables/useQuantity';
import {
    boxConversionFactor,
    catalogBoxesPerCarton,
    catalogStripsPerBox,
    hasBoxAndCartonUnits,
    unitLabel,
} from '@/composables/useProductUnits';
import { computed } from 'vue';

const props = defineProps({
    line: { type: Object, required: true },
    storageLocations: { type: Array, default: () => [] },
    requireFields: { type: Boolean, default: true },
});

defineEmits(['batch-change', 'unit-change']);

const { t } = useLocale();
const { formatMoney, currencyCode, currencySymbol } = useMoney();
const { formatQty } = useQuantity();

function locationLabel(loc) {
    if (!loc) {
        return '';
    }
    return loc.code ? `${loc.name} (${loc.code})` : loc.name;
}

function formatConversionFactor(value) {
    const n = Number(value);
    if (Number.isNaN(n) || n <= 0) {
        return 0.0001;
    }
    return Math.round(n * 10000) / 10000;
}

const usesBoxesPerCarton = computed(
    () => props.line.sell_unit === 'carton' && hasBoxAndCartonUnits(props.line.unit_options),
);
const usesStripsPerBox = computed(
    () => props.line.sell_unit === 'box' && props.line.base_unit === 'strip',
);
const usesPackSizeFriendlyInput = computed(() => usesStripsPerBox.value || usesBoxesPerCarton.value);
const needsPackSize = computed(() => props.line.sell_unit !== props.line.base_unit);

function defaultConversion() {
    const option = props.line.unit_options?.find((u) => u.sell_unit === props.line.sell_unit);
    return Number(option?.conversion_factor ?? 1);
}

function defaultStripsPerBox() {
    const product = {
        units: props.line.unit_options,
        strips_per_box: props.line.catalog_strips_per_box,
        base_unit: props.line.base_unit,
    };
    const value = catalogStripsPerBox(product);
    return value && value > 0 ? value : defaultConversion();
}

function defaultBoxesPerCarton() {
    const product = {
        units: props.line.unit_options,
        boxes_per_carton: props.line.catalog_boxes_per_carton,
    };
    const value = catalogBoxesPerCarton(product);
    return value && value > 0
        ? value
        : defaultConversion() / Math.max(0.0001, boxConversionFactor(props.line.unit_options));
}

function syncPackInput() {
    if (usesStripsPerBox.value) {
        const strips = Number(props.line.pack_strips_per_box);
        if (!Number.isNaN(strips) && strips > 0) {
            props.line.conversion_factor = formatConversionFactor(strips);
        }
        return;
    }
    if (!usesBoxesPerCarton.value) {
        return;
    }
    const boxes = Number(props.line.pack_boxes_per_carton);
    const boxFactor = boxConversionFactor(props.line.unit_options);
    if (Number.isNaN(boxes) || boxes <= 0 || boxFactor <= 0) {
        return;
    }
    props.line.conversion_factor = formatConversionFactor(boxes * boxFactor);
}

const packSizeLabel = computed(() => {
    if (usesBoxesPerCarton.value) {
        return t('catalog.purchase_boxes_per_carton');
    }
    if (usesStripsPerBox.value) {
        return t('catalog.purchase_strips_per_box');
    }
    return `${unitLabel(props.line.base_unit)} per 1 ${unitLabel(props.line.sell_unit)} (this receipt)`;
});

const packSizeBreakdown = computed(() => {
    if (!usesPackSizeFriendlyInput.value) {
        return '';
    }
    const factor = Number(props.line.conversion_factor);
    if (Number.isNaN(factor) || factor <= 0) {
        return '';
    }
    return t('catalog.purchase_pack_equals', {
        qty: formatQty(factor),
        unit: unitLabel(props.line.base_unit),
        sell_unit: unitLabel(props.line.sell_unit),
    });
});

const packSizeDiffersFromDefault = computed(() => {
    if (usesStripsPerBox.value) {
        const current = Number(props.line.pack_strips_per_box);
        return !Number.isNaN(current) && Math.abs(current - defaultStripsPerBox()) > 0.0001;
    }
    if (usesBoxesPerCarton.value) {
        const current = Number(props.line.pack_boxes_per_carton);
        return !Number.isNaN(current) && Math.abs(current - defaultBoxesPerCarton()) > 0.0001;
    }
    return Number(props.line.conversion_factor) !== defaultConversion();
});

const packSizeDefaultHint = computed(() => {
    if (usesStripsPerBox.value) {
        return t('catalog.purchase_catalog_default_strips', { qty: formatQty(defaultStripsPerBox()) });
    }
    if (usesBoxesPerCarton.value) {
        return t('catalog.purchase_catalog_default_boxes', { qty: formatQty(defaultBoxesPerCarton()) });
    }
    return `catalog default: ${defaultConversion()}`;
});

const quantityBase = computed(() => {
    const qty = Number(props.line.quantity);
    if (usesPackSizeFriendlyInput.value) {
        syncPackInput();
    }
    const factor = needsPackSize.value ? Number(props.line.conversion_factor) : 1;
    if (Number.isNaN(qty) || Number.isNaN(factor)) {
        return 0;
    }
    return qty * factor;
});

const priceComparisonLabel = computed(() => {
    if (props.line.last_purchase_unit_cost == null || props.line.last_purchase_unit_cost === '') {
        return '';
    }
    return t('purchases.price_comparison', {
        last: formatMoney(props.line.last_purchase_unit_cost),
        unit: unitLabel(props.line.last_purchase_sell_unit || props.line.sell_unit),
        date: props.line.last_purchase_date || '—',
    });
});

const priceComparisonClass = computed(() => {
    const last = Number(props.line.last_purchase_unit_cost);
    const current = Number(props.line.unit_cost);
    if (Number.isNaN(last) || Number.isNaN(current)) {
        return 'text-muted';
    }
    if (current > last + 0.0001) {
        return 'text-danger';
    }
    if (current < last - 0.0001) {
        return 'text-success';
    }
    return 'text-muted';
});
</script>
