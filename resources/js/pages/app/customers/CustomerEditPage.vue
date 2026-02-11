<template>
  <div>
    <div class="flex items-center gap-4 mb-6">
      <router-link
        :to="`/customers/${route.params.id}`"
        class="text-sm text-gray-500 hover:text-gray-700"
      >
        &larr; Back
      </router-link>
      <h1 class="text-2xl font-bold text-gray-900">
        Edit Customer
      </h1>
    </div>

    <div
      v-if="isLoadingCustomer"
      class="text-sm text-gray-500"
    >
      Loading...
    </div>

    <div
      v-else-if="customer"
      class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-lg"
    >
      <CustomerForm
        :initial="customer"
        :loading="isSaving"
        :errors="errors"
        @submit="handleUpdate"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { customerApi } from '@/api';
import { useNotificationStore } from '@/stores/notification';
import CustomerForm from '@/components/shared/CustomerForm.vue';
import type { Customer } from '@/api/types';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';

const router = useRouter();
const route = useRoute();
const notifications = useNotificationStore();

const customer = ref<Customer | null>(null);
const isLoadingCustomer = ref(true);
const isSaving = ref(false);
const errors = reactive<Record<string, string>>({});

onMounted(async () => {
    try {
        const { data } = await customerApi.show(Number(route.params.id));
        customer.value = data.data;
    } finally {
        isLoadingCustomer.value = false;
    }
});

async function handleUpdate(data: Record<string, string>): Promise<void> {
    isSaving.value = true;
    Object.keys(errors).forEach((k) => delete errors[k]);

    try {
        await customerApi.update(Number(route.params.id), data);
        notifications.success('Customer updated.');
        router.push({ name: 'customer-show', params: { id: route.params.id } });
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;
        if (axiosError.response?.data?.errors) {
            Object.assign(
                errors,
                Object.fromEntries(
                    Object.entries(axiosError.response.data.errors).map(([k, v]) => [k, v[0]]),
                ),
            );
        }
    } finally {
        isSaving.value = false;
    }
}
</script>
