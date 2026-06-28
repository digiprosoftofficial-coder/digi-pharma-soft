<template>
    <TenantShellLayout page-title="Package templates">
        <Head title="Package templates" />
        <div v-if="$page.props.flash?.success" class="alert alert-success small">{{ $page.props.flash.success }}</div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Package templates</h1>
                <p class="text-muted small mb-0">Prepare common product bundles for faster package sale checkout.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <Link href="/sales/package" class="btn btn-outline-primary btn-sm">Package sale</Link>
                <Link href="/sales/packages/create" class="btn btn-primary btn-sm">New package</Link>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div v-if="!templates.data?.length" class="card-body text-muted text-center py-4">
                No package templates yet. Create one to start selling prepared packages.
            </div>
            <div v-else class="list-group list-group-flush">
                <div v-for="template in templates.data" :key="template.id" class="list-group-item package-template-row">
                    <div class="min-w-0">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <strong>{{ template.name }}</strong>
                            <span class="badge" :class="template.is_active ? 'text-bg-success' : 'text-bg-secondary'">
                                {{ template.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <div class="small text-muted">
                            {{ template.items_count }} items
                            <span v-if="template.discount_percent"> · {{ template.discount_percent }}% discount</span>
                            <span v-if="template.fixed_price"> · Fixed {{ formatMoney(template.fixed_price) }}</span>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 package-template-row__actions">
                        <Link :href="`/sales/packages/${template.id}/edit`" class="btn btn-sm btn-outline-secondary">Edit</Link>
                        <button type="button" class="btn btn-sm btn-outline-danger" @click="remove(template)">Delete</button>
                    </div>
                </div>
            </div>
        </div>

        <PaginationLinks v-if="templates.links" class="mt-3" :links="templates.links" />
    </TenantShellLayout>
</template>

<script setup>
import PaginationLinks from '@/Pages/Reports/Partials/PaginationLinks.vue';
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useMoney } from '@/composables/useMoney';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ templates: { type: Object, required: true } });

const { formatMoney } = useMoney();

function remove(template) {
    if (!window.confirm(`Delete package template "${template.name}"?`)) {
        return;
    }
    router.delete(`/sales/packages/${template.id}`, { preserveScroll: true });
}
</script>

<style scoped>
.package-template-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

@media (max-width: 575.98px) {
    .package-template-row {
        align-items: stretch;
        flex-direction: column;
    }

    .package-template-row__actions {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
