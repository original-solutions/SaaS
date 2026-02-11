<template>
  <GuestLayout>
    <!-- 2FA Challenge Step -->
    <template v-if="showTwoFactor">
      <h2 class="text-lg font-semibold text-gray-900 mb-2">
        Two-Factor Authentication
      </h2>
      <p class="text-sm text-gray-600 mb-6">
        {{
          useRecoveryCode
            ? 'Enter one of your recovery codes.'
            : 'Enter the code from your authenticator app.'
        }}
      </p>

      <form
        class="space-y-4"
        @submit.prevent="handleTwoFactor"
      >
        <BaseInput
          v-if="!useRecoveryCode"
          id="2fa-code"
          v-model="twoFactorCode"
          v-bind="twoFactorCodeAttrs"
          label="Authentication Code"
          type="text"
          inputmode="numeric"
          autocomplete="one-time-code"
          placeholder="000000"
          :error="generalError || twoFactorErrors.code"
        />

        <BaseInput
          v-else
          id="recovery-code"
          v-model="recoveryCode"
          v-bind="recoveryCodeAttrs"
          label="Recovery Code"
          type="text"
          placeholder="XXXXXXXX"
          :error="generalError || twoFactorErrors.recovery_code"
        />

        <p
          v-if="generalError"
          class="text-sm text-red-600"
        >
          {{ generalError }}
        </p>

        <BaseButton
          type="submit"
          variant="primary"
          :loading="isLoading"
          class="w-full"
        >
          Verify
        </BaseButton>

        <div class="text-center">
          <button
            type="button"
            class="text-sm text-gray-600 hover:text-gray-900"
            @click="toggleTwoFactorMode"
          >
            {{ useRecoveryCode ? 'Use authenticator code' : 'Use a recovery code' }}
          </button>
        </div>
      </form>
    </template>

    <!-- Login Step -->
    <template v-else>
      <h2 class="text-lg font-semibold text-gray-900 mb-6">
        Sign in
      </h2>

      <form
        class="space-y-4"
        @submit.prevent="handleLogin"
      >
        <BaseInput
          id="email"
          v-model="email"
          v-bind="emailAttrs"
          label="Email"
          type="email"
          placeholder="you@example.com"
          :error="loginErrors.email"
        />

        <BaseInput
          id="password"
          v-model="password"
          v-bind="passwordAttrs"
          label="Password"
          type="password"
          placeholder="••••••••"
          :error="loginErrors.password"
        />

        <p
          v-if="generalError"
          class="text-sm text-red-600"
        >
          {{ generalError }}
        </p>

        <BaseButton
          type="submit"
          variant="primary"
          :loading="isLoading"
          class="w-full"
        >
          Sign in
        </BaseButton>

        <div class="flex items-center justify-between text-sm">
          <router-link
            to="/forgot-password"
            class="text-gray-600 hover:text-gray-900"
          >
            Forgot password?
          </router-link>
          <router-link
            to="/magic-link"
            class="text-gray-600 hover:text-gray-900"
          >
            Sign in with magic link
          </router-link>
        </div>
      </form>
    </template>
  </GuestLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import { useNotificationStore } from '@/stores/notification';
import GuestLayout from '@/layouts/GuestLayout.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import { loginSchema, twoFactorCodeSchema, twoFactorRecoverySchema } from '@/api/schemas/auth';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';
import { mapLaravelErrors } from '@/lib/laravelErrors';

const auth = useAuthStore();
const tenant = useTenantStore();
const notifications = useNotificationStore();
const router = useRouter();
const route = useRoute();

const isLoading = ref(false);
const showTwoFactor = ref(false);
const useRecoveryCode = ref(false);
const generalError = ref('');

const {
    handleSubmit: handleLoginSubmit,
    defineField: defineLoginField,
    errors: loginErrors,
    setErrors: setLoginErrors,
} = useForm({
    validationSchema: toTypedSchema(loginSchema),
    initialValues: {
        email: '',
        password: '',
    },
});

const [email, emailAttrs] = defineLoginField('email');
const [password, passwordAttrs] = defineLoginField('password');

const {
    handleSubmit: handleTwoFactorSubmit,
    defineField: defineTwoFactorField,
    errors: twoFactorErrors,
    resetForm: resetTwoFactorForm,
} = useForm({
    validationSchema: computed(() =>
        toTypedSchema(useRecoveryCode.value ? twoFactorRecoverySchema : twoFactorCodeSchema),
    ),
    initialValues: {
        code: '',
        recovery_code: '',
    },
});

const [twoFactorCode, twoFactorCodeAttrs] = defineTwoFactorField('code');
const [recoveryCode, recoveryCodeAttrs] = defineTwoFactorField('recovery_code');

function clearErrors(): void {
    generalError.value = '';
    setLoginErrors({});
}

function redirectAfterLogin(): void {
    tenant.initialize(auth.tenants);
    notifications.success('Signed in successfully');
    const redirect = (route.query.redirect as string) || '/';
    router.push(redirect);
}

const handleLogin = handleLoginSubmit(async (values) => {
    clearErrors();
    isLoading.value = true;

    try {
        const result = await auth.login(values);

        if ('two_factor' in result && result.two_factor) {
            showTwoFactor.value = true;
            resetTwoFactorForm();
            return;
        }

        redirectAfterLogin();
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;
        if (axiosError.response?.status === 422) {
            const data = axiosError.response.data;
            if (data.errors) {
                setLoginErrors(mapLaravelErrors(data.errors));
            }
        } else if (axiosError.response?.status === 401) {
            generalError.value = 'Invalid credentials.';
        } else {
            generalError.value = 'An error occurred. Please try again.';
        }
    } finally {
        isLoading.value = false;
    }
});

const handleTwoFactor = handleTwoFactorSubmit(async (values) => {
    generalError.value = '';
    isLoading.value = true;

    try {
        if (useRecoveryCode.value) {
            const recovery = 'recovery_code' in values ? values.recovery_code : '';
            await auth.completeTwoFactorRecovery(recovery);
        } else {
            const code = 'code' in values ? values.code : '';
            await auth.completeTwoFactor(code);
        }

        redirectAfterLogin();
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;
        generalError.value =
            axiosError.response?.data?.message ?? 'Invalid code. Please try again.';
    } finally {
        isLoading.value = false;
    }
});

function toggleTwoFactorMode(): void {
    generalError.value = '';
    useRecoveryCode.value = !useRecoveryCode.value;
    resetTwoFactorForm();
}
</script>
