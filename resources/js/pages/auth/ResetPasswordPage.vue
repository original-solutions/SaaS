<template>
    <GuestLayout>
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Reset password</h2>

        <form @submit.prevent="handleSubmit" class="space-y-4">
            <BaseInput
                v-model="form.email"
                label="Email"
                type="email"
                :error="errors.email"
                id="email"
            />

            <BaseInput
                v-model="form.password"
                label="New password"
                type="password"
                :error="errors.password"
                id="password"
            />

            <BaseInput
                v-model="form.password_confirmation"
                label="Confirm password"
                type="password"
                id="password_confirmation"
            />

            <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

            <BaseButton type="submit" variant="primary" :loading="isLoading" class="w-full">
                Reset password
            </BaseButton>
        </form>
    </GuestLayout>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { authApi } from '@/api';
import { useNotificationStore } from '@/stores/notification';
import GuestLayout from '@/layouts/GuestLayout.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';

const router = useRouter();
const route = useRoute();
const notifications = useNotificationStore();

const isLoading = ref(false);
const form = reactive({
    email: (route.query.email as string) || '',
    password: '',
    password_confirmation: '',
});
const errors = reactive<Record<string, string>>({ email: '', password: '', general: '' });

async function handleSubmit(): Promise<void> {
    errors.email = '';
    errors.password = '';
    errors.general = '';
    isLoading.value = true;

    try {
        await authApi.resetPassword({
            token: route.params.token as string,
            email: form.email,
            password: form.password,
            password_confirmation: form.password_confirmation,
        });

        notifications.success('Password reset successfully. Please sign in.');
        router.push({ name: 'login' });
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;
        if (axiosError.response?.data?.errors) {
            const e = axiosError.response.data.errors;
            errors.email = e.email?.[0] ?? '';
            errors.password = e.password?.[0] ?? '';
        } else {
            errors.general = 'Unable to reset password. The link may have expired.';
        }
    } finally {
        isLoading.value = false;
    }
}
</script>
