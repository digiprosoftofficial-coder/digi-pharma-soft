<template>
    <PlatformShellLayout :page-title="tenant.name">
        <Head :title="tenant.name" />
        <Link href="/platform/tenants" class="small text-decoration-none">← {{ t('platform.nav_pharmacies') }}</Link>
        <h1 class="h4 mt-2 mb-3">{{ t('common.edit') }}: {{ tenant.name }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
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
});

const { t } = useLocale();

const initialSubscriptionEnds = isoToDateInput(props.tenant.subscription_ends_at);

const form = useForm({
    name: props.tenant.name,
    is_active: props.tenant.is_active,
    trial_ends_at: isoToDateInput(props.tenant.trial_ends_at),
    subscription_ends_at: initialSubscriptionEnds,
    subscription_plan_id: props.tenant.subscription?.plan_id ?? null,
    internal_notes: props.tenant.internal_notes ?? '',
    reseller_id: props.tenant.reseller_id ?? null,
    wholesale_pricing_override: props.tenant.wholesale_pricing_override ?? 'inherit',
});

const { subscriptionInputKey, onSubscriptionEndsChange, committedSubscriptionEnds } = useSubscriptionEndsConfirm(
    form,
    t,
    initialSubscriptionEnds,
);

function submit() {
    form.put(`/platform/tenants/${props.tenant.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            committedSubscriptionEnds.value = form.subscription_ends_at;
        },
    });
}
</script>
