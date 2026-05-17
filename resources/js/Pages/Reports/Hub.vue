<template>
    <TenantShellLayout page-title="Reports">
        <Head title="Reports" />
        <h1 class="h4 mb-3">Reports hub</h1>
        <div class="row g-3">
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6">Sales summary</h2>
                        <p class="small text-muted">Date range, totals, top products, CSV export.</p>
                        <Link href="/reports/summary" class="btn btn-sm btn-primary">Open</Link>
                    </div>
                </div>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>
