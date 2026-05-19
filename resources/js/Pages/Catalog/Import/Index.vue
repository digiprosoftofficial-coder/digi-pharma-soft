<template>
    <TenantShellLayout page-title="Bulk import">
        <Head title="Bulk import" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <h1 class="h4 mb-3">Import products from CSV</h1>
        <p class="text-muted small">
            Upload a CSV with columns: name, sku, barcode, product_type, base_unit, category_slug, manufacturer_name,
            purchase_price, sale_price, min_stock, is_active.
        </p>
        <a href="/catalog/import/sample" class="btn btn-sm btn-outline-secondary mb-3">Download sample CSV</a>

        <form class="card border-0 shadow-sm card-body mb-3" @submit.prevent="runPreview">
            <label class="form-label">CSV file</label>
            <input ref="fileInput" type="file" accept=".csv,text/csv" class="form-control mb-2" required @change="onFile" />
            <button type="submit" class="btn btn-outline-primary btn-sm" :disabled="!selectedFile || previewForm.processing">
                Preview
            </button>
        </form>

        <form v-if="selectedFile" class="card border-0 shadow-sm card-body mb-3" @submit.prevent="runImport">
            <div class="form-check mb-2">
                <input id="skip" v-model="importForm.skip_duplicates" type="checkbox" class="form-check-input" />
                <label class="form-check-label" for="skip">Skip duplicate SKUs</label>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="importForm.processing">Import products</button>
        </form>

        <div v-if="preview" class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <div class="fw-semibold">Preview — {{ preview.valid_count }} valid, {{ preview.error_count }} with errors</div>
                <p v-if="preview.headers?.length" class="small text-muted mb-0 mt-1">
                    Columns detected: {{ preview.headers.map(headerLabel).join(', ') }}
                </p>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">#</th>
                            <th v-for="h in preview.headers" :key="h" class="text-nowrap">
                                {{ headerLabel(h) }}
                            </th>
                            <th class="text-nowrap">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in previewRows"
                            :key="row.row"
                            :class="row.errors.length ? 'table-danger' : ''"
                        >
                            <td class="text-muted">{{ row.row }}</td>
                            <td v-for="h in preview.headers" :key="h" class="small text-nowrap">
                                {{ cellValue(row, h) }}
                            </td>
                            <td>
                                <span v-if="row.errors.length" class="text-danger small">{{ row.errors.join('; ') }}</span>
                                <span v-else class="text-success small">OK</span>
                            </td>
                        </tr>
                        <tr v-if="!previewRows.length">
                            <td :colspan="(preview.headers?.length ?? 0) + 2" class="text-muted text-center py-3">
                                No data rows in file.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="preview.rows.length > previewLimit" class="card-footer small text-muted">
                Showing first {{ previewLimit }} of {{ preview.rows.length }} rows.
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ preview: { type: Object, default: null } });

const previewLimit = 50;

const fileInput = ref(null);
const selectedFile = ref(null);

const previewForm = useForm({ file: null });
const importForm = useForm({ file: null, skip_duplicates: true });

const previewRows = computed(() => props.preview?.rows?.slice(0, previewLimit) ?? []);

function headerLabel(key) {
    if (!key) {
        return '';
    }
    return key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function cellValue(row, header) {
    const value = row.raw?.[header];
    if (value === undefined || value === null || value === '') {
        return '—';
    }
    return value;
}

function onFile(e) {
    selectedFile.value = e.target.files?.[0] ?? null;
}

function runPreview() {
    previewForm.file = selectedFile.value;
    previewForm.post('/catalog/import/preview', { forceFormData: true, preserveScroll: true });
}

function runImport() {
    importForm.file = selectedFile.value;
    importForm.post('/catalog/import', { forceFormData: true });
}
</script>
