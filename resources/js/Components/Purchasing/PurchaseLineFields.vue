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
        </div>

        <div class="purchase-unit-panel mt-3">
            <div class="purchase-unit-panel__label">{{ t('purchases.buy_unit') }}</div>
            <div class="purchase-unit-chips" role="group" :aria-label="t('purchases.buy_unit')">
                <button
                    v-for="u in line.unit_options"
                    :key="u.sell_unit"
                    type="button"
                    class="purchase-unit-chip"
                    :class="{ 'purchase-unit-chip--active': line.sell_unit === u.sell_unit }"
                    @click="selectUnit(u.sell_unit)"
                >
                    {{ unitLabel(u.sell_unit) }}
                </button>
            </div>
            <p class="purchase-unit-panel__hint mb-0">
                {{ t('purchases.stock_tracked_as', { unit: unitLabel(line.base_unit) }) }}
            </p>
        </div>

        <div class="row g-2 align-items-end mt-2">
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
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label small">{{ t('purchases.sale_price_mrp') }} ({{ currencyCode() }})</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text">{{ currencySymbol() }}</span>
                    <input v-model.number="line.sale_price" type="number" min="0" step="0.01" class="form-control" />
                </div>
            </div>
        </div>

        <div v-if="lineTotal > 0" class="purchase-line-total mt-2">
            <div class="purchase-line-total__label">
                <span>{{ t('purchases.line_total') }}</span>
                <small>{{ lineTotalBreakdown }}</small>
            </div>
            <strong class="purchase-line-total__value">{{ formatMoney(lineTotal) }}</strong>
        </div>

        <div v-if="needsPackSize" class="purchase-pack-card mt-3">
            <div class="purchase-pack-card__title">{{ packSizeLabel }}</div>
            <input
                v-if="usesStripsPerBox"
                v-model.number="line.pack_strips_per_box"
                type="number"
                min="0.0001"
                step="any"
                class="form-control form-control-sm"
                :required="requireFields"
                inputmode="decimal"
                @input="syncPackInput"
            />
            <input
                v-else-if="usesPiecesPerBox"
                v-model.number="line.pack_pieces_per_box"
                type="number"
                min="0.0001"
                step="any"
                class="form-control form-control-sm"
                :required="requireFields"
                inputmode="decimal"
                @input="syncPackInput"
            />
            <input
                v-else-if="usesPiecesPerStrip"
                v-model.number="line.pack_pieces_per_strip"
                type="number"
                min="0.0001"
                step="any"
                class="form-control form-control-sm"
                :required="requireFields"
                inputmode="decimal"
                @input="syncPackInput"
            />
            <input
                v-else-if="usesBoxesPerCarton"
                v-model.number="line.pack_boxes_per_carton"
                type="number"
                min="0.0001"
                step="any"
                class="form-control form-control-sm"
                :required="requireFields"
                inputmode="decimal"
                @input="syncPackInput"
            />
            <input
                v-else
                v-model.number="line.conversion_factor"
                type="number"
                min="0.0001"
                step="any"
                class="form-control form-control-sm"
                :required="requireFields"
                inputmode="decimal"
            />
            <div v-if="quantityBase > 0" class="purchase-pack-card__preview">
                <p v-if="usesPackSizeFriendlyInput && packSizeBreakdown" class="mb-1">{{ packSizeBreakdown }}</p>
                <p class="mb-0">
                    {{ t('purchases.adds_to_stock') }}
                    <strong>{{ formatQty(quantityBase) }}</strong>
                    {{ unitLabel(line.base_unit) }}
                    <span v-if="packSizeDiffersFromDefault" class="text-warning">
                        ({{ packSizeDefaultHint }})
                    </span>
                </p>
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
    catalogPiecesPerBox,
    catalogPiecesPerStrip,
    catalogStripsPerBox,
    unitLabel,
} from '@/composables/useProductUnits';
import { computed } from 'vue';

const props = defineProps({
    line: { type: Object, required: true },
    storageLocations: { type: Array, default: () => [] },
    requireFields: { type: Boolean, default: true },
});

const emit = defineEmits(['batch-change', 'unit-change']);

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

function selectUnit(sellUnit) {
    if (props.line.sell_unit === sellUnit) {
        return;
    }
    props.line.sell_unit = sellUnit;
    emit('unit-change', props.line);
}

function resolveBoxFactor() {
    if (props.line.base_unit === 'box') {
        return 1;
    }
    const fromUnits = boxConversionFactor(props.line.unit_options);
    if (fromUnits > 0) {
        return fromUnits;
    }
    if (props.line.base_unit === 'strip') {
        const strips = Number(props.line.pack_strips_per_box ?? props.line.catalog_strips_per_box);
        return strips > 0 ? strips : 0;
    }
    if (props.line.base_unit === 'piece') {
        const pieces = Number(props.line.pack_pieces_per_box ?? props.line.catalog_pieces_per_box);
        return pieces > 0 ? pieces : 0;
    }
    return 0;
}

const usesBoxesPerCarton = computed(
    () => props.line.sell_unit === 'carton' && resolveBoxFactor() > 0,
);
const usesStripsPerBox = computed(
    () => props.line.sell_unit === 'box' && props.line.base_unit === 'strip',
);
const usesPiecesPerBox = computed(
    () => props.line.sell_unit === 'box' && props.line.base_unit === 'piece',
);
const usesPiecesPerStrip = computed(
    () => props.line.sell_unit === 'piece' && props.line.base_unit === 'strip',
);
const usesPackSizeFriendlyInput = computed(
    () => usesStripsPerBox.value || usesPiecesPerBox.value || usesBoxesPerCarton.value || usesPiecesPerStrip.value,
);
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

function defaultPiecesPerBox() {
    const product = {
        units: props.line.unit_options,
        pieces_per_box: props.line.catalog_pieces_per_box,
        base_unit: props.line.base_unit,
    };
    const value = catalogPiecesPerBox(product);
    return value && value > 0 ? value : defaultConversion();
}

function defaultPiecesPerStrip() {
    const value = catalogPiecesPerStrip({ pieces_per_strip: props.line.catalog_pieces_per_strip });
    if (value && value > 0) {
        return value;
    }
    const factor = defaultConversion();
    return factor > 0 && factor < 1 ? formatConversionFactor(1 / factor) : 10;
}

function defaultBoxesPerCarton() {
    const product = {
        units: props.line.unit_options,
        boxes_per_carton: props.line.catalog_boxes_per_carton,
    };
    const value = catalogBoxesPerCarton(product);
    const boxFactor = resolveBoxFactor();
    return value && value > 0
        ? value
        : defaultConversion() / Math.max(0.0001, boxFactor);
}

function syncPackInput() {
    if (usesStripsPerBox.value) {
        const strips = Number(props.line.pack_strips_per_box);
        if (!Number.isNaN(strips) && strips > 0) {
            props.line.conversion_factor = formatConversionFactor(strips);
        }
        return;
    }
    if (usesPiecesPerBox.value) {
        const pieces = Number(props.line.pack_pieces_per_box);
        if (!Number.isNaN(pieces) && pieces > 0) {
            props.line.conversion_factor = formatConversionFactor(pieces);
        }
        return;
    }
    if (usesPiecesPerStrip.value) {
        const pieces = Number(props.line.pack_pieces_per_strip);
        if (!Number.isNaN(pieces) && pieces > 0) {
            props.line.conversion_factor = formatConversionFactor(1 / pieces);
        }
        return;
    }
    if (!usesBoxesPerCarton.value) {
        return;
    }
    const boxes = Number(props.line.pack_boxes_per_carton);
    const boxFactor = resolveBoxFactor();
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
    if (usesPiecesPerBox.value) {
        return t('catalog.purchase_pieces_per_box');
    }
    if (usesPiecesPerStrip.value) {
        return t('catalog.purchase_pieces_per_strip');
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
    if (usesPiecesPerBox.value) {
        const current = Number(props.line.pack_pieces_per_box);
        return !Number.isNaN(current) && Math.abs(current - defaultPiecesPerBox()) > 0.0001;
    }
    if (usesPiecesPerStrip.value) {
        const current = Number(props.line.pack_pieces_per_strip);
        return !Number.isNaN(current) && Math.abs(current - defaultPiecesPerStrip()) > 0.0001;
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
    if (usesPiecesPerBox.value) {
        return t('catalog.purchase_catalog_default_pieces', { qty: formatQty(defaultPiecesPerBox()) });
    }
    if (usesPiecesPerStrip.value) {
        return t('catalog.purchase_catalog_default_pieces', { qty: formatQty(defaultPiecesPerStrip()) });
    }
    if (usesBoxesPerCarton.value) {
        return t('catalog.purchase_catalog_default_boxes', { qty: formatQty(defaultBoxesPerCarton()) });
    }
    return `catalog default: ${defaultConversion()}`;
});

const lineTotal = computed(() => {
    const qty = Number(props.line.quantity);
    const cost = Number(props.line.unit_cost);
    if (Number.isNaN(qty) || Number.isNaN(cost) || qty <= 0 || cost <= 0) {
        return 0;
    }
    return qty * cost;
});

const lineTotalBreakdown = computed(() => {
    if (lineTotal.value <= 0) {
        return '';
    }
    return `${formatQty(props.line.quantity)} ${unitLabel(props.line.sell_unit)} × ${formatMoney(props.line.unit_cost)}`;
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

<style scoped>
.purchase-unit-panel {
    padding: 0.85rem 0.9rem;
    border: 1px solid #dbe7f0;
    border-radius: 0.9rem;
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.purchase-unit-panel__label {
    color: #475569;
    font-size: 0.78rem;
    font-weight: 700;
    margin-bottom: 0.55rem;
}

.purchase-unit-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
}

.purchase-unit-chip {
    flex: 1 1 calc(50% - 0.45rem);
    min-width: 5.5rem;
    padding: 0.7rem 0.75rem;
    border: 1px solid #d7e3ee;
    border-radius: 0.75rem;
    background: #fff;
    color: #334155;
    font-size: 0.92rem;
    font-weight: 700;
    text-transform: capitalize;
    transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease;
}

.purchase-unit-chip--active {
    border-color: rgba(var(--bs-primary-rgb), 0.55);
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
    box-shadow: inset 0 0 0 1px rgba(var(--bs-primary-rgb), 0.2);
}

.purchase-unit-panel__hint {
    margin-top: 0.55rem;
    color: #94a3b8;
    font-size: 0.72rem;
}

.purchase-line-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.65rem 0.9rem;
    border: 1px solid #dbe7f0;
    border-radius: 0.8rem;
    background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%);
}

.purchase-line-total__label {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.purchase-line-total__label span {
    color: #475569;
    font-size: 0.76rem;
    font-weight: 700;
}

.purchase-line-total__label small {
    color: #94a3b8;
    font-size: 0.7rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.purchase-line-total__value {
    color: var(--bs-primary);
    font-size: 1.1rem;
    font-weight: 700;
    white-space: nowrap;
}

.purchase-pack-card {
    padding: 0.85rem 0.9rem;
    border: 1px solid #dcece6;
    border-radius: 0.9rem;
    background: #f7fffb;
}

.purchase-pack-card__title {
    color: #0f766e;
    font-size: 0.8rem;
    font-weight: 700;
    margin-bottom: 0.45rem;
}

.purchase-pack-card__preview {
    margin-top: 0.65rem;
    color: #64748b;
    font-size: 0.78rem;
    line-height: 1.35;
}

@media (min-width: 768px) {
    .purchase-unit-chip {
        flex: 0 1 auto;
        min-width: 6.5rem;
    }
}
</style>
