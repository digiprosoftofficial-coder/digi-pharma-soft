<template>
    <TenantShellLayout page-title="Employees">
        <Head title="Employees" />
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">Employees</h1>
            <Link href="/employees/create" class="btn btn-primary btn-sm">Add employee</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Hire date</th>
                        <th class="text-end">Salary</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="e in employees.data" :key="e.id">
                        <td>{{ e.employee_code }}</td>
                        <td>{{ e.hire_date || '—' }}</td>
                        <td class="text-end">{{ e.salary ?? '—' }}</td>
                        <td class="text-end">
                            <Link :href="`/employees/${e.id}/edit`" class="btn btn-sm btn-outline-secondary">Edit</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ employees: { type: Object, required: true } });
</script>
