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
            <div class="card-header bg-white fw-semibold">
                Preview — {{ preview.valid_count }} valid, {{ preview.error_count }} with errors
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Row</th>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in preview.rows.slice(0, 20)" :key="row.row">
                            <td>{{ row.row }}</td>
                            <td>{{ row.data.name }}</td>
                            <td>{{ row.data.sku }}</td>
                            <td>
                                <span v-if="row.errors.length" class="text-danger small">{{ row.errors.join('; ') }}</span>
                                <span v-else class="text-success small">OK</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ preview: { type: Object, default: null } });

const fileInput = ref(null);
const selectedFile = ref(null);

const previewForm = useForm({ file: null });
const importForm = useForm({ file: null, skip_duplicates: true });

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
