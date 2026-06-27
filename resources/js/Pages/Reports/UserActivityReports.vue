<template>
    <TenantShellLayout page-title="User Activity Reports">
        <Head title="User Activity Reports" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">User Activity Reports</h1>
                <p class="small text-muted mb-0">Tenant audit activity by user, event, subject, date, and exportable history.</p>
            </div>
            <Link href="/reports" class="btn btn-sm btn-outline-secondary">Report Hub</Link>
        </div>
        <SmartReportFilters
            :filters="filters"
            :branches="options.branches"
            :branch-label="branchLabel"
            :can-view-all-branches="canViewAllBranches"
            :options="options"
            :enabled-filters="['user', 'eventType']"
            report-path="/reports/user-activity"
            export-path="/reports/user-activity/export"
        />
        <SummaryCards :cards="summaryCards" />
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Event</th>
                            <th>Description</th>
                            <th>User</th>
                            <th>Subject</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in rows.data" :key="row.id">
                            <td>{{ formatDate(row.created_at) }}</td>
                            <td><span class="badge text-bg-light">{{ row.event }}</span></td>
                            <td>{{ row.description }}</td>
                            <td>{{ row.causer?.name ?? 'System' }}</td>
                            <td class="small text-muted">{{ row.subject_type }} #{{ row.subject_id }}</td>
                        </tr>
                        <tr v-if="!rows.data?.length">
                            <td colspan="5" class="text-center text-muted py-4">No activity found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <PaginationLinks :links="rows.links" />
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import PaginationLinks from '@/Pages/Reports/Partials/PaginationLinks.vue';
import SmartReportFilters from '@/Pages/Reports/Partials/SmartReportFilters.vue';
import SummaryCards from '@/Pages/Reports/Partials/SummaryCards.vue';
import { formatHumanDateTime as formatDate } from '@/utils/dates';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    summary: { type: Object, required: true },
    rows: { type: Object, required: true },
    filters: { type: Object, required: true },
    branchLabel: { type: String, required: true },
    canViewAllBranches: { type: Boolean, default: false },
    options: { type: Object, required: true },
});

const summaryCards = computed(() => [
    { label: 'Events', value: props.summary.events, money: false },
    { label: 'Users', value: props.summary.users, money: false },
    { label: 'Logins', value: props.summary.logins, money: false },
    { label: 'Imports', value: props.summary.imports, money: false },
]);

</script>
