<template>
    <TenantShellLayout :page-title="employee ? t('employees.edit') : t('employees.new')">
        <Head :title="employee ? t('employees.edit') : t('employees.new')" />
        <h1 class="h4 mb-3">{{ employee ? t('employees.edit') : t('employees.new') }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="row g-2">
                <div class="col-md-6 mb-2">
                    <label class="form-label">{{ t('employees.employee_code') }}</label>
                    <input v-model="form.employee_code" class="form-control" required />
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">{{ t('employees.name') }}</label>
                    <input v-model="form.name" class="form-control" required />
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">{{ t('employees.phone') }}</label>
                    <input v-model="form.phone" class="form-control" />
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">{{ t('employees.designation') }}</label>
                    <input v-model="form.designation" class="form-control" />
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">{{ t('employees.hire_date') }}</label>
                    <input v-model="form.hire_date" type="date" class="form-control" />
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">{{ t('employees.salary') }}</label>
                    <input v-model="form.salary" type="number" min="0" step="0.01" class="form-control" />
                </div>
                <div class="col-md-6 mb-2">
                    <label class="form-label">{{ t('employees.link_user') }}</label>
                    <select v-model="form.user_id" class="form-select">
                        <option :value="null">—</option>
                        <option v-for="u in linkableUsers" :key="u.id" :value="u.id">{{ u.name }} ({{ u.email }})</option>
                    </select>
                    <div class="form-text">{{ t('employees.link_user_help') }}</div>
                </div>
                <div v-if="multiBranch && branches.length" class="col-md-6 mb-2">
                    <label class="form-label">{{ t('employees.default_branch') }}</label>
                    <select v-model="form.default_branch_id" class="form-select">
                        <option :value="null">—</option>
                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }} ({{ b.code }})</option>
                    </select>
                </div>
                <div class="col-12 mb-2">
                    <div class="form-check">
                        <input id="is_active" v-model="form.is_active" type="checkbox" class="form-check-input" />
                        <label class="form-check-label" for="is_active">{{ t('employees.is_active') }}</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
            <Link href="/employees" class="btn btn-link">{{ t('common.cancel') }}</Link>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    employee: { type: Object, default: null },
    linkableUsers: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
});

const { t } = useLocale();
const page = usePage();
const multiBranch = computed(() => page.props.features?.multi_branch ?? false);

const form = useForm({
    employee_code: props.employee?.employee_code ?? '',
    name: props.employee?.name ?? '',
    phone: props.employee?.phone ?? '',
    designation: props.employee?.designation ?? '',
    hire_date: props.employee?.hire_date?.slice?.(0, 10) ?? '',
    salary: props.employee?.salary ?? '',
    user_id: props.employee?.user_id ?? null,
    default_branch_id: props.employee?.default_branch_id ?? null,
    is_active: props.employee?.is_active ?? true,
});

function submit() {
    if (props.employee) {
        form.put(`/employees/${props.employee.id}`);
    } else {
        form.post('/employees');
    }
}
</script>
