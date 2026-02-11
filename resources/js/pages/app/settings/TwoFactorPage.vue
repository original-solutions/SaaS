<template>
  <div>
    <div class="flex items-center gap-3 mb-6">
      <router-link to="/settings" class="text-gray-400 hover:text-gray-600">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 19l-7-7 7-7"
          />
        </svg>
      </router-link>
      <h1 class="text-2xl font-bold text-gray-900">Two-Factor Authentication</h1>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <!-- Already enabled -->
      <template v-if="authStore.hasTwoFactor && !showingRecovery">
        <div class="flex items-center gap-3 mb-4">
          <div
            class="flex-shrink-0 h-10 w-10 rounded-full bg-green-100 flex items-center justify-center"
          >
            <svg
              class="h-5 w-5 text-green-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
              />
            </svg>
          </div>
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Two-factor is enabled</h2>
            <p class="text-sm text-gray-600">
              Your account is protected with two-factor authentication.
            </p>
          </div>
        </div>

        <div class="flex gap-3">
          <BaseButton variant="secondary" size="sm" @click="viewRecoveryCodes">
            View Recovery Codes
          </BaseButton>
          <BaseButton variant="danger" size="sm" @click="showDisable = true">
            Disable 2FA
          </BaseButton>
        </div>

        <!-- Disable confirmation -->
        <div v-if="showDisable" class="mt-4 p-4 border border-red-200 rounded-md bg-red-50">
          <p class="text-sm text-red-800 mb-3">Enter your password to disable 2FA:</p>
          <form class="flex gap-2 items-end max-w-sm" @submit.prevent="disable2FA">
            <BaseInput
              id="disable-code"
              v-model="disablePassword"
              v-bind="disablePasswordAttrs"
              type="password"
              autocomplete="current-password"
              placeholder="Password"
              :error="disableErrors.password"
            />
            <BaseButton type="submit" variant="danger" size="sm" :loading="disabling">
              Disable
            </BaseButton>
            <BaseButton variant="ghost" size="sm" @click="cancelDisable"> Cancel </BaseButton>
          </form>
          <p v-if="disableError" class="mt-2 text-sm text-red-600">
            {{ disableError }}
          </p>
        </div>
      </template>

      <!-- Recovery codes view -->
      <template v-else-if="showingRecovery">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Recovery Codes</h2>
        <p class="text-sm text-gray-600 mb-4">
          Store these codes in a safe place. Each code can only be used once.
        </p>
        <div class="bg-gray-50 rounded-md p-4 mb-4 font-mono text-sm grid grid-cols-2 gap-2">
          <div v-for="code in recoveryCodes" :key="code" class="text-gray-700">
            {{ code }}
          </div>
        </div>
        <BaseButton variant="secondary" size="sm" @click="showingRecovery = false">
          Done
        </BaseButton>
      </template>

      <!-- Setup flow -->
      <template v-else-if="setupData">
        <h2 class="text-lg font-semibold text-gray-900 mb-2">Setup Two-Factor Authentication</h2>
        <p class="text-sm text-gray-600 mb-4">
          Scan the QR code below with your authenticator app (Google Authenticator, Authy, etc.).
        </p>

        <!-- QR Code -->
        <div class="flex justify-center mb-4">
          <img
            v-if="qrCodeDataUrl"
            :src="qrCodeDataUrl"
            alt="2FA QR Code"
            class="p-4 bg-white rounded-lg border border-gray-200 w-48 h-48"
          />
        </div>

        <div class="text-center mb-6">
          <p class="text-xs text-gray-500 mb-1">Or enter this secret manually:</p>
          <code class="text-sm font-mono bg-gray-100 px-3 py-1 rounded">{{
            setupData.secret
          }}</code>
        </div>

        <form class="max-w-xs mx-auto space-y-4" @submit.prevent="confirmSetup">
          <BaseInput
            id="confirm-code"
            v-model="confirmCode"
            v-bind="confirmCodeAttrs"
            label="Verification Code"
            type="text"
            inputmode="numeric"
            autocomplete="one-time-code"
            placeholder="000000"
            :error="confirmErrors.code"
          />
          <BaseButton type="submit" variant="primary" :loading="confirming" class="w-full">
            Confirm &amp; Enable
          </BaseButton>
        </form>

        <!-- Recovery codes after confirmation -->
        <div
          v-if="recoveryCodes.length > 0"
          class="mt-6 p-4 border border-green-200 rounded-md bg-green-50"
        >
          <p class="text-sm text-green-800 font-medium mb-2">Two-factor authentication enabled!</p>
          <p class="text-sm text-green-700 mb-3">Save these recovery codes in a secure place:</p>
          <div class="bg-white rounded-md p-3 font-mono text-sm grid grid-cols-2 gap-2">
            <div v-for="code in recoveryCodes" :key="code" class="text-gray-700">
              {{ code }}
            </div>
          </div>
        </div>
      </template>

      <!-- Not enabled — prompt to set up -->
      <template v-else>
        <div class="flex items-center gap-3 mb-4">
          <div
            class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center"
          >
            <svg
              class="h-5 w-5 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
              />
            </svg>
          </div>
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Two-factor is not enabled</h2>
            <p class="text-sm text-gray-600">
              Add an extra layer of security to your account with TOTP-based two-factor
              authentication.
            </p>
          </div>
        </div>

        <BaseButton variant="primary" size="sm" :loading="settingUp" @click="startSetup">
          Enable Two-Factor
        </BaseButton>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';
import { twoFactorApi } from '@/api';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { TwoFactorSetupResponse } from '@/api/types';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import { twoFactorConfirmSchema, twoFactorDisableSchema } from '@/api/schemas/settings';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';
import { mapLaravelErrors } from '@/lib/laravelErrors';

const authStore = useAuthStore();
const notifications = useNotificationStore();

const setupData = ref<TwoFactorSetupResponse | null>(null);
const qrCodeDataUrl = computed(() => {
  if (!setupData.value?.qr_code) return '';
  return 'data:image/svg+xml;base64,' + btoa(setupData.value.qr_code);
});
const settingUp = ref(false);
const {
  handleSubmit: handleConfirmSubmit,
  defineField: defineConfirmField,
  errors: confirmErrors,
  setErrors: setConfirmErrors,
  resetForm: resetConfirmForm,
  isSubmitting: confirming,
} = useForm({
  validationSchema: toTypedSchema(twoFactorConfirmSchema),
  initialValues: {
    code: '',
  },
});

const [confirmCode, confirmCodeAttrs] = defineConfirmField('code');

const recoveryCodes = ref<string[]>([]);
const showingRecovery = ref(false);
const showDisable = ref(false);
const disableError = ref('');

const {
  handleSubmit: handleDisableSubmit,
  defineField: defineDisableField,
  errors: disableErrors,
  setErrors: setDisableErrors,
  resetForm: resetDisableForm,
  isSubmitting: disabling,
} = useForm({
  validationSchema: toTypedSchema(twoFactorDisableSchema),
  initialValues: {
    password: '',
  },
});

const [disablePassword, disablePasswordAttrs] = defineDisableField('password');

async function startSetup(): Promise<void> {
  settingUp.value = true;
  try {
    const { data } = await twoFactorApi.setup();
    setupData.value = data.data;
  } catch {
    notifications.error('Failed to start 2FA setup.');
  } finally {
    settingUp.value = false;
  }
}

const confirmSetup = handleConfirmSubmit(async (values) => {
  setConfirmErrors({});

  try {
    const { data } = await twoFactorApi.confirm(values.code);
    recoveryCodes.value = data.data.recovery_codes;
    await authStore.fetchUser();
    notifications.success('Two-factor authentication enabled!');
    resetConfirmForm();
  } catch (err) {
    const axiosError = err as AxiosError<ApiError>;

    if (axiosError.response?.status === 422) {
      setConfirmErrors(mapLaravelErrors(axiosError.response.data.errors));
      return;
    }

    setConfirmErrors({ code: 'Invalid code. Please try again.' });
  }
});

async function viewRecoveryCodes(): Promise<void> {
  try {
    const { data } = await twoFactorApi.recoveryCodes();
    recoveryCodes.value = data.data.recovery_codes;
    showingRecovery.value = true;
  } catch {
    notifications.error('Failed to load recovery codes.');
  }
}

const disable2FA = handleDisableSubmit(async (values) => {
  disableError.value = '';
  setDisableErrors({});
  try {
    await twoFactorApi.disable(values.password);
    await authStore.fetchUser();
    setupData.value = null;
    recoveryCodes.value = [];
    showDisable.value = false;
    resetDisableForm();
    notifications.success('Two-factor authentication disabled.');
  } catch (err) {
    const axiosError = err as AxiosError<ApiError>;

    if (axiosError.response?.status === 422) {
      setDisableErrors(mapLaravelErrors(axiosError.response.data.errors));
      return;
    }

    disableError.value = axiosError.response?.data?.message ?? 'Failed to disable 2FA.';
  }
});

function cancelDisable(): void {
  showDisable.value = false;
  disableError.value = '';
  resetDisableForm();
}
</script>
