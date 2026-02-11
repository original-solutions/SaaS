<template>
    <div>
        <div class="flex items-center gap-4 mb-6">
            <router-link to="/customers" class="text-sm text-gray-500 hover:text-gray-700">
                &larr; Back
            </router-link>
            <h1 class="text-2xl font-bold text-gray-900">New Customer</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-lg">
            <CustomerForm :loading="isLoading" :errors="errors" @submit="handleCreate" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { customerApi } from '@/api';
import { useNotificationStore } from '@/stores/notification';
import CustomerForm from '@/components/shared/CustomerForm.vue';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';

const router = useRouter();
const notifications = useNotificationStore();
const isLoading = ref(false);
const errors = reactive<Record<string, string>>({});

async function handleCreate(data: Record<string, string>): Promise<void> {
    isLoading.value = true;
    Object.keys(errors).forEach((k) => delete errors[k]);

    try {
        await customerApi.create(data);
        notifications.success('Customer created.');
        router.push({ name: 'customers' });
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
        isLoading.value = false;
    }
}
</script>
