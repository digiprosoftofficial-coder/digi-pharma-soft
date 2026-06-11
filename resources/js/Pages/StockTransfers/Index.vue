<template>
    <TenantShellLayout :page-title="t('tenant_nav.stock_transfer')">
        <Head :title="t('tenant_nav.stock_transfer')" />
        <div class="d-flex justify-content-between mb-3">
            <h1 class="h4 mb-0">{{ t('tenant_nav.stock_transfer') }}</h1>
            <Link href="/stock-transfers/create" class="btn btn-primary btn-sm">{{ t('common.create') }}</Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Date</th>
                        <th v-if="multiBranch">{{ t('branches.transfer_from_branch') }}</th>
                        <th v-if="multiBranch">{{ t('branches.transfer_to_branch') }}</th>
                        <th>Lines</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="transfer in transfers.data" :key="transfer.id">
                        <td>{{ transfer.transfer_no }}</td>
                        <td>{{ transfer.transferred_at }}</td>
                        <td v-if="multiBranch">{{ transfer.from_branch?.name ?? '—' }}</td>
                        <td v-if="multiBranch">{{ transfer.to_branch?.name ?? '—' }}</td>
                        <td>{{ transfer.lines_count }}</td>
                        <td>{{ transfer.status }}</td>
                    </tr>
                    <tr v-if="!transfers.data.length">
                        <td :colspan="multiBranch ? 6 : 4" class="text-muted small text-center py-3">No transfers yet.</td>
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

defineProps({
    transfers: { type: Object, required: true },
    multiBranch: { type: Boolean, default: false },
});

const { t } = useLocale();
</script>
