<template>
    <PlatformShellLayout :page-title="plan ? t('common.edit') : t('platform.new_plan')">
        <Head :title="plan ? 'Edit plan' : 'New plan'" />
        <h1 class="h4 mb-3">{{ plan ? t('common.edit') : t('platform.new_plan') }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">Name</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Slug</label>
                <input v-model="form.slug" class="form-control" required :disabled="!!plan" />
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.plan_price_bdt') }}</label>
                    <input v-model.number="priceTaka" type="number" min="0" step="0.01" class="form-control" required />
                    <div class="form-text">
                        {{ t('platform.plan_price_preview') }}: {{ formatMoney(priceTaka, { currency }) }}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Trial days</label>
                    <input v-model.number="form.trial_days" type="number" min="0" class="form-control" required />
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input id="fpos" v-model="form.features.pos" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="fpos">POS</label>
                </div>
                <div class="form-check">
                    <input id="freports" v-model="form.features.reports" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="freports">Reports</label>
                </div>
                <div class="form-check">
                    <input id="fwholesale" v-model="form.features.wholesale_pricing" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="fwholesale">{{ t('platform.feature_wholesale_pricing') }}</label>
                </div>
                <p class="form-text small mb-2">{{ t('platform.feature_wholesale_pricing_help') }}</p>
                <div class="form-check">
                    <input id="fmarkup" v-model="form.features.markup_pricing" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="fmarkup">{{ t('platform.feature_markup_pricing') }}</label>
                </div>
                <p class="form-text small mb-2">{{ t('platform.feature_markup_pricing_help') }}</p>
                <div class="form-check">
                    <input id="fbulkimport" v-model="form.features.bulk_import" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="fbulkimport">{{ t('platform.feature_bulk_import') }}</label>
                </div>
                <p class="form-text small mb-2">{{ t('platform.feature_bulk_import_help') }}</p>
                <div class="form-check">
                    <input id="fadvcatalog" v-model="form.features.advanced_catalog" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="fadvcatalog">{{ t('platform.feature_advanced_catalog') }}</label>
                </div>
                <p class="form-text small mb-2">{{ t('platform.feature_advanced_catalog_help') }}</p>
                <div class="form-check">
                    <input id="fmultibranch" v-model="form.features.multi_branch" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="fmultibranch">{{ t('platform.feature_multi_branch') }}</label>
                </div>
                <p class="form-text small mb-2">{{ t('platform.feature_multi_branch_help') }}</p>
                <div class="form-check">
                    <input
                        id="fsupplierledger"
                        v-model="form.features.supplier_branch_ledger"
                        type="checkbox"
                        class="form-check-input"
                        :disabled="!form.features.multi_branch"
                    />
                    <label class="form-check-label" for="fsupplierledger">{{ t('platform.feature_supplier_branch_ledger') }}</label>
                </div>
                <p class="form-text small mb-2">{{ t('platform.feature_supplier_branch_ledger_help') }}</p>
                <div class="form-check">
                    <input id="femployeemgmt" v-model="form.features.employee_management" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="femployeemgmt">{{ t('platform.feature_employee_management') }}</label>
                </div>
                <p class="form-text small mb-2">{{ t('platform.feature_employee_management_help') }}</p>
                <div class="form-check">
                    <input id="fattendance" v-model="form.features.attendance" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="fattendance">{{ t('platform.feature_attendance') }}</label>
                </div>
                <p class="form-text small mb-2">{{ t('platform.feature_attendance_help') }}</p>
                <div class="form-check">
                    <input id="fhrpayroll" v-model="form.features.hr_payroll" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="fhrpayroll">{{ t('platform.feature_hr_payroll') }}</label>
                </div>
                <p class="form-text small mb-2">{{ t('platform.feature_hr_payroll_help') }}</p>
                <div class="form-check">
                    <input id="fbarcodecamera" v-model="form.features.barcode_camera_scan" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="fbarcodecamera">{{ t('platform.feature_barcode_camera_scan') }}</label>
                </div>
                <p class="form-text small mb-2">{{ t('platform.feature_barcode_camera_scan_help') }}</p>
                <div class="form-check">
                    <input id="fpharmacynotes" v-model="form.features.pharmacy_notes" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="fpharmacynotes">{{ t('platform.feature_pharmacy_notes') }}</label>
                </div>
                <p class="form-text small mb-2">{{ t('platform.feature_pharmacy_notes_help') }}</p>
                <div class="form-check">
                    <input id="fpackagesales" v-model="form.features.package_sales" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="fpackagesales">Package sales</label>
                </div>
                <p class="form-text small mb-3">Allow tenants on this plan to enable package templates and package sale checkout.</p>

                <h2 class="h6 mb-2">{{ t('platform.feature_theme_section') }}</h2>
                <p class="form-text small mb-2">{{ t('platform.feature_theme_templates_help') }}</p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <div v-for="tmpl in themeTemplates" :key="tmpl.id" class="form-check border rounded px-3 py-2 mb-0">
                        <input
                            :id="'theme_' + tmpl.id"
                            v-model="form.features.theme_templates"
                            type="checkbox"
                            class="form-check-input"
                            :value="tmpl.id"
                        />
                        <label class="form-check-label d-flex align-items-center gap-2" :for="'theme_' + tmpl.id">
                            <span class="theme-swatch" :style="{ background: tmpl.primary }"></span>
                            {{ t(tmpl.label_key) }}
                        </label>
                    </div>
                </div>
                <div class="form-check">
                    <input id="fcustomprimary" v-model="form.features.allow_custom_primary" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="fcustomprimary">{{ t('platform.feature_allow_custom_primary') }}</label>
                </div>
                <p class="form-text small mb-0">{{ t('platform.feature_allow_custom_primary_help') }}</p>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.limit_max_products') }}</label>
                    <input v-model="form.limits.max_products" type="number" min="0" step="1" class="form-control" :placeholder="t('platform.limit_unlimited_placeholder')" />
                    <div class="form-text">{{ t('platform.limit_max_products_help') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.limit_max_import_rows') }}</label>
                    <input v-model="form.limits.max_import_rows" type="number" min="0" step="1" class="form-control" :placeholder="t('platform.limit_unlimited_placeholder')" />
                    <div class="form-text">{{ t('platform.limit_max_import_rows_help') }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.limit_max_branches') }}</label>
                    <input v-model="form.limits.max_branches" type="number" min="1" step="1" class="form-control" />
                    <div class="form-text">{{ t('platform.limit_max_branches_help') }}</div>
                </div>
            </div>
            <div v-if="form.features.bulk_import" class="mb-3">
                <label class="form-label">{{ t('platform.import_preset_label') }}</label>
                <select v-model="form.features.import_preset" class="form-select">
                    <option v-for="preset in importPresets" :key="preset" :value="preset">
                        {{ t('platform.import_preset_' + preset) }}
                    </option>
                </select>
                <div class="form-text">{{ t('platform.import_preset_help') }}</div>
            </div>
            <div v-if="form.features.bulk_import && form.features.import_preset === 'custom'" class="mb-3">
                <label class="form-label">{{ t('platform.import_columns_label') }}</label>
                <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto">
                    <div v-for="col in importColumns" :key="col" class="form-check">
                        <input
                            :id="'col_' + col"
                            v-model="form.features.import_columns"
                            type="checkbox"
                            class="form-check-input"
                            :value="col"
                            :disabled="col === 'name'"
                        />
                        <label class="form-check-label" :for="'col_' + col">{{ formatColumnName(col) }}</label>
                    </div>
                </div>
                <div class="form-text">{{ t('platform.import_columns_help') }}</div>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
            <Link href="/platform/plans" class="btn btn-link">{{ t('common.cancel') }}</Link>
        </form>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    plan: { type: Object, default: null },
    currency: { type: String, default: 'BDT' },
    importPresets: { type: Array, default: () => ['basic', 'standard', 'pro', 'custom'] },
    importColumns: { type: Array, default: () => [] },
    themeTemplates: { type: Array, default: () => [] },
});

function formatColumnName(col) {
    return col.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

const { t } = useLocale();
const { formatMoney } = useMoney({ currency: props.currency });

const priceTaka = ref(Number(props.plan?.price_cents ?? 0) / 100);

const form = useForm({
    name: props.plan?.name ?? '',
    slug: props.plan?.slug ?? '',
    price_cents: props.plan?.price_cents ?? 0,
    trial_days: props.plan?.trial_days ?? 14,
    features: {
        pos: props.plan?.features?.pos ?? true,
        reports: props.plan?.features?.reports ?? true,
        wholesale_pricing: props.plan?.features?.wholesale_pricing ?? false,
        markup_pricing: props.plan?.features?.markup_pricing ?? false,
        bulk_import: props.plan?.features?.bulk_import ?? true,
        advanced_catalog: props.plan?.features?.advanced_catalog ?? true,
        multi_branch: props.plan?.features?.multi_branch ?? false,
        supplier_branch_ledger: props.plan?.features?.supplier_branch_ledger ?? false,
        employee_management: props.plan?.features?.employee_management ?? true,
        attendance: props.plan?.features?.attendance ?? false,
        hr_payroll: props.plan?.features?.hr_payroll ?? false,
        barcode_camera_scan: props.plan?.features?.barcode_camera_scan ?? false,
        pharmacy_notes: props.plan?.features?.pharmacy_notes ?? false,
        package_sales: props.plan?.features?.package_sales ?? false,
        allow_custom_primary: props.plan?.features?.allow_custom_primary ?? false,
        theme_templates: props.plan?.features?.theme_templates ?? ['classic_blue'],
        import_preset: props.plan?.features?.import_preset ?? 'pro',
        import_columns: props.plan?.features?.import_columns ?? ['name'],
    },
    limits: {
        max_products: props.plan?.limits?.max_products ?? null,
        max_import_rows: props.plan?.limits?.max_import_rows ?? null,
        max_branches: props.plan?.limits?.max_branches ?? 1,
    },
});

function submit() {
    form.price_cents = Math.round(Number(priceTaka.value || 0) * 100);

    if (!form.features.theme_templates?.length) {
        form.features.theme_templates = ['classic_blue'];
    }

    if (props.plan) {
        form.put(`/platform/plans/${props.plan.id}`);
    } else {
        form.post('/platform/plans');
    }
}
</script>

<style scoped>
.theme-swatch {
    display: inline-block;
    width: 1rem;
    height: 1rem;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, 0.15);
}
</style>
