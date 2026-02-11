<template>
  <GuestLayout>
    <h2 class="text-lg font-semibold text-gray-900 mb-6">
      Reset password
    </h2>

    <form
      class="space-y-4"
      @submit.prevent="onSubmit"
    >
      <BaseInput
        id="email"
        v-model="email"
        v-bind="emailAttrs"
        label="Email"
        type="email"
        :error="errors.email"
      />

      <BaseInput
        id="password"
        v-model="password"
        v-bind="passwordAttrs"
        label="New password"
        type="password"
        :error="errors.password"
      />

      <BaseInput
        id="password_confirmation"
        v-model="passwordConfirmation"
        v-bind="passwordConfirmationAttrs"
        label="Confirm password"
        type="password"
        :error="errors.password_confirmation"
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
        :loading="isSubmitting"
        class="w-full"
      >
        Reset password
      </BaseButton>
    </form>
  </GuestLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { authApi } from '@/api';
import { useNotificationStore } from '@/stores/notification';
import GuestLayout from '@/layouts/GuestLayout.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import { resetPasswordSchema } from '@/api/schemas/auth';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';
import { mapLaravelErrors } from '@/lib/laravelErrors';

const router = useRouter();
const route = useRoute();
const notifications = useNotificationStore();

const generalError = ref('');

const { handleSubmit, defineField, errors, setErrors, isSubmitting } = useForm({
    validationSchema: toTypedSchema(resetPasswordSchema),
    initialValues: {
        email: (route.query.email as string) || '',
        password: '',
        password_confirmation: '',
    },
});

const [email, emailAttrs] = defineField('email');
const [password, passwordAttrs] = defineField('password');
const [passwordConfirmation, passwordConfirmationAttrs] = defineField('password_confirmation');

const onSubmit = handleSubmit(async (values) => {
    generalError.value = '';
    setErrors({});

    try {
        await authApi.resetPassword({
            token: route.params.token as string,
            email: values.email,
            password: values.password,
            password_confirmation: values.password_confirmation,
        });

        notifications.success('Password reset successfully. Please sign in.');
        router.push({ name: 'login' });
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;

        if (axiosError.response?.status === 422) {
            setErrors(mapLaravelErrors(axiosError.response.data.errors));
            return;
        }

        generalError.value = 'Unable to reset password. The link may have expired.';
    }
});
</script>
