<template>
    <GuestLayout>
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Magic Link Sign In</h2>
        <p class="text-sm text-gray-600 mb-6">
            Enter your email and we'll send you a sign-in link.
        </p>

        <template v-if="sent">
            <div class="rounded-md bg-green-50 p-4">
                <p class="text-sm text-green-800">
                    A magic link has been sent to <strong>{{ email }}</strong
                    >. Check your inbox and click the link to sign in.
                </p>
            </div>
            <div class="mt-4 text-center">
                <router-link to="/login" class="text-sm text-gray-600 hover:text-gray-900"
                    >Back to sign in</router-link
                >
            </div>
        </template>

        <form v-else @submit.prevent="handleSubmit" class="space-y-4">
            <BaseInput
                v-model="email"
                label="Email"
                type="email"
                placeholder="you@example.com"
                :error="error"
                id="email"
            />

            <BaseButton type="submit" variant="primary" :loading="isLoading" class="w-full">
                Send Magic Link
            </BaseButton>

            <div class="text-center">
                <router-link to="/login" class="text-sm text-gray-600 hover:text-gray-900"
                    >Back to sign in</router-link
                >
            </div>
        </form>
    </GuestLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { authApi } from '@/api';
import GuestLayout from '@/layouts/GuestLayout.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';

const email = ref('');
const error = ref('');
const isLoading = ref(false);
const sent = ref(false);

async function handleSubmit(): Promise<void> {
    error.value = '';
    isLoading.value = true;

    try {
        await authApi.requestMagicLink(email.value);
        sent.value = true;
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;
        error.value =
            axiosError.response?.data?.errors?.email?.[0] ??
            axiosError.response?.data?.message ??
            'An error occurred.';
    } finally {
        isLoading.value = false;
    }
}
</script>
