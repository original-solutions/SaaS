<template>
    <GuestLayout>
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Sign in</h2>

        <form @submit.prevent="handleLogin" class="space-y-4">
            <BaseInput
                v-model="form.email"
                label="Email"
                type="email"
                placeholder="you@example.com"
                :error="errors.email"
                id="email"
            />

            <BaseInput
                v-model="form.password"
                label="Password"
                type="password"
                placeholder="••••••••"
                :error="errors.password"
                id="password"
            />

            <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

            <BaseButton type="submit" variant="primary" :loading="isLoading" class="w-full">
                Sign in
            </BaseButton>

            <div class="text-center">
                <router-link
                    to="/forgot-password"
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    Forgot password?
                </router-link>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { useNotificationStore } from '@/stores/notification';
import GuestLayout from '@/layouts/GuestLayout.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';

const auth = useAuthStore();
const tenant = useTenantStore();
const notifications = useNotificationStore();
const router = useRouter();
const route = useRoute();

const isLoading = ref(false);
const form = reactive({ email: '', password: '' });
const errors = reactive<Record<string, string>>({ email: '', password: '', general: '' });

async function handleLogin(): Promise<void> {
    errors.email = '';
    errors.password = '';
    errors.general = '';
    isLoading.value = true;

    try {
        await auth.login(form);
        tenant.initialize(auth.tenants);
        notifications.success('Signed in successfully');

        const redirect = (route.query.redirect as string) || '/';
        router.push(redirect);
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;
        if (axiosError.response?.status === 422) {
            const data = axiosError.response.data;
            if (data.errors) {
                errors.email = data.errors.email?.[0] ?? '';
                errors.password = data.errors.password?.[0] ?? '';
            }
        } else if (axiosError.response?.status === 401) {
            errors.general = 'Invalid credentials.';
        } else {
            errors.general = 'An error occurred. Please try again.';
        }
    } finally {
        isLoading.value = false;
    }
}
</script>
