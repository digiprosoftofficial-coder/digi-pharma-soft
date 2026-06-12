<template>
    <TenantShellLayout :page-title="t('employees.leave_types')">
        <Head :title="t('employees.leave_types')" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <Link href="/hr/payroll" class="small text-decoration-none">← {{ t('employees.payroll_title') }}</Link>
        <h1 class="h4 mt-2 mb-3">{{ t('employees.leave_types') }}</h1>
        <form v-if="can('employees.manage')" class="card border-0 shadow-sm card-body mb-3" @submit.prevent="create">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">{{ t('employees.name') }}</label>
                    <input v-model="form.name" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ t('employees.days_per_year') }}</label>
                    <input v-model.number="form.days_per_year" type="number" min="0" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input id="is_paid" v-model="form.is_paid" type="checkbox" class="form-check-input" />
                        <label class="form-check-label" for="is_paid">{{ t('employees.is_paid') }}</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100" :disabled="form.processing">{{ t('common.save') }}</button>
                </div>
            </div>
        </form>
        <div class="card border-0 shadow-sm">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('employees.name') }}</th>
                        <th>{{ t('employees.days_per_year') }}</th>
                        <th>{{ t('employees.is_paid') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="lt in leaveTypes" :key="lt.id">
                        <td>{{ lt.name }}</td>
                        <td>{{ lt.days_per_year }}</td>
                        <td>{{ lt.is_paid ? t('common.yes') : t('common.no') }}</td>
                        <td class="text-end">
                            <button v-if="can('employees.manage')" type="button" class="btn btn-sm btn-outline-danger" @click="remove(lt)">
                                {{ t('common.delete') }}
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

defineProps({ leaveTypes: { type: Array, default: () => [] } });

const { t } = useLocale();
const { can } = usePermissions();

const form = useForm({ name: '', days_per_year: 0, is_paid: true });

function create() {
    form.post('/hr/leave-types', { onSuccess: () => form.reset() });
}

function remove(lt) {
    if (!window.confirm(t('employees.delete_confirm', { name: lt.name }))) {
        return;
    }
    router.delete(`/hr/leave-types/${lt.id}`);
}
</script>
