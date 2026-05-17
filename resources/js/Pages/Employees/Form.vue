<template>
    <TenantShellLayout page-title="Employee">
        <Head :title="employee ? 'Edit employee' : 'New employee'" />
        <h1 class="h4 mb-3">{{ employee ? 'Edit employee' : 'New employee' }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">Employee code</label>
                <input v-model="form.employee_code" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">Hire date</label>
                <input v-model="form.hire_date" type="date" class="form-control" />
            </div>
            <div class="mb-2">
                <label class="form-label">Salary</label>
                <input v-model="form.salary" type="number" min="0" step="0.01" class="form-control" />
            </div>
            <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
            <Link href="/employees" class="btn btn-link">Cancel</Link>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ employee: { type: Object, default: null } });

const form = useForm({
    employee_code: props.employee?.employee_code ?? '',
    hire_date: props.employee?.hire_date?.slice?.(0, 10) ?? '',
    salary: props.employee?.salary ?? '',
});

function submit() {
    if (props.employee) {
        form.put(`/employees/${props.employee.id}`);
    } else {
        form.post('/employees');
    }
}
</script>
