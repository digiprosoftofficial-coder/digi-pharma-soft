<template>
    <TenantShellLayout :page-title="account.name">
        <Head :title="account.name" />
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <Link href="/accounts" class="small text-decoration-none">← Accounts</Link>
                <h1 class="h4 mb-0">{{ account.name }} <span class="text-muted small">({{ account.code }})</span></h1>
            </div>
        </div>
        <div class="row g-3">
            <div class="col-lg-5">
                <div v-if="can('accounting.manage')" class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="h6">Post entry</h2>
                        <form @submit.prevent="postEntry">
                            <div class="mb-2">
                                <label class="form-label small">Direction</label>
                                <select v-model="entryForm.direction" class="form-select form-select-sm">
                                    <option value="debit">Debit</option>
                                    <option value="credit">Credit</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Amount</label>
                                <input v-model.number="entryForm.amount" type="number" min="0.0001" step="0.0001" class="form-control form-control-sm" required />
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Memo</label>
                                <input v-model="entryForm.memo" type="text" class="form-control form-control-sm" />
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary" :disabled="entryForm.processing">Post</button>
                        </form>
                    </div>
                </div>
                <p v-else class="small text-muted">You can view entries; posting requires accounting permission.</p>
            </div>
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Posted</th>
                                <th>Dir</th>
                                <th class="text-end">Amount</th>
                                <th>Memo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="e in entries.data" :key="e.id">
                                <td>{{ e.posted_at }}</td>
                                <td>{{ e.direction }}</td>
                                <td class="text-end">{{ e.amount }}</td>
                                <td class="small">{{ e.memo || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { usePermissions } from '@/composables/usePermissions';

const props = defineProps({
    account: { type: Object, required: true },
    entries: { type: Object, required: true },
});

const { can } = usePermissions();

const entryForm = useForm({
    direction: 'debit',
    amount: null,
    memo: '',
});

function postEntry() {
    entryForm.post(`/accounts/${props.account.id}/entries`, { preserveScroll: true });
}
</script>
