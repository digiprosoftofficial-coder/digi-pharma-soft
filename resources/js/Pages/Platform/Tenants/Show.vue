<template>
    <PlatformShellLayout :page-title="tenant.name">
        <Head :title="tenant.name" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <Link href="/platform/tenants" class="small text-decoration-none">← {{ t('platform.nav_pharmacies') }}</Link>
                <div class="d-flex align-items-center gap-2 mt-1">
                    <h1 class="h4 mb-0">{{ tenant.name }}</h1>
                    <TenantStatusBadge :status="tenant.status" />
                </div>
                <code class="small">{{ tenant.slug }}</code>
                <p v-if="tenant.reseller_name" class="small text-muted mb-0 mt-1">
                    {{ t('platform.reseller_label') }}: {{ tenant.reseller_name }}
                </p>
            </div>
            <div class="d-flex gap-2">
                <button
                    v-if="canImpersonate"
                    type="button"
                    class="btn btn-sm btn-primary"
                    @click="impersonate"
                >
                    {{ t('platform.impersonate') }}
                </button>
                <Link :href="`/platform/tenants/${tenant.id}/edit`" class="btn btn-sm btn-outline-primary">{{ t('common.edit') }}</Link>
                <button v-if="tenant.suspended_at" type="button" class="btn btn-sm btn-success" @click="openUnsuspendModal">
                    {{ t('common.unsuspend') }}
                </button>
                <button v-else type="button" class="btn btn-sm btn-outline-danger" @click="openSuspendModal">
                    {{ t('common.suspend') }}
                </button>
            </div>
        </div>
        <div v-if="tenant.internal_notes" class="alert alert-secondary small mb-3">
            <strong>{{ t('platform.internal_notes') }}:</strong>
            <div class="mt-1" style="white-space: pre-wrap">{{ tenant.internal_notes }}</div>
        </div>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm card-body">
                    <h2 class="h6">Subscription</h2>
                    <dl class="row small mb-0">
                        <dt class="col-5">Plan</dt>
                        <dd class="col-7">{{ tenant.subscription?.plan_name || '—' }}</dd>
                        <dt class="col-5">Trial ends</dt>
                        <dd class="col-7">{{ formatDate(tenant.trial_ends_at) }}</dd>
                        <dt class="col-5">Subscription ends</dt>
                        <dd class="col-7">{{ formatDate(tenant.subscription_ends_at) }}</dd>
                        <dt class="col-5">{{ t('platform.wholesale_override_label') }}</dt>
                        <dd class="col-7">
                            <span
                                class="badge"
                                :class="tenant.wholesale_pricing_enabled ? 'text-bg-success' : 'text-bg-secondary'"
                            >
                                {{
                                    tenant.wholesale_pricing_enabled
                                        ? t('platform.wholesale_effective_on')
                                        : t('platform.wholesale_effective_off')
                                }}
                            </span>
                            <span v-if="tenant.wholesale_pricing_override !== 'inherit'" class="text-muted small ms-1">
                                ({{ t(`platform.wholesale_override_${tenant.wholesale_pricing_override}`) }})
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm card-body">
                    <h2 class="h6">Users ({{ tenant.users?.length ?? 0 }})</h2>
                    <div
                        v-if="ownerInvitePending"
                        class="alert alert-info py-2 px-3 small mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2"
                    >
                        <span>{{ t('platform.owner_invite_pending', { email: ownerInvitePending.email }) }}</span>
                        <button
                            v-if="canResendOwnerInvite"
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            :disabled="resendInviteForm.processing"
                            @click="resendInvite"
                        >
                            {{ t('platform.owner_invite_resend') }}
                        </button>
                    </div>
                    <ul v-if="tenant.users?.length" class="list-unstyled small mb-0">
                        <li v-for="u in tenant.users" :key="u.id" class="mb-2">
                            <div>{{ u.name }} — {{ u.email }}</div>
                            <span v-if="u.invite_pending" class="badge text-bg-warning ms-1">Pending</span>
                            <div v-if="u.last_login_at" class="text-muted">
                                {{ t('platform.last_login') }}: {{ formatDate(u.last_login_at) }}
                            </div>
                            <div v-else class="text-muted">{{ t('platform.last_login') }}: —</div>
                        </li>
                    </ul>
                    <p v-else class="text-muted small mb-3">{{ t('platform.owner_later_help') }}</p>
                    <form
                        v-if="canAddOwner"
                        class="border-top pt-3 mt-2"
                        @submit.prevent="submitOwner"
                    >
                        <h3 class="h6 mb-3">{{ t('platform.add_owner_title') }}</h3>
                        <div class="mb-2">
                            <label class="form-label small">Owner name</label>
                            <input
                                v-model="ownerForm.owner_name"
                                type="text"
                                class="form-control form-control-sm"
                                :class="{ 'is-invalid': ownerForm.errors.owner_name }"
                                required
                            />
                            <div v-if="ownerForm.errors.owner_name" class="invalid-feedback d-block">
                                {{ ownerForm.errors.owner_name }}
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Owner email</label>
                            <input
                                v-model="ownerForm.owner_email"
                                type="email"
                                class="form-control form-control-sm"
                                :class="{ 'is-invalid': ownerForm.errors.owner_email }"
                                required
                            />
                            <div v-if="ownerForm.errors.owner_email" class="invalid-feedback d-block">
                                {{ ownerForm.errors.owner_email }}
                            </div>
                        </div>
                        <div class="form-check mb-3">
                            <input
                                id="owner-invite"
                                v-model="ownerForm.owner_invite"
                                type="checkbox"
                                class="form-check-input"
                            />
                            <label class="form-check-label small" for="owner-invite">
                                {{ t('platform.owner_invite_by_email') }}
                            </label>
                            <div class="form-text">{{ t('platform.owner_invite_help') }}</div>
                        </div>
                        <template v-if="!ownerForm.owner_invite">
                            <div class="mb-2">
                                <label class="form-label small">Password</label>
                                <input
                                    v-model="ownerForm.owner_password"
                                    type="password"
                                    class="form-control form-control-sm"
                                    :class="{ 'is-invalid': ownerForm.errors.owner_password }"
                                    required
                                />
                                <div v-if="ownerForm.errors.owner_password" class="invalid-feedback d-block">
                                    {{ ownerForm.errors.owner_password }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Confirm password</label>
                                <input
                                    v-model="ownerForm.owner_password_confirmation"
                                    type="password"
                                    class="form-control form-control-sm"
                                    required
                                />
                            </div>
                        </template>
                        <button type="submit" class="btn btn-primary btn-sm" :disabled="ownerForm.processing">
                            {{
                                ownerForm.owner_invite
                                    ? t('platform.add_owner_invite_submit')
                                    : t('platform.add_owner_submit')
                            }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div v-if="tenant.subscription_history?.length" class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">{{ t('platform.subscription_history') }}</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Starts</th>
                            <th>Ends</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sub in tenant.subscription_history" :key="sub.id">
                            <td>{{ sub.plan_name || '—' }}</td>
                            <td>{{ sub.status }}</td>
                            <td>{{ formatDate(sub.starts_at) }}</td>
                            <td>{{ formatDate(sub.ends_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                <span>{{ t('platform.billing_title') }}</span>
                <Link href="/platform/billing" class="small">{{ t('platform.billing_view') }}</Link>
            </div>
            <div class="card-body small">
                <p class="mb-2">
                    {{ t('platform.billing_status_label') }}:
                    <span class="badge text-bg-secondary">{{ billingStatus }}</span>
                    <span v-if="gracePeriodEndsAt" class="text-muted ms-2">
                        ({{ t('platform.billing_grace_until') }} {{ formatDate(gracePeriodEndsAt) }})
                    </span>
                </p>
                <ul v-if="tenantInvoices?.length" class="list-unstyled mb-0">
                    <li v-for="inv in tenantInvoices" :key="inv.id" class="d-flex justify-content-between border-top pt-2 mt-2">
                        <span>{{ inv.invoice_no }} — {{ inv.status }}</span>
                        <span>{{ formatInvoiceMoney(inv) }}</span>
                    </li>
                </ul>
                <p v-else class="text-muted mb-0">{{ t('platform.billing_no_invoices') }}</p>
            </div>
        </div>
        <div v-if="catalogTemplates?.length" class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">{{ t('platform.catalog_apply_title') }}</div>
            <div class="card-body small">
                <p class="text-muted mb-2">{{ t('platform.catalog_apply_help') }}</p>
                <form class="d-flex flex-wrap gap-2 align-items-end" @submit.prevent="applyCatalog">
                    <div class="flex-grow-1" style="min-width: 12rem">
                        <label class="form-label small mb-1">{{ t('platform.catalog_name') }}</label>
                        <select v-model="catalogForm.catalog_template_id" class="form-select form-select-sm" required>
                            <option :value="null" disabled>{{ t('platform.catalog_select_template') }}</option>
                            <option v-for="tpl in catalogTemplates" :key="tpl.id" :value="tpl.id">
                                {{ tpl.name }} ({{ tpl.items_count }})
                            </option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary" :disabled="catalogForm.processing">
                        {{ t('platform.catalog_apply') }}
                    </button>
                </form>
            </div>
        </div>
        <div v-if="canExportData || canPurgeData" class="card border-0 shadow-sm mt-3 border-warning-subtle">
            <div class="card-header bg-white fw-semibold">{{ t('platform.compliance_title') }}</div>
            <div class="card-body small">
                <p class="text-muted mb-3">{{ t('platform.compliance_help') }}</p>
                <div class="d-flex flex-wrap gap-2">
                    <a
                        v-if="canExportData"
                        :href="`/platform/tenants/${tenant.id}/export`"
                        class="btn btn-sm btn-outline-primary"
                    >
                        {{ t('platform.compliance_export') }}
                    </a>
                    <button
                        v-if="canPurgeData"
                        type="button"
                        class="btn btn-sm btn-outline-danger"
                        :disabled="!tenant.suspended_at"
                        @click="openPurgeModal"
                    >
                        {{ t('platform.compliance_purge') }}
                    </button>
                </div>
                <p v-if="canPurgeData && !tenant.suspended_at" class="text-warning mb-0 mt-2">
                    {{ t('platform.compliance_purge_requires_suspend') }}
                </p>
            </div>
        </div>
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">Activity</div>
            <ul class="list-group list-group-flush small">
                <li v-for="(a, i) in activities" :key="i" class="list-group-item d-flex justify-content-between">
                    <span>{{ a.description }}</span>
                    <span class="text-muted">{{ formatDate(a.created_at) }}</span>
                </li>
                <li v-if="!activities.length" class="list-group-item text-muted">{{ t('common.no_results') }}</li>
            </ul>
        </div>

        <ConfirmModal
            :show="showSuspendModal"
            :title="t('platform.suspend_modal_title', { name: tenant.name })"
            :confirm-label="t('common.suspend')"
            confirm-class="btn-danger"
            :processing="suspendForm.processing"
            @close="closeSuspendModal"
            @confirm="submitSuspend"
        >
            <p class="small text-muted mb-3">{{ t('platform.suspend_confirm') }}</p>
            <label class="form-label small mb-1" for="suspend-reason">
                {{ t('common.reason') }}
                <span class="text-muted">({{ t('common.optional') }})</span>
            </label>
            <textarea
                id="suspend-reason"
                v-model="suspendForm.reason"
                class="form-control form-control-sm"
                :class="{ 'is-invalid': suspendForm.errors.reason }"
                rows="3"
                maxlength="2000"
                :placeholder="t('platform.suspend_reason_placeholder')"
            />
            <div v-if="suspendForm.errors.reason" class="invalid-feedback d-block">
                {{ suspendForm.errors.reason }}
            </div>
        </ConfirmModal>

        <ConfirmModal
            :show="showPurgeModal"
            :title="t('platform.compliance_purge_modal_title', { name: tenant.name })"
            :confirm-label="t('platform.compliance_purge')"
            confirm-class="btn-danger"
            :processing="purgeForm.processing"
            @close="closePurgeModal"
            @confirm="submitPurge"
        >
            <p class="small text-danger mb-3">{{ t('platform.compliance_purge_warning') }}</p>
            <label class="form-label small mb-1" for="purge-reason">{{ t('common.reason') }}</label>
            <textarea
                id="purge-reason"
                v-model="purgeForm.reason"
                class="form-control form-control-sm mb-3"
                :class="{ 'is-invalid': purgeForm.errors.reason }"
                rows="3"
                maxlength="2000"
                required
            />
            <div v-if="purgeForm.errors.reason" class="invalid-feedback d-block mb-2">
                {{ purgeForm.errors.reason }}
            </div>
            <label class="form-label small mb-1" for="purge-slug">
                {{ t('platform.compliance_confirm_slug', { slug: tenant.slug }) }}
            </label>
            <input
                id="purge-slug"
                v-model="purgeForm.confirm_slug"
                type="text"
                class="form-control form-control-sm"
                :class="{ 'is-invalid': purgeForm.errors.confirm_slug || purgeForm.errors.purge }"
                :placeholder="tenant.slug"
                autocomplete="off"
                required
            />
            <div v-if="purgeForm.errors.confirm_slug" class="invalid-feedback d-block">
                {{ purgeForm.errors.confirm_slug }}
            </div>
            <div v-if="purgeForm.errors.purge" class="invalid-feedback d-block">
                {{ purgeForm.errors.purge }}
            </div>
        </ConfirmModal>

        <ConfirmModal
            :show="showUnsuspendModal"
            :title="t('platform.unsuspend_modal_title', { name: tenant.name })"
            :confirm-label="t('common.unsuspend')"
            confirm-class="btn-success"
            :processing="unsuspendForm.processing"
            @close="closeUnsuspendModal"
            @confirm="submitUnsuspend"
        >
            <p class="small text-muted mb-0">{{ t('platform.unsuspend_confirm') }}</p>
        </ConfirmModal>
    </PlatformShellLayout>
</template>

<script setup>
import ConfirmModal from '@/Components/ConfirmModal.vue';
import TenantStatusBadge from '@/Components/TenantStatusBadge.vue';
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    activities: { type: Array, default: () => [] },
    canAddOwner: { type: Boolean, default: false },
    canImpersonate: { type: Boolean, default: false },
    canResendOwnerInvite: { type: Boolean, default: false },
    ownerInvitePending: { type: Object, default: null },
    canExportData: { type: Boolean, default: false },
    canPurgeData: { type: Boolean, default: false },
    tenantInvoices: { type: Array, default: () => [] },
    billingStatus: { type: String, default: 'trialing' },
    gracePeriodEndsAt: { type: String, default: null },
    catalogTemplates: { type: Array, default: () => [] },
});

const { t } = useLocale();
const showSuspendModal = ref(false);
const showUnsuspendModal = ref(false);
const showPurgeModal = ref(false);
const suspendForm = useForm({ reason: '' });
const unsuspendForm = useForm({});
const purgeForm = useForm({ confirm_slug: '', reason: '' });
const catalogForm = useForm({ catalog_template_id: null });

const resendInviteForm = useForm({});

const ownerForm = useForm({
    owner_name: '',
    owner_email: '',
    owner_invite: true,
    owner_password: '',
    owner_password_confirmation: '',
});

function formatDate(iso) {
    if (!iso) {
        return '—';
    }

    return String(iso).slice(0, 10);
}

function formatInvoiceMoney(inv) {
    const amount = Number(inv.amount_cents || 0) / 100;
    const currency = inv.currency ?? 'BDT';

    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

function submitOwner() {
    ownerForm.post(`/platform/tenants/${props.tenant.id}/owner`, {
        preserveScroll: true,
        onSuccess: () => {
            ownerForm.reset();
            ownerForm.owner_invite = true;
        },
    });
}

function applyCatalog() {
    const tplId = catalogForm.catalog_template_id;
    if (!tplId) {
        return;
    }

    catalogForm
        .transform(() => ({ tenant_id: props.tenant.id }))
        .post(`/platform/catalog-templates/${tplId}/apply`, { preserveScroll: true });
}

function resendInvite() {
    resendInviteForm.post(`/platform/tenants/${props.tenant.id}/owner/resend-invite`, {
        preserveScroll: true,
    });
}

function openSuspendModal() {
    suspendForm.clearErrors();
    suspendForm.reason = '';
    showSuspendModal.value = true;
}

function closeSuspendModal() {
    if (suspendForm.processing) {
        return;
    }
    showSuspendModal.value = false;
}

function submitSuspend() {
    suspendForm.post(`/platform/tenants/${props.tenant.id}/suspend`, {
        preserveScroll: true,
        onSuccess: () => {
            showSuspendModal.value = false;
            suspendForm.reset();
        },
    });
}

function openUnsuspendModal() {
    unsuspendForm.clearErrors();
    showUnsuspendModal.value = true;
}

function closeUnsuspendModal() {
    if (unsuspendForm.processing) {
        return;
    }
    showUnsuspendModal.value = false;
}

function submitUnsuspend() {
    unsuspendForm.post(`/platform/tenants/${props.tenant.id}/unsuspend`, {
        preserveScroll: true,
        onSuccess: () => {
            showUnsuspendModal.value = false;
        },
    });
}

function impersonate() {
    if (!confirm(t('platform.impersonate_confirm'))) {
        return;
    }
    router.post(`/platform/tenants/${props.tenant.id}/impersonate`);
}

function openPurgeModal() {
    purgeForm.clearErrors();
    purgeForm.confirm_slug = '';
    purgeForm.reason = '';
    showPurgeModal.value = true;
}

function closePurgeModal() {
    if (purgeForm.processing) {
        return;
    }
    showPurgeModal.value = false;
}

function submitPurge() {
    purgeForm.post(`/platform/tenants/${props.tenant.id}/purge`, {
        preserveScroll: true,
        onSuccess: () => {
            showPurgeModal.value = false;
        },
    });
}
</script>
