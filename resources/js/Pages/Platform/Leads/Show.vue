<template>
    <PlatformShellLayout :page-title="lead.pharmacy_name">
        <Head :title="lead.pharmacy_name" />
        <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <h1 class="page-heading mb-1">{{ lead.pharmacy_name }}</h1>
                <div class="text-muted small">{{ lead.owner_name }} · {{ lead.phone }} · <span class="badge text-bg-light">{{ lead.status }}</span></div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <Link
                    :href="`/platform/leads/${lead.id}/edit`"
                    class="btn btn-sm btn-outline-primary"
                    :title="t('common.edit')"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M12 20h9" />
                        <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                    </svg>
                    <span class="visually-hidden">{{ t('common.edit') }}</span>
                </Link>
                <button
                    v-if="canConvert && lead.status !== 'converted'"
                    type="button"
                    class="btn btn-sm btn-success"
                    @click="openConvert"
                >
                    {{ t('platform.convert_lead') }}
                </button>
                <button
                    v-if="canDelete"
                    type="button"
                    class="btn btn-sm btn-outline-danger"
                    :title="t('common.delete')"
                    @click="destroyLead"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M3 6h18" />
                        <path d="M8 6V4h8v2" />
                        <path d="M19 6l-1 14H6L5 6" />
                    </svg>
                    <span class="visually-hidden">{{ t('common.delete') }}</span>
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

        <div
            v-if="showConvert"
            class="modal fade show d-block"
            tabindex="-1"
            style="background: rgba(0,0,0,.45)"
            @click.self="showConvert = false"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5 mb-0">{{ t('platform.convert_lead') }}</h2>
                        <button type="button" class="btn-close" @click="showConvert = false" />
                    </div>
                    <form @submit.prevent="submitConvert">
                        <div class="modal-body">
                            <p class="small text-muted">{{ t('platform.convert_lead_help') }}</p>
                            <div class="mb-3">
                                <label class="form-label">{{ t('platform.interested_plan') }}</label>
                                <select v-model="convertForm.subscription_plan_id" class="form-select form-select-sm" required @change="onPlanChange">
                                    <option :value="null" disabled>{{ t('platform.convert_select_plan') }}</option>
                                    <option v-for="p in plans" :key="p.id" :value="p.id">
                                        {{ p.name }} ({{ p.trial_days }}d {{ t('platform.kpi_trial').toLowerCase() }})
                                    </option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ t('platform.convert_trial_days') }}</label>
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <button
                                        v-for="d in trialPresets"
                                        :key="d"
                                        type="button"
                                        class="btn btn-sm"
                                        :class="Number(convertForm.trial_days) === d ? 'btn-primary' : 'btn-outline-primary'"
                                        @click="convertForm.trial_days = d"
                                    >
                                        {{ d }}d
                                    </button>
                                </div>
                                <input
                                    v-model.number="convertForm.trial_days"
                                    type="number"
                                    min="1"
                                    max="365"
                                    class="form-control form-control-sm"
                                    required
                                />
                                <div class="form-text">{{ t('platform.convert_trial_days_help') }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ t('platform.billing_interval') }}</label>
                                <select v-model="convertForm.billing_interval" class="form-select form-select-sm">
                                    <option value="monthly">{{ t('platform.monthly_price') }}</option>
                                    <option value="yearly">{{ t('platform.yearly_price') }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Owner email</label>
                                <input v-model="convertForm.owner_email" type="email" class="form-control form-control-sm" required />
                            </div>
                            <div class="mb-2">
                                <label class="form-label">{{ t('platform.convert_owner_password') }}</label>
                                <input
                                    v-model="convertForm.owner_password"
                                    type="password"
                                    class="form-control form-control-sm"
                                    :class="{ 'is-invalid': convertForm.errors.owner_password }"
                                    :required="!convertForm.owner_invite"
                                    autocomplete="new-password"
                                />
                                <div v-if="convertForm.errors.owner_password" class="invalid-feedback d-block">{{ convertForm.errors.owner_password }}</div>
                                <div class="form-text">{{ t('platform.owner_password_and_invite_help') }}</div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">{{ t('platform.convert_owner_password_confirm') }}</label>
                                <input
                                    v-model="convertForm.owner_password_confirmation"
                                    type="password"
                                    class="form-control form-control-sm"
                                    :required="!convertForm.owner_invite"
                                    autocomplete="new-password"
                                />
                            </div>
                            <div class="form-check mb-2">
                                <input id="convert-owner-invite" v-model="convertForm.owner_invite" type="checkbox" class="form-check-input" />
                                <label class="form-check-label" for="convert-owner-invite">{{ t('platform.owner_invite_by_email') }}</label>
                                <div class="form-text">{{ t('platform.owner_invite_optional_help') }}</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm" @click="showConvert = false">{{ t('common.cancel') }}</button>
                            <button type="submit" class="btn btn-success btn-sm" :disabled="convertForm.processing">
                                {{ t('platform.convert_lead') }}
                            </button>
                        </div>
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
const showConvert = ref(false);
const trialPresets = [7, 14, 30];
const statusForm = useForm({
    status: statusEditable.value ? props.lead.status : props.statuses[0],
    assigned_to: props.lead.assigned_to,
    follow_up_at: props.lead.follow_up_at ? props.lead.follow_up_at.slice(0, 16) : '',
});

const defaultPlanId = props.lead.interested_plan_id || props.plans[0]?.id || null;
const defaultPlan = props.plans.find((p) => Number(p.id) === Number(defaultPlanId));
const convertForm = useForm({
    subscription_plan_id: defaultPlanId,
    trial_days: Number(defaultPlan?.trial_days || 7),
    billing_interval: 'monthly',
    owner_email: props.lead.email || '',
    owner_invite: true,
    owner_password: '',
    owner_password_confirmation: '',
});

function onPlanChange() {
    const plan = props.plans.find((p) => Number(p.id) === Number(convertForm.subscription_plan_id));
    if (plan?.trial_days) {
        convertForm.trial_days = Number(plan.trial_days);
    }
}

function openConvert() {
    showConvert.value = true;
}

function submitConvert() {
    convertForm.post(`/platform/leads/${props.lead.id}/convert`, {
        onSuccess: () => {
            showConvert.value = false;
        },
    });
}

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

function destroyLead() {
    if (!confirm(t('platform.lead_delete_confirm', { name: props.lead.pharmacy_name }))) {
        return;
    }
    router.delete(`/platform/leads/${props.lead.id}`);
}
</script>
