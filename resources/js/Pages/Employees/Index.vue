<template>
    <TenantShellLayout :page-title="t('employees.title')">
        <Head :title="t('employees.title')" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div v-if="formError" class="alert alert-danger small">{{ formError }}</div>
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0 d-lg-none">{{ t('employees.title') }}</h1>
            <Link v-if="can('employees.manage')" href="/employees/create" class="btn btn-primary btn-sm">{{ t('employees.new') }}</Link>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('employees.name') }}</th>
                            <th>{{ t('employees.employee_code') }}</th>
                            <th>{{ t('employees.phone') }}</th>
                            <th>{{ t('employees.designation') }}</th>
                            <th>{{ t('employees.linked_user') }}</th>
                            <th class="text-end">{{ t('employees.salary') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="e in employees.data" :key="e.id">
                            <td>
                                <Link :href="`/employees/${e.id}`" class="text-decoration-none">{{ e.name }}</Link>
                                <span v-if="!e.is_active" class="badge bg-secondary ms-1">Inactive</span>
                            </td>
                            <td>{{ e.employee_code }}</td>
                            <td>{{ e.phone || '—' }}</td>
                            <td>{{ e.designation || '—' }}</td>
                            <td>{{ e.user?.name || t('employees.no_linked_user') }}</td>
                            <td class="text-end">{{ e.salary != null ? formatMoney(e.salary) : '—' }}</td>
                            <td class="text-end text-nowrap">
                                <Link
                                    v-if="can('employees.manage')"
                                    :href="`/employees/${e.id}/edit`"
                                    class="btn btn-sm btn-outline-secondary me-1"
                                >
                                    {{ t('common.edit') }}
                                </Link>
                                <button
                                    v-if="can('employees.manage')"
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    @click="remove(e)"
                                >
                                    {{ t('common.delete') }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!employees.data?.length">
                            <td colspan="7" class="text-muted text-center py-3">—</td>
                        </tr>
                    </tbody>
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
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({ employees: { type: Object, required: true } });

const { t } = useLocale();
const { formatMoney } = useMoney();
const { can } = usePermissions();
const page = usePage();

const formError = computed(() => page.props.errors?.employee);

function remove(employee) {
    if (!window.confirm(t('employees.delete_confirm', { name: employee.name }))) {
        return;
    }
    router.delete(`/employees/${employee.id}`);
}
</script>
