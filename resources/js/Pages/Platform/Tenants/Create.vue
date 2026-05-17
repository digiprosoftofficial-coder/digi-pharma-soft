<template>
    <PlatformShellLayout :page-title="t('platform.add_pharmacy')">
        <Head :title="t('platform.add_pharmacy')" />
        <ul class="nav nav-pills mb-4 small">
            <li v-for="(label, i) in stepLabels" :key="i" class="nav-item">
                <span class="nav-link" :class="{ active: step === i + 1, disabled: step < i + 1 }">{{ i + 1 }}. {{ label }}</span>
            </li>
        </ul>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="onSubmit">
            <div v-if="Object.keys(form.errors).length" class="alert alert-danger small" role="alert">
                {{ t('platform.form_has_errors') }}
            </div>
            <div v-show="step === 1">
                <div class="mb-2">
                    <label class="form-label">Name</label>
                    <input v-model="form.name" class="form-control" :class="{ 'is-invalid': form.errors.name }" required @input="maybeSlug" />
                    <div v-if="form.errors.name" class="invalid-feedback d-block">{{ form.errors.name }}</div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Slug</label>
                    <input v-model="form.slug" class="form-control" :class="{ 'is-invalid': form.errors.slug }" required />
                    <div v-if="form.errors.slug" class="invalid-feedback d-block">{{ form.errors.slug }}</div>
                    <div class="form-text">{{ t('platform.slug_help') }}</div>
                </div>
            </div>
            <div v-show="step === 2">
                <div class="form-check mb-3">
                    <input id="later" v-model="form.add_owner_later" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="later">{{ t('platform.add_owner_later') }}</label>
                    <div class="form-text">{{ t('platform.owner_later_help') }}</div>
                </div>
                <template v-if="!form.add_owner_later">
                    <div class="mb-2">
                        <label class="form-label">Owner name</label>
                        <input v-model="form.owner_name" class="form-control" :class="{ 'is-invalid': form.errors.owner_name }" :required="!form.add_owner_later" />
                        <div v-if="form.errors.owner_name" class="invalid-feedback d-block">{{ form.errors.owner_name }}</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Owner email</label>
                        <input v-model="form.owner_email" type="email" class="form-control" :class="{ 'is-invalid': form.errors.owner_email }" :required="!form.add_owner_later" />
                        <div v-if="form.errors.owner_email" class="invalid-feedback d-block">{{ form.errors.owner_email }}</div>
                    </div>
                    <div class="form-check mb-3">
                        <input id="owner-invite-create" v-model="form.owner_invite" type="checkbox" class="form-check-input" />
                        <label class="form-check-label" for="owner-invite-create">{{ t('platform.owner_invite_by_email') }}</label>
                        <div class="form-text">{{ t('platform.owner_invite_help') }}</div>
                    </div>
                    <template v-if="!form.owner_invite">
                        <div class="mb-2">
                            <label class="form-label">Password</label>
                            <input
                                v-model="form.owner_password"
                                type="password"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.owner_password }"
                                :required="!form.add_owner_later && !form.owner_invite"
                            />
                            <div v-if="form.errors.owner_password" class="invalid-feedback d-block">{{ form.errors.owner_password }}</div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Confirm password</label>
                            <input v-model="form.owner_password_confirmation" type="password" class="form-control" />
                        </div>
                    </template>
                </template>
            </div>
            <div v-show="step === 3">
                <div class="mb-2">
                    <label class="form-label">Plan</label>
                    <select v-model="form.subscription_plan_id" class="form-select" :class="{ 'is-invalid': form.errors.subscription_plan_id }" required @change="onPlanChange">
                        <option :value="null" disabled>Select plan</option>
                        <option v-for="p in plans" :key="p.id" :value="p.id">{{ p.name }} ({{ p.trial_days }}d trial)</option>
                    </select>
                    <div v-if="form.errors.subscription_plan_id" class="invalid-feedback d-block">{{ form.errors.subscription_plan_id }}</div>
                </div>
                <div class="row g-2">
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
                <div class="alert alert-light border mt-3 small">
                    <strong>{{ form.name || '—' }}</strong> · <code>{{ form.slug || '—' }}</code>
                </div>
            </div>
            <div class="d-flex justify-content-between mt-4">
                <button v-if="step > 1" type="button" class="btn btn-outline-secondary" @click="step--">{{ t('common.previous') }}</button>
                <span v-else></span>
                <button v-if="step < 3" type="button" class="btn btn-primary" @click="nextStep">{{ t('common.next') }}</button>
                <button v-else type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.submit') }}</button>
            </div>
        </form>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useSubscriptionEndsConfirm } from '@/composables/useSubscriptionEndsConfirm';
import { addDays, formatDateInput, oneYearFromToday } from '@/utils/dates';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    plans: { type: Array, required: true },
    default_trial_days: { type: Number, default: 14 },
});

const { t } = useLocale();
const step = ref(1);

const stepLabels = computed(() => [
    t('platform.wizard_step_pharmacy'),
    t('platform.wizard_step_owner'),
    t('platform.wizard_step_plan'),
]);

const form = useForm({
    name: '',
    slug: '',
    add_owner_later: false,
    owner_invite: true,
    owner_name: '',
    owner_email: '',
    owner_password: '',
    owner_password_confirmation: '',
    subscription_plan_id: props.plans[0]?.id ?? null,
    trial_ends_at: '',
    subscription_ends_at: oneYearFromToday(),
});

const {
    subscriptionInputKey,
    onSubscriptionEndsChange,
    applyTrialFromPlan,
} = useSubscriptionEndsConfirm(form, t, oneYearFromToday());

function maybeSlug() {
    if (!form.slug && form.name) {
        form.slug = form.name
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
    }
}

function trialForSelectedPlan() {
    const plan = props.plans.find((p) => p.id === form.subscription_plan_id);
    if (!plan) {
        return '';
    }
    return formatDateInput(addDays(new Date(), Number(plan.trial_days || props.default_trial_days || 14)));
}

function onPlanChange() {
    applyTrialFromPlan(trialForSelectedPlan());
}

function nextStep() {
    if (step.value === 2) {
        onPlanChange();
    }
    if (step.value < 3) {
        step.value += 1;
    }
}

function onSubmit() {
    form.post('/platform/tenants', {
        preserveScroll: true,
        onError: () => {
            step.value = 3;
        },
    });
}

onPlanChange();
</script>
