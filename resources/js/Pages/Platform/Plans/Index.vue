<template>
    <PlatformShellLayout :page-title="t('platform.plans_title')">
        <Head :title="t('platform.nav_plans')" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-muted small mb-0">{{ t('platform.plans_sub') }}</p>
            <Link href="/platform/plans/create" class="btn btn-primary btn-sm">{{ t('platform.new_plan') }}</Link>
        </div>
        <div class="row g-3">
            <div v-for="p in plans" :key="p.id" class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-1">{{ p.name }}</h2>
                        <p class="small text-muted mb-2"><code>{{ p.slug }}</code></p>
                        <p class="mb-1 small">Trial: {{ p.trial_days }} days</p>
                        <p class="mb-3 small fw-semibold">
                            {{ t('platform.plan_price') }}: {{ formatCents(p.price_cents, currency) }}
                        </p>
                        <Link :href="`/platform/plans/${p.id}/edit`" class="btn btn-sm btn-outline-primary">{{ t('common.edit') }}</Link>
                    </div>
                </div>
            </div>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    plans: { type: Array, required: true },
    currency: { type: String, default: 'BDT' },
});

const { t } = useLocale();
const { formatCents } = useMoney({ currency: props.currency });
</script>
