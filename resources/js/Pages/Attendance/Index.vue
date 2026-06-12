<template>
    <TenantShellLayout :page-title="t('employees.attendance_title')">
        <Head :title="t('employees.attendance_title')" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div v-if="formError" class="alert alert-danger small">{{ formError }}</div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h1 class="h4 mb-0">{{ t('employees.attendance_title') }}</h1>
            <form class="d-flex gap-2 align-items-center" @submit.prevent="filterDate">
                <input v-model="dateInput" type="date" class="form-control form-control-sm" />
                <button type="submit" class="btn btn-sm btn-outline-secondary">{{ t('common.search') }}</button>
            </form>
        </div>
        <div v-if="myEmployee" class="card border-0 shadow-sm card-body mb-3">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="small text-muted">{{ myEmployee.name }}</span>
                <form method="post" @submit.prevent="selfClock('in')">
                    <button type="submit" class="btn btn-sm btn-primary">{{ t('employees.clock_in') }}</button>
                </form>
                <form method="post" @submit.prevent="selfClock('out')">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ t('employees.clock_out') }}</button>
                </form>
            </div>
        </div>
        <div v-if="canManageOthers" class="card border-0 shadow-sm card-body mb-3">
            <label class="form-label small mb-1">{{ t('employees.mark_for') }}</label>
            <div class="d-flex flex-wrap gap-2">
                <select v-model="selectedEmployeeId" class="form-select form-select-sm" style="max-width: 240px">
                    <option :value="null">—</option>
                    <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                </select>
                <button type="button" class="btn btn-sm btn-primary" :disabled="!selectedEmployeeId" @click="managerClock('in')">
                    {{ t('employees.clock_in') }}
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" :disabled="!selectedEmployeeId" @click="managerClock('out')">
                    {{ t('employees.clock_out') }}
                </button>
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('employees.name') }}</th>
                            <th>{{ t('employees.work_date') }}</th>
                            <th>{{ t('employees.clock_in_at') }}</th>
                            <th>{{ t('employees.clock_out_at') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in attendances.data" :key="row.id">
                            <td>{{ row.employee?.name }}</td>
                            <td>{{ row.work_date?.slice?.(0, 10) }}</td>
                            <td>{{ formatTime(row.clock_in) }}</td>
                            <td>{{ formatTime(row.clock_out) }}</td>
                        </tr>
                        <tr v-if="!attendances.data?.length">
                            <td colspan="4" class="text-muted text-center py-3">—</td>
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
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    date: { type: String, required: true },
    attendances: { type: Object, required: true },
    employees: { type: Array, default: () => [] },
    myEmployee: { type: Object, default: null },
    canManageOthers: { type: Boolean, default: false },
});

const { t } = useLocale();
const page = usePage();
const formError = computed(() => page.props.errors?.attendance);
const dateInput = ref(props.date);
const selectedEmployeeId = ref(null);

function filterDate() {
    router.get('/attendance', { date: dateInput.value }, { preserveState: true });
}

function formatTime(value) {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleTimeString();
}

function selfClock(action) {
    router.post(`/attendance/clock-${action}`);
}

function managerClock(action) {
    if (!selectedEmployeeId.value) {
        return;
    }
    router.post(`/attendance/${selectedEmployeeId.value}/clock-${action}`);
}
</script>
