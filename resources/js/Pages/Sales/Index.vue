<template>
    <TenantShellLayout page-title="Sales list">
        <Head title="Sales" />
        <h1 class="h4 mb-3">Sales list</h1>
        <div class="card border-0 shadow-sm table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 2rem"></th>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th class="text-end">Total ({{ currencyCode() }})</th>
                        <th class="text-end">Due ({{ currencyCode() }})</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="s in sales.data" :key="s.id">
                        <tr>
                            <td>
                                <button
                                    v-if="s.lines?.length"
                                    type="button"
                                    class="btn btn-sm btn-link p-0 text-decoration-none"
                                    :aria-expanded="expanded === s.id"
                                    @click="toggle(s.id)"
                                >
                                    {{ expanded === s.id ? '−' : '+' }}
                                </button>
                            </td>
                            <td>{{ s.invoice_no }}</td>
                            <td>{{ s.sold_at }}</td>
                            <td class="text-end">{{ formatMoney(s.total) }}</td>
                            <td class="text-end">{{ formatMoney(s.due) }}</td>
                        </tr>
                        <tr v-if="expanded === s.id && s.lines?.length" class="table-light">
                            <td></td>
                            <td colspan="4" class="py-2">
                                <ul class="list-unstyled small mb-0">
                                    <li v-for="line in s.lines" :key="line.id" class="mb-1">
                                        <span class="fw-medium">{{ line.product?.name ?? 'Product' }}</span>
                                        <span class="text-muted">
                                            — {{ line.quantity }} {{ line.sell_unit ?? '' }}
                                            <template v-if="line.batch">
                                                · {{ batchLineLabel(line.batch) }}
                                            </template>
                                        </span>
                                        <span class="float-end">{{ formatMoney(line.line_total) }}</span>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { formatBatchLabel } from '@/composables/usePosBatches';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({ sales: { type: Object, required: true } });

const { t } = useLocale();
const { formatMoney, currencyCode } = useMoney();
const expanded = ref(null);

function toggle(id) {
    expanded.value = expanded.value === id ? null : id;
}

function batchLineLabel(batch) {
    const label = formatBatchLabel(batch);
    if (batch.expiry_date) {
        return `${label} (${t('catalog.sale_line_expiry', { date: batch.expiry_date })})`;
    }

    return label;
}
</script>
