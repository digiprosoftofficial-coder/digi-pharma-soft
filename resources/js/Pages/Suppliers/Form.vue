<template>
    <TenantShellLayout :page-title="t('purchases.supplier')">
        <Head :title="supplier ? t('suppliers.edit_supplier') : t('suppliers.new_supplier')" />
        <div v-if="formError" class="alert alert-danger small">{{ formError }}</div>
        <h1 class="h4 mb-3">{{ supplier ? t('suppliers.edit_supplier') : t('suppliers.new_supplier') }}</h1>
        <form class="card border-0 shadow-sm card-body" @submit.prevent="submit">
            <div class="mb-2">
                <label class="form-label">{{ t('common.name') }}</label>
                <input v-model="form.name" class="form-control" required />
            </div>
            <div class="mb-2">
                <label class="form-label">{{ t('customers.phone_label') }}</label>
                <input v-model="form.phone" class="form-control" />
            </div>
            <div class="mb-2">
                <label class="form-label">{{ t('customers.email') }}</label>
                <input v-model="form.email" type="email" class="form-control" />
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">{{ t('common.save') }}</button>
                <Link href="/suppliers" class="btn btn-link">{{ t('common.cancel') }}</Link>
                <button
                    v-if="supplier"
                    type="button"
                    class="btn btn-outline-danger ms-auto"
                    @click="remove"
                >
                    {{ t('common.delete') }}
                </button>
            </div>
        </form>
    </TenantShellLayout>
</template>

<script setup>
import TenantShellLayout from '@/Layouts/TenantShellLayout.vue';
import { useLocale } from '@/composables/useLocale';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ supplier: { type: Object, default: null } });

const { t } = useLocale();
const page = usePage();
const formError = computed(() => page.props.errors?.supplier);

const form = useForm({
    name: props.supplier?.name ?? '',
    phone: props.supplier?.phone ?? '',
    email: props.supplier?.email ?? '',
});

function hasPurchaseHistory() {
    return Number(props.supplier?.purchases_count) > 0 || Number(props.supplier?.purchase_returns_count) > 0;
}

function submit() {
    if (props.supplier) {
        form.put(`/suppliers/${props.supplier.id}`);
    } else {
        form.post('/suppliers');
    }
}

function remove() {
    if (hasPurchaseHistory()) {
        window.alert(t('suppliers.cannot_delete_has_purchases'));
        return;
    }
    if (!window.confirm(t('suppliers.delete_confirm', { name: props.supplier.name }))) {
        return;
    }
    router.delete(`/suppliers/${props.supplier.id}`);
}
</script>
