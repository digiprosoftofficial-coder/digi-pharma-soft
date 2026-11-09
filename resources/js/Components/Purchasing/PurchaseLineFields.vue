<template>
    <div class="purchase-line-fields">
        <div class="purchase-batch-row">
            <div class="purchase-batch-field purchase-batch-field--grow">
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
            <div v-if="line.batch_pick === '__new__'" class="purchase-batch-field purchase-batch-field--grow">
                <label class="form-label small">New batch no</label>
                <input
                    v-model="line.batch_no"
                    type="text"
                    class="form-control form-control-sm"
                    :required="requireFields"
                    placeholder="e.g. LOT-20261103"
                />
            </div>
            <div v-else class="purchase-batch-field purchase-batch-field--grow">
                <label class="form-label small">Batch no</label>
                <input :value="line.batch_no" type="text" class="form-control form-control-sm" disabled />
            </div>
            <div class="purchase-batch-field purchase-batch-field--date">
                <label class="form-label small">
                    {{ t('purchases.expiry') }} <span class="text-danger">*</span>
                </label>
                <input
                    v-model="line.expiry_date"
                    type="date"
                    class="form-control form-control-sm"
                    :required="requireFields"
                />
            </div>
            <div class="purchase-batch-field purchase-batch-field--date">
                <label class="form-label small">
                    {{ t('purchases.manufactured_at') }} <span class="text-danger">*</span>
                </label>
                <input
                    v-model="line.manufactured_at"
                    type="date"
                    class="form-control form-control-sm"
                    :required="requireFields"
                />
            </div>
            <div v-if="storageLocations.length" class="purchase-batch-field purchase-batch-field--shelf">
                <label class="form-label small">{{ t('catalog.storage_location_shelf') }}</label>
                <select v-model="line.storage_location_id" class="form-select form-select-sm">
                    <option :value="null">{{ t('catalog.storage_location_use_default') }}</option>
                    <option v-for="loc in storageLocations" :key="loc.id" :value="loc.id">
                        {{ locationLabel(loc) }}
                    </option>
                </select>
            </div>
        </div>

        <div
            class="purchase-trade-panel mt-2"
            :class="{ 'purchase-trade-panel--flash': unitFieldsFlash }"
        >
            <div class="purchase-trade-top">
                <div class="purchase-trade-units">
                    <div class="purchase-trade-label">{{ t('purchases.buy_unit') }}</div>
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
                    <p class="purchase-trade-hint mb-0">
                        {{ t('purchases.buy_unit_compact_hint', {
                            buy: unitLabel(line.sell_unit),
                            stock: unitLabel(line.base_unit),
                        }) }}
                    </p>
                </div>

                <div
                    v-if="needsPackSize"
                    class="purchase-trade-pack"
                    :class="{ 'purchase-trade-pack--dual': usesBoxesPerCarton && (cartonShowsPiecesPerBox || cartonShowsStripsPerBox) }"
                >
                    <template v-if="usesBoxesPerCarton">
                        <div class="purchase-trade-pack__row">
                            <div v-if="cartonShowsPiecesPerBox" class="purchase-trade-pack__field">
                                <label class="purchase-trade-label" :for="packBoxFactorInputId">
                                    {{ t('catalog.purchase_pieces_per_box') }}
                                </label>
                                <input
                                    :id="packBoxFactorInputId"
                                    v-model.number="line.pack_pieces_per_box"
                                    type="number"
                                    min="0.0001"
                                    step="any"
                                    class="form-control form-control-sm"
                                    :required="requireFields"
                                    inputmode="decimal"
                                    @input="syncPackInput"
                                />
                            </div>
                            <div v-else-if="cartonShowsStripsPerBox" class="purchase-trade-pack__field">
                                <label class="purchase-trade-label" :for="packBoxFactorInputId">
                                    {{ t('catalog.purchase_strips_per_box') }}
                                </label>
                                <input
                                    :id="packBoxFactorInputId"
                                    v-model.number="line.pack_strips_per_box"
                                    type="number"
                                    min="0.0001"
                                    step="any"
                                    class="form-control form-control-sm"
                                    :required="requireFields"
                                    inputmode="decimal"
                                    @input="syncPackInput"
                                />
                            </div>
                            <div class="purchase-trade-pack__field">
                                <label class="purchase-trade-label" :for="packInputId">
                                    {{ t('catalog.purchase_boxes_per_carton') }}
                                </label>
                                <input
                                    :id="packInputId"
                                    v-model.number="line.pack_boxes_per_carton"
                                    type="number"
                                    min="0.0001"
                                    step="any"
                                    class="form-control form-control-sm"
                                    :required="requireFields"
                                    inputmode="decimal"
                                    @input="syncPackInput"
                                />
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <label class="purchase-trade-label" :for="packInputId">{{ packSizeLabel }}</label>
                        <input
                            v-if="usesStripsPerBox"
                            :id="packInputId"
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
                            :id="packInputId"
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
                            :id="packInputId"
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
                            v-else
                            :id="packInputId"
                            v-model.number="line.conversion_factor"
                            type="number"
                            min="0.0001"
                            step="any"
                            class="form-control form-control-sm"
                            :required="requireFields"
                            inputmode="decimal"
                        />
                    </template>
                    <p v-if="quantityBase > 0" class="purchase-trade-pack__meta mb-0">
                        <span>
                            {{ t('purchases.stock_add_short', {
                                qty: formatQty(quantityBase),
                                unit: unitLabel(line.base_unit),
                            }) }}
                        </span>
                        <span v-if="packSizeBreakdown" class="text-muted"> · {{ packSizeBreakdown }}</span>
                        <span v-if="packSizeDiffersFromDefault" class="text-warning"> · {{ packSizeDefaultHint }}</span>
                    </p>
                </div>
            </div>

            <div class="purchase-trade-prices">
                <div class="purchase-trade-field">
                    <label class="purchase-trade-label">{{ t('purchases.qty_in_unit', { unit: unitLabel(line.sell_unit) }) }}</label>
                    <div class="input-group input-group-sm">
                        <input
                            v-model.number="line.quantity"
                            type="number"
                            min="0.0001"
                            step="0.0001"
                            class="form-control"
                            :required="requireFields"
                        />
                        <span class="input-group-text text-capitalize">{{ unitLabel(line.sell_unit) }}</span>
                    </div>
                </div>
                <div class="purchase-trade-field">
                    <label class="purchase-trade-label">{{ t('purchases.cost_per_unit', { unit: unitLabel(line.sell_unit) }) }}</label>
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
                        <span class="input-group-text">/{{ unitLabel(line.sell_unit) }}</span>
                    </div>
                    <div v-if="priceComparisonLabel" class="form-text py-0" :class="priceComparisonClass">
                        {{ priceComparisonLabel }}
                    </div>
                    <div v-else-if="costPerBaseHint" class="form-text text-muted py-0 mb-0">
                        {{ costPerBaseHint }}
                    </div>
                </div>
                <div class="purchase-trade-field">
                    <label class="purchase-trade-label">{{ t('purchases.sale_price_per_unit', { unit: unitLabel(line.sell_unit) }) }}</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">{{ currencySymbol() }}</span>
                        <input v-model.number="line.sale_price" type="number" min="0" step="0.01" class="form-control" />
                        <span class="input-group-text">/{{ unitLabel(line.sell_unit) }}</span>
                    </div>
                    <div v-if="salePerBaseHint" class="form-text text-muted py-0 mb-0">
                        {{ salePerBaseHint }}
                    </div>
                </div>
                <div class="purchase-trade-total" :class="{ 'purchase-trade-total--empty': lineTotal <= 0 }">
                    <span class="purchase-trade-label">{{ t('purchases.line_total') }}</span>
                    <strong>{{ lineTotal > 0 ? formatMoney(lineTotal) : '—' }}</strong>
                    <small v-if="lineTotal > 0">{{ lineTotalBreakdown }}</small>
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
    catalogBoxesPerCarton,
    catalogPiecesPerBox,
    catalogPiecesPerStrip,
    catalogStripsPerBox,
    resolveBoxFactorForLine,
    unitLabel,
} from '@/composables/useProductUnits';
import { computed, nextTick, onBeforeUnmount, ref, useId } from 'vue';

const props = defineProps({
    line: { type: Object, required: true },
    storageLocations: { type: Array, default: () => [] },
    requireFields: { type: Boolean, default: true },
});

const emit = defineEmits(['batch-change', 'unit-change']);

const { t } = useLocale();
const { formatMoney, currencySymbol } = useMoney();
const { formatQty } = useQuantity();
const packInputId = useId();
const packBoxFactorInputId = useId();

const unitFieldsFlash = ref(false);
let unitFlashTimer = null;

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

function flashUnitFields() {
    unitFieldsFlash.value = false;
    void nextTick(() => {
        unitFieldsFlash.value = true;
        clearTimeout(unitFlashTimer);
        unitFlashTimer = setTimeout(() => {
            unitFieldsFlash.value = false;
        }, 900);
    });
}

function selectUnit(sellUnit) {
    if (props.line.sell_unit === sellUnit) {
        return;
    }
    props.line.sell_unit = sellUnit;
    flashUnitFields();
    emit('unit-change', props.line);
}

onBeforeUnmount(() => {
    clearTimeout(unitFlashTimer);
});

function resolveBoxFactor() {
    return resolveBoxFactorForLine(props.line);
}

const usesBoxesPerCarton = computed(
    () => props.line.sell_unit === 'carton' && (
        props.line.base_unit === 'box'
        || props.line.base_unit === 'strip'
        || props.line.base_unit === 'piece'
    ),
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
const cartonShowsPiecesPerBox = computed(
    () => usesBoxesPerCarton.value && props.line.base_unit === 'piece',
);
const cartonShowsStripsPerBox = computed(
    () => usesBoxesPerCarton.value && props.line.base_unit === 'strip',
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
    if (value && value > 0) {
        return value;
    }
    if (props.line.sell_unit === 'box') {
        return defaultConversion();
    }
    const fromUnits = resolveBoxFactorForLine({ ...props.line, pack_strips_per_box: null });
    return fromUnits > 0 ? fromUnits : 1;
}

function defaultPiecesPerBox() {
    const product = {
        units: props.line.unit_options,
        pieces_per_box: props.line.catalog_pieces_per_box,
        base_unit: props.line.base_unit,
    };
    const value = catalogPiecesPerBox(product);
    if (value && value > 0) {
        return value;
    }
    if (props.line.sell_unit === 'box') {
        return defaultConversion();
    }
    const fromUnits = resolveBoxFactorForLine({ ...props.line, pack_pieces_per_box: null });
    return fromUnits > 0 ? fromUnits : 1;
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

function roundLineMoney(value) {
    const n = Number(value);
    if (!Number.isFinite(n)) {
        return 0;
    }
    return Math.round(n * 10000) / 10000;
}

function applyPricesFromBasePack() {
    if (!needsPackSize.value || props.line.sell_unit === props.line.base_unit) {
        return;
    }
    const baseRow = props.line.unit_options?.find((u) => u.sell_unit === props.line.base_unit)
        ?? props.line.unit_options?.[0];
    const basePurchase = Number(baseRow?.purchase_price ?? 0);
    const baseSale = Number(baseRow?.sale_price ?? 0);
    const factor = Number(props.line.conversion_factor);
    if (!Number.isFinite(factor) || factor <= 0) {
        return;
    }
    props.line.unit_cost = roundLineMoney(basePurchase * factor);
    props.line.sale_price = roundLineMoney(baseSale * factor);
}

function syncPackInput() {
    if (usesStripsPerBox.value) {
        const strips = Number(props.line.pack_strips_per_box);
        if (!Number.isNaN(strips) && strips > 0) {
            props.line.conversion_factor = formatConversionFactor(strips);
            applyPricesFromBasePack();
        }
        return;
    }
    if (usesPiecesPerBox.value) {
        const pieces = Number(props.line.pack_pieces_per_box);
        if (!Number.isNaN(pieces) && pieces > 0) {
            props.line.conversion_factor = formatConversionFactor(pieces);
            applyPricesFromBasePack();
        }
        return;
    }
    if (usesPiecesPerStrip.value) {
        const pieces = Number(props.line.pack_pieces_per_strip);
        if (!Number.isNaN(pieces) && pieces > 0) {
            props.line.conversion_factor = formatConversionFactor(1 / pieces);
            applyPricesFromBasePack();
        }
        return;
    }
    if (!usesBoxesPerCarton.value) {
        return;
    }
    const boxes = Number(props.line.pack_boxes_per_carton);
    let boxFactor = 1;
    if (cartonShowsPiecesPerBox.value) {
        boxFactor = Number(props.line.pack_pieces_per_box);
    } else if (cartonShowsStripsPerBox.value) {
        boxFactor = Number(props.line.pack_strips_per_box);
    } else {
        boxFactor = resolveBoxFactor();
    }
    if (Number.isNaN(boxes) || boxes <= 0 || Number.isNaN(boxFactor) || boxFactor <= 0) {
        return;
    }
    props.line.conversion_factor = formatConversionFactor(boxes * boxFactor);
    applyPricesFromBasePack();
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
        const boxes = Number(props.line.pack_boxes_per_carton);
        const boxesDiffer = !Number.isNaN(boxes) && Math.abs(boxes - defaultBoxesPerCarton()) > 0.0001;
        if (cartonShowsPiecesPerBox.value) {
            const pieces = Number(props.line.pack_pieces_per_box);
            const piecesDiffer = !Number.isNaN(pieces) && Math.abs(pieces - defaultPiecesPerBox()) > 0.0001;
            return boxesDiffer || piecesDiffer;
        }
        if (cartonShowsStripsPerBox.value) {
            const strips = Number(props.line.pack_strips_per_box);
            const stripsDiffer = !Number.isNaN(strips) && Math.abs(strips - defaultStripsPerBox()) > 0.0001;
            return boxesDiffer || stripsDiffer;
        }
        return boxesDiffer;
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
        const parts = [t('catalog.purchase_catalog_default_boxes', { qty: formatQty(defaultBoxesPerCarton()) })];
        if (cartonShowsPiecesPerBox.value) {
            parts.push(t('catalog.purchase_catalog_default_pieces', { qty: formatQty(defaultPiecesPerBox()) }));
        } else if (cartonShowsStripsPerBox.value) {
            parts.push(t('catalog.purchase_catalog_default_strips', { qty: formatQty(defaultStripsPerBox()) }));
        }
        return parts.join(' · ');
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

const activeConversionFactor = computed(() => {
    if (usesPackSizeFriendlyInput.value) {
        syncPackInput();
    }
    if (!needsPackSize.value) {
        return 1;
    }
    const factor = Number(props.line.conversion_factor);
    return Number.isNaN(factor) || factor <= 0 ? 0 : factor;
});

function perBaseAmount(amount) {
    const value = Number(amount);
    const factor = activeConversionFactor.value;
    if (Number.isNaN(value) || value <= 0 || factor <= 0 || !needsPackSize.value) {
        return null;
    }
    return value / factor;
}

const costPerBaseHint = computed(() => {
    const perBase = perBaseAmount(props.line.unit_cost);
    if (perBase === null) {
        return '';
    }
    return t('purchases.per_base_unit_hint', {
        amount: formatMoney(perBase),
        unit: unitLabel(props.line.base_unit),
    });
});

const salePerBaseHint = computed(() => {
    const perBase = perBaseAmount(props.line.sale_price);
    if (perBase === null) {
        return '';
    }
    return t('purchases.per_base_unit_hint', {
        amount: formatMoney(perBase),
        unit: unitLabel(props.line.base_unit),
    });
});

const quantityBase = computed(() => {
    const qty = Number(props.line.quantity);
    const factor = activeConversionFactor.value || 1;
    if (Number.isNaN(qty) || factor <= 0) {
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
.purchase-batch-row {
    display: flex;
    flex-wrap: nowrap;
    align-items: flex-end;
    gap: 0.5rem;
    overflow-x: auto;
    padding-bottom: 0.15rem;
}

.purchase-batch-field {
    min-width: 0;
}

.purchase-batch-field--grow {
    flex: 1 1 8rem;
    min-width: 8rem;
}

.purchase-batch-field--date {
    flex: 0 0 8.5rem;
    width: 8.5rem;
}

.purchase-batch-field--shelf {
    flex: 1 1 9rem;
    min-width: 9rem;
}

.purchase-trade-panel {
    padding: 0.7rem 0.8rem;
    border: 1px solid #dbe7f0;
    border-radius: 0.85rem;
    background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.purchase-trade-label {
    display: block;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 700;
    margin-bottom: 0.3rem;
    line-height: 1.2;
}

.purchase-trade-hint {
    margin-top: 0.4rem;
    color: #94a3b8;
    font-size: 0.72rem;
}

.purchase-trade-top {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 0.65rem;
    padding-bottom: 0.7rem;
    margin-bottom: 0.7rem;
    border-bottom: 1px dashed #d7e3ee;
}

.purchase-trade-units {
    min-width: 0;
    flex: 0 1 auto;
}

.purchase-unit-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
}

.purchase-unit-chip {
    flex: 1 1 calc(25% - 0.3rem);
    min-width: 4.25rem;
    padding: 0.4rem 0.55rem;
    border: 1px solid #d7e3ee;
    border-radius: 0.55rem;
    background: #fff;
    color: #334155;
    font-size: 0.8rem;
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

.purchase-trade-pack {
    min-width: 0;
    flex: 0 1 auto;
    max-width: 100%;
    padding: 0.55rem 0.65rem;
    border: 1px solid #dcece6;
    border-radius: 0.65rem;
    background: #f7fffb;
}

.purchase-trade-pack__row {
    display: grid;
    gap: 0.5rem;
    grid-template-columns: 1fr;
}

.purchase-trade-pack--dual .purchase-trade-pack__row {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
}

.purchase-trade-pack__field {
    min-width: 0;
}

.purchase-trade-pack .purchase-trade-label {
    color: #0f766e;
}

.purchase-trade-pack__meta {
    margin-top: 0.35rem;
    color: #64748b;
    font-size: 0.7rem;
    line-height: 1.3;
}

.purchase-trade-prices {
    display: grid;
    gap: 0.55rem;
    grid-template-columns: 1fr;
}

.purchase-trade-field {
    min-width: 0;
}

.purchase-trade-field .form-text {
    font-size: 0.7rem;
}

.purchase-trade-total {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
    padding: 0.55rem 0.7rem;
    border-radius: 0.65rem;
    background: linear-gradient(135deg, #f0f7ff 0%, #e8f1ff 100%);
    border: 1px solid #d5e6fb;
}

.purchase-trade-total strong {
    color: var(--bs-primary);
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1.15;
}

.purchase-trade-total small {
    color: #94a3b8;
    font-size: 0.68rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.purchase-trade-total--empty strong {
    color: #94a3b8;
    font-weight: 600;
}

.purchase-trade-panel--flash .form-control,
.purchase-trade-panel--flash .input-group-text {
    animation: purchase-unit-flash 0.9s ease;
}

@keyframes purchase-unit-flash {
    0% {
        box-shadow: 0 0 0 0 rgba(var(--bs-primary-rgb), 0.35);
        border-color: rgba(var(--bs-primary-rgb), 0.55);
    }
    100% {
        box-shadow: 0 0 0 0 transparent;
    }
}

@media (min-width: 768px) {
    .purchase-trade-top {
        flex-direction: row;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: flex-start;
        gap: 0.75rem;
    }

    .purchase-trade-units {
        flex: 0 1 auto;
    }

    .purchase-trade-pack {
        flex: 0 1 14rem;
        width: auto;
        min-width: 11rem;
    }

    .purchase-trade-pack--dual {
        flex: 0 1 22rem;
        min-width: 18rem;
    }

    .purchase-unit-chip {
        flex: 0 1 auto;
        min-width: 4.75rem;
    }

    .purchase-trade-prices {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr) minmax(0, 1.15fr) minmax(7.5rem, 0.9fr);
        align-items: start;
    }
}
</style>
