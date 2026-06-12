<template>
    <TenantShellLayout :page-title="t('employees.payroll_title')">
        <Head :title="t('employees.payroll_title')" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div v-if="formError" class="alert alert-danger small">{{ formError }}</div>
        <h1 class="h4 mb-3">{{ t('employees.payroll_title') }}</h1>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm card-body">
                    <div class="text-muted small">{{ t('employees.active_employees') }}</div>
                    <div class="h5 mb-0">{{ summary.active_employees }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm card-body">
                    <div class="text-muted small">{{ t('employees.pending_leave') }}</div>
                    <div class="h5 mb-0">{{ summary.pending_leave }}</div>
                </div>
            </div>
        </div>
        <form v-if="can('employees.manage')" class="card border-0 shadow-sm card-body mb-3 d-flex flex-wrap gap-2 align-items-end" @submit.prevent="generate">
            <div>
                <label class="form-label small">{{ t('employees.payroll_period') }}</label>
                <input v-model="period" type="month" class="form-control form-control-sm" required />
            </div>
            <button type="submit" class="btn btn-sm btn-primary" :disabled="generating">{{ t('employees.generate_payroll') }}</button>
        </form>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('employees.payroll_period') }}</th>
                            <th>{{ t('employees.status') }}</th>
                            <th class="text-end">{{ t('employees.net_pay') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="run in runs.data" :key="run.id">
                            <td>{{ run.period }}</td>
                            <td>{{ run.status }}</td>
                            <td class="text-end">{{ formatMoney(run.total_amount) }}</td>
                            <td class="text-end">
                                <Link :href="`/hr/payroll/${run.id}`" class="btn btn-sm btn-outline-secondary">{{ t('common.edit') }}</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2">
            <Link href="/hr/leave-types" class="btn btn-sm btn-outline-secondary">{{ t('employees.leave_types') }}</Link>
            <Link href="/hr/leave-requests" class="btn btn-sm btn-outline-secondary">{{ t('employees.leave_requests') }}</Link>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    runs: { type: Object, required: true },
    summary: { type: Object, required: true },
});

const { t } = useLocale();
const { formatMoney } = useMoney();
const { can } = usePermissions();
const page = usePage();
const formError = computed(() => page.props.errors?.payroll);
const period = ref(new Date().toISOString().slice(0, 7));
const generating = ref(false);

function generate() {
    generating.value = true;
    router.post('/hr/payroll', { period: period.value }, {
        onFinish: () => { generating.value = false; },
    });
}
</script>
