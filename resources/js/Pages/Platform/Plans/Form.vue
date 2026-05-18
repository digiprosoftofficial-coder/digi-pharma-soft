<template>
    <PlatformShellLayout :page-title="plan ? t('common.edit') : t('platform.new_plan')">
        <Head :title="plan ? 'Edit plan' : 'New plan'" />
        <h1 class="h4 mb-3">{{ plan ? t('common.edit') : t('platform.new_plan') }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">Name</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Slug</label>
                <input v-model="form.slug" class="form-control" required :disabled="!!plan" />
            </div>
            <div class="row g-2 mb-2">
                <div class="col-md-6">
                    <label class="form-label">{{ t('platform.plan_price_bdt') }}</label>
                    <input v-model.number="priceTaka" type="number" min="0" step="0.01" class="form-control" required />
                    <div class="form-text">
                        {{ t('platform.plan_price_preview') }}: {{ formatMoney(priceTaka, { currency }) }}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Trial days</label>
                    <input v-model.number="form.trial_days" type="number" min="0" class="form-control" required />
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input id="fpos" v-model="form.features.pos" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="fpos">POS</label>
                </div>
                <div class="form-check">
                    <input id="freports" v-model="form.features.reports" type="checkbox" class="form-check-input" />
                    <label class="form-check-label" for="freports">Reports</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
            <Link href="/platform/plans" class="btn btn-link">{{ t('common.cancel') }}</Link>
        </form>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    plan: { type: Object, default: null },
    currency: { type: String, default: 'BDT' },
});

const { t } = useLocale();
const { formatMoney } = useMoney({ currency: props.currency });

const priceTaka = ref(Number(props.plan?.price_cents ?? 0) / 100);

const form = useForm({
    name: props.plan?.name ?? '',
    slug: props.plan?.slug ?? '',
    price_cents: props.plan?.price_cents ?? 0,
    trial_days: props.plan?.trial_days ?? 14,
    features: {
        pos: props.plan?.features?.pos ?? true,
        reports: props.plan?.features?.reports ?? true,
    },
});

function submit() {
    form.price_cents = Math.round(Number(priceTaka.value || 0) * 100);

    if (props.plan) {
        form.put(`/platform/plans/${props.plan.id}`);
    } else {
        form.post('/platform/plans');
    }
}
</script>
