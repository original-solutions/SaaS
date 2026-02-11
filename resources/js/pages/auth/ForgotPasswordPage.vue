<template>
    <GuestLayout>
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Forgot password</h2>

        <div v-if="sent" class="text-sm text-green-700 bg-green-50 rounded-md p-4">
            We've emailed you a password reset link.
        </div>

        <form v-else @submit.prevent="handleSubmit" class="space-y-4">
            <p class="text-sm text-gray-600">Enter your email and we'll send you a reset link.</p>

            <BaseInput
                v-model="email"
                label="Email"
                type="email"
                placeholder="you@example.com"
                :error="error"
                id="email"
            />

            <BaseButton type="submit" variant="primary" :loading="isLoading" class="w-full">
                Send reset link
            </BaseButton>

            <div class="text-center">
                <router-link to="/login" class="text-sm text-gray-600 hover:text-gray-900">
                    Back to sign in
                </router-link>
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

const email = ref('');
const error = ref('');
const sent = ref(false);
const isLoading = ref(false);

async function handleSubmit(): Promise<void> {
    error.value = '';
    isLoading.value = true;

    try {
        await authApi.forgotPassword(email.value);
        sent.value = true;
    } catch {
        error.value = 'Unable to send reset link. Please check your email.';
    } finally {
        isLoading.value = false;
    }
}
</script>
