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
            <h1 class="text-2xl font-bold text-gray-900">Personal Access Tokens</h1>
        </div>

        <!-- Create Token -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Create New Token</h2>

            <div
                v-if="newTokenValue"
                class="rounded-md bg-green-50 border border-green-200 p-4 mb-4"
            >
                <p class="text-sm text-green-800 mb-2">
                    Token created! Copy it now — you won't be able to see it again.
                </p>
                <div class="flex items-center gap-2">
                    <code
                        class="flex-1 text-xs bg-white px-3 py-2 rounded border border-green-300 font-mono break-all"
                        >{{ newTokenValue }}</code
                    >
                    <BaseButton variant="secondary" size="sm" @click="copyToken">
                        {{ copied ? 'Copied!' : 'Copy' }}
                    </BaseButton>
                </div>
            </div>

            <form @submit.prevent="createToken" class="flex items-end gap-3 max-w-md">
                <BaseInput
                    v-model="tokenName"
                    label="Token Name"
                    placeholder="e.g. CI/CD"
                    id="token-name"
                    :error="tokenError"
                    class="flex-1"
                />
                <BaseButton type="submit" variant="primary" :loading="creating" size="sm"
                    >Create</BaseButton
                >
            </form>
        </div>

        <!-- Token List -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Your Tokens</h2>
            </div>

            <div v-if="isLoading" class="p-6 text-center">
                <div
                    class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-gray-300 border-t-gray-900"
                ></div>
            </div>

            <ul v-else class="divide-y divide-gray-200">
                <li
                    v-for="token in tokens"
                    :key="token.id"
                    class="p-4 flex items-center justify-between"
                >
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ token.name }}</p>
                        <p class="text-xs text-gray-500">
                            Created {{ formatDate(token.created_at) }}
                            <template v-if="token.last_used_at">
                                &middot; Last used {{ formatDate(token.last_used_at) }}</template
                            >
                        </p>
                    </div>
                    <BaseButton
                        variant="danger"
                        size="sm"
                        :loading="revokingId === token.id"
                        @click="revokeToken(token.id)"
                    >
                        Revoke
                    </BaseButton>
                </li>
                <li v-if="tokens.length === 0" class="p-6 text-center text-sm text-gray-500">
                    No personal access tokens yet.
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { tokenApi } from '@/api';
import { useNotificationStore } from '@/stores/notification';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { PersonalAccessToken } from '@/api/types';

const notifications = useNotificationStore();

const tokens = ref<PersonalAccessToken[]>([]);
const isLoading = ref(true);
const tokenName = ref('');
const tokenError = ref('');
const creating = ref(false);
const newTokenValue = ref('');
const copied = ref(false);
const revokingId = ref<number | null>(null);

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

async function loadTokens(): Promise<void> {
    try {
        const { data } = await tokenApi.list();
        tokens.value = data.data;
    } finally {
        isLoading.value = false;
    }
}

async function createToken(): Promise<void> {
    tokenError.value = '';
    if (!tokenName.value.trim()) {
        tokenError.value = 'Token name is required.';
        return;
    }
    creating.value = true;
    newTokenValue.value = '';
    try {
        const { data } = await tokenApi.create({ name: tokenName.value });
        newTokenValue.value = data.data.token;
        tokens.value.unshift(data.data.accessToken);
        tokenName.value = '';
    } catch {
        notifications.error('Failed to create token.');
    } finally {
        creating.value = false;
    }
}

async function revokeToken(id: number): Promise<void> {
    revokingId.value = id;
    try {
        await tokenApi.revoke(id);
        tokens.value = tokens.value.filter((t) => t.id !== id);
        notifications.success('Token revoked.');
    } catch {
        notifications.error('Failed to revoke token.');
    } finally {
        revokingId.value = null;
    }
}

async function copyToken(): Promise<void> {
    await navigator.clipboard.writeText(newTokenValue.value);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2000);
}

onMounted(loadTokens);
</script>
