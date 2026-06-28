<template>
    <TenantShellLayout :page-title="t('common.general_accounts')">
        <Head :title="t('common.accounts')" />
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">{{ t('common.ledger_accounts') }}</h1>
            <Link href="/accounts/create" class="btn btn-primary btn-sm">{{ t('common.new_account') }}</Link>
        </div>
        <div class="card border-0 shadow-sm">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('common.code') }}</th>
                        <th>{{ t('common.name') }}</th>
                        <th>{{ t('catalog.product_type') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="a in accounts" :key="a.id">
                        <td>{{ a.code }}</td>
                        <td>{{ a.name }}</td>
                        <td>{{ a.type }}</td>
                        <td><Link :href="'/accounts/' + a.id" class="btn btn-sm btn-outline-primary">{{ t('reports.entries') }}</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ accounts: { type: Array, required: true } });

const { t } = useLocale();
</script>
