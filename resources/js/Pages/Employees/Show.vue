<template>
    <TenantShellLayout :page-title="employee.name">
        <Head :title="employee.name" />
        <Link href="/employees" class="small text-decoration-none">← {{ t('employees.title') }}</Link>
        <div class="d-flex justify-content-between align-items-start mt-2 mb-3">
            <div>
                <h1 class="h4 mb-1">{{ employee.name }}</h1>
                <div class="text-muted small">{{ employee.employee_code }}</div>
            </div>
            <Link v-if="can('employees.manage')" :href="`/employees/${employee.id}/edit`" class="btn btn-sm btn-outline-secondary">
                {{ t('common.edit') }}
            </Link>
        </div>
        <div class="card border-0 shadow-sm card-body">
            <dl class="row mb-0 small">
                <dt class="col-sm-3">{{ t('employees.designation') }}</dt>
                <dd class="col-sm-9">{{ employee.designation || '—' }}</dd>
                <dt class="col-sm-3">{{ t('employees.phone') }}</dt>
                <dd class="col-sm-9">{{ employee.phone || '—' }}</dd>
                <dt class="col-sm-3">{{ t('employees.hire_date') }}</dt>
                <dd class="col-sm-9">{{ employee.hire_date?.slice?.(0, 10) || '—' }}</dd>
                <dt class="col-sm-3">{{ t('employees.salary') }}</dt>
                <dd class="col-sm-9">{{ employee.salary != null ? formatMoney(employee.salary) : '—' }}</dd>
                <dt class="col-sm-3">{{ t('employees.linked_user') }}</dt>
                <dd class="col-sm-9">
                    <template v-if="employee.user">
                        {{ employee.user.name }} ({{ employee.user.email }})
                        <Link v-if="can('team.users.manage')" :href="`/team/users/${employee.user.id}/edit`" class="ms-2 small">
                            {{ t('common.edit') }}
                        </Link>
                    </template>
                    <span v-else>{{ t('employees.no_linked_user') }}</span>
                </dd>
                <dt v-if="employee.default_branch" class="col-sm-3">{{ t('employees.default_branch') }}</dt>
                <dd v-if="employee.default_branch" class="col-sm-9">{{ employee.default_branch.name }}</dd>
            </dl>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ employee: { type: Object, required: true } });

const { t } = useLocale();
const { formatMoney } = useMoney();
const { can } = usePermissions();
</script>
