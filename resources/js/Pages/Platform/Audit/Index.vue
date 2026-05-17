<template>
    <PlatformShellLayout :page-title="t('platform.audit_title')">
        <Head :title="t('platform.audit_title')" />
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>When</th>
                        <th>Event</th>
                        <th>Description</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in activities.data" :key="a.id">
                        <td class="small text-muted">{{ a.created_at }}</td>
                        <td><code>{{ a.event }}</code></td>
                        <td>{{ a.description }}</td>
                        <td>{{ a.causer_name || '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PlatformShellLayout>
</template>

<script setup>
import PlatformShellLayout from '@/Layouts/PlatformShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head } from '@inertiajs/vue3';

defineProps({ activities: { type: Object, required: true } });

const { t } = useLocale();
</script>
