<template>
    <PlatformShellLayout :page-title="t('platform.leads_title')">
        <Head :title="t('platform.nav_leads')" />
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge text-bg-secondary">{{ t('platform.total') }}: {{ stats.total }}</span>
                <span class="badge text-bg-primary">{{ t('platform.status_new') }}: {{ stats.new }}</span>
                <span class="badge text-bg-success">{{ t('platform.status_converted') }}: {{ stats.converted }}</span>
                <span class="badge text-bg-warning">{{ t('platform.follow_ups_due') }}: {{ stats.follow_ups_due }}</span>
            </div>
            <Link href="/platform/leads/create" class="btn btn-primary btn-sm">{{ t('platform.new_lead') }}</Link>
        </div>
        <form class="row g-2 mb-3" @submit.prevent="filter">
            <div class="col-md-3"><input v-model="local.search" class="form-control form-control-sm" :placeholder="t('common.search')" /></div>
            <div class="col-md-2">
                <select v-model="local.status" class="form-select form-select-sm">
                    <option value="">{{ t('platform.all_statuses') }}</option>
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <div class="col-md-2">
                <select v-model="local.source" class="form-select form-select-sm">
                    <option value="">{{ t('platform.all_sources') }}</option>
                    <option v-for="s in sources" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">{{ t('common.filter') || 'Filter' }}</button></div>
        </form>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('platform.pharmacy_name') }}</th>
                        <th>{{ t('platform.owner_name') }}</th>
                        <th>{{ t('platform.mobile') }}</th>
                        <th>{{ t('common.status') }}</th>
                        <th>{{ t('platform.assigned_to') }}</th>
                        <th>{{ t('platform.requested_at') }}</th>
                        <th>{{ t('platform.follow_up_at') }}</th>
                        <th class="text-end">{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="lead in leads.data"
                        :key="lead.id"
                        class="lead-row"
                        role="link"
                        tabindex="0"
                        @click="openDetails(lead)"
                        @keydown.enter.prevent="openDetails(lead)"
                    >
                        <td class="text-primary fw-semibold">{{ lead.pharmacy_name }}</td>
                        <td>{{ lead.owner_name }}</td>
                        <td>{{ lead.phone }}</td>
                        <td><span class="badge text-bg-light">{{ lead.status }}</span></td>
                        <td>{{ lead.assignee?.name || '—' }}</td>
                        <td>{{ formatWhen(lead.created_at) }}</td>
                        <td>{{ lead.follow_up_at ? formatWhen(lead.follow_up_at) : '—' }}</td>
                        <td class="text-end text-nowrap" @click.stop>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary me-1"
                                :title="t('common.edit')"
                                @click="openEdit(lead)"
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
                                @click="destroyLead(lead)"
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
                    <tr v-if="!leads.data?.length"><td colspan="8" class="text-center text-muted py-4">{{ t('common.no_results') }}</td></tr>
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
                                <h2 class="modal-title h5 mb-0">{{ t('common.edit') }} · {{ editing.pharmacy_name }}</h2>
                                <p class="small text-muted mb-0">{{ editing.owner_name }} · {{ editing.phone }}</p>
                            </div>
                            <button type="button" class="btn-close" :aria-label="t('common.close')" @click="closeEdit" />
                        </div>
                        <form @submit.prevent="saveEdit">
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">{{ t('platform.pharmacy_name') }}</label>
                                        <input v-model="editForm.pharmacy_name" class="form-control" required />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">{{ t('platform.owner_name') }}</label>
                                        <input v-model="editForm.owner_name" class="form-control" required />
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ t('platform.mobile') }}</label>
                                        <input v-model="editForm.phone" class="form-control" required />
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">WhatsApp</label>
                                        <input v-model="editForm.whatsapp" class="form-control" />
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Email</label>
                                        <input v-model="editForm.email" type="email" class="form-control" />
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ t('platform.branch_count') }}</label>
                                        <input v-model.number="editForm.branch_count" type="number" min="1" class="form-control" />
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ t('platform.source') }}</label>
                                        <select v-model="editForm.source" class="form-select">
                                            <option v-for="s in sources" :key="s" :value="s">{{ s }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ t('platform.interested_plan') }}</label>
                                        <select v-model="editForm.interested_plan_id" class="form-select">
                                            <option :value="null">—</option>
                                            <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ t('common.status') }}</label>
                                        <select v-if="statusEditable" v-model="editForm.status" class="form-select">
                                            <option v-for="s in editableStatuses" :key="s" :value="s">{{ s }}</option>
                                        </select>
                                        <template v-else>
                                            <input class="form-control" :value="editing.status || '—'" disabled readonly />
                                            <div class="form-text">{{ t('platform.lead_status_locked') }}</div>
                                        </template>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ t('platform.assigned_to') }}</label>
                                        <select v-model="editForm.assigned_to" class="form-select">
                                            <option :value="null">—</option>
                                            <option
                                                v-if="currentAssigneeOutsideList"
                                                :value="editing.assigned_to"
                                                disabled
                                            >
                                                {{ editing.assignee?.name || '—' }}
                                            </option>
                                            <option v-for="u in salesStaff" :key="u.id" :value="u.id">{{ u.name }}</option>
                                        </select>
                                        <div v-if="!canAssignToAdmins" class="form-text">{{ t('platform.assigned_to_sales_hint') }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">{{ t('platform.follow_up_at') }}</label>
                                        <input v-model="editForm.follow_up_at" type="datetime-local" class="form-control" />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">{{ t('platform.address') }}</label>
                                        <textarea v-model="editForm.address" class="form-control" rows="2" />
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">{{ t('platform.notes') }}</label>
                                        <textarea v-model="editForm.notes" class="form-control" rows="3" />
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
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';

const props = defineProps({
    leads: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    stats: { type: Object, required: true },
    statuses: { type: Array, required: true },
    pipelineStatuses: { type: Array, default: () => [] },
    sources: { type: Array, required: true },
    salesStaff: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
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
    source: props.filters.source || '',
});

const editing = ref(null);
const editForm = useForm({
    pharmacy_name: '',
    owner_name: '',
    phone: '',
    whatsapp: '',
    email: '',
    branch_count: 1,
    address: '',
    source: 'other',
    interested_plan_id: null,
    status: 'new',
    notes: '',
    follow_up_at: '',
    assigned_to: null,
});

const editableStatuses = computed(() => (props.pipelineStatuses?.length ? props.pipelineStatuses : props.statuses));
const statusEditable = computed(() => {
    if (!editing.value?.status) {
        return true;
    }
    return editableStatuses.value.includes(editing.value.status);
});
const currentAssigneeOutsideList = computed(() => {
    const id = editing.value?.assigned_to;
    if (!id) {
        return false;
    }
    return !(props.salesStaff || []).some((u) => Number(u.id) === Number(id));
});

watch(editing, (value) => {
    document.body.classList.toggle('modal-open', Boolean(value));
});

onBeforeUnmount(() => {
    document.body.classList.remove('modal-open');
});

function filter() {
    router.get('/platform/leads', { ...local }, { preserveState: true });
}

function formatWhen(value) {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleString();
}

function openDetails(lead) {
    router.visit(`/platform/leads/${lead.id}`);
}

function openEdit(lead) {
    editing.value = lead;
    editForm.clearErrors();
    editForm.pharmacy_name = lead.pharmacy_name || '';
    editForm.owner_name = lead.owner_name || '';
    editForm.phone = lead.phone || '';
    editForm.whatsapp = lead.whatsapp || '';
    editForm.email = lead.email || '';
    editForm.branch_count = lead.branch_count || 1;
    editForm.address = lead.address || '';
    editForm.source = lead.source || 'other';
    editForm.interested_plan_id = lead.interested_plan_id || null;
    editForm.status = editableStatuses.value.includes(lead.status)
        ? lead.status
        : (editableStatuses.value[0] || 'new');
    editForm.notes = lead.notes || '';
    editForm.follow_up_at = lead.follow_up_at ? String(lead.follow_up_at).slice(0, 16) : '';
    editForm.assigned_to = lead.assigned_to || null;
}

function closeEdit() {
    editing.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function saveEdit() {
    if (!editing.value) {
        return;
    }
    const leadId = editing.value.id;
    const locked = !editableStatuses.value.includes(editing.value.status);
    editForm
        .transform((data) => {
            const payload = { ...data };
            if (locked) {
                delete payload.status;
            }
            return payload;
        })
        .put(`/platform/leads/${leadId}`, {
            preserveScroll: true,
            onSuccess: () => closeEdit(),
        });
}

function destroyLead(lead) {
    if (!confirm(t('platform.lead_delete_confirm', { name: lead.pharmacy_name }))) {
        return;
    }
    router.delete(`/platform/leads/${lead.id}`);
}
</script>

<style scoped>
.lead-row {
    cursor: pointer;
}

.lead-row:hover {
    background: rgba(var(--bs-primary-rgb), 0.04);
}
</style>
