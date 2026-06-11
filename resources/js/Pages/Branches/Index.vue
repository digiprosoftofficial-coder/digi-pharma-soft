<template>
    <TenantShellLayout :page-title="t('branches.title')">
        <Head :title="t('branches.title')" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>
        <div v-if="$page.props.errors?.branch" class="alert alert-danger small">{{ $page.props.errors.branch }}</div>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-0">{{ t('branches.title') }}</h1>
                <p v-if="maxBranches" class="text-muted small mb-0">
                    {{ t('branches.usage', { count: branchCount, max: maxBranches }) }}
                </p>
            </div>
            <Link v-if="canManage && multiBranchEnabled" href="/branches/create" class="btn btn-primary btn-sm">
                {{ t('branches.new_branch') }}
            </Link>
        </div>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{{ t('branches.name') }}</th>
                        <th>{{ t('branches.code') }}</th>
                        <th>{{ t('branches.phone') }}</th>
                        <th>{{ t('common.status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="b in branches.data" :key="b.id">
                        <td>
                            {{ b.name }}
                            <span v-if="b.is_default" class="badge text-bg-secondary ms-1">{{ t('branches.default_badge') }}</span>
                        </td>
                        <td><code>{{ b.code }}</code></td>
                        <td class="small text-muted">{{ b.phone || '—' }}</td>
                        <td>
                            <span class="badge" :class="b.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                {{ b.is_active ? t('common.active') : t('common.inactive') }}
                            </span>
                        </td>
                        <td class="text-end text-nowrap">
                            <Link v-if="canManage" :href="`/branches/${b.id}/edit`" class="btn btn-sm btn-outline-secondary me-1">
                                {{ t('common.edit') }}
                            </Link>
                            <button
                                v-if="canManage && !b.is_default"
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                @click="remove(b)"
                            >
                                {{ t('common.delete') }}
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!branches.data?.length">
                        <td colspan="5" class="text-muted text-center py-3">{{ t('branches.empty') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    branches: { type: Object, required: true },
    branchCount: { type: Number, default: 0 },
    maxBranches: { type: Number, default: null },
    canManage: { type: Boolean, default: false },
    multiBranchEnabled: { type: Boolean, default: false },
});

const { t } = useLocale();

function remove(branch) {
    if (!window.confirm(t('branches.delete_confirm', { name: branch.name }))) {
        return;
    }
    router.delete(`/branches/${branch.id}`);
}
</script>
