<template>
    <PlatformShellLayout :page-title="t('platform.master_import_title')">
        <Head :title="t('platform.master_import_title')" />

        <Link href="/platform/master-catalog" class="small text-decoration-none text-teal">← {{ t('platform.master_title') }}</Link>

        <section class="import-hero rounded-4 mt-2 mb-4 p-4 p-md-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-7">
                    <p class="small text-uppercase fw-semibold text-teal mb-2 letter-space">{{ t('platform.master_import_eyebrow') }}</p>
                    <h1 class="h2 fw-semibold mb-2">{{ t('platform.master_import_title') }}</h1>
                    <p class="text-muted mb-0" style="max-width: 34rem">{{ t('platform.master_import_sub') }}</p>
                </div>
                <div class="col-lg-5">
                    <div class="import-side-card">
                        <div class="small text-muted mb-1">{{ t('platform.master_stat_total') }}</div>
                        <div class="display-6 fw-bold text-teal mb-2">{{ stats.total }}</div>
                        <a href="/platform/master-catalog/import/sample" class="btn btn-sm btn-outline-teal">
                            {{ t('platform.master_sample_csv') }}
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-lg-5">
                <form class="card border-0 shadow-sm h-100" @submit.prevent="runPreview">
                    <div class="card-body p-4">
                        <h2 class="h6 mb-3">{{ t('platform.master_upload_step') }}</h2>
                        <label
                            class="dropzone d-block text-center mb-3"
                            :class="{ 'dropzone--active': dragging, 'dropzone--has-file': !!selectedFile }"
                            @dragover.prevent="dragging = true"
                            @dragleave.prevent="dragging = false"
                            @drop.prevent="onDrop"
                        >
                            <input ref="fileInput" type="file" accept=".csv,text/csv" class="d-none" @change="onFile" />
                            <div class="dropzone__icon mb-2">CSV</div>
                            <div class="fw-semibold mb-1">
                                {{ selectedFile ? selectedFile.name : t('platform.master_drop_title') }}
                            </div>
                            <div class="small text-muted">
                                {{ selectedFile ? t('platform.master_drop_change') : t('platform.master_drop_hint') }}
                            </div>
                        </label>
                        <p class="small text-muted mb-3">
                            {{ t('platform.master_import_limit', { max: String(maxRows) }) }}
                        </p>
                        <div v-if="previewForm.errors.file" class="alert alert-danger small py-2">{{ previewForm.errors.file }}</div>
                        <button type="submit" class="btn btn-primary w-100" :disabled="!selectedFile || previewForm.processing">
                            {{ previewForm.processing ? t('common.searching') : t('platform.master_preview_btn') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h6 mb-3">{{ t('platform.master_columns_title') }}</h2>
                        <p class="small text-muted mb-3">{{ t('platform.master_columns_hint') }}</p>
                        <div class="d-flex flex-wrap gap-2">
                            <span v-for="col in csvColumns" :key="col" class="column-chip">
                                <span v-if="col === 'name'" class="text-danger">*</span>{{ col }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="editablePreview" class="card border-0 shadow-sm mt-4 overflow-hidden">
            <div class="card-header bg-white border-0 py-3 px-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-semibold">
                            {{ t('platform.master_preview_title') }} —
                            <span class="text-success">{{ editablePreview.valid_count }}</span>
                            {{ t('platform.master_valid') }},
                            <span class="text-danger">{{ editablePreview.error_count }}</span>
                            {{ t('platform.master_with_errors') }}
                        </div>
                        <p class="small text-muted mb-0 mt-1">{{ t('platform.master_preview_hint') }}</p>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th v-for="h in editablePreview.headers" :key="h" class="text-nowrap">{{ h }}</th>
                            <th>{{ t('catalog.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in previewRows"
                            :key="row.row"
                            :class="row.errors?.length ? 'table-danger' : ''"
                        >
                            <td class="text-muted">{{ row.row }}</td>
                            <td v-for="h in editablePreview.headers" :key="h" class="p-1" style="min-width: 7rem">
                                <input v-model="row.raw[h]" type="text" class="form-control form-control-sm" />
                            </td>
                            <td>
                                <span v-if="row.errors?.length" class="text-danger small">{{ row.errors.join('; ') }}</span>
                                <span v-else class="text-success small">OK</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="editablePreview.rows.length > previewLimit" class="px-4 py-2 small text-muted border-top">
                {{ t('platform.master_preview_truncated', { shown: String(previewLimit), total: String(editablePreview.rows.length) }) }}
            </div>
            <div class="card-footer bg-white px-4 py-3">
                <div class="form-check mb-3">
                    <input id="update_existing" v-model="importForm.update_existing" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="update_existing">{{ t('platform.master_update_existing') }}</label>
                </div>
                <button
                    type="button"
                    class="btn btn-primary"
                    :disabled="importForm.processing || editablePreview.valid_count < 1"
                    @click="runImport"
                >
                    {{ importForm.processing ? t('common.saving') : t('platform.master_import_confirm') }}
                </button>
            </div>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    preview: { type: Object, default: null },
    csvColumns: { type: Array, default: () => [] },
    maxRows: { type: Number, default: 5000 },
    stats: { type: Object, default: () => ({ total: 0 }) },
});

const { t } = useLocale();
const fileInput = ref(null);
const selectedFile = ref(null);
const dragging = ref(false);
const previewLimit = 40;

const editablePreview = reactive({
    headers: [],
    rows: [],
    valid_count: 0,
    error_count: 0,
});

watch(
    () => props.preview,
    (value) => {
        if (!value) {
            editablePreview.headers = [];
            editablePreview.rows = [];
            editablePreview.valid_count = 0;
            editablePreview.error_count = 0;
            return;
        }
        editablePreview.headers = [...(value.headers ?? [])];
        editablePreview.rows = (value.rows ?? []).map((r) => ({
            row: r.row,
            raw: { ...(r.raw ?? {}) },
            errors: [...(r.errors ?? [])],
        }));
        editablePreview.valid_count = value.valid_count ?? 0;
        editablePreview.error_count = value.error_count ?? 0;
    },
    { immediate: true },
);

const previewRows = computed(() => editablePreview.rows.slice(0, previewLimit));

const previewForm = useForm({ file: null });
const importForm = useForm({
    headers: [],
    rows: [],
    update_existing: true,
});

function onFile(event) {
    selectedFile.value = event.target.files?.[0] ?? null;
}

function onDrop(event) {
    dragging.value = false;
    const file = event.dataTransfer?.files?.[0] ?? null;
    if (!file) {
        return;
    }
    selectedFile.value = file;
    if (fileInput.value) {
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.value.files = dt.files;
    }
}

function runPreview() {
    if (!selectedFile.value) {
        return;
    }
    previewForm.file = selectedFile.value;
    previewForm.post('/platform/master-catalog/import/preview', { forceFormData: true });
}

function runImport() {
    importForm.headers = editablePreview.headers;
    importForm.rows = editablePreview.rows.map((r) => ({
        row: r.row,
        raw: r.raw,
    }));
    importForm.post('/platform/master-catalog/import');
}
</script>

<style scoped>
.text-teal {
    color: #0f766e !important;
}

.btn-outline-teal {
    --bs-btn-color: #0f766e;
    --bs-btn-border-color: #0f766e;
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: #0f766e;
    --bs-btn-hover-border-color: #0f766e;
}

.import-hero {
    background:
        radial-gradient(700px 280px at 90% 0%, rgba(15, 118, 110, 0.12), transparent 60%),
        linear-gradient(180deg, #f0fdfa 0%, #ffffff 100%);
    border: 1px solid rgba(15, 118, 110, 0.12);
}

.letter-space {
    letter-spacing: 0.08em;
}

.import-side-card {
    background: #fff;
    border-radius: 1rem;
    padding: 1.25rem 1.5rem;
    border: 1px solid rgba(15, 118, 110, 0.12);
    box-shadow: 0 10px 30px rgba(15, 118, 110, 0.08);
}

.dropzone {
    border: 2px dashed #99f6e4;
    border-radius: 1rem;
    padding: 2rem 1rem;
    background: #f0fdfa;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease, transform 0.15s ease;
}

.dropzone:hover,
.dropzone--active {
    border-color: #0f766e;
    background: #ccfbf1;
}

.dropzone--has-file {
    border-style: solid;
    background: #fff;
}

.dropzone__icon {
    display: inline-grid;
    place-items: center;
    min-width: 3rem;
    height: 2rem;
    padding: 0 0.65rem;
    border-radius: 0.5rem;
    background: #0f766e;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
}

.column-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.15rem;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.78rem;
    color: #334155;
}
</style>
