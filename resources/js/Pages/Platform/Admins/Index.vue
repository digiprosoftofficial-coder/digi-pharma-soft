<template>
    <PlatformShellLayout :page-title="t('platform.admins_title')">
        <Head :title="t('platform.admins_title')" />
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Verified</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in admins" :key="a.id">
                        <td>{{ a.name }}</td>
                        <td>{{ a.email }}</td>
                        <td>{{ a.email_verified_at ? t('common.yes') : t('common.no') }}</td>
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

defineProps({ admins: { type: Array, required: true } });

const { t } = useLocale();
</script>
