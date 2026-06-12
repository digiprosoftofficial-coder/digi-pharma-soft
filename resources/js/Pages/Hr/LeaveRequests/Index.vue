<template>
    <TenantShellLayout :page-title="t('employees.leave_requests')">
        <Head :title="t('employees.leave_requests')" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <Link href="/hr/payroll" class="small text-decoration-none">← {{ t('employees.payroll_title') }}</Link>
        <h1 class="h4 mt-2 mb-3">{{ t('employees.leave_requests') }}</h1>
        <form v-if="can('employees.manage')" class="card border-0 shadow-sm card-body mb-3" @submit.prevent="submit">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label small">{{ t('employees.name') }}</label>
                    <select v-model="form.employee_id" class="form-select form-select-sm" required>
                        <option :value="null">—</option>
                        <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ t('employees.leave_types') }}</label>
                    <select v-model="form.leave_type_id" class="form-select form-select-sm" required>
                        <option :value="null">—</option>
                        <option v-for="lt in leaveTypes" :key="lt.id" :value="lt.id">{{ lt.name }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ t('employees.start_date') }}</label>
                    <input v-model="form.start_date" type="date" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ t('employees.end_date') }}</label>
                    <input v-model="form.end_date" type="date" class="form-control form-control-sm" required />
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary w-100" :disabled="form.processing">{{ t('common.save') }}</button>
                </div>
            </div>
        </form>
        <div class="card border-0 shadow-sm">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('employees.name') }}</th>
                        <th>{{ t('employees.leave_types') }}</th>
                        <th>{{ t('employees.start_date') }}</th>
                        <th>{{ t('employees.end_date') }}</th>
                        <th>{{ t('employees.days') }}</th>
                        <th>{{ t('employees.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="req in requests.data" :key="req.id">
                        <td>{{ req.employee?.name }}</td>
                        <td>{{ req.leave_type?.name }}</td>
                        <td>{{ req.start_date?.slice?.(0, 10) }}</td>
                        <td>{{ req.end_date?.slice?.(0, 10) }}</td>
                        <td>{{ req.days }}</td>
                        <td>{{ req.status }}</td>
                        <td class="text-end">
                            <select
                                v-if="can('employees.manage') && req.status === 'pending'"
                                class="form-select form-select-sm"
                                style="width: auto; display: inline-block"
                                @change="updateStatus(req, $event.target.value)"
                            >
                                <option value="pending" selected>pending</option>
                                <option value="approved">approved</option>
                                <option value="rejected">rejected</option>
                            </select>
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

defineProps({
    requests: { type: Object, required: true },
    employees: { type: Array, default: () => [] },
    leaveTypes: { type: Array, default: () => [] },
});

const { t } = useLocale();
const { can } = usePermissions();

const form = useForm({
    employee_id: null,
    leave_type_id: null,
    start_date: '',
    end_date: '',
    notes: '',
});

function submit() {
    form.post('/hr/leave-requests', { onSuccess: () => form.reset() });
}

function updateStatus(req, status) {
    router.patch(`/hr/leave-requests/${req.id}`, { status });
}
</script>
