<template>
    <PlatformShellLayout :page-title="tenant.name">
        <Head :title="tenant.name" />

        <Link href="/platform/tenants" class="small text-decoration-none">← {{ t('platform.nav_pharmacies') }}</Link>
        <h1 class="page-heading mt-2 mb-3">{{ t('common.edit') }}: {{ tenant.name }}</h1>

        <div v-if="canManageOwner" class="card border-0 shadow-sm card-body mb-3">
            <h2 class="h6">{{ t('platform.owner_section_title') }}</h2>
            <p class="small text-muted">{{ t('platform.owner_section_help') }}</p>

            <template v-if="owner">
                <p class="small mb-3">
                    <span class="text-muted">{{ t('platform.owner_current_label') }}:</span>
                    <strong class="ms-1">{{ owner.name }}</strong>
                    <span class="text-muted"> · {{ owner.email }}</span>
                </p>
                <form @submit.prevent="submitOwnerAccess">
                    <div class="mb-2">
                        <label class="form-label">{{ t('platform.convert_owner_password') }}</label>
                        <input
                            v-model="ownerForm.owner_password"
                            type="password"
                            class="form-control"
                            :class="{ 'is-invalid': ownerForm.errors.owner_password }"
                            :required="!ownerForm.owner_invite"
                            autocomplete="new-password"
                        />
                        <div v-if="ownerForm.errors.owner_password" class="invalid-feedback d-block">{{ ownerForm.errors.owner_password }}</div>
                        <div class="form-text">{{ t('platform.owner_password_and_invite_help') }}</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ t('platform.convert_owner_password_confirm') }}</label>
                        <input
                            v-model="ownerForm.owner_password_confirmation"
                            type="password"
                            class="form-control"
                            :required="!ownerForm.owner_invite"
                            autocomplete="new-password"
                        />
                    </div>
                    <div class="form-check mb-3">
                        <input id="edit-owner-invite" v-model="ownerForm.owner_invite" type="checkbox" class="form-check-input" />
                        <label class="form-check-label" for="edit-owner-invite">{{ t('platform.owner_invite_by_email') }}</label>
                        <div class="form-text">{{ t('platform.owner_invite_optional_help') }}</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-sm" :disabled="ownerForm.processing">
                            {{ t('platform.owner_save_access') }}
                        </button>
                        <button
                            v-if="canResendOwnerInvite"
                            type="button"
                            class="btn btn-outline-secondary btn-sm"
                            :disabled="resendInviteForm.processing"
                            @click="resendInvite"
                        >
                            {{ t('platform.owner_invite_resend') }}
                        </button>
                    </div>
                </form>
            </template>

            <form v-else-if="canAddOwner" @submit.prevent="submitAddOwner">
                <div class="mb-2">
                    <label class="form-label">Owner name</label>
                    <input v-model="addOwnerForm.owner_name" class="form-control" required />
                    <div v-if="addOwnerForm.errors.owner_name" class="text-danger small">{{ addOwnerForm.errors.owner_name }}</div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Owner email</label>
                    <input v-model="addOwnerForm.owner_email" type="email" class="form-control" required />
                    <div v-if="addOwnerForm.errors.owner_email" class="text-danger small">{{ addOwnerForm.errors.owner_email }}</div>
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ t('platform.convert_owner_password') }}</label>
                    <input
                        v-model="addOwnerForm.owner_password"
                        type="password"
                        class="form-control"
                        :required="!addOwnerForm.owner_invite"
                        autocomplete="new-password"
                    />
                    <div v-if="addOwnerForm.errors.owner_password" class="text-danger small">{{ addOwnerForm.errors.owner_password }}</div>
                    <div class="form-text">{{ t('platform.owner_password_and_invite_help') }}</div>
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ t('platform.convert_owner_password_confirm') }}</label>
                    <input
                        v-model="addOwnerForm.owner_password_confirmation"
                        type="password"
                        class="form-control"
                        :required="!addOwnerForm.owner_invite"
                        autocomplete="new-password"
                    />
                </div>
                <div class="form-check mb-3">
                    <input id="edit-add-owner-invite" v-model="addOwnerForm.owner_invite" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="edit-add-owner-invite">{{ t('platform.owner_invite_by_email') }}</label>
                    <div class="form-text">{{ t('platform.owner_invite_optional_help') }}</div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm" :disabled="addOwnerForm.processing">
                    {{ t('platform.add_owner_submit') }}
                </button>
            </form>
        </div>

        <form v-if="canManage" class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div v-if="Object.keys(form.errors).length" class="alert alert-danger small" role="alert">
                {{ t('platform.form_has_errors') }}
            </div>
            <div class="mb-2">
                <label class="form-label">Name</label>
                <input v-model="form.name" class="form-control" :class="{ 'is-invalid': form.errors.name }" required />
                <div v-if="form.errors.name" class="invalid-feedback d-block">{{ form.errors.name }}</div>
            </div>
            <div class="mb-2">
                <label class="form-label">Slug</label>
                <input :value="tenant.slug" class="form-control" disabled />
            </div>
            <div class="form-check mb-3">
                <input id="active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                <label class="form-check-label" for="active">{{ t('common.active') }}</label>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Trial ends</label>
                    <input v-model="form.trial_ends_at" type="date" class="form-control" :class="{ 'is-invalid': form.errors.trial_ends_at }" />
                    <div v-if="form.errors.trial_ends_at" class="invalid-feedback d-block">{{ form.errors.trial_ends_at }}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Subscription ends</label>
                    <input
                        :key="subscriptionInputKey"
                        v-model="form.subscription_ends_at"
                        type="date"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.subscription_ends_at }"
                        @change="onSubscriptionEndsChange"
                    />
                    <div v-if="form.errors.subscription_ends_at" class="invalid-feedback d-block">{{ form.errors.subscription_ends_at }}</div>
                    <div class="form-text">{{ t('platform.subscription_ends_extend_hint') }}</div>
                </div>
            </div>
            <div v-if="resellers.length" class="mb-3">
                <label class="form-label">{{ t('platform.reseller_label') }}</label>
                <select v-model="form.reseller_id" class="form-select">
                    <option :value="null">—</option>
                    <option v-for="r in resellers" :key="r.id" :value="r.id">{{ r.name }}</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Plan</label>
                <select v-model="form.subscription_plan_id" class="form-select">
                    <option :value="null">—</option>
                    <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }}</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.wholesale_override_label') }}</label>
                <select v-model="form.wholesale_pricing_override" class="form-select">
                    <option value="inherit">{{ t('platform.wholesale_override_inherit') }}</option>
                    <option value="on">{{ t('platform.wholesale_override_on') }}</option>
                    <option value="off">{{ t('platform.wholesale_override_off') }}</option>
                </select>
                <p class="form-text small mb-0">
                    {{
                        t('platform.wholesale_override_help', {
                            plan: tenant.plan_wholesale_pricing
                                ? t('platform.wholesale_plan_on')
                                : t('platform.wholesale_plan_off'),
                            effective: tenant.wholesale_pricing_enabled
                                ? t('platform.wholesale_effective_on')
                                : t('platform.wholesale_effective_off'),
                        })
                    }}
                </p>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.multi_branch_override_label') }}</label>
                <select v-model="form.multi_branch_override" class="form-select">
                    <option value="inherit">{{ t('platform.multi_branch_override_inherit') }}</option>
                    <option value="on">{{ t('platform.multi_branch_override_on') }}</option>
                    <option value="off">{{ t('platform.multi_branch_override_off') }}</option>
                </select>
                <p class="form-text small mb-0">
                    {{
                        t('platform.multi_branch_override_help', {
                            plan: tenant.plan_multi_branch
                                ? t('platform.multi_branch_plan_on')
                                : t('platform.multi_branch_plan_off'),
                            effective: tenant.multi_branch_enabled
                                ? t('platform.multi_branch_effective_on')
                                : t('platform.multi_branch_effective_off'),
                        })
                    }}
                    · {{ tenant.branches_count }} / {{ formatLimit(tenant.max_branches_effective) }} {{ t('platform.tenant_branches').toLowerCase() }}
                </p>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.supplier_branch_ledger_override_label') }}</label>
                <select
                    v-model="form.supplier_branch_ledger_override"
                    class="form-select"
                    :disabled="!tenant.multi_branch_enabled"
                >
                    <option value="inherit">{{ t('platform.supplier_branch_ledger_override_inherit') }}</option>
                    <option value="on">{{ t('platform.supplier_branch_ledger_override_on') }}</option>
                    <option value="off">{{ t('platform.supplier_branch_ledger_override_off') }}</option>
                </select>
                <p class="form-text small mb-0">
                    {{
                        t('platform.supplier_branch_ledger_override_help', {
                            plan: tenant.plan_supplier_branch_ledger
                                ? t('platform.supplier_branch_ledger_plan_on')
                                : t('platform.supplier_branch_ledger_plan_off'),
                            effective: tenant.supplier_branch_ledger_enabled
                                ? t('platform.supplier_branch_ledger_effective_on')
                                : t('platform.supplier_branch_ledger_effective_off'),
                        })
                    }}
                </p>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.employee_management_override_label') }}</label>
                <select v-model="form.employee_management_override" class="form-select">
                    <option value="inherit">{{ t('platform.employee_management_override_inherit') }}</option>
                    <option value="on">{{ t('platform.employee_management_override_on') }}</option>
                    <option value="off">{{ t('platform.employee_management_override_off') }}</option>
                </select>
                <p class="form-text small mb-0">
                    {{
                        t('platform.employee_management_override_help', {
                            plan: tenant.plan_employee_management
                                ? t('platform.employee_management_plan_on')
                                : t('platform.employee_management_plan_off'),
                            effective: tenant.employee_management_enabled
                                ? t('platform.employee_management_effective_on')
                                : t('platform.employee_management_effective_off'),
                        })
                    }}
                </p>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.attendance_override_label') }}</label>
                <select v-model="form.attendance_override" class="form-select">
                    <option value="inherit">{{ t('platform.attendance_override_inherit') }}</option>
                    <option value="on">{{ t('platform.attendance_override_on') }}</option>
                    <option value="off">{{ t('platform.attendance_override_off') }}</option>
                </select>
                <p class="form-text small mb-0">
                    {{
                        t('platform.attendance_override_help', {
                            plan: tenant.plan_attendance
                                ? t('platform.attendance_plan_on')
                                : t('platform.attendance_plan_off'),
                            effective: tenant.attendance_enabled
                                ? t('platform.attendance_effective_on')
                                : t('platform.attendance_effective_off'),
                        })
                    }}
                </p>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.hr_payroll_override_label') }}</label>
                <select v-model="form.hr_payroll_override" class="form-select">
                    <option value="inherit">{{ t('platform.hr_payroll_override_inherit') }}</option>
                    <option value="on">{{ t('platform.hr_payroll_override_on') }}</option>
                    <option value="off">{{ t('platform.hr_payroll_override_off') }}</option>
                </select>
                <p class="form-text small mb-0">
                    {{
                        t('platform.hr_payroll_override_help', {
                            plan: tenant.plan_hr_payroll
                                ? t('platform.hr_payroll_plan_on')
                                : t('platform.hr_payroll_plan_off'),
                            effective: tenant.hr_payroll_enabled
                                ? t('platform.hr_payroll_effective_on')
                                : t('platform.hr_payroll_effective_off'),
                        })
                    }}
                </p>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.limit_max_products_override') }}</label>
                    <input
                        v-model="form.max_products_override"
                        type="text"
                        class="form-control"
                        :placeholder="t('platform.limit_override_inherit_placeholder')"
                    />
                    <p class="form-text small mb-0">
                        {{ t('platform.limit_override_help', { plan: formatLimit(tenant.plan_max_products), effective: formatLimit(tenant.max_products_effective) }) }}
                    </p>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.limit_max_import_rows_override') }}</label>
                    <input
                        v-model="form.max_import_rows_override"
                        type="text"
                        class="form-control"
                        :placeholder="t('platform.limit_override_inherit_placeholder')"
                    />
                    <p class="form-text small mb-0">
                        {{ t('platform.limit_override_help', { plan: formatLimit(tenant.plan_max_import_rows), effective: formatLimit(tenant.max_import_rows_effective) }) }}
                    </p>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.limit_max_branches_override') }}</label>
                    <input
                        v-model="form.max_branches_override"
                        type="text"
                        class="form-control"
                        :placeholder="t('platform.limit_override_inherit_placeholder')"
                    />
                    <p class="form-text small mb-0">
                        {{ t('platform.limit_override_help', { plan: formatLimit(tenant.plan_max_branches), effective: formatLimit(tenant.max_branches_effective) }) }}
                    </p>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">{{ t('platform.internal_notes') }}</label>
                <textarea
                    v-model="form.internal_notes"
                    class="form-control"
                    rows="4"
                    maxlength="5000"
                    :placeholder="t('platform.internal_notes_help')"
                />
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
            <Link :href="`/platform/tenants/${tenant.id}`" class="btn btn-link">{{ t('common.cancel') }}</Link>
        </form>
        <div v-else class="mt-2">
            <Link :href="`/platform/tenants/${tenant.id}`" class="btn btn-link px-0">{{ t('common.cancel') }}</Link>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useSubscriptionEndsConfirm } from '@/composables/useSubscriptionEndsConfirm';
import { isoToDateInput } from '@/utils/dates';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    tenant: { type: Object, required: true },
    plans: { type: Array, required: true },
    resellers: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: true },
    canManageOwner: { type: Boolean, default: false },
    canAddOwner: { type: Boolean, default: false },
    owner: { type: Object, default: null },
    canResendOwnerInvite: { type: Boolean, default: false },
});

const { t } = useLocale();

const initialSubscriptionEnds = isoToDateInput(props.tenant.subscription_ends_at);

function initLimitOverride(val) {
    if (val === 'inherit' || val === null || val === undefined) {
        return '';
    }
    return String(val);
}

function formatLimit(val) {
    if (val === null || val === undefined) {
        return t('platform.limit_unlimited_placeholder');
    }
    return String(val);
}

const form = useForm({
    name: props.tenant.name,
    is_active: props.tenant.is_active,
    trial_ends_at: isoToDateInput(props.tenant.trial_ends_at),
    subscription_ends_at: initialSubscriptionEnds,
    subscription_plan_id: props.tenant.subscription?.plan_id ?? null,
    internal_notes: props.tenant.internal_notes ?? '',
    reseller_id: props.tenant.reseller_id ?? null,
    wholesale_pricing_override: props.tenant.wholesale_pricing_override ?? 'inherit',
    multi_branch_override: props.tenant.multi_branch_override ?? 'inherit',
    supplier_branch_ledger_override: props.tenant.supplier_branch_ledger_override ?? 'inherit',
    employee_management_override: props.tenant.employee_management_override ?? 'inherit',
    attendance_override: props.tenant.attendance_override ?? 'inherit',
    hr_payroll_override: props.tenant.hr_payroll_override ?? 'inherit',
    max_products_override: initLimitOverride(props.tenant.max_products_override),
    max_import_rows_override: initLimitOverride(props.tenant.max_import_rows_override),
    max_branches_override: initLimitOverride(props.tenant.max_branches_override),
});

const { subscriptionInputKey, onSubscriptionEndsChange, committedSubscriptionEnds } = useSubscriptionEndsConfirm(
    form,
    t,
    initialSubscriptionEnds,
);

const ownerForm = useForm({
    owner_password: '',
    owner_password_confirmation: '',
    owner_invite: false,
});

const addOwnerForm = useForm({
    owner_name: '',
    owner_email: '',
    owner_password: '',
    owner_password_confirmation: '',
    owner_invite: true,
});

const resendInviteForm = useForm({});

function submit() {
    form.put(`/platform/tenants/${props.tenant.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            committedSubscriptionEnds.value = form.subscription_ends_at;
        },
    });
}

function submitOwnerAccess() {
    ownerForm.put(`/platform/tenants/${props.tenant.id}/owner`, {
        preserveScroll: true,
        onSuccess: () => {
            ownerForm.reset('owner_password', 'owner_password_confirmation');
            ownerForm.owner_invite = false;
        },
    });
}

function submitAddOwner() {
    addOwnerForm.post(`/platform/tenants/${props.tenant.id}/owner`, { preserveScroll: true });
}

function resendInvite() {
    resendInviteForm.post(`/platform/tenants/${props.tenant.id}/owner/resend-invite`, { preserveScroll: true });
}
</script>
