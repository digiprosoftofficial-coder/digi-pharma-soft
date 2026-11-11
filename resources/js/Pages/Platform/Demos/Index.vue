<template>
    <PlatformShellLayout :page-title="t('platform.demos_title')">
        <Head :title="t('platform.nav_demos')" />
        <div class="d-flex gap-2 mb-3 flex-wrap align-items-center" role="group" :aria-label="t('common.status')">
            <button
                type="button"
                class="btn btn-sm rounded-pill"
                :class="!local.status ? 'btn-secondary' : 'btn-outline-secondary'"
                @click="filterByStatus('')"
            >
                {{ t('platform.total') }}: {{ stats.total ?? 0 }}
            </button>
            <button
                v-for="chip in statusChips"
                :key="chip.status"
                type="button"
                class="btn btn-sm rounded-pill"
                :class="statusChipClass(chip.status)"
                @click="filterByStatus(chip.status)"
            >
                {{ chip.status }}: {{ stats[chip.status] ?? 0 }}
            </button>
        </div>
        <div class="row g-2 mb-3 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <input
                        v-model="local.search"
                        type="search"
                        class="form-control"
                        :placeholder="t('common.search')"
                        @input="onSearchInput"
                    />
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        :title="t('common.reset')"
                        :aria-label="t('common.reset')"
                        :disabled="!hasActiveFilters"
                        @click="resetFilters"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
                            <path d="M3 3v5h5" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="col-md-3">
                <select v-model="local.status" class="form-select form-select-sm" @change="applyFilters">
                    <option value="">{{ t('platform.all_statuses') }}</option>
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('platform.pharmacy_name') }}</th>
                        <th>{{ t('platform.owner_name') }}</th>
                        <th>{{ t('platform.mobile') }}</th>
                        <th>{{ t('common.status') }}</th>
                        <th>{{ t('platform.requested_at') }}</th>
                        <th>{{ t('platform.scheduled_at') }}</th>
                        <th class="text-end">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="d in demos.data" :key="d.id">
                        <td>{{ d.pharmacy_name }}</td>
                        <td>{{ d.owner_name }}</td>
                        <td>{{ d.mobile }}</td>
                        <td><span class="badge text-bg-light">{{ d.status }}</span></td>
                        <td>{{ formatWhen(d.created_at) }}</td>
                        <td>{{ d.scheduled_at ? formatWhen(d.scheduled_at) : '—' }}</td>
                        <td class="text-end text-nowrap">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary me-1"
                                :title="t('common.edit')"
                                @click="openEdit(d)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                </svg>
                                <span class="visually-hidden">{{ t('common.edit') }}</span>
                            </button>
                            <button
                                v-if="canDelete"
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                :title="t('common.delete')"
                                @click="destroyDemo(d)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M3 6h18" />
                                    <path d="M8 6V4h8v2" />
                                    <path d="M19 6l-1 14H6L5 6" />
                                </svg>
                                <span class="visually-hidden">{{ t('common.delete') }}</span>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!demos.data?.length">
                        <td colspan="7" class="text-center text-muted py-4">{{ t('common.no_results') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Teleport to="body">
            <div
                v-if="editing"
                class="modal fade show d-block"
                tabindex="-1"
                role="dialog"
                aria-modal="true"
                @keydown.esc.prevent="closeEdit"
            >
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h2 class="modal-title h5 mb-0">{{ editing.pharmacy_name }}</h2>
                                <p class="small text-muted mb-0">
                                    {{ editing.owner_name }} · {{ editing.mobile }} · {{ editing.status }}
                                </p>
                            </div>
                            <button type="button" class="btn-close" :aria-label="t('common.close')" @click="closeEdit" />
                        </div>
                        <form @submit.prevent="saveEdit">
                            <div class="modal-body">
                                <h3 class="h6">{{ t('platform.lead_details') }}</h3>
                                <dl class="row small mb-3">
                                    <dt class="col-sm-3">{{ t('platform.email') }}</dt>
                                    <dd class="col-sm-9">{{ editing.lead?.email || '—' }}</dd>
                                    <dt class="col-sm-3">{{ t('platform.address') }}</dt>
                                    <dd class="col-sm-9">{{ editing.lead?.address || '—' }}</dd>
                                    <dt class="col-sm-3">{{ t('platform.message') }}</dt>
                                    <dd class="col-sm-9">{{ editing.message || '—' }}</dd>
                                    <dt class="col-sm-3">{{ t('platform.requested_at') }}</dt>
                                    <dd class="col-sm-9">{{ formatWhen(editing.created_at) }}</dd>
                                </dl>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">{{ t('common.status') }}</label>
                                        <select v-model="editForm.status" class="form-select" required>
                                            <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ t('platform.scheduled_at') }}</label>
                                        <input v-model="editForm.scheduled_at" type="datetime-local" class="form-control" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ t('platform.assigned_to') }}</label>
                                        <select v-model="editForm.assigned_to" class="form-select">
                                            <option :value="null">—</option>
                                            <option
                                                v-if="currentAssigneeOutsideList"
                                                :value="editing.assigned_to"
                                                disabled
                                            >
                                                {{ assigneeName }}
                                            </option>
                                            <option v-for="u in salesStaff" :key="u.id" :value="u.id">{{ u.name }}</option>
                                        </select>
                                        <div v-if="!canAssignToAdmins" class="form-text">{{ t('platform.assigned_to_sales_hint') }}</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">{{ t('platform.meeting_notes') }}</label>
                                        <textarea v-model="editForm.meeting_notes" class="form-control" rows="3" />
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" @click="closeEdit">{{ t('common.cancel') }}</button>
                                <button type="submit" class="btn btn-primary" :disabled="editForm.processing">{{ t('common.save') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div v-if="editing" class="modal-backdrop fade show" />
        </Teleport>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';

const props = defineProps({
    demos: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
    statuses: { type: Array, required: true },
    salesStaff: { type: Array, default: () => [] },
    canAssignToAdmins: { type: Boolean, default: false },
});

const { t } = useLocale();
const page = usePage();
const canDelete = computed(() => {
    const user = page.props.auth?.user;
    return Boolean(user?.is_platform_super_admin || (user?.roles || []).includes('super admin'));
});

const local = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
});

const editing = ref(null);
const editForm = useForm({
    status: 'pending',
    scheduled_at: '',
    assigned_to: null,
    meeting_notes: '',
});

let filterTimer = null;

const hasActiveFilters = computed(() => Boolean(local.search || local.status));

const statusChips = computed(() => (props.statuses || []).map((status) => ({ status })));

const statusTone = {
    pending: 'warning',
    approved: 'info',
    rejected: 'danger',
    scheduled: 'primary',
    completed: 'success',
    cancelled: 'dark',
};

function statusChipClass(status) {
    const tone = statusTone[status] || 'secondary';
    const active = local.status === status;
    return active ? `btn-${tone}` : `btn-outline-${tone}`;
}

function filterByStatus(status) {
    local.status = status || '';
    applyFilters();
}

const assigneeName = computed(() => {
    if (!editing.value) {
        return '—';
    }
    const match = (props.salesStaff || []).find((u) => Number(u.id) === Number(editing.value.assigned_to));
    return match?.name || editing.value.assignee?.name || '—';
});

const currentAssigneeOutsideList = computed(() => {
    const id = editing.value?.assigned_to;
    if (!id) {
        return false;
    }
    return !(props.salesStaff || []).some((u) => Number(u.id) === Number(id));
});

watch(
    () => props.filters,
    (filters) => {
        local.search = filters.search || '';
        local.status = filters.status || '';
    },
    { deep: true },
);

function applyFilters() {
    clearTimeout(filterTimer);
    router.get('/platform/demos', {
        search: local.search || undefined,
        status: local.status || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['demos', 'filters', 'stats'],
    });
}

function onSearchInput() {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => applyFilters(), 350);
}

function resetFilters() {
    clearTimeout(filterTimer);
    local.search = '';
    local.status = '';
    applyFilters();
}

function openEdit(demo) {
    editing.value = demo;
    editForm.clearErrors();
    editForm.status = demo.status;
    editForm.scheduled_at = demo.scheduled_at ? String(demo.scheduled_at).slice(0, 16) : '';
    editForm.assigned_to = demo.assigned_to ?? null;
    editForm.meeting_notes = demo.meeting_notes || '';
    document.body.classList.add('modal-open');
}

function closeEdit() {
    editing.value = null;
    document.body.classList.remove('modal-open');
}

function saveEdit() {
    if (!editing.value) {
        return;
    }
    editForm.put(`/platform/demos/${editing.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeEdit();
            router.reload({ only: ['demos', 'stats'] });
        },
    });
}

function formatWhen(value) {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleString();
}

function destroyDemo(demo) {
    if (!confirm(t('platform.demo_delete_confirm', { name: demo.pharmacy_name }))) {
        return;
    }
    router.delete(`/platform/demos/${demo.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            if (editing.value?.id === demo.id) {
                closeEdit();
            }
        },
    });
}

onBeforeUnmount(() => {
    clearTimeout(filterTimer);
    document.body.classList.remove('modal-open');
});
</script>
