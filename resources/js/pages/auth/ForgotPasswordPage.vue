<template>
  <GuestLayout>
    <h2 class="text-lg font-semibold text-gray-900 mb-6">
      Forgot password
    </h2>

    <div
      v-if="sent"
      class="text-sm text-green-700 bg-green-50 rounded-md p-4"
    >
      We've emailed you a password reset link.
    </div>

    <form
      v-else
      class="space-y-4"
      @submit.prevent="onSubmit"
    >
      <p class="text-sm text-gray-600">
        Enter your email and we'll send you a reset link.
      </p>

      <BaseInput
        id="email"
        v-model="email"
        v-bind="emailAttrs"
        label="Email"
        type="email"
        placeholder="you@example.com"
        :error="errors.email"
      />

      <BaseButton
        type="submit"
        variant="primary"
        :loading="isSubmitting"
        class="w-full"
      >
        Send reset link
      </BaseButton>

      <div class="text-center">
        <router-link
          to="/login"
          class="text-sm text-gray-600 hover:text-gray-900"
        >
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
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import { forgotPasswordSchema } from '@/api/schemas/auth';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';
import { mapLaravelErrors } from '@/lib/laravelErrors';

const sent = ref(false);

const { handleSubmit, defineField, errors, setErrors, resetForm, isSubmitting } = useForm({
    validationSchema: toTypedSchema(forgotPasswordSchema),
    initialValues: {
        email: '',
    },
});

const [email, emailAttrs] = defineField('email');

const onSubmit = handleSubmit(async (values) => {
    setErrors({});

    try {
        await authApi.forgotPassword(values.email);
        sent.value = true;
        resetForm();
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;

        if (axiosError.response?.status === 422) {
            setErrors(mapLaravelErrors(axiosError.response.data.errors));
            return;
        }

        setErrors({ email: 'Unable to send reset link. Please check your email.' });
    }
});
</script>
