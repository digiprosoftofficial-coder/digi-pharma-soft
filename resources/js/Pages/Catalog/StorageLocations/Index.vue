<template>
    <TenantShellLayout :page-title="t('catalog.storage_locations_title')">
        <Head :title="t('catalog.storage_locations_title')" />
        <div class="storage-locations-page">
            <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
            <div v-if="$page.props.errors?.storage_location" class="alert alert-danger small">
                {{ $page.props.errors.storage_location }}
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 storage-locations-header">
                <h1 class="h4 mb-0 storage-locations-title">{{ t('catalog.storage_locations_title') }}</h1>
                <button type="button" class="btn btn-primary btn-sm storage-locations-add" @click="openCreate">
                    {{ t('catalog.new_storage_location') }}
                </button>
            </div>

            <div class="storage-locations-mobile d-md-none">
                <div v-if="!locations.data?.length" class="card border-0 shadow-sm card-body text-muted text-center small">
                    {{ t('catalog.storage_locations_empty') }}
                </div>
                <div
                    v-for="loc in locations.data"
                    :key="loc.id"
                    class="card border-0 shadow-sm mb-2 storage-location-card"
                >
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold text-truncate">{{ loc.name }}</div>
                                <div class="small text-muted text-truncate">
                                    <code v-if="loc.code">{{ loc.code }}</code>
                                    <span v-else>—</span>
                                </div>
                            </div>
                            <span
                                class="badge flex-shrink-0"
                                :class="loc.is_active ? 'text-bg-success' : 'text-bg-secondary'"
                            >
                                {{ loc.is_active ? t('common.active') : t('common.inactive') }}
                            </span>
                        </div>

                        <div class="storage-location-card__counts mb-2">
                            <div>
                                <span class="text-muted">{{ t('catalog.storage_location_products') }}</span>
                                <Link
                                    v-if="loc.products_count > 0"
                                    :href="`/products?storage_location_id=${loc.id}`"
                                    class="fw-semibold text-decoration-none"
                                >
                                    {{ loc.products_count }}
                                </Link>
                                <strong v-else>0</strong>
                            </div>
                            <div>
                                <span class="text-muted">{{ t('catalog.storage_location_batches') }}</span>
                                <strong>{{ loc.batches_count || 0 }}</strong>
                            </div>
                        </div>

                        <p v-if="locationUsageCount(loc) > 0" class="small text-muted mb-2">
                            {{ t('catalog.storage_location_delete_blocked') }}
                        </p>

                        <div class="storage-location-card__actions">
                            <button type="button" class="btn btn-sm btn-outline-secondary" @click="openEdit(loc)">
                                {{ t('common.edit') }}
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                :disabled="locationUsageCount(loc) > 0"
                                :title="locationUsageCount(loc) > 0 ? t('catalog.storage_location_delete_blocked') : ''"
                                @click="remove(loc)"
                            >
                                {{ t('common.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm d-none d-md-block">
                <div class="table-responsive storage-locations-table">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ t('catalog.storage_location_name') }}</th>
                                <th>{{ t('catalog.storage_location_code') }}</th>
                                <th class="text-end">{{ t('catalog.storage_location_products') }}</th>
                                <th class="text-end">{{ t('catalog.storage_location_batches') }}</th>
                                <th>{{ t('common.status') }}</th>
                                <th class="text-end">{{ t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="loc in locations.data" :key="loc.id">
                                <td>{{ loc.name }}</td>
                                <td>
                                    <code v-if="loc.code">{{ loc.code }}</code>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="text-end">
                                    <Link
                                        v-if="loc.products_count > 0"
                                        :href="`/products?storage_location_id=${loc.id}`"
                                        class="badge text-bg-light border text-decoration-none"
                                    >
                                        {{ loc.products_count }}
                                    </Link>
                                    <span v-else class="text-muted">0</span>
                                </td>
                                <td class="text-end">
                                    <span v-if="loc.batches_count > 0" class="badge text-bg-light border">
                                        {{ loc.batches_count }}
                                    </span>
                                    <span v-else class="text-muted">0</span>
                                </td>
                                <td>
                                    <span class="badge" :class="loc.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                        {{ loc.is_active ? t('common.active') : t('common.inactive') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary me-1"
                                        @click="openEdit(loc)"
                                    >
                                        {{ t('common.edit') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        :disabled="locationUsageCount(loc) > 0"
                                        :title="locationUsageCount(loc) > 0 ? t('catalog.storage_location_delete_blocked') : ''"
                                        @click="remove(loc)"
                                    >
                                        {{ t('common.delete') }}
                                    </button>
                                    <div v-if="locationUsageCount(loc) > 0" class="small text-muted mt-1">
                                        {{ t('catalog.storage_location_delete_blocked') }}
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!locations.data?.length">
                                <td colspan="6" class="text-muted text-center py-3">
                                    {{ t('catalog.storage_locations_empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showFormModal"
            :title="editingLocation ? t('common.edit') : t('catalog.new_storage_location')"
            :confirm-label="t('common.save')"
            confirm-class="btn-primary"
            :processing="form.processing"
            @close="closeFormModal"
            @confirm="submitForm"
        >
            <div class="mb-3">
                <label class="form-label" for="storage-location-name">{{ t('catalog.storage_location_name') }}</label>
                <input
                    id="storage-location-name"
                    v-model="form.name"
                    class="form-control"
                    required
                    :disabled="form.processing"
                    @keydown.enter.prevent="submitForm"
                />
                <div v-if="form.errors.name" class="text-danger small mt-1">{{ form.errors.name }}</div>
            </div>
            <div class="mb-3">
                <label class="form-label" for="storage-location-code">{{ t('catalog.storage_location_code') }}</label>
                <input
                    id="storage-location-code"
                    v-model="form.code"
                    class="form-control"
                    :placeholder="t('catalog.storage_location_code_hint')"
                    :disabled="form.processing"
                    @keydown.enter.prevent="submitForm"
                />
                <div v-if="form.errors.code" class="text-danger small mt-1">{{ form.errors.code }}</div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label" for="storage-location-sort">{{ t('catalog.storage_location_sort') }}</label>
                    <input
                        id="storage-location-sort"
                        v-model.number="form.sort_order"
                        type="number"
                        min="0"
                        class="form-control"
                        :disabled="form.processing"
                    />
                    <div v-if="form.errors.sort_order" class="text-danger small mt-1">{{ form.errors.sort_order }}</div>
                </div>
                <div class="col-6 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input
                            id="storage-location-active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="form-check-input"
                            :disabled="form.processing"
                        />
                        <label class="form-check-label" for="storage-location-active">{{ t('common.active') }}</label>
                    </div>
                </div>
            </div>
            <div class="mb-0">
                <label class="form-label" for="storage-location-notes">{{ t('catalog.storage_location_notes') }}</label>
                <textarea
                    id="storage-location-notes"
                    v-model="form.notes"
                    class="form-control"
                    rows="2"
                    maxlength="2000"
                    :disabled="form.processing"
                />
                <div v-if="form.errors.notes" class="text-danger small mt-1">{{ form.errors.notes }}</div>
            </div>
        </ConfirmModal>
    </TenantShellLayout>
</template>

<script setup>
import ConfirmModal from '@/Components/ConfirmModal.vue';
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({ locations: { type: Object, required: true } });

const { t } = useLocale();

const showFormModal = ref(false);
const editingLocation = ref(null);

const form = useForm({
    name: '',
    code: '',
    sort_order: 0,
    is_active: true,
    notes: '',
});

const isEditing = computed(() => editingLocation.value != null);

function resetFormFields(location = null) {
    form.clearErrors();
    form.name = location?.name ?? '';
    form.code = location?.code ?? '';
    form.sort_order = location?.sort_order ?? 0;
    form.is_active = location?.is_active ?? true;
    form.notes = location?.notes ?? '';
}

function openCreate() {
    editingLocation.value = null;
    resetFormFields();
    showFormModal.value = true;
}

function openEdit(location) {
    editingLocation.value = location;
    resetFormFields(location);
    showFormModal.value = true;
}

function closeFormModal() {
    if (form.processing) {
        return;
    }
    resetModalState();
}

function resetModalState() {
    showFormModal.value = false;
    editingLocation.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
}

function submitForm() {
    const options = {
        preserveScroll: true,
        onSuccess: () => resetModalState(),
    };

    if (isEditing.value) {
        form.put(`/storage-locations/${editingLocation.value.id}`, options);
        return;
    }

    form.post('/storage-locations', options);
}

function locationUsageCount(location) {
    return Number(location.products_count ?? 0) + Number(location.batches_count ?? 0);
}

function remove(location) {
    if (locationUsageCount(location) > 0) {
        return;
    }
    if (!window.confirm(t('catalog.storage_location_delete_confirm', { name: location.name }))) {
        return;
    }

    router.delete(`/storage-locations/${location.id}`);
}
</script>

<style scoped>
.storage-locations-page {
    width: 100%;
    max-width: 100%;
    min-width: 0;
    overflow-x: clip;
}

.storage-locations-title {
    min-width: 0;
}

.storage-locations-table table {
    min-width: 0;
}

@media (min-width: 768px) {
    .storage-locations-table table {
        min-width: 700px;
    }
}

.storage-location-card {
    max-width: 100%;
    min-width: 0;
}

.storage-location-card__counts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}

.storage-location-card__counts > div {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
    min-width: 0;
    padding: 0.55rem 0.65rem;
    background: #f8f9fa;
    border: 1px solid #eef0f2;
    border-radius: 0.6rem;
}

.storage-location-card__actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.5rem;
}

@media (max-width: 767.98px) {
    .storage-locations-header {
        align-items: stretch !important;
    }

    .storage-locations-title {
        width: 100%;
    }

    .storage-locations-add {
        width: 100%;
        min-height: 2.25rem;
    }

    .storage-location-card .card-body {
        padding: 0.85rem !important;
    }

    .storage-location-card__actions .btn {
        width: 100%;
        min-height: 2.15rem;
        font-size: 0.8rem;
    }
}
</style>
