<template>
    <PlatformShellLayout :page-title="t('platform.billing_title')">
        <Head :title="t('platform.billing_title')" />
        <p class="text-muted small mb-4">{{ t('platform.billing_sub') }}</p>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="text-muted small text-uppercase fw-semibold">{{ t('platform.billing_mrr') }}</span>
                        <p class="h4 fw-semibold mb-0 mt-2">{{ formatCents(metrics.mrr_cents, metrics.currency) }}</p>
                    </div>
                </article>
            </div>
            <div class="col-sm-6 col-xl-3">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="text-muted small text-uppercase fw-semibold">{{ t('platform.billing_collected_month') }}</span>
                        <p class="h4 fw-semibold mb-0 mt-2">
                            {{ formatCents(metrics.collected_this_month_cents, metrics.currency) }}
                        </p>
                    </div>
                </article>
            </div>
            <div class="col-sm-6 col-xl-3">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="text-muted small text-uppercase fw-semibold">{{ t('platform.billing_past_due') }}</span>
                        <p class="h4 fw-semibold mb-0 mt-2 text-warning">{{ metrics.past_due_tenants }}</p>
                    </div>
                </article>
            </div>
            <div class="col-sm-6 col-xl-3">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="text-muted small text-uppercase fw-semibold">{{ t('platform.billing_open_invoices') }}</span>
                        <p class="h4 fw-semibold mb-0 mt-2">{{ metrics.open_invoices }}</p>
                    </div>
                </article>
            </div>
        </div>

        <div v-if="pastDueTenants.length" class="alert alert-warning small mb-4">
            <strong>{{ t('platform.billing_past_due') }}:</strong>
            <ul class="mb-0 mt-2">
                <li v-for="row in pastDueTenants" :key="row.id">
                    <Link :href="`/platform/tenants/${row.id}`" class="alert-link">{{ row.name }}</Link>
                    — {{ t('platform.billing_grace_until') }} {{ formatDate(row.grace_period_ends_at) }}
                </li>
            </ul>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <form class="card border-0 shadow-sm card-body" @submit.prevent="submitInvoice">
                    <h2 class="h6">{{ t('platform.billing_create_invoice') }}</h2>
                    <div class="mb-3">
                        <label class="form-label small">{{ t('platform.analytics_pharmacy') }}</label>
                        <select v-model="invoiceForm.tenant_id" class="form-select form-select-sm" required>
                            <option value="">—</option>
                            <option v-for="row in tenants" :key="row.id" :value="row.id">{{ row.name }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">{{ t('platform.nav_plans') }}</label>
                        <select v-model="invoiceForm.subscription_plan_id" class="form-select form-select-sm">
                            <option value="">{{ t('common.optional') }}</option>
                            <option v-for="p in plans" :key="p.id" :value="p.id">
                                {{ p.name }} ({{ formatCents(p.price_cents, metrics.currency) }})
                            </option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">
                            {{ t('platform.billing_amount_in_currency', { currency: metrics.currency }) }}
                        </label>
                        <input
                            v-model.number="amountMajor"
                            type="number"
                            min="0"
                            step="0.01"
                            class="form-control form-control-sm"
                            :placeholder="t('platform.billing_amount_optional')"
                        />
                        <div v-if="amountMajor > 0" class="form-text">
                            {{ t('platform.billing_amount_preview') }}:
                            {{ formatMoney(amountMajor, { currency: metrics.currency }) }}
                        </div>
                        <div v-else class="form-text">{{ t('platform.billing_amount_optional') }}</div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm" :disabled="invoiceForm.processing">
                        {{ t('platform.billing_create_invoice') }}
                    </button>
                </form>
                <p v-if="stripe_configured" class="small text-muted mt-2 mb-0">{{ t('platform.billing_stripe_ready') }}</p>
            </div>
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex flex-wrap gap-2 align-items-center">
                        <span class="fw-semibold">{{ t('platform.billing_invoices') }}</span>
                        <select v-model="statusFilter" class="form-select form-select-sm ms-auto" style="width: auto" @change="applyFilter">
                            <option value="all">{{ t('platform.filter_all') }}</option>
                            <option value="open">{{ t('platform.billing_status_open') }}</option>
                            <option value="paid">{{ t('platform.billing_status_paid') }}</option>
                            <option value="void">{{ t('platform.billing_status_void') }}</option>
                            <option value="uncollectible">{{ t('platform.billing_status_uncollectible') }}</option>
                        </select>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ t('platform.billing_invoice_no') }}</th>
                                    <th>{{ t('platform.analytics_pharmacy') }}</th>
                                    <th class="text-end">{{ t('platform.billing_amount') }}</th>
                                    <th>{{ t('platform.health_status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="inv in invoices.data" :key="inv.id">
                                    <td class="small">{{ inv.invoice_no }}</td>
                                    <td class="small">
                                        <Link :href="`/platform/tenants/${inv.tenant_id}`">{{ inv.tenant_name }}</Link>
                                    </td>
                                    <td class="text-end small">{{ formatCents(inv.amount_cents, inv.currency) }}</td>
                                    <td class="small">
                                        <span class="badge" :class="statusBadgeClass(inv.status)">{{ inv.status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                            :aria-expanded="openMenuId === inv.id"
                                            @click.stop="toggleMenu(inv, $event)"
                                        >
                                            {{ t('common.actions') }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="!invoices.data?.length">
                                    <td colspan="5" class="text-muted small">{{ t('common.no_results') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmModal
            :show="showEditModal"
            :title="t('platform.billing_edit_invoice')"
            :confirm-label="t('common.save')"
            confirm-class="btn-primary"
            :processing="editForm.processing"
            @close="closeEditModal"
            @confirm="submitEdit"
        >
            <p class="small text-muted mb-3">{{ editingInvoice?.invoice_no }} — {{ editingInvoice?.tenant_name }}</p>
            <div class="mb-3">
                <label class="form-label small">{{ t('platform.nav_plans') }}</label>
                <select v-model="editForm.subscription_plan_id" class="form-select form-select-sm">
                    <option value="">{{ t('common.optional') }}</option>
                    <option v-for="p in plans" :key="p.id" :value="p.id">
                        {{ p.name }} ({{ formatCents(p.price_cents, metrics.currency) }})
                    </option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label small">
                    {{ t('platform.billing_amount_in_currency', { currency: metrics.currency }) }}
                </label>
                <input v-model.number="editAmountMajor" type="number" min="0" step="0.01" class="form-control form-control-sm" />
                <div v-if="editAmountMajor > 0" class="form-text">
                    {{ t('platform.billing_amount_preview') }}:
                    {{ formatMoney(editAmountMajor, { currency: metrics.currency }) }}
                </div>
            </div>
            <div class="mb-0">
                <label class="form-label small">{{ t('platform.billing_due_date') }}</label>
                <input v-model="editForm.due_at" type="date" class="form-control form-control-sm" />
            </div>
        </ConfirmModal>

        <Teleport to="body">
            <ul
                v-if="menuInvoice"
                class="dropdown-menu show shadow-sm billing-invoice-dropdown"
                :style="menuPosition"
                @click.stop
            >
                <li>
                    <a
                        class="dropdown-item"
                        :href="`/platform/billing/invoices/${menuInvoice.id}/preview`"
                        target="_blank"
                        rel="noopener"
                        @click="closeMenu"
                    >
                        {{ t('platform.billing_print_preview') }}
                    </a>
                </li>
                <li>
                    <a
                        class="dropdown-item"
                        :href="`/platform/billing/invoices/${menuInvoice.id}/pdf`"
                        target="_blank"
                        rel="noopener"
                        @click="closeMenu"
                    >
                        {{ t('platform.billing_download_pdf') }}
                    </a>
                </li>
                <template v-if="menuInvoice.status === 'open'">
                    <li><hr class="dropdown-divider" /></li>
                    <li>
                        <button type="button" class="dropdown-item" @click="openEditModal(menuInvoice)">
                            {{ t('common.edit') }}
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item text-success" @click="markPaid(menuInvoice.id)">
                            {{ t('platform.billing_mark_paid') }}
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item text-danger" @click="markFailed(menuInvoice.id)">
                            {{ t('platform.billing_mark_failed') }}
                        </button>
                    </li>
                    <li><hr class="dropdown-divider" /></li>
                    <li>
                        <button
                            type="button"
                            class="dropdown-item text-danger fw-semibold"
                            @click="openVoidModal(menuInvoice)"
                        >
                            {{ t('platform.billing_delete_invoice') }}
                        </button>
                    </li>
                </template>
            </ul>
        </Teleport>

        <ConfirmModal
            :show="showVoidModal"
            :title="t('platform.billing_delete_invoice')"
            :confirm-label="t('common.delete')"
            confirm-class="btn-danger"
            :processing="voidForm.processing"
            @close="closeVoidModal"
            @confirm="submitVoid"
        >
            <p class="small text-muted mb-2">{{ t('platform.billing_void_confirm') }}</p>
            <p class="small text-muted mb-0">{{ t('platform.billing_delete_invoice_hint') }}</p>
            <p v-if="voidingInvoice" class="small fw-semibold mt-2 mb-0">{{ voidingInvoice.invoice_no }}</p>
        </ConfirmModal>
    </PlatformShellLayout>
</template>

<script setup>
import ConfirmModal from '@/Components/ConfirmModal.vue';
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { formatHumanDate as formatDate } from '@/utils/dates';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    metrics: { type: Object, required: true },
    invoices: { type: Object, required: true },
    pastDueTenants: { type: Array, default: () => [] },
    tenants: { type: Array, default: () => [] },
    plans: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    stripe_configured: { type: Boolean, default: false },
});

const { t } = useLocale();
const { formatMoney, formatCents } = useMoney({ currency: props.metrics.currency });
const statusFilter = ref(props.filters.status ?? 'all');
const amountMajor = ref(null);

const showEditModal = ref(false);
const showVoidModal = ref(false);
const editingInvoice = ref(null);
const voidingInvoice = ref(null);
const editAmountMajor = ref(null);

const invoiceForm = useForm({
    tenant_id: '',
    subscription_plan_id: '',
});

const editForm = useForm({
    subscription_plan_id: '',
    due_at: '',
});

const voidForm = useForm({});
const openMenuId = ref(null);
const menuInvoice = ref(null);
const menuPosition = ref({});

function toggleMenu(inv, event) {
    if (openMenuId.value === inv.id) {
        closeMenu();

        return;
    }

    const button = event.currentTarget;
    const rect = button.getBoundingClientRect();
    const menuWidth = 220;
    const menuHeight = inv.status === 'open' ? 260 : 48;

    let top = rect.bottom + 4;
    let left = rect.right - menuWidth;

    if (left < 8) {
        left = 8;
    }

    if (left + menuWidth > window.innerWidth - 8) {
        left = window.innerWidth - menuWidth - 8;
    }

    if (top + menuHeight > window.innerHeight - 8) {
        top = Math.max(8, rect.top - menuHeight - 4);
    }

    menuInvoice.value = inv;
    openMenuId.value = inv.id;
    menuPosition.value = {
        position: 'fixed',
        top: `${top}px`,
        left: `${left}px`,
        minWidth: `${Math.max(rect.width, menuWidth)}px`,
        zIndex: 1060,
    };
}

function closeMenu() {
    openMenuId.value = null;
    menuInvoice.value = null;
    menuPosition.value = {};
}

function onDocumentClick(event) {
    if (event.target.closest('.billing-invoice-dropdown')) {
        return;
    }
    closeMenu();
}

function onViewportChange() {
    closeMenu();
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    window.addEventListener('scroll', onViewportChange, true);
    window.addEventListener('resize', onViewportChange);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    window.removeEventListener('scroll', onViewportChange, true);
    window.removeEventListener('resize', onViewportChange);
});

function statusBadgeClass(status) {
    const map = {
        open: 'text-bg-primary',
        paid: 'text-bg-success',
        void: 'text-bg-secondary',
        uncollectible: 'text-bg-danger',
    };

    return map[status] ?? 'text-bg-secondary';
}

function submitInvoice() {
    invoiceForm
        .transform((data) => ({
            tenant_id: data.tenant_id,
            subscription_plan_id: data.subscription_plan_id || null,
            amount_cents:
                amountMajor.value != null && amountMajor.value !== '' && Number(amountMajor.value) > 0
                    ? Math.round(Number(amountMajor.value) * 100)
                    : null,
        }))
        .post('/platform/billing/invoices', {
            preserveScroll: true,
            onSuccess: () => {
                invoiceForm.reset();
                amountMajor.value = null;
            },
        });
}

function applyFilter() {
    router.get('/platform/billing', { status: statusFilter.value }, { preserveState: true, replace: true });
}

function markPaid(id) {
    closeMenu();
    router.post(`/platform/billing/invoices/${id}/paid`, {}, { preserveScroll: true });
}

function markFailed(id) {
    closeMenu();
    if (!confirm(t('platform.billing_mark_failed_confirm'))) {
        return;
    }
    router.post(`/platform/billing/invoices/${id}/failed`, {}, { preserveScroll: true });
}

function openEditModal(inv) {
    closeMenu();
    editingInvoice.value = inv;
    editForm.subscription_plan_id = inv.subscription_plan_id ?? '';
    editForm.due_at = inv.due_at_date ?? '';
    editAmountMajor.value = inv.amount_cents ? inv.amount_cents / 100 : null;
    editForm.clearErrors();
    showEditModal.value = true;
}

function closeEditModal() {
    if (editForm.processing) {
        return;
    }
    showEditModal.value = false;
    editingInvoice.value = null;
}

function submitEdit() {
    if (!editingInvoice.value) {
        return;
    }

    editForm
        .transform((data) => ({
            subscription_plan_id: data.subscription_plan_id || null,
            due_at: data.due_at || null,
            amount_cents:
                editAmountMajor.value != null && editAmountMajor.value !== '' && Number(editAmountMajor.value) > 0
                    ? Math.round(Number(editAmountMajor.value) * 100)
                    : null,
        }))
        .put(`/platform/billing/invoices/${editingInvoice.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                closeEditModal();
                editForm.reset();
                editAmountMajor.value = null;
            },
        });
}

function openVoidModal(inv) {
    closeMenu();
    voidingInvoice.value = inv;
    voidForm.clearErrors();
    showVoidModal.value = true;
}

function closeVoidModal() {
    if (voidForm.processing) {
        return;
    }
    showVoidModal.value = false;
    voidingInvoice.value = null;
}

function submitVoid() {
    if (!voidingInvoice.value) {
        return;
    }

    voidForm.delete(`/platform/billing/invoices/${voidingInvoice.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeVoidModal();
        },
    });
}
</script>

<style scoped>
.billing-invoice-dropdown {
    display: block;
    margin: 0;
}
</style>
