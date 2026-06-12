<template>
    <TenantShellLayout page-title="Customer">
        <Head :title="customer ? 'Edit customer' : 'New customer'" />
        <div v-if="formError" class="alert alert-danger small">{{ formError }}</div>
        <h1 class="h4 mb-3">{{ customer ? 'Edit customer' : 'New customer' }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">Name</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">{{ t('customers.phone_label') }}</label>
                <input v-model="form.phone" class="form-control" />
            </div>
            <div class="mb-2">
                <label class="form-label">Email</label>
                <input v-model="form.email" type="email" class="form-control" />
            </div>
            <div class="mb-2">
                <label class="form-label">{{ t('customers.address') }}</label>
                <textarea v-model="form.address" class="form-control" rows="2" maxlength="500" />
            </div>
            <div v-if="customer" class="alert alert-light border small">
                <div>{{ t('sales.due') }}: <strong>{{ formatMoney(customer.balance_due) }}</strong></div>
                <div>Loyalty points: <strong>{{ customer.loyalty_points }}</strong></div>
                <Link
                    v-if="Number(customer.balance_due) > 0"
                    :href="`/sales/customer-bills/${customer.id}`"
                    class="btn btn-sm btn-primary mt-2"
                >
                    {{ t('customers.collect_due') }}
                </Link>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">Save</button>
                <Link href="/customers" class="btn btn-link">Cancel</Link>
                <button
                    v-if="customer && can('customers.manage')"
                    type="button"
                    class="btn btn-outline-danger ms-auto"
                    @click="remove"
                >
                    Delete
                </button>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { useMoney } from '@/composables/useMoney';
import { usePermissions } from '@/composables/usePermissions';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ customer: { type: Object, default: null } });

const { t } = useLocale();
const { formatMoney } = useMoney();
const { can } = usePermissions();
const page = usePage();
const formError = computed(() => page.props.errors?.customer);

const form = useForm({
    name: props.customer?.name ?? '',
    phone: props.customer?.phone ?? '',
    email: props.customer?.email ?? '',
    address: props.customer?.address ?? '',
});

function hasSaleHistory() {
    return Number(props.customer?.sales_count) > 0 || Number(props.customer?.balance_due) > 0;
}

function submit() {
    if (props.customer) {
        form.put(`/customers/${props.customer.id}`);
    } else {
        form.post('/customers');
    }
}

function remove() {
    if (hasSaleHistory()) {
        window.alert(t('customers.cannot_delete_has_sales'));
        return;
    }
    if (!window.confirm(t('customers.delete_confirm', { name: props.customer.name }))) {
        return;
    }
    router.delete(`/customers/${props.customer.id}`);
}
</script>
