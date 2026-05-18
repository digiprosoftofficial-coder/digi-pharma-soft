<template>
    <PlatformShellLayout :page-title="t('platform.health_title')">
        <Head :title="t('platform.health_title')" />
        <p class="text-secondary small mb-4">{{ t('platform.health_sub') }}</p>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="text-muted small text-uppercase fw-semibold">{{ t('platform.health_status') }}</span>
                        <p class="h4 mb-0 mt-2">
                            <span class="badge" :class="statusBadgeClass">{{ statusLabel }}</span>
                        </p>
                    </div>
                </article>
            </div>
            <div class="col-md-4">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="text-muted small text-uppercase fw-semibold">{{ t('platform.health_failed_jobs') }}</span>
                        <p class="display-6 fw-semibold mb-0 mt-2">{{ health.failed_jobs }}</p>
                    </div>
                </article>
            </div>
            <div class="col-md-4">
                <article class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <span class="text-muted small text-uppercase fw-semibold">{{ t('platform.health_pending_jobs') }}</span>
                        <p class="display-6 fw-semibold mb-0 mt-2">{{ health.pending_jobs }}</p>
                    </div>
                </article>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white fw-semibold">{{ t('platform.health_environment') }}</div>
            <ul class="list-group list-group-flush small">
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ t('platform.health_queue') }}</span>
                    <code>{{ health.queue_connection }}</code>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>APP_ENV</span>
                    <code>{{ health.app_env }}</code>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>APP_DEBUG</span>
                    <code>{{ health.app_debug ? 'true' : 'false' }}</code>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ t('platform.health_migration_batch') }}</span>
                    <code>{{ health.latest_migration?.batch ?? '—' }}</code>
                </li>
            </ul>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white fw-semibold">{{ t('platform.health_recent_failures') }}</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ t('platform.health_failed_at') }}</th>
                            <th>Queue</th>
                            <th>{{ t('platform.health_exception') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="job in health.recent_failed_jobs" :key="job.id">
                            <td class="text-nowrap small">{{ job.failed_at?.slice(0, 19) }}</td>
                            <td class="small"><code>{{ job.queue }}</code></td>
                            <td class="small text-muted font-monospace">{{ job.exception_summary }}</td>
                        </tr>
                        <tr v-if="!health.recent_failed_jobs?.length">
                            <td colspan="3" class="text-muted small">{{ t('platform.health_no_failures') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    health: { type: Object, required: true },
});

const { t } = useLocale();

const statusLabel = computed(() => {
    const map = {
        healthy: t('platform.health_status_healthy'),
        warning: t('platform.health_status_warning'),
        degraded: t('platform.health_status_degraded'),
    };
    return map[props.health.status] ?? props.health.status;
});

const statusBadgeClass = computed(() => {
    const map = {
        healthy: 'text-bg-success',
        warning: 'text-bg-warning',
        degraded: 'text-bg-danger',
    };
    return map[props.health.status] ?? 'text-bg-secondary';
});
</script>
