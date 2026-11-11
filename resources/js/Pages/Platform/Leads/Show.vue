<template>
    <PlatformShellLayout :page-title="lead.pharmacy_name">
        <Head :title="lead.pharmacy_name" />
        <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <h1 class="page-heading mb-1">{{ lead.pharmacy_name }}</h1>
                <div class="text-muted small">{{ lead.owner_name }} · {{ lead.phone }} · <span class="badge text-bg-light">{{ lead.status }}</span></div>
            </div>
            <div class="d-flex gap-2">
                <Link :href="`/platform/leads/${lead.id}/edit`" class="btn btn-sm btn-outline-primary">{{ t('common.edit') }}</Link>
                <button
                    v-if="canConvert && lead.status !== 'converted'"
                    type="button"
                    class="btn btn-sm btn-success"
                    @click="convert"
                >
                    {{ t('platform.convert_lead') }}
                </button>
                <button
                    v-if="canDelete"
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    @click="destroyLead"
                >
                    {{ t('common.delete') }}
                </button>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm card-body mb-3">
                    <h2 class="h6">{{ t('platform.lead_details') }}</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ lead.email || '—' }}</dd>
                        <dt class="col-sm-4">WhatsApp</dt><dd class="col-sm-8">{{ lead.whatsapp || '—' }}</dd>
                        <dt class="col-sm-4">{{ t('platform.source') }}</dt><dd class="col-sm-8">{{ lead.source }}</dd>
                        <dt class="col-sm-4">{{ t('platform.interested_plan') }}</dt><dd class="col-sm-8">{{ lead.interested_plan?.name || '—' }}</dd>
                        <dt class="col-sm-4">{{ t('platform.assigned_to') }}</dt><dd class="col-sm-8">{{ lead.assignee?.name || '—' }}</dd>
                        <dt class="col-sm-4">{{ t('platform.address') }}</dt><dd class="col-sm-8">{{ lead.address || '—' }}</dd>
                        <dt class="col-sm-4">{{ t('platform.notes') }}</dt><dd class="col-sm-8">{{ lead.notes || '—' }}</dd>
                    </dl>
                </div>
                <div class="card border-0 shadow-sm card-body">
                    <h2 class="h6">{{ t('platform.activity_history') }}</h2>
                    <ul class="list-unstyled mb-3">
                        <li v-for="a in lead.activities" :key="a.id" class="border-bottom py-2 small">
                            <div class="fw-semibold">{{ a.type }} <span class="text-muted fw-normal">· {{ a.user?.name || 'System' }}</span></div>
                            <div>{{ a.body }}</div>
                            <div class="text-muted">{{ new Date(a.created_at).toLocaleString() }}</div>
                        </li>
                    </ul>
                    <form class="d-flex gap-2" @submit.prevent="addNote">
                        <input v-model="note" class="form-control form-control-sm" :placeholder="t('platform.add_note')" />
                        <button class="btn btn-sm btn-primary">{{ t('common.save') }}</button>
                    </form>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm card-body">
                    <h2 class="h6">{{ t('platform.quick_update') }}</h2>
                    <p class="small text-muted">{{ t('platform.lead_status_pipeline_hint') }}</p>
                    <form @submit.prevent="updateStatus">
                        <label class="form-label">{{ t('common.status') }}</label>
                        <select
                            v-if="statusEditable"
                            v-model="statusForm.status"
                            class="form-select form-select-sm mb-2"
                        >
                            <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                        </select>
                        <template v-else>
                            <input class="form-control form-control-sm mb-1" :value="lead.status" disabled readonly />
                            <div class="form-text mb-2">{{ t('platform.lead_status_locked') }}</div>
                        </template>
                        <label class="form-label">{{ t('platform.assigned_to') }}</label>
                        <select v-model="statusForm.assigned_to" class="form-select form-select-sm mb-2">
                            <option :value="null">—</option>
                            <option
                                v-if="currentAssigneeOutsideList"
                                :value="lead.assigned_to"
                                disabled
                            >
                                {{ lead.assignee?.name || '—' }}
                            </option>
                            <option v-for="u in salesStaff" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        <div v-if="!canAssignToAdmins" class="form-text mb-2">{{ t('platform.assigned_to_sales_hint') }}</div>
                        <label class="form-label">{{ t('platform.follow_up_at') }}</label>
                        <input v-model="statusForm.follow_up_at" type="datetime-local" class="form-control form-control-sm mb-3" />
                        <button class="btn btn-sm btn-outline-primary">{{ t('common.save') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    lead: { type: Object, required: true },
    statuses: { type: Array, required: true },
    allStatuses: { type: Array, default: () => [] },
    sources: { type: Array, required: true },
    salesStaff: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    canConvert: { type: Boolean, default: false },
    canAssignToAdmins: { type: Boolean, default: false },
});

const { t } = useLocale();
const page = usePage();
const canDelete = computed(() => {
    const user = page.props.auth?.user;
    return Boolean(user?.is_platform_super_admin || (user?.roles || []).includes('super admin'));
});
const statusEditable = computed(() => props.statuses.includes(props.lead.status));
const currentAssigneeOutsideList = computed(() => {
    const id = props.lead.assigned_to;
    if (!id) {
        return false;
    }
    return !(props.salesStaff || []).some((u) => Number(u.id) === Number(id));
});
const note = ref('');
const statusForm = useForm({
    status: statusEditable.value ? props.lead.status : props.statuses[0],
    assigned_to: props.lead.assigned_to,
    follow_up_at: props.lead.follow_up_at ? props.lead.follow_up_at.slice(0, 16) : '',
});

function addNote() {
    router.post(`/platform/leads/${props.lead.id}/notes`, { body: note.value }, {
        onSuccess: () => { note.value = ''; },
    });
}

function updateStatus() {
    const payload = {
        follow_up_at: statusForm.follow_up_at || null,
        assigned_to: statusForm.assigned_to,
    };
    if (statusEditable.value) {
        payload.status = statusForm.status;
    }
    statusForm.transform(() => payload).put(`/platform/leads/${props.lead.id}`);
}

function convert() {
    if (!confirm(t('platform.convert_lead_confirm'))) return;
    router.post(`/platform/leads/${props.lead.id}/convert`, {
        subscription_plan_id: props.lead.interested_plan_id,
        owner_email: props.lead.email,
        owner_invite: true,
        billing_interval: 'monthly',
    });
}

function destroyLead() {
    if (!confirm(t('platform.lead_delete_confirm', { name: props.lead.pharmacy_name }))) {
        return;
    }
    router.delete(`/platform/leads/${props.lead.id}`);
}
</script>
