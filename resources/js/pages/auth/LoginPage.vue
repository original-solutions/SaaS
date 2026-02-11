<template>
    <GuestLayout>
        <!-- 2FA Challenge Step -->
        <template v-if="showTwoFactor">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">Two-Factor Authentication</h2>
            <p class="text-sm text-gray-600 mb-6">
                {{
                    useRecoveryCode
                        ? 'Enter one of your recovery codes.'
                        : 'Enter the code from your authenticator app.'
                }}
            </p>

            <form @submit.prevent="handleTwoFactor" class="space-y-4">
                <BaseInput
                    v-if="!useRecoveryCode"
                    v-model="twoFactorCode"
                    label="Authentication Code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    placeholder="000000"
                    :error="errors.general"
                    id="2fa-code"
                />

                <BaseInput
                    v-else
                    v-model="recoveryCode"
                    label="Recovery Code"
                    type="text"
                    placeholder="XXXXXXXX"
                    :error="errors.general"
                    id="recovery-code"
                />

                <p v-if="errors.general" class="text-sm text-red-600">{{ errors.general }}</p>

                <BaseButton type="submit" variant="primary" :loading="isLoading" class="w-full">
                    Verify
                </BaseButton>

                <div class="text-center">
                    <button
                        type="button"
                        class="text-sm text-gray-600 hover:text-gray-900"
                        @click="useRecoveryCode = !useRecoveryCode"
                    >
                        {{ useRecoveryCode ? 'Use authenticator code' : 'Use a recovery code' }}
                    </button>
                </div>
            </form>
        </template>

        <!-- Login Step -->
        <template v-else>
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

                <div class="flex items-center justify-between text-sm">
                    <router-link to="/forgot-password" class="text-gray-600 hover:text-gray-900">
                        Forgot password?
                    </router-link>
                    <router-link to="/magic-link" class="text-gray-600 hover:text-gray-900">
                        Sign in with magic link
                    </router-link>
                </div>
            </form>
        </template>
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
const showTwoFactor = ref(false);
const useRecoveryCode = ref(false);
const twoFactorCode = ref('');
const recoveryCode = ref('');
const form = reactive({ email: '', password: '' });
const errors = reactive<Record<string, string>>({ email: '', password: '', general: '' });

function clearErrors(): void {
    errors.email = '';
    errors.password = '';
    errors.general = '';
}

function redirectAfterLogin(): void {
    tenant.initialize(auth.tenants);
    notifications.success('Signed in successfully');
    const redirect = (route.query.redirect as string) || '/';
    router.push(redirect);
}

async function handleLogin(): Promise<void> {
    clearErrors();
    isLoading.value = true;

    try {
        const result = await auth.login(form);

        if ('two_factor' in result && result.two_factor) {
            showTwoFactor.value = true;
            return;
        }

        redirectAfterLogin();
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

async function handleTwoFactor(): Promise<void> {
    clearErrors();
    isLoading.value = true;

    try {
        if (useRecoveryCode.value) {
            await auth.completeTwoFactorRecovery(recoveryCode.value);
        } else {
            await auth.completeTwoFactor(twoFactorCode.value);
        }
        redirectAfterLogin();
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;
        errors.general = axiosError.response?.data?.message ?? 'Invalid code. Please try again.';
    } finally {
        isLoading.value = false;
    }
}
</script>
