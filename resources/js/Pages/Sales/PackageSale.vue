<template>
    <TenantShellLayout page-title="Package sale">
        <Head title="Package sale" />
        <p class="text-muted small">Same checkout as POS — use for bundles or fixed packages.</p>
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card card-body">
                    <label class="form-label">Search product</label>
                    <input v-model="q" type="search" class="form-control" placeholder="Name, SKU, or barcode" @input="debouncedSearch" />
                    <ul class="list-group mt-2 small">
                        <li
                            v-for="item in results"
                            :key="item.id"
                            class="list-group-item list-group-item-action d-flex justify-content-between"
                            @click="addLine(item)"
                        >
                            <span>{{ item.name }}</span>
                            <span class="text-muted">{{ item.sku }}</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card card-body">
                    <h2 class="h6">Package cart</h2>
                    <p v-if="!cart.length" class="text-muted small">Add products from search.</p>
                    <table v-else class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th style="width: 7rem">Qty</th>
                                <th style="width: 9rem">Unit ({{ currencyCode() }})</th>
                                <th class="text-end">Line</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(line, idx) in cart" :key="idx">
                                <td>{{ line.name }}</td>
                                <td><input v-model.number="line.quantity" type="number" min="0.0001" step="0.0001" class="form-control form-control-sm" /></td>
                                <td><input v-model.number="line.unit_price" type="number" min="0" step="0.0001" class="form-control form-control-sm" /></td>
                                <td class="text-end">{{ formatMoney(Number(line.quantity || 0) * Number(line.unit_price || 0)) }}</td>
                                <td><button type="button" class="btn btn-sm btn-outline-danger" @click="cart.splice(idx, 1)">×</button></td>
                            </tr>
                        </tbody>
                        <tfoot v-if="cart.length" class="fw-semibold">
                            <tr>
                                <td colspan="3" class="text-end">Total</td>
                                <td class="text-end">{{ formatMoney(cartTotal) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="mb-2">
                        <label class="form-label">Coupon code (optional)</label>
                        <input v-model="couponCode" class="form-control form-control-sm" placeholder="SAVE10" />
                    </div>
                    <form class="mt-3" @submit.prevent="submitSale">
                        <div class="mb-2">
                            <label class="form-label">Payment method</label>
                            <select v-model="paymentMethod" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile">Mobile</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success" :disabled="!cart.length || submitting">Complete package sale</button>
                    </form>
                </div>
            </div>
        </div>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useMoney } from '@/composables/useMoney';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const { formatMoney, currencyCode } = useMoney();

const q = ref('');
const results = ref([]);
const cart = ref([]);
const paymentMethod = ref('cash');
const couponCode = ref('');
const submitting = ref(false);
let timer;

const cartTotal = computed(() =>
    cart.value.reduce((s, l) => s + Number(l.quantity || 0) * Number(l.unit_price || 0), 0),
);

function debouncedSearch() {
    clearTimeout(timer);
    timer = setTimeout(runSearch, 250);
}

async function runSearch() {
    if (q.value.length < 2) {
        results.value = [];
        return;
    }
    const { data } = await window.axios.get('/catalog/product-search', { params: { q: q.value } });
    results.value = data.data;
}

function addLine(item) {
    const batch = item.batches?.[0];
    if (!batch) {
        alert('No stock batch for this product.');
        return;
    }
    cart.value.push({
        product_batch_id: batch.id,
        name: item.name,
        quantity: 1,
        unit_price: Number(item.sale_price),
    });
}

function submitSale() {
    submitting.value = true;
    router.post(
        '/pos/sales',
        {
            lines: cart.value.map((l) => ({
                product_batch_id: l.product_batch_id,
                quantity: l.quantity,
                unit_price: l.unit_price,
            })),
            payments: [{ method: paymentMethod.value, amount: cartTotal.value }],
            discount: 0,
            tax: 0,
            coupon_code: couponCode.value || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                submitting.value = false;
                cart.value = [];
                couponCode.value = '';
            },
        },
    );
}
</script>
