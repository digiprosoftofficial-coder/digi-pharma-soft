<template>
    <TenantShellLayout :page-title="existing ? 'Edit product' : 'New product'">
        <Head :title="existing ? 'Edit product' : 'New product'" />
        <h1 class="h4 mb-4 d-lg-none">{{ existing ? 'Edit product' : 'New product' }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input v-model="form.name" type="text" class="form-control" required />
                    <div v-if="form.errors.name" class="text-danger small">{{ form.errors.name }}</div>
                </div>
                <div v-if="advancedCatalogEnabled" class="col-md-4">
                    <label class="form-label">Generic name <span class="text-muted fw-normal">(optional)</span></label>
                    <input v-model="form.generic_name" type="text" class="form-control" placeholder="e.g. Paracetamol" />
                    <div v-if="form.errors.generic_name" class="text-danger small">{{ form.errors.generic_name }}</div>
                </div>
                <div v-if="advancedCatalogEnabled" class="col-md-2">
                    <label class="form-label">{{ t('catalog.strength') }} <span class="text-muted fw-normal">(optional)</span></label>
                    <input v-model="form.strength" type="text" class="form-control" :placeholder="t('catalog.strength_placeholder')" />
                    <div v-if="form.errors.strength" class="text-danger small">{{ form.errors.strength }}</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">SKU</label>
                    <input v-if="existing" v-model="form.sku" type="text" class="form-control" readonly />
                    <input v-else type="text" class="form-control bg-light" disabled placeholder="Auto (PRD-000001)" />
                    <p v-if="!existing" class="form-text small mb-0">Generated automatically when you save.</p>
                    <div v-if="form.errors.sku" class="text-danger small">{{ form.errors.sku }}</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Barcode</label>
                    <input v-model="form.barcode" type="text" class="form-control" />
                    <div v-if="existing" class="mt-2">
                        <span class="small text-muted d-block mb-1">Label preview</span>
                        <img :src="`/barcodes/${existing.id}`" alt="Barcode" class="border rounded bg-white p-1" style="max-height: 64px" />
                    </div>
                </div>
                <div v-if="wholesaleEnabled" class="col-md-4">
                    <label class="form-label">Wholesale price <span class="text-muted fw-normal">(optional)</span></label>
                    <input v-model="form.wholesale_price" type="number" min="0" step="0.01" class="form-control" />
                    <div v-if="form.errors.wholesale_price" class="text-danger small">{{ form.errors.wholesale_price }}</div>
                </div>
                <div v-if="advancedCatalogEnabled" class="col-md-4">
                    <label class="form-label">VAT / tax % <span class="text-muted fw-normal">(optional)</span></label>
                    <input v-model="form.vat_percent" type="number" min="0" max="100" step="0.01" class="form-control" placeholder="e.g. 5" />
                    <div v-if="form.errors.vat_percent" class="text-danger small">{{ form.errors.vat_percent }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Product image <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="file" accept="image/*" class="form-control" @change="onImageChange" />
                    <div v-if="form.errors.image" class="text-danger small">{{ form.errors.image }}</div>
                    <div v-if="imagePreviewUrl" class="mt-2">
                        <img :src="imagePreviewUrl" alt="Product" class="border rounded" style="max-height: 120px; max-width: 100%" />
                    </div>
                    <div v-if="existing?.image_url && !form.remove_image && !imagePreviewUrl" class="mt-2">
                        <img :src="existing.image_url" alt="Product" class="border rounded" style="max-height: 120px; max-width: 100%" />
                    </div>
                    <div v-if="existing?.image_url" class="form-check mt-2">
                        <input id="remove_image" v-model="form.remove_image" type="checkbox" class="form-check-input" />
                        <label class="form-check-label small" for="remove_image">Remove current image</label>
                    </div>
                </div>
                <div v-if="advancedCatalogEnabled" class="col-12">
                    <label class="form-label">Short description <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea v-model="form.short_description" class="form-control" rows="2" maxlength="2000" />
                    <div v-if="form.errors.short_description" class="text-danger small">{{ form.errors.short_description }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Product type</label>
                    <div class="d-flex align-items-center gap-2">
                        <img
                            v-if="selectedTypeIconUrl"
                            :src="selectedTypeIconUrl"
                            alt=""
                            width="30"
                            height="30"
                            class="flex-shrink-0"
                            style="object-fit: contain"
                        />
                        <ProductTypeIcon v-else :type="form.product_type" size="lg" class="flex-shrink-0" />
                        <select v-model="form.product_type" class="form-select" required>
                            <option v-for="pt in catalogOptions.productTypes" :key="pt" :value="pt">
                                {{ labelForType(pt) }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Base stock unit</label>
                    <select v-model="form.base_unit" class="form-select" required @change="onBaseUnitChange">
                        <option v-for="u in availableSellUnits" :key="u" :value="u">{{ unitLabel(u) }}</option>
                    </select>
                    <p class="form-text small mb-0">Inventory is tracked in this unit.</p>
                </div>
                <div v-if="showPiecesPerStrip" class="col-md-4">
                    <label class="form-label">Pieces per strip</label>
                    <input
                        v-model="form.pieces_per_strip"
                        type="number"
                        min="1"
                        step="1"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.pieces_per_strip }"
                        placeholder="e.g. 10"
                        @input="syncPiecesPerStripToUnits"
                    />
                    <div v-if="form.errors.pieces_per_strip" class="text-danger small">{{ form.errors.pieces_per_strip }}</div>
                    <p v-else class="form-text small mb-0">Tablets/capsules in one strip — needed for piece sales &amp; stock count.</p>
                </div>
                <div v-if="showStripsPerBox" class="col-md-4">
                    <label class="form-label">Strips per box</label>
                    <input
                        v-model="form.strips_per_box"
                        type="number"
                        min="1"
                        step="1"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.strips_per_box }"
                        placeholder="e.g. 10"
                        @input="syncStripsPerBoxToUnits"
                    />
                    <div v-if="form.errors.strips_per_box" class="text-danger small">{{ form.errors.strips_per_box }}</div>
                    <p v-else class="form-text small mb-0">Strips in one box — syncs box conversion for purchases &amp; sales.</p>
                </div>
                <div v-if="showBoxesPerCarton" class="col-md-4">
                    <label class="form-label">Boxes per carton</label>
                    <input
                        v-model="form.boxes_per_carton"
                        type="number"
                        min="1"
                        step="1"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.boxes_per_carton }"
                        placeholder="e.g. 12"
                        @input="syncBoxesPerCartonToUnits"
                    />
                    <div v-if="form.errors.boxes_per_carton" class="text-danger small">{{ form.errors.boxes_per_carton }}</div>
                    <p v-else class="form-text small mb-0">
                        Boxes in one carton — syncs carton conversion (× box size).
                        <span v-if="cartonConversionPreview" class="d-block">
                            = {{ formatQty(cartonConversionPreview) }} {{ unitLabel(form.base_unit) }} per carton
                        </span>
                    </p>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ t('catalog.default_markup_percent') }}</label>
                    <input
                        v-model="form.default_markup_percent"
                        type="number"
                        min="0"
                        max="1000"
                        step="0.01"
                        class="form-control"
                        :placeholder="t('catalog.default_markup_placeholder')"
                    />
                    <p class="form-text small mb-2">{{ t('catalog.default_markup_hint') }}</p>
                    <div class="alert alert-info small py-2 mb-0">
                        <div class="fw-semibold mb-1">{{ t('catalog.markup_pricing_help_title') }}</div>
                        <ul class="mb-0 ps-3">
                            <li>{{ t('catalog.default_markup_blank_means') }}</li>
                            <li>{{ t('catalog.default_markup_set_means') }}</li>
                            <li>{{ t('catalog.default_markup_example') }}</li>
                        </ul>
                    </div>
                    <div v-if="form.errors.default_markup_percent" class="text-danger small mt-1">
                        {{ form.errors.default_markup_percent }}
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Min stock alert</label>
                    <input v-model="form.min_stock" type="number" min="0" class="form-control" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select v-model="form.category_id" class="form-select">
                        <option :value="null">— None —</option>
                        <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Manufacturer</label>
                    <select v-model="form.manufacturer_id" class="form-select">
                        <option :value="null">— None —</option>
                        <option v-for="m in manufacturers" :key="m.id" :value="m.id">{{ m.name }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('catalog.default_storage_location') }}</label>
                    <select v-model="form.storage_location_id" class="form-select">
                        <option :value="null">{{ t('catalog.storage_location_none') }}</option>
                        <option v-for="loc in storageLocations" :key="loc.id" :value="loc.id">
                            {{ locationLabel(loc) }}
                        </option>
                    </select>
                </div>
            </div>

            <div v-if="!existing" class="mt-4 card border-primary border-2">
                <div class="card-header bg-primary-subtle py-3">
                    <h2 class="h5 mb-1">Opening stock</h2>
                    <p class="small text-muted mb-0">
                        Set how much you have on hand when adding this product (in <strong>{{ unitLabel(form.base_unit) }}</strong>).
                        Leave quantity empty if stock will come from a purchase later.
                    </p>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Quantity</label>
                            <input
                                v-model.number="form.opening_quantity"
                                type="number"
                                min="0"
                                step="0.0001"
                                class="form-control form-control-lg"
                                placeholder="e.g. 100"
                            />
                            <div v-if="form.errors.opening_quantity" class="text-danger small">{{ form.errors.opening_quantity }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Batch no</label>
                            <input
                                v-model="form.opening_batch_no"
                                type="text"
                                class="form-control"
                                :placeholder="form.sku ? `OPEN-${form.sku}` : 'Auto if empty'"
                            />
                            <div v-if="form.errors.opening_batch_no" class="text-danger small">{{ form.errors.opening_batch_no }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expiry date</label>
                            <input v-model="form.opening_expiry_date" type="date" class="form-control" />
                        </div>
                        <div v-if="storageLocations.length" class="col-md-4">
                            <label class="form-label">{{ t('catalog.opening_storage_location') }}</label>
                            <select v-model="form.opening_storage_location_id" class="form-select">
                                <option :value="null">{{ t('catalog.storage_location_use_default') }}</option>
                                <option v-for="loc in storageLocations" :key="loc.id" :value="loc.id">
                                    {{ locationLabel(loc) }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="existing" class="mt-4 card border-warning border-2">
                <div class="card-header bg-warning-subtle py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h2 class="h5 mb-1">Stock</h2>
                        <p class="small text-muted mb-0">Adjust on-hand quantity in <strong>{{ unitLabel(form.base_unit) }}</strong>.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge text-bg-dark fs-6 px-3 py-2">
                            Total: {{ formatQty(totalStock) }} {{ unitLabel(form.base_unit) }}
                        </span>
                        <span v-if="totalStockPieces !== null" class="badge text-bg-secondary fs-6 px-3 py-2">
                            {{ formatQty(totalStockPieces) }} pieces
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div v-if="batches.length" class="table-responsive mb-3">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Batch</th>
                                    <th>Expiry</th>
                                    <th class="text-end">On hand</th>
                                    <th>{{ t('catalog.storage_location_shelf') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="b in batches" :key="b.id">
                                    <td>{{ b.batch_no }}</td>
                                    <td>{{ b.expiry_date || '—' }}</td>
                                    <td class="text-end">{{ formatQty(b.quantity_on_hand) }}</td>
                                    <td>
                                        <select
                                            v-model="batchLocationEdits[b.id]"
                                            class="form-select form-select-sm"
                                        >
                                            <option :value="null">{{ t('catalog.storage_location_use_default') }}</option>
                                            <option v-for="loc in storageLocations" :key="loc.id" :value="loc.id">
                                                {{ locationLabel(loc) }}
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="text-muted small mb-3">No batches yet. A positive adjustment will create one.</p>

                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Adjust by (+ / −)</label>
                            <input
                                v-model.number="form.stock_adjustment"
                                type="number"
                                step="0.0001"
                                class="form-control form-control-lg"
                                placeholder="e.g. 50 or -10"
                            />
                            <p class="form-text small mb-0">Positive adds stock; negative removes.</p>
                            <div v-if="form.errors.stock_adjustment" class="text-danger small">{{ form.errors.stock_adjustment }}</div>
                        </div>
                        <div v-if="batches.length > 1" class="col-md-4">
                            <label class="form-label">Apply to batch</label>
                            <select v-model="form.stock_adjust_batch_id" class="form-select">
                                <option :value="null">— Select batch —</option>
                                <option v-for="b in batches" :key="b.id" :value="b.id">
                                    {{ b.batch_no }} ({{ formatQty(b.quantity_on_hand) }})
                                </option>
                            </select>
                            <div v-if="form.errors.stock_adjust_batch_id" class="text-danger small">{{ form.errors.stock_adjust_batch_id }}</div>
                        </div>
                        <div v-else-if="!batches.length" class="col-md-4">
                            <label class="form-label">New batch no</label>
                            <input
                                v-model="form.stock_adjust_batch_no"
                                type="text"
                                class="form-control"
                                :placeholder="`ADJ-${existing.sku}`"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="existing && batches.length" class="mt-4 card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h2 class="h6 mb-1">{{ t('catalog.batch_pricing_title') }}</h2>
                    <p class="small text-muted mb-0">{{ t('catalog.batch_markup_help') }}</p>
                    <p class="small text-muted mb-0">{{ t('catalog.batch_sale_price_hint') }}</p>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Batch</th>
                                <th>Expiry</th>
                                <th class="text-end">On hand</th>
                                <th class="text-end">Unit cost</th>
                                <th class="text-end">{{ t('catalog.batch_sale_price') }}</th>
                                <th class="text-end">{{ t('catalog.batch_markup_percent') }}</th>
                                <th class="text-end">{{ t('catalog.batch_suggested_price') }}</th>
                                <th style="width: 3rem"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="b in batches" :key="`pricing-${b.id}`">
                                <td>{{ b.batch_no }}</td>
                                <td>{{ b.expiry_date || '—' }}</td>
                                <td class="text-end">{{ formatQty(b.quantity_on_hand) }}</td>
                                <td class="text-end">
                                    <div class="fw-medium">
                                        {{ formatMoney(b.purchase_unit_cost) }}
                                        <span class="text-muted small fw-normal">
                                            {{ t('catalog.batch_per_unit', { unit: unitLabel(batchStoredPriceUnit(b, form.base_unit)) }) }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="batchStoredPriceDiffersFromBase(b, form.base_unit)"
                                        class="text-muted small"
                                    >
                                        {{
                                            t('catalog.batch_cost_in_base_unit', {
                                                amount: formatMoney(batchBaseUnitCost(b)),
                                                unit: unitLabel(form.base_unit),
                                            })
                                        }}
                                    </div>
                                </td>
                                <td class="text-end">
                                    <input
                                        v-model="batchSalePrices[b.id]"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control form-control-sm text-end"
                                        style="min-width: 5.5rem"
                                        :placeholder="batchEffectivePriceLabel(b)"
                                        @focus="previewBatchId = b.id"
                                        @input="previewBatchId = b.id"
                                        @blur="onBatchSalePriceBlur(b.id)"
                                    />
                                    <div class="text-muted small mt-1">
                                        {{ t('catalog.batch_per_unit', { unit: unitLabel(batchStoredPriceUnit(b, form.base_unit)) }) }}
                                    </div>
                                </td>
                                <td class="text-end">
                                    <input
                                        v-model="batchMarkups[b.id]"
                                        type="number"
                                        min="0"
                                        max="1000"
                                        step="0.01"
                                        class="form-control form-control-sm text-end"
                                        style="width: 4.5rem"
                                        :placeholder="form.default_markup_percent || '—'"
                                        @focus="previewBatchId = b.id"
                                        @input="onBatchMarkupInput(b)"
                                    />
                                </td>
                                <td class="text-end text-muted small">
                                    <div>{{ batchSuggestedLabel(b) }}</div>
                                    <div class="text-muted">
                                        {{ t('catalog.batch_per_unit', { unit: unitLabel(defaultSellUnit(productForPricing())) }) }}
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary py-0"
                                        :disabled="batchPricingSaving[b.id]"
                                        @click="saveBatchPricing(b)"
                                    >
                                        ✓
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                    <h2 class="h6 mb-0">Sell units &amp; prices</h2>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <label v-if="batches.length" class="small text-muted mb-0">
                            {{ t('catalog.lot_price_preview_label') }}
                        </label>
                        <select
                            v-if="batches.length"
                            v-model="previewBatchId"
                            class="form-select form-select-sm"
                            style="width: auto; min-width: 10rem"
                        >
                            <option :value="null">{{ t('catalog.unit_prices_product_default') }}</option>
                            <option v-for="b in batches" :key="`preview-${b.id}`" :value="b.id">
                                {{ b.batch_no }}
                            </option>
                        </select>
                        <button
                            v-if="!previewBatchId"
                            type="button"
                            class="btn btn-sm btn-outline-secondary"
                            @click="addUnitRow"
                        >
                            Add unit
                        </button>
                    </div>
                </div>
                <div v-if="previewBatchId && selectedPreviewBatch" class="alert alert-info small py-2 mb-2">
                    {{ t('catalog.lot_price_preview_hint', { batch: selectedPreviewBatch.batch_no }) }}
                </div>
                <div v-if="form.errors.units" class="text-danger small mb-2">{{ form.errors.units }}</div>
                <p v-if="priceAutoFillHint && !previewBatchId" class="small text-muted mb-2">
                    {{ priceAutoFillHint }}
                </p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 9rem">Unit</th>
                                <th style="width: 18rem">{{ t('catalog.pack_relation') }}</th>
                                <th style="width: 9rem">Purchase</th>
                                <th style="width: 9rem">Sale</th>
                                <th>Default</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, idx) in form.units"
                                :key="idx"
                                :class="{ 'table-light': previewBatchId }"
                            >
                                <td>
                                    <select
                                        v-model="row.sell_unit"
                                        class="form-select form-select-sm"
                                        style="max-width: 8.5rem"
                                        :disabled="previewBatchId || row.sell_unit === form.base_unit"
                                    >
                                        <option v-for="u in availableSellUnits" :key="u" :value="u">{{ unitLabel(u) }}</option>
                                    </select>
                                </td>
                                <td>
                                    <div v-if="!previewBatchId" class="d-flex align-items-center gap-2">
                                        <input
                                            v-model.number="row.conversion_factor"
                                            type="number"
                                            min="0.0001"
                                            step="any"
                                            class="form-control form-control-sm flex-shrink-0"
                                            style="width: 5.75rem"
                                            :disabled="row.sell_unit === form.base_unit"
                                            @input="onUnitConversionInput(row)"
                                            @blur="onConversionFactorBlur(row)"
                                        />
                                        <span class="small text-muted text-nowrap">
                                            {{ unitRelationLabel(row) }}
                                        </span>
                                    </div>
                                    <span v-else class="small text-muted">
                                        {{ unitRelationLabel(row) }}
                                    </span>
                                </td>
                                <td>
                                    <input
                                        v-if="!previewBatchId"
                                        v-model="row.purchase_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control form-control-sm"
                                        required
                                        @input="onBaseUnitPriceInput(row)"
                                        @blur="onUnitPriceBlur(row, 'purchase_price')"
                                    />
                                    <span v-else class="form-control form-control-sm bg-white text-end">
                                        {{ lotPreviewPurchase(row.sell_unit) }}
                                    </span>
                                </td>
                                <td>
                                    <input
                                        v-if="!previewBatchId"
                                        v-model="row.sale_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="form-control form-control-sm"
                                        required
                                        @input="onBaseUnitPriceInput(row)"
                                        @blur="onUnitPriceBlur(row, 'sale_price')"
                                    />
                                    <span v-else class="form-control form-control-sm bg-white text-end">
                                        {{ lotPreviewSale(row.sell_unit) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button
                                        type="button"
                                        class="btn btn-sm rounded-pill px-3 py-0"
                                        :class="row.is_default ? 'btn-primary' : 'btn-outline-secondary'"
                                        :disabled="!!previewBatchId"
                                        @click="setDefault(idx)"
                                    >
                                        {{ row.is_default ? t('catalog.default_unit') : 'Set' }}
                                    </button>
                                </td>
                                <td>
                                    <button
                                        v-if="!previewBatchId"
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        :disabled="form.units.length <= 1 || row.sell_unit === form.base_unit"
                                        :title="row.sell_unit === form.base_unit ? 'Cannot remove base stock unit' : ''"
                                        @click="removeUnitRow(idx)"
                                    >
                                        ×
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <div class="form-check">
                    <input id="active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="active">Active</label>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
                <Link href="/products" class="btn btn-outline-secondary">Cancel</Link>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import ProductTypeIcon from '@/Components/Catalog/ProductTypeIcon.vue';
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { productTypeLabel } from '@/composables/useProductType';
import {
    defaultBaseUnitForProductType,
    sellUnitsForProductType,
    usesStripProductType,
} from '@/composables/useProductTypeUnits';
import {
    suggestedUnitPrice,
    batchSalePriceInSellUnit,
    unitCostInSellUnit,
    batchStoredPriceUnit,
    batchBaseUnitCost,
    batchStoredPriceDiffersFromBase,
} from '@/composables/useBatchPricing';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { useQuantity } from '@/composables/useQuantity';
import { defaultSellUnit, unitSalePrice } from '@/composables/useProductUnits';
import { formatPrice, precisionDecimal } from '@/utils/formatNumber';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';

const { t } = useLocale();
const { formatMoney } = useMoney();
const { formatQty } = useQuantity();

const page = usePage();
const wholesaleEnabled = computed(() => page.props.features?.wholesale_pricing ?? false);
const advancedCatalogEnabled = computed(() => page.props.features?.advanced_catalog ?? true);

const props = defineProps({
    product: { type: Object, default: null },
    catalogOptions: {
        type: Object,
        default: () => ({
            productTypes: ['other'],
            productTypeOptions: [],
            sellUnits: ['piece', 'strip', 'box', 'carton'],
            stripProductTypes: ['tablet', 'capsule'],
        }),
    },
    categories: { type: Array, default: () => [] },
    manufacturers: { type: Array, default: () => [] },
    storageLocations: { type: Array, default: () => [] },
});

/** Unwrap JsonResource { data: ... } if present */
function productData() {
    if (!props.product) {
        return null;
    }
    return props.product.data ?? props.product;
}

function labelForType(slug) {
    return productTypeLabel(slug, t);
}

const selectedTypeIconUrl = computed(() => {
    const options = props.catalogOptions.productTypeOptions ?? [];
    const match = options.find((o) => o.slug === form.product_type);

    return match?.icon_url ?? null;
});

function unitLabel(u) {
    return u.charAt(0).toUpperCase() + u.slice(1);
}

function unitRow(sellUnit, conversionFactor, isDefault) {
    return {
        sell_unit: sellUnit,
        conversion_factor: conversionFactor,
        purchase_price: '0',
        sale_price: '0',
        is_default: isDefault,
    };
}

function buildDefaultUnits(baseUnit = 'strip') {
    const presets = {
        strip: [
            unitRow('strip', 1, true),
            unitRow('piece', 0.1, false),
            unitRow('box', 10, false),
        ],
        piece: [unitRow('piece', 1, true)],
        box: [unitRow('box', 1, true)],
        carton: [unitRow('carton', 1, true)],
    };

    return presets[baseUnit] ?? [unitRow(baseUnit, 1, true)];
}

function initialUnits() {
    const product = productData();
    if (product?.units?.length) {
        return product.units.map((u) => ({
            sell_unit: u.sell_unit,
            conversion_factor: formatConversionFactor(u.conversion_factor),
            purchase_price: formatPrice(u.purchase_price),
            sale_price: formatPrice(u.sale_price),
            is_default: Boolean(u.is_default),
        }));
    }
    const base = product?.base_unit ?? 'strip';

    return buildDefaultUnits(base);
}

function initialStripsPerBox(units) {
    const product = productData();
    if (product?.strips_per_box != null && product.strips_per_box !== '') {
        return Number(product.strips_per_box);
    }
    const box = units.find((u) => u.sell_unit === 'box');
    if (box && Number(box.conversion_factor) > 0) {
        return Number(box.conversion_factor);
    }
    return '';
}

function initialBoxesPerCarton(units) {
    const product = productData();
    if (product?.boxes_per_carton != null && product.boxes_per_carton !== '') {
        return Number(product.boxes_per_carton);
    }
    const carton = units.find((u) => u.sell_unit === 'carton');
    const box = units.find((u) => u.sell_unit === 'box');
    if (carton && box && Number(box.conversion_factor) > 0) {
        return Math.round((Number(carton.conversion_factor) / Number(box.conversion_factor)) * 10000) / 10000;
    }
    return '';
}

const existing = productData();

const stripProductTypes = computed(() => props.catalogOptions.stripProductTypes ?? ['tablet', 'capsule']);
const allSellUnits = computed(() => props.catalogOptions.sellUnits ?? ['piece', 'strip', 'box', 'carton']);

const batchLocationEdits = reactive({});
const batchMarkups = reactive({});
const batchSalePrices = reactive({});
const batchPricingSaving = reactive({});
const previewBatchId = ref(null);

function locationLabel(loc) {
    if (!loc) {
        return '';
    }

    return loc.code ? `${loc.name} (${loc.code})` : loc.name;
}

const batches = computed(() => {
    const product = productData();
    if (!product?.batches) {
        return [];
    }
    const raw = product.batches;
    if (Array.isArray(raw)) {
        return raw;
    }

    return raw.data ?? [];
});

watch(
    batches,
    (list) => {
        list.forEach((b) => {
            if (!(b.id in batchLocationEdits)) {
                batchLocationEdits[b.id] = b.storage_location_id ?? null;
            }
            if (batchMarkups[b.id] === undefined) {
                batchMarkups[b.id] = b.markup_percent ?? '';
            }
            if (batchSalePrices[b.id] === undefined) {
                batchSalePrices[b.id] =
                    b.sale_price != null && b.sale_price !== '' ? formatPrice(b.sale_price) : '';
            }
        });
    },
    { immediate: true },
);

function productForPricing() {
    return {
        default_markup_percent: form.default_markup_percent,
        sale_price: productData()?.sale_price,
        units: form.units,
        base_unit: form.base_unit,
    };
}

const selectedPreviewBatch = computed(() => {
    if (!previewBatchId.value) {
        return null;
    }

    return batches.value.find((b) => b.id === previewBatchId.value) ?? null;
});

watch(batches, (list) => {
    if (previewBatchId.value && !list.some((b) => b.id === previewBatchId.value)) {
        previewBatchId.value = null;
    }
});

function batchForPreview(batch) {
    const saleDraft = batchSalePrices[batch.id];
    const markupDraft = batchMarkups[batch.id];

    return {
        ...batch,
        sale_price:
            saleDraft !== undefined && saleDraft !== '' && saleDraft !== null
                ? saleDraft
                : batch.sale_price,
        markup_percent:
            markupDraft !== undefined && markupDraft !== '' && markupDraft !== null
                ? markupDraft
                : batch.markup_percent,
    };
}

function lotPreviewPurchase(sellUnit) {
    const batch = selectedPreviewBatch.value;
    if (!batch) {
        return '—';
    }

    return formatPrice(unitCostInSellUnit(batchForPreview(batch), sellUnit, form.units));
}

function lotPreviewSale(sellUnit) {
    const batch = selectedPreviewBatch.value;
    if (!batch) {
        return '—';
    }

    const previewBatch = batchForPreview(batch);
    const product = productForPricing();
    const mrp = batchSalePriceInSellUnit(previewBatch, sellUnit, form.units);
    if (mrp !== null) {
        return formatPrice(mrp);
    }

    const suggested = suggestedUnitPrice(previewBatch, product, sellUnit, form.units);
    if (suggested !== null) {
        return formatPrice(suggested);
    }

    return formatPrice(unitSalePrice(product, sellUnit));
}

function batchSuggestedLabel(batch) {
    const previewBatch = batchForPreview(batch);
    const sellUnit = defaultSellUnit(productForPricing());
    const mrp = batchSalePriceInSellUnit(previewBatch, sellUnit, form.units);
    if (mrp !== null) {
        return formatMoney(mrp);
    }

    const suggested = suggestedUnitPrice(previewBatch, productForPricing(), sellUnit, form.units);
    if (suggested !== null) {
        return formatMoney(suggested);
    }

    return formatMoney(unitSalePrice(productForPricing(), sellUnit));
}

function batchEffectivePriceLabel(batch) {
    const sellUnit = defaultSellUnit(productForPricing());
    const batchPrice = batchSalePriceInSellUnit(batch, sellUnit, form.units);
    if (batchPrice !== null) {
        return formatMoney(batchPrice);
    }

    return '—';
}

function onBatchSalePriceBlur(batchId) {
    const value = batchSalePrices[batchId];
    if (value === '' || value === null || value === undefined) {
        return;
    }
    batchSalePrices[batchId] = formatPrice(value);
}

function onBatchMarkupInput(batch) {
    previewBatchId.value = batch.id;

    const markup = Number(batchMarkups[batch.id]);
    const cost = Number(batch.purchase_unit_cost ?? 0);
    if (Number.isNaN(markup) || cost <= 0) {
        return;
    }

    batchSalePrices[batch.id] = formatPrice(cost * (1 + markup / 100));
}

function saveBatchPricing(batch) {
    const product = productData();
    if (!product?.id) {
        return;
    }

    previewBatchId.value = batch.id;
    batchPricingSaving[batch.id] = true;
    const saleRaw = batchSalePrices[batch.id];
    const markupRaw = batchMarkups[batch.id];

    router.patch(
        `/products/${product.id}/batches/${batch.id}/markup`,
        {
            markup_percent: markupRaw === '' || markupRaw === null || markupRaw === undefined ? null : markupRaw,
            sale_price:
                saleRaw === '' || saleRaw === null || saleRaw === undefined ? null : precisionDecimal(saleRaw),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                if (saleRaw === '' || saleRaw === null || saleRaw === undefined) {
                    batchSalePrices[batch.id] = '';
                } else {
                    batchSalePrices[batch.id] = formatPrice(precisionDecimal(saleRaw));
                }
            },
            onFinish: () => {
                batchPricingSaving[batch.id] = false;
            },
        },
    );
}

const totalStock = computed(() =>
    batches.value.reduce((sum, b) => sum + Number(b.quantity_on_hand ?? 0), 0),
);

const showPiecesPerStrip = computed(() => {
    if (!usesStripForType.value) {
        return false;
    }
    const units = form.units.map((r) => r.sell_unit);
    return units.includes('piece') || units.includes('strip') || form.base_unit === 'piece' || form.base_unit === 'strip';
});

const showStripsPerBox = computed(() => {
    if (!usesStripForType.value) {
        return false;
    }
    const units = form.units.map((r) => r.sell_unit);
    return form.base_unit === 'strip' && units.includes('strip') && units.includes('box');
});

const showBoxesPerCarton = computed(() => {
    const units = form.units.map((r) => r.sell_unit);
    return units.includes('carton') && units.includes('box');
});

const cartonConversionPreview = computed(() => {
    const bpc = Number(form.boxes_per_carton);
    const boxRow = form.units.find((r) => r.sell_unit === 'box');
    const boxFactor = boxRow ? unitRowFactor(boxRow) : 0;
    if (!bpc || bpc <= 0 || boxFactor <= 0) {
        return null;
    }
    return formatConversionFactor(bpc * boxFactor);
});

const priceAutoFillHint = computed(() => {
    const otherUnits = form.units
        .map((r) => r.sell_unit)
        .filter((u) => u !== form.base_unit);
    if (otherUnits.length === 0) {
        return '';
    }
    const labels = otherUnits.map((u) => unitLabel(u)).join(', ');

    return `${unitLabel(form.base_unit)} purchase/sale prices auto-fill ${labels} (using conversion factors).`;
});

const totalStockPieces = computed(() => {
    const pps = Number(form.pieces_per_strip);
    if (!pps || pps <= 0) {
        return null;
    }
    if (form.base_unit === 'strip') {
        return totalStock.value * pps;
    }
    if (form.base_unit === 'piece') {
        return totalStock.value;
    }
    return null;
});

const imagePreviewUrl = ref(null);

const form = useForm({
    name: existing?.name ?? '',
    generic_name: existing?.generic_name ?? '',
    strength: existing?.strength ?? '',
    sku: existing?.sku ?? '',
    barcode: existing?.barcode ?? '',
    wholesale_price:
        existing?.wholesale_price != null && existing.wholesale_price !== '' ? existing.wholesale_price : '',
    vat_percent: existing?.vat_percent != null && existing.vat_percent !== '' ? existing.vat_percent : '',
    default_markup_percent:
        existing?.default_markup_percent != null && existing.default_markup_percent !== ''
            ? existing.default_markup_percent
            : '',
    short_description: existing?.short_description ?? '',
    image: null,
    remove_image: false,
    category_id: existing?.category?.id ?? null,
    manufacturer_id: existing?.manufacturer?.id ?? null,
    storage_location_id: existing?.storage_location_id ?? existing?.storage_location?.id ?? null,
    opening_storage_location_id: null,
    product_type: existing?.product_type ?? 'tablet',
    base_unit: existing?.base_unit ?? 'strip',
    pieces_per_strip:
        existing?.pieces_per_strip != null && existing.pieces_per_strip !== ''
            ? Number(existing.pieces_per_strip)
            : '',
    strips_per_box: initialStripsPerBox(initialUnits()),
    boxes_per_carton: initialBoxesPerCarton(initialUnits()),
    units: initialUnits(),
    min_stock: existing?.min_stock ?? 0,
    is_active: existing?.is_active ?? true,
    opening_batch_no: '',
    opening_expiry_date: '',
    opening_quantity: null,
    stock_adjustment: null,
    stock_adjust_batch_id: null,
    stock_adjust_batch_no: '',
});

const availableSellUnits = computed(() =>
    sellUnitsForProductType(
        form.product_type,
        allSellUnits.value,
        [form.base_unit, ...form.units.map((r) => r.sell_unit)],
        stripProductTypes.value,
    ),
);

const usesStripForType = computed(() => usesStripProductType(form.product_type, stripProductTypes.value));

function clearStripConversionFields() {
    form.pieces_per_strip = '';
    form.strips_per_box = '';
    form.boxes_per_carton = '';
}

function ensureBaseUnitRow() {
    if (!form.units.some((r) => r.sell_unit === form.base_unit)) {
        form.units.unshift({
            sell_unit: form.base_unit,
            conversion_factor: 1,
            purchase_price: '0',
            sale_price: '0',
            is_default: true,
        });
    }
}

function applyBaseUnitAsDefault() {
    ensureBaseUnitRow();
    form.units.forEach((row) => {
        row.is_default = row.sell_unit === form.base_unit;
        if (row.sell_unit === form.base_unit) {
            row.conversion_factor = 1;
        }
    });
}

function onBaseUnitChange() {
    applyBaseUnitAsDefault();
    syncPiecesPerStripToUnits();
    syncStripsPerBoxToUnits();
    syncBoxesPerCartonToUnits();
    syncDerivedUnitPricesFromBase();
}

function unitRowFactor(row) {
    if (row.sell_unit === form.base_unit) {
        return 1;
    }
    const factor = Number(row.conversion_factor);
    return Number.isNaN(factor) || factor <= 0 ? 0 : factor;
}

function unitRelationLabel(row) {
    const factor = unitRowFactor(row);
    const sellUnit = unitLabel(row.sell_unit);
    const baseUnit = unitLabel(form.base_unit);

    if (row.sell_unit === form.base_unit || factor === 1) {
        return t('catalog.base_unit_relation');
    }

    if (row.sell_unit === 'piece' && form.base_unit === 'strip' && factor > 0 && factor < 1) {
        return t('catalog.piece_unit_relation', {
            pieces: formatQty(1 / factor),
            base: baseUnit,
        });
    }

    return t('catalog.sell_unit_relation', {
        sell_unit: sellUnit,
        qty: formatQty(factor),
        base: baseUnit,
    });
}

const CONVERSION_FACTOR_DECIMALS = 4;

function formatConversionFactor(value) {
    const n = Number(value);
    if (Number.isNaN(n) || n <= 0) {
        return 0.0001;
    }
    const scale = 10 ** CONVERSION_FACTOR_DECIMALS;
    return Math.round(n * scale) / scale;
}

function formatDerivedPrice(value) {
    if (Number.isNaN(value) || value < 0) {
        return '0';
    }

    return formatPrice(value);
}

function onUnitPriceBlur(row, field) {
    const n = Number(row[field]);
    if (Number.isNaN(n) || n < 0) {
        row[field] = '0';
        return;
    }
    row[field] = formatPrice(n);
}

function syncDerivedUnitPricesFromBase() {
    const anchorRow = form.units.find((r) => r.sell_unit === form.base_unit);
    if (!anchorRow) {
        return;
    }

    const anchorFactor = unitRowFactor(anchorRow);
    if (anchorFactor <= 0) {
        return;
    }

    const anchorPurchase = precisionDecimal(anchorRow.purchase_price);
    const anchorSale = precisionDecimal(anchorRow.sale_price);
    const hasPurchase = anchorRow.purchase_price !== '' && !Number.isNaN(anchorPurchase);
    const hasSale = anchorRow.sale_price !== '' && !Number.isNaN(anchorSale);

    const pricePerBase = hasPurchase ? anchorPurchase / anchorFactor : null;
    const salePerBase = hasSale ? anchorSale / anchorFactor : null;

    form.units.forEach((row) => {
        if (row.sell_unit === form.base_unit) {
            return;
        }
        const factor = unitRowFactor(row);
        if (factor <= 0) {
            return;
        }
        if (pricePerBase !== null) {
            row.purchase_price = formatDerivedPrice(precisionDecimal(pricePerBase * factor));
        }
        if (salePerBase !== null) {
            row.sale_price = formatDerivedPrice(precisionDecimal(salePerBase * factor));
        }
    });
}

function onBaseUnitPriceInput(row) {
    if (row.sell_unit !== form.base_unit) {
        return;
    }
    syncDerivedUnitPricesFromBase();
}

function onUnitConversionInput(row) {
    if (row.sell_unit === 'box' && form.base_unit === 'strip') {
        const factor = unitRowFactor(row);
        if (factor > 0) {
            form.strips_per_box = factor;
        }
        syncBoxesPerCartonToUnits();
    }
    syncDerivedUnitPricesFromBase();
}

function onConversionFactorBlur(row) {
    if (row.sell_unit === form.base_unit) {
        return;
    }
    row.conversion_factor = formatConversionFactor(row.conversion_factor);
}

function syncPiecesPerStripToUnits() {
    const pps = Number(form.pieces_per_strip);
    if (!pps || pps <= 0) {
        return;
    }

    if (form.base_unit === 'strip') {
        let pieceRow = form.units.find((r) => r.sell_unit === 'piece');
        const pieceFactor = formatConversionFactor(1 / pps);
        if (!pieceRow) {
            form.units.push({
                sell_unit: 'piece',
                conversion_factor: pieceFactor,
                purchase_price: '0',
                sale_price: '0',
                is_default: false,
            });
        } else {
            pieceRow.conversion_factor = pieceFactor;
        }
    } else if (form.base_unit === 'piece') {
        let stripRow = form.units.find((r) => r.sell_unit === 'strip');
        const stripFactor = formatConversionFactor(pps);
        if (!stripRow) {
            form.units.push({
                sell_unit: 'strip',
                conversion_factor: stripFactor,
                purchase_price: '0',
                sale_price: '0',
                is_default: false,
            });
        } else {
            stripRow.conversion_factor = stripFactor;
        }
    }

    syncDerivedUnitPricesFromBase();
}

function syncStripsPerBoxToUnits() {
    const spb = Number(form.strips_per_box);
    if (!spb || spb <= 0 || form.base_unit !== 'strip') {
        return;
    }

    const boxFactor = formatConversionFactor(spb);
    let boxRow = form.units.find((r) => r.sell_unit === 'box');
    if (!boxRow) {
        form.units.push({
            sell_unit: 'box',
            conversion_factor: boxFactor,
            purchase_price: '0',
            sale_price: '0',
            is_default: false,
        });
    } else {
        boxRow.conversion_factor = boxFactor;
    }

    syncDerivedUnitPricesFromBase();
    syncBoxesPerCartonToUnits();
}

function syncBoxesPerCartonToUnits() {
    const bpc = Number(form.boxes_per_carton);
    if (!bpc || bpc <= 0) {
        return;
    }

    const boxRow = form.units.find((r) => r.sell_unit === 'box');
    const boxFactor = boxRow ? unitRowFactor(boxRow) : 0;
    if (boxFactor <= 0) {
        return;
    }

    const cartonFactor = formatConversionFactor(bpc * boxFactor);
    let cartonRow = form.units.find((r) => r.sell_unit === 'carton');
    if (!cartonRow) {
        form.units.push({
            sell_unit: 'carton',
            conversion_factor: cartonFactor,
            purchase_price: '0',
            sale_price: '0',
            is_default: false,
        });
    } else {
        cartonRow.conversion_factor = cartonFactor;
    }

    syncDerivedUnitPricesFromBase();
}

watch(
    () => form.units.map((r) => r.sell_unit).join(','),
    () => {
        if (showPiecesPerStrip.value && form.pieces_per_strip) {
            syncPiecesPerStripToUnits();
        }
        if (showStripsPerBox.value && form.strips_per_box) {
            syncStripsPerBoxToUnits();
        }
        if (showBoxesPerCarton.value && form.boxes_per_carton) {
            syncBoxesPerCartonToUnits();
        }
    },
);

watch(
    () => form.product_type,
    (type) => {
        if (!usesStripProductType(type, stripProductTypes.value) && form.base_unit === 'strip') {
            form.base_unit = defaultBaseUnitForProductType(type, stripProductTypes.value);
            form.units = buildDefaultUnits(form.base_unit);
            clearStripConversionFields();
            applyBaseUnitAsDefault();
            return;
        }

        const defaultBase = defaultBaseUnitForProductType(type, stripProductTypes.value);
        if (!availableSellUnits.value.includes(form.base_unit)) {
            form.base_unit = defaultBase;
            form.units = buildDefaultUnits(defaultBase);
            clearStripConversionFields();
            applyBaseUnitAsDefault();
        }
    },
);

onMounted(() => {
    applyBaseUnitAsDefault();
});

function setDefault(idx) {
    const unit = form.units[idx]?.sell_unit;
    if (!unit) {
        return;
    }
    form.base_unit = unit;
    applyBaseUnitAsDefault();
    syncPiecesPerStripToUnits();
    syncStripsPerBoxToUnits();
    syncBoxesPerCartonToUnits();
    syncDerivedUnitPricesFromBase();
}

function addUnitRow() {
    const used = form.units.map((r) => r.sell_unit);
    const next = availableSellUnits.value.find((u) => !used.includes(u)) ?? 'piece';
    form.units.push({
        sell_unit: next,
        conversion_factor: next === form.base_unit ? 1 : 1,
        purchase_price: '0',
        sale_price: '0',
        is_default: false,
    });
}

function removeUnitRow(idx) {
    if (form.units[idx].sell_unit === form.base_unit) {
        return;
    }
    form.units.splice(idx, 1);
    applyBaseUnitAsDefault();
}

function normalizePiecesPerStripForSubmit(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }
    const n = Number(value);
    if (Number.isNaN(n) || n <= 0) {
        return null;
    }
    return n;
}

function normalizeBoxesPerCartonForSubmit(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }
    const n = Number(value);
    if (Number.isNaN(n) || n <= 0) {
        return null;
    }
    return n;
}

function normalizeStripsPerBoxForSubmit(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }
    const n = Number(value);
    if (Number.isNaN(n) || n <= 0) {
        return null;
    }
    return n;
}

function onImageChange(event) {
    const file = event.target.files?.[0] ?? null;
    form.image = file;
    form.remove_image = false;
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value);
    }
    imagePreviewUrl.value = file ? URL.createObjectURL(file) : null;
}

onUnmounted(() => {
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value);
    }
});

function normalizeOptionalNumberForSubmit(value) {
    if (value === '' || value === null || value === undefined) {
        return null;
    }
    const n = Number(value);
    if (Number.isNaN(n) || n < 0) {
        return null;
    }
    return n;
}

function buildPayload() {
    applyBaseUnitAsDefault();
    syncPiecesPerStripToUnits();
    syncStripsPerBoxToUnits();
    syncBoxesPerCartonToUnits();
    syncDerivedUnitPricesFromBase();

    const payload = {
        ...form.data(),
        sku: existing ? form.sku : null,
        pieces_per_strip: normalizePiecesPerStripForSubmit(form.pieces_per_strip),
        strips_per_box: normalizeStripsPerBoxForSubmit(form.strips_per_box),
        boxes_per_carton: normalizeBoxesPerCartonForSubmit(form.boxes_per_carton),
        wholesale_price: wholesaleEnabled.value
            ? normalizeOptionalNumberForSubmit(form.wholesale_price)
            : null,
        vat_percent: advancedCatalogEnabled.value ? normalizeOptionalNumberForSubmit(form.vat_percent) : null,
        generic_name: advancedCatalogEnabled.value ? form.generic_name?.trim() || null : null,
        strength: advancedCatalogEnabled.value ? form.strength?.trim() || null : null,
        short_description: advancedCatalogEnabled.value ? form.short_description?.trim() || null : null,
        units: form.units.map((row) => ({
            sell_unit: row.sell_unit,
            conversion_factor:
                row.sell_unit === form.base_unit ? 1 : formatConversionFactor(row.conversion_factor),
            purchase_price: precisionDecimal(row.purchase_price),
            sale_price: precisionDecimal(row.sale_price),
            is_default: row.is_default,
        })),
    };

    if (!existing) {
        delete payload.stock_adjustment;
        delete payload.stock_adjust_batch_id;
        delete payload.stock_adjust_batch_no;
    } else {
        delete payload.opening_batch_no;
        delete payload.opening_expiry_date;
        delete payload.opening_quantity;
        delete payload.opening_storage_location_id;
        payload.batch_locations = batches.value.map((b) => ({
            id: b.id,
            storage_location_id: batchLocationEdits[b.id] ?? null,
        }));
        if (payload.stock_adjustment === null || payload.stock_adjustment === '') {
            delete payload.stock_adjustment;
            delete payload.stock_adjust_batch_id;
            delete payload.stock_adjust_batch_no;
        }
    }

    return payload;
}

function submit() {
    const payload = buildPayload();

    if (existing) {
        if (form.image) {
            form.transform(() => ({
                ...payload,
                image: form.image,
                remove_image: form.remove_image,
                _method: 'put',
            })).post(`/products/${existing.id}`, { forceFormData: true });
        } else {
            form.transform(() => ({
                ...payload,
                remove_image: form.remove_image,
            })).put(`/products/${existing.id}`);
        }
    } else if (form.image) {
        form.transform(() => ({
            ...payload,
            image: form.image,
        })).post('/products', { forceFormData: true });
    } else {
        form.transform(() => payload).post('/products');
    }
}
</script>
