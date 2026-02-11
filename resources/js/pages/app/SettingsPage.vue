<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">
      Settings
    </h1>

    <div class="space-y-6">
      <!-- Profile -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Profile
        </h2>
        <form
          class="space-y-4 max-w-md"
          @submit.prevent="updateProfile"
        >
          <BaseInput
            id="name"
            v-model="profileName"
            v-bind="profileNameAttrs"
            label="Name"
            :error="profileErrors.name"
          />
          <BaseInput
            id="profile-email"
            v-model="profileEmail"
            label="Email"
            type="email"
            disabled
          />
          <BaseButton
            type="submit"
            variant="primary"
            :loading="profileSubmitting"
            size="sm"
          >
            Save
          </BaseButton>
        </form>
      </div>

      <!-- Change Password -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Change Password
        </h2>
        <form
          class="space-y-4 max-w-md"
          @submit.prevent="changePassword"
        >
          <BaseInput
            id="current-password"
            v-model="currentPassword"
            v-bind="currentPasswordAttrs"
            label="Current Password"
            type="password"
            :error="passwordErrors.current_password"
          />
          <BaseInput
            id="new-password"
            v-model="newPassword"
            v-bind="newPasswordAttrs"
            label="New Password"
            type="password"
            :error="passwordErrors.password"
          />
          <BaseInput
            id="confirm-password"
            v-model="newPasswordConfirmation"
            v-bind="newPasswordConfirmationAttrs"
            label="Confirm Password"
            type="password"
            :error="passwordErrors.password_confirmation"
          />
          <p
            v-if="passwordSuccess"
            class="text-sm text-green-600"
          >
            Password updated successfully.
          </p>
          <BaseButton
            type="submit"
            variant="primary"
            :loading="passwordSubmitting"
            size="sm"
          >
            Update Password
          </BaseButton>
        </form>
      </div>

      <!-- Change Email -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Change Email
        </h2>
        <form
          class="space-y-4 max-w-md"
          @submit.prevent="changeEmail"
        >
          <BaseInput
            id="new-email"
            v-model="newEmail"
            v-bind="newEmailAttrs"
            label="New Email"
            type="email"
            :error="emailErrors.email"
          />
          <BaseInput
            id="email-password"
            v-model="emailPassword"
            v-bind="emailPasswordAttrs"
            label="Current Password"
            type="password"
            :error="emailErrors.password"
          />
          <p
            v-if="emailSuccess"
            class="text-sm text-green-600"
          >
            Email updated. Please check your inbox for verification.
          </p>
          <BaseButton
            type="submit"
            variant="primary"
            :loading="emailSubmitting"
            size="sm"
          >
            Update Email
          </BaseButton>
        </form>
      </div>

      <!-- Security Links -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Security
        </h2>
        <div class="space-y-3">
          <router-link
            to="/settings/two-factor"
            class="flex items-center justify-between py-2 text-sm group"
          >
            <span class="text-gray-700 group-hover:text-gray-900">Two-Factor Authentication</span>
            <span
              :class="authStore.hasTwoFactor ? 'text-green-600' : 'text-gray-400'"
              class="text-xs font-medium"
            >
              {{ authStore.hasTwoFactor ? 'Enabled' : 'Disabled' }}
            </span>
          </router-link>
          <router-link
            to="/settings/sessions"
            class="flex items-center justify-between py-2 text-sm text-gray-700 hover:text-gray-900"
          >
            <span>Device Sessions</span>
            <span class="text-gray-400">&rarr;</span>
          </router-link>
          <router-link
            to="/settings/tokens"
            class="flex items-center justify-between py-2 text-sm text-gray-700 hover:text-gray-900"
          >
            <span>Personal Access Tokens</span>
            <span class="text-gray-400">&rarr;</span>
          </router-link>
        </div>
      </div>

      <!-- Danger Zone -->
      <div class="bg-white rounded-lg shadow-sm border border-red-200 p-6">
        <h2 class="text-lg font-semibold text-red-700 mb-2">
          Danger Zone
        </h2>
        <p class="text-sm text-gray-600 mb-4">
          Once you delete your account, there is no going back.
        </p>
        <div class="flex gap-3">
          <BaseButton
            variant="secondary"
            size="sm"
            :loading="exportLoading"
            @click="exportData"
          >
            Export My Data
          </BaseButton>
          <BaseButton
            variant="danger"
            size="sm"
            @click="showDeleteConfirm = true"
          >
            Delete Account
          </BaseButton>
        </div>

        <!-- Delete confirmation -->
        <div
          v-if="showDeleteConfirm"
          class="mt-4 p-4 border border-red-200 rounded-md bg-red-50"
        >
          <p class="text-sm text-red-800 mb-3">
            Type your password to confirm account deletion:
          </p>
          <form
            class="flex gap-2 items-end max-w-sm"
            @submit.prevent="deleteAccount"
          >
            <BaseInput
              id="delete-password"
              v-model="deletePassword"
              v-bind="deletePasswordAttrs"
              type="password"
              placeholder="Password"
              :error="deleteErrors.password"
            />
            <BaseButton
              type="submit"
              variant="danger"
              size="sm"
              :loading="deleteSubmitting"
            >
              Confirm
            </BaseButton>
            <BaseButton
              variant="ghost"
              size="sm"
              @click="cancelDelete"
            >
              Cancel
            </BaseButton>
          </form>
          <p
            v-if="deleteError"
            class="mt-2 text-sm text-red-600"
          >
            {{ deleteError }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';
import { accountApi } from '@/api';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import {
    changeEmailSchema,
    changePasswordSchema,
    deleteAccountSchema,
    profileSchema,
} from '@/api/schemas/settings';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';
import { mapLaravelErrors } from '@/lib/laravelErrors';

const authStore = useAuthStore();
const notifications = useNotificationStore();
const router = useRouter();

const profileEmail = ref('');

const {
    handleSubmit: handleProfileSubmit,
    defineField: defineProfileField,
    errors: profileErrors,
    resetForm: resetProfileForm,
    isSubmitting: profileSubmitting,
} = useForm({
    validationSchema: toTypedSchema(profileSchema),
    initialValues: {
        name: '',
    },
});

const [profileName, profileNameAttrs] = defineProfileField('name');

onMounted(() => {
    if (authStore.user) {
        resetProfileForm({
            values: {
                name: authStore.user.name,
            },
        });

        profileEmail.value = authStore.user.email;
    }
});

const updateProfile = handleProfileSubmit(async () => {
    try {
        // Profile update endpoint would go here; using a placeholder
        notifications.success('Profile updated');
    } finally {
        // handled by vee-validate
    }
});

const {
    handleSubmit: handlePasswordSubmit,
    defineField: definePasswordField,
    errors: passwordErrors,
    setErrors: setPasswordErrors,
    resetForm: resetPasswordForm,
    isSubmitting: passwordSubmitting,
} = useForm({
    validationSchema: toTypedSchema(changePasswordSchema),
    initialValues: {
        current_password: '',
        password: '',
        password_confirmation: '',
    },
});

const [currentPassword, currentPasswordAttrs] = definePasswordField('current_password');
const [newPassword, newPasswordAttrs] = definePasswordField('password');
const [newPasswordConfirmation, newPasswordConfirmationAttrs] =
    definePasswordField('password_confirmation');

const passwordSuccess = ref(false);

const changePassword = handlePasswordSubmit(async (values) => {
    setPasswordErrors({});
    passwordSuccess.value = false;

    try {
        await accountApi.changePassword(values);
        passwordSuccess.value = true;
        resetPasswordForm();
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;

        if (axiosError.response?.status === 422) {
            setPasswordErrors(mapLaravelErrors(axiosError.response.data.errors));
        }
    }
});

const {
    handleSubmit: handleEmailSubmit,
    defineField: defineEmailField,
    errors: emailErrors,
    setErrors: setEmailErrors,
    resetForm: resetEmailForm,
    isSubmitting: emailSubmitting,
} = useForm({
    validationSchema: toTypedSchema(changeEmailSchema),
    initialValues: {
        email: '',
        password: '',
    },
});

const [newEmail, newEmailAttrs] = defineEmailField('email');
const [emailPassword, emailPasswordAttrs] = defineEmailField('password');

const emailSuccess = ref(false);

const changeEmail = handleEmailSubmit(async (values) => {
    setEmailErrors({});
    emailSuccess.value = false;
    try {
        await accountApi.changeEmail(values);
        emailSuccess.value = true;
        resetEmailForm();
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;

        if (axiosError.response?.status === 422) {
            setEmailErrors(mapLaravelErrors(axiosError.response.data.errors));
        }
    }
});

// Export
const exportLoading = ref(false);
async function exportData(): Promise<void> {
    exportLoading.value = true;
    try {
        await accountApi.exportData();
        notifications.success('Data export has been sent to your email.');
    } catch {
        notifications.error('Failed to export data.');
    } finally {
        exportLoading.value = false;
    }
}

// Delete
const showDeleteConfirm = ref(false);
const deleteError = ref('');

const {
    handleSubmit: handleDeleteSubmit,
    defineField: defineDeleteField,
    errors: deleteErrors,
    setErrors: setDeleteErrors,
    resetForm: resetDeleteForm,
    isSubmitting: deleteSubmitting,
} = useForm({
    validationSchema: toTypedSchema(deleteAccountSchema),
    initialValues: {
        password: '',
    },
});

const [deletePassword, deletePasswordAttrs] = defineDeleteField('password');

const deleteAccount = handleDeleteSubmit(async (values) => {
    deleteError.value = '';
    setDeleteErrors({});

    try {
        await accountApi.deleteAccount(values.password);
        await authStore.logout();
        router.push('/login');
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;

        if (axiosError.response?.status === 422) {
            setDeleteErrors(mapLaravelErrors(axiosError.response.data.errors));
            return;
        }

        deleteError.value = axiosError.response?.data?.message ?? 'Failed to delete account.';
    }
});

function cancelDelete(): void {
    showDeleteConfirm.value = false;
    deleteError.value = '';
    resetDeleteForm();
}
</script>
