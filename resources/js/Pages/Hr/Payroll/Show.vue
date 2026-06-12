<template>
    <TenantShellLayout :page-title="run.period">
        <Head :title="run.period" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <Link href="/hr/payroll" class="small text-decoration-none">← {{ t('employees.payroll_title') }}</Link>
        <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
            <h1 class="h4 mb-0">{{ run.period }}</h1>
            <button
                v-if="can('employees.manage') && run.status !== 'finalized'"
                type="button"
                class="btn btn-sm btn-primary"
                @click="finalize"
            >
                {{ t('employees.finalize_payroll') }}
            </button>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('employees.name') }}</th>
                            <th class="text-end">{{ t('employees.base_salary') }}</th>
                            <th class="text-end">{{ t('employees.deductions') }}</th>
                            <th class="text-end">{{ t('employees.net_pay') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="line in run.lines" :key="line.id">
                            <td>{{ line.employee?.name }}</td>
                            <td class="text-end">{{ formatMoney(line.base_salary) }}</td>
                            <td class="text-end">{{ formatMoney(line.deductions) }}</td>
                            <td class="text-end">{{ formatMoney(line.net_pay) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-medium">
                            <td colspan="3" class="text-end">{{ t('employees.net_pay') }}</td>
                            <td class="text-end">{{ formatMoney(run.total_amount) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({ run: { type: Object, required: true } });

const { t } = useLocale();
const { formatMoney } = useMoney();
const { can } = usePermissions();

function finalize() {
    router.post(`/hr/payroll/${props.run.id}/finalize`);
}
</script>
