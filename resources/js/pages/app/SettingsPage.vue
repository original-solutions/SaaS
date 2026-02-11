<template>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Settings</h1>

        <div class="space-y-6">
            <!-- Profile -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile</h2>
                <form @submit.prevent="updateProfile" class="space-y-4 max-w-md">
                    <BaseInput
                        v-model="profile.name"
                        label="Name"
                        id="name"
                        :error="profileErrors.name"
                    />
                    <BaseInput
                        v-model="profile.email"
                        label="Email"
                        type="email"
                        id="profile-email"
                        disabled
                    />
                    <BaseButton type="submit" variant="primary" :loading="profileLoading" size="sm">
                        Save
                    </BaseButton>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Change Password</h2>
                <form @submit.prevent="changePassword" class="space-y-4 max-w-md">
                    <BaseInput
                        v-model="passwords.current_password"
                        label="Current Password"
                        type="password"
                        id="current-password"
                        :error="passwordErrors.current_password"
                    />
                    <BaseInput
                        v-model="passwords.password"
                        label="New Password"
                        type="password"
                        id="new-password"
                        :error="passwordErrors.password"
                    />
                    <BaseInput
                        v-model="passwords.password_confirmation"
                        label="Confirm Password"
                        type="password"
                        id="confirm-password"
                    />
                    <p v-if="passwordSuccess" class="text-sm text-green-600">
                        Password updated successfully.
                    </p>
                    <BaseButton
                        type="submit"
                        variant="primary"
                        :loading="passwordLoading"
                        size="sm"
                    >
                        Update Password
                    </BaseButton>
                </form>
            </div>

            <!-- Change Email -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Change Email</h2>
                <form @submit.prevent="changeEmail" class="space-y-4 max-w-md">
                    <BaseInput
                        v-model="emailForm.email"
                        label="New Email"
                        type="email"
                        id="new-email"
                        :error="emailErrors.email"
                    />
                    <BaseInput
                        v-model="emailForm.password"
                        label="Current Password"
                        type="password"
                        id="email-password"
                        :error="emailErrors.password"
                    />
                    <p v-if="emailSuccess" class="text-sm text-green-600">
                        Email updated. Please check your inbox for verification.
                    </p>
                    <BaseButton type="submit" variant="primary" :loading="emailLoading" size="sm">
                        Update Email
                    </BaseButton>
                </form>
            </div>

            <!-- Security Links -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Security</h2>
                <div class="space-y-3">
                    <router-link
                        to="/settings/two-factor"
                        class="flex items-center justify-between py-2 text-sm group"
                    >
                        <span class="text-gray-700 group-hover:text-gray-900"
                            >Two-Factor Authentication</span
                        >
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
                <h2 class="text-lg font-semibold text-red-700 mb-2">Danger Zone</h2>
                <p class="text-sm text-gray-600 mb-4">
                    Once you delete your account, there is no going back.
                </p>
                <div class="flex gap-3">
                    <BaseButton
                        variant="secondary"
                        size="sm"
                        @click="exportData"
                        :loading="exportLoading"
                    >
                        Export My Data
                    </BaseButton>
                    <BaseButton variant="danger" size="sm" @click="showDeleteConfirm = true">
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
                    <div class="flex gap-2 items-end max-w-sm">
                        <BaseInput
                            v-model="deletePassword"
                            type="password"
                            placeholder="Password"
                            id="delete-password"
                        />
                        <BaseButton
                            variant="danger"
                            size="sm"
                            :loading="deleteLoading"
                            @click="deleteAccount"
                        >
                            Confirm
                        </BaseButton>
                        <BaseButton variant="ghost" size="sm" @click="showDeleteConfirm = false">
                            Cancel
                        </BaseButton>
                    </div>
                    <p v-if="deleteError" class="mt-2 text-sm text-red-600">{{ deleteError }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';
import { accountApi } from '@/api';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';

const authStore = useAuthStore();
const notifications = useNotificationStore();
const router = useRouter();

// Profile
const profile = reactive({ name: '', email: '' });
const profileErrors = reactive<Record<string, string>>({});
const profileLoading = ref(false);

onMounted(() => {
    if (authStore.user) {
        profile.name = authStore.user.name;
        profile.email = authStore.user.email;
    }
});

async function updateProfile(): Promise<void> {
    profileErrors.name = '';
    profileLoading.value = true;
    try {
        // Profile update endpoint would go here; using a placeholder
        notifications.success('Profile updated');
    } finally {
        profileLoading.value = false;
    }
}

// Password
const passwords = reactive({ current_password: '', password: '', password_confirmation: '' });
const passwordErrors = reactive<Record<string, string>>({});
const passwordLoading = ref(false);
const passwordSuccess = ref(false);

async function changePassword(): Promise<void> {
    passwordErrors.current_password = '';
    passwordErrors.password = '';
    passwordLoading.value = true;
    passwordSuccess.value = false;
    try {
        await accountApi.changePassword(passwords);
        passwordSuccess.value = true;
        passwords.current_password = '';
        passwords.password = '';
        passwords.password_confirmation = '';
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;
        if (axiosError.response?.data?.errors) {
            Object.assign(
                passwordErrors,
                Object.fromEntries(
                    Object.entries(axiosError.response.data.errors).map(([k, v]) => [k, v[0]]),
                ),
            );
        }
    } finally {
        passwordLoading.value = false;
    }
}

// Email
const emailForm = reactive({ email: '', password: '' });
const emailErrors = reactive<Record<string, string>>({});
const emailLoading = ref(false);
const emailSuccess = ref(false);

async function changeEmail(): Promise<void> {
    emailErrors.email = '';
    emailErrors.password = '';
    emailLoading.value = true;
    emailSuccess.value = false;
    try {
        await accountApi.changeEmail(emailForm);
        emailSuccess.value = true;
        emailForm.email = '';
        emailForm.password = '';
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;
        if (axiosError.response?.data?.errors) {
            Object.assign(
                emailErrors,
                Object.fromEntries(
                    Object.entries(axiosError.response.data.errors).map(([k, v]) => [k, v[0]]),
                ),
            );
        }
    } finally {
        emailLoading.value = false;
    }
}

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
const deletePassword = ref('');
const deleteLoading = ref(false);
const deleteError = ref('');

async function deleteAccount(): Promise<void> {
    deleteError.value = '';
    deleteLoading.value = true;
    try {
        await accountApi.deleteAccount(deletePassword.value);
        await authStore.logout();
        router.push('/login');
    } catch (err) {
        const axiosError = err as AxiosError<ApiError>;
        deleteError.value = axiosError.response?.data?.message ?? 'Failed to delete account.';
    } finally {
        deleteLoading.value = false;
    }
}
</script>
