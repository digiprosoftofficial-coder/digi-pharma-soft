<template>
    <TenantShellLayout page-title="Bulk import">
        <Head title="Bulk import" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <h1 class="h4 mb-3">Import products from CSV</h1>
        <p class="text-muted small mb-1">
            Upload a CSV with the columns below. <code>name</code> is required; <code>sku</code> is optional (auto-generated if empty).
            Use existing <code>category_slug</code> and <code>storage_location_code</code> values from your catalog.
        </p>
        <div class="small text-muted mb-3">
            <span class="d-block fw-semibold text-body">
                Your plan: <span class="badge text-bg-primary">{{ presetLabel }}</span>
                <span class="text-muted fw-normal ms-2">({{ csvColumns.length }} columns)</span>
            </span>
            <span class="d-block mt-1">{{ columnList }}</span>
        </div>
        <ul v-if="maxImportRows !== null || remainingProducts !== null" class="small text-muted mb-3 ps-3">
            <li v-if="maxImportRows !== null">Your plan allows up to <strong>{{ maxImportRows }}</strong> rows per upload.</li>
            <li v-if="remainingProducts !== null">You can still add <strong>{{ remainingProducts }}</strong> more products on your plan.</li>
        </ul>
        <a href="/catalog/import/sample" class="btn btn-sm btn-outline-secondary mb-3">Download sample CSV</a>

        <form class="card border-0 shadow-sm card-body mb-3" @submit.prevent="runPreview">
            <label class="form-label">CSV file</label>
            <input ref="fileInput" type="file" accept=".csv,text/csv" class="form-control mb-2" required @change="onFile" />
            <button type="submit" class="btn btn-outline-primary btn-sm" :disabled="!selectedFile || previewForm.processing">
                Preview
            </button>
        </form>

        <div v-if="editablePreview" class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-semibold">
                            Preview — {{ editablePreview.valid_count }} valid, {{ editablePreview.error_count }} with errors
                        </div>
                        <p class="small text-muted mb-0 mt-1">{{ t('catalog.import_edit_hint') }}</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            :disabled="revalidateForm.processing"
                            @click="runRevalidate"
                        >
                            {{ t('catalog.import_revalidate') }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">#</th>
                            <th v-for="h in editablePreview.headers" :key="h" class="text-nowrap">
                                {{ headerLabel(h) }}
                            </th>
                            <th class="text-nowrap">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in previewRows"
                            :key="row.row"
                            :class="rowErrors(row).length ? 'table-danger' : ''"
                        >
                            <td class="text-muted">{{ row.row }}</td>
                            <td v-for="h in editablePreview.headers" :key="h" class="p-1" style="min-width: 7rem">
                                <input
                                    v-model="row.raw[h]"
                                    type="text"
                                    class="form-control form-control-sm"
                                    :class="{ 'is-invalid': rowErrors(row).length && isLikelyErrorField(row, h) }"
                                />
                            </td>
                            <td>
                                <span v-if="rowErrors(row).length" class="text-danger small">{{ rowErrors(row).join('; ') }}</span>
                                <span v-else class="text-success small">OK</span>
                            </td>
                        </tr>
                        <tr v-if="!previewRows.length">
                            <td :colspan="(editablePreview.headers?.length ?? 0) + 2" class="text-muted text-center py-3">
                                No data rows in file.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="editablePreview.rows.length > previewLimit" class="card-footer small text-muted">
                Showing first {{ previewLimit }} of {{ editablePreview.rows.length }} rows.
            </div>
            <div class="card-footer bg-white">
                <div v-if="importForm.errors.rows || importForm.errors.file" class="alert alert-danger small py-2">
                    {{ importForm.errors.rows || importForm.errors.file }}
                </div>
                <p class="small text-muted mb-2">{{ t('catalog.import_valid_rows_only') }}</p>
                <div class="form-check mb-2">
                    <input id="skip" v-model="importForm.skip_duplicates" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="skip">Skip duplicate SKUs</label>
                </div>
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="importForm.processing || editablePreview.valid_count < 1"
                    @click="runImport"
                >
                    {{ t('catalog.import_import_rows') }}
                    <span v-if="editablePreview.valid_count > 0" class="ms-1">({{ editablePreview.valid_count }})</span>
                </button>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    preview: { type: Object, default: null },
    csvColumns: { type: Array, default: () => [] },
    importPreset: { type: String, default: 'pro' },
    maxImportRows: { type: Number, default: null },
    remainingProducts: { type: Number, default: null },
});

const { t } = useLocale();

const presetLabels = {
    basic: 'Basic',
    standard: 'Standard',
    pro: 'Pro',
    custom: 'Custom',
};

const presetLabel = computed(() => presetLabels[props.importPreset] || 'Pro');

const columnList = computed(() =>
    (props.csvColumns.length ? props.csvColumns : []).join(', '),
);

const previewLimit = 50;

const fileInput = ref(null);
const selectedFile = ref(null);
const editablePreview = ref(null);

const previewForm = useForm({ file: null });
const revalidateForm = useForm({ headers: [], rows: [] });
const importForm = useForm({ skip_duplicates: true, headers: [], rows: [] });

const previewRows = computed(() => editablePreview.value?.rows?.slice(0, previewLimit) ?? []);

watch(
    () => props.preview,
    (preview) => {
        if (!preview) {
            return;
        }
        editablePreview.value = clonePreview(preview);
    },
    { immediate: true },
);

function clonePreview(preview) {
    return {
        headers: [...(preview.headers ?? [])],
        valid_count: preview.valid_count ?? 0,
        error_count: preview.error_count ?? 0,
        rows: (preview.rows ?? []).map((row) => ({
            row: row.row,
            raw: { ...(row.raw ?? {}) },
            errors: [...(row.errors ?? [])],
        })),
    };
}

function headerLabel(key) {
    if (!key) {
        return '';
    }
    return key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function rowErrors(row) {
    return row.errors ?? [];
}

function isLikelyErrorField(row, header) {
    const errors = rowErrors(row);
    const label = header.replace(/_/g, ' ');
    return errors.some((e) => e.toLowerCase().includes(header) || e.toLowerCase().includes(label));
}

function onFile(e) {
    selectedFile.value = e.target.files?.[0] ?? null;
}

function runPreview() {
    previewForm.file = selectedFile.value;
    previewForm.post('/catalog/import/preview', { forceFormData: true, preserveScroll: true });
}

function payloadFromEditable() {
    return {
        headers: editablePreview.value.headers,
        rows: editablePreview.value.rows.map((row) => ({
            row: row.row,
            raw: row.raw,
        })),
    };
}

function runRevalidate() {
    const payload = payloadFromEditable();
    revalidateForm.headers = payload.headers;
    revalidateForm.rows = payload.rows;
    revalidateForm.post('/catalog/import/revalidate', { preserveScroll: true });
}

function runImport() {
    const payload = payloadFromEditable();
    importForm.headers = payload.headers;
    importForm.rows = payload.rows;
    importForm.post('/catalog/import', { preserveScroll: true });
}
</script>
