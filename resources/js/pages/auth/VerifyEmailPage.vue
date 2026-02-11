<template>
    <GuestLayout>
        <template v-if="verifying">
            <div class="text-center py-8">
                <div
                    class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900"
                ></div>
                <p class="mt-3 text-sm text-gray-600">Verifying your email...</p>
            </div>
        </template>

        <template v-else-if="verified">
            <div class="text-center">
                <div
                    class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100"
                >
                    <svg
                        class="h-6 w-6 text-green-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </div>
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Email Verified</h2>
                <p class="text-sm text-gray-600 mb-6">Your email has been verified successfully.</p>
                <router-link
                    to="/"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                >
                    Go to dashboard
                </router-link>
            </div>
        </template>

        <template v-else>
            <div class="text-center">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Verify Your Email</h2>
                <p class="text-sm text-gray-600 mb-6">
                    We sent a verification link to your email. Click the link to verify your
                    account.
                </p>

                <div v-if="error" class="rounded-md bg-red-50 p-4 mb-4">
                    <p class="text-sm text-red-800">{{ error }}</p>
                </div>

                <div v-if="resent" class="rounded-md bg-green-50 p-4 mb-4">
                    <p class="text-sm text-green-800">
                        A new verification link has been sent to your email.
                    </p>
                </div>

                <BaseButton variant="secondary" :loading="isResending" @click="resendVerification">
                    Resend Verification Email
                </BaseButton>
            </div>
        </template>
    </GuestLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { authApi } from '@/api';
import GuestLayout from '@/layouts/GuestLayout.vue';
import BaseButton from '@/components/ui/BaseButton.vue';

const route = useRoute();

const verifying = ref(false);
const verified = ref(false);
const error = ref('');
const resent = ref(false);
const isResending = ref(false);

async function verifyFromUrl(): Promise<void> {
    const verifyUrl = route.query.verify_url as string | undefined;
    if (!verifyUrl) return;

    verifying.value = true;
    try {
        await authApi.verifyEmail(verifyUrl);
        verified.value = true;
    } catch {
        error.value = 'Verification failed. The link may have expired.';
    } finally {
        verifying.value = false;
    }
}

async function resendVerification(): Promise<void> {
    isResending.value = true;
    resent.value = false;
    error.value = '';

    try {
        await authApi.resendVerification();
        resent.value = true;
    } catch {
        error.value = 'Failed to resend verification email.';
    } finally {
        isResending.value = false;
    }
}

onMounted(verifyFromUrl);
</script>
