<template>
  <div>
    <h1 class="text-2xl font-bold text-white mb-6">
      Search
    </h1>

    <input
      v-model="query"
      type="text"
      placeholder="Search tenants, users, customers..."
      class="w-full max-w-lg rounded-md border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-400 focus:border-indigo-500 focus:outline-none mb-6"
      @input="debouncedSearch"
    >

    <div
      v-if="isLoading"
      class="text-gray-400 text-sm"
    >
      Searching...
    </div>

    <div
      v-else-if="hasResults"
      class="space-y-6"
    >
      <div v-if="results.tenants?.length">
        <h2 class="text-sm font-medium text-gray-400 uppercase mb-2">
          Tenants
        </h2>
        <div class="bg-gray-800 rounded-lg border border-gray-700 divide-y divide-gray-700">
          <router-link
            v-for="t in results.tenants"
            :key="t.id"
            :to="`/admin/tenants/${t.id}`"
            class="block px-4 py-3 hover:bg-gray-700/50"
          >
            <p class="text-sm font-medium text-white">
              {{ t.name }}
            </p>
            <p class="text-xs text-gray-400">
              {{ t.slug }}
            </p>
          </router-link>
        </div>
      </div>

      <div v-if="results.users?.length">
        <h2 class="text-sm font-medium text-gray-400 uppercase mb-2">
          Users
        </h2>
        <div class="bg-gray-800 rounded-lg border border-gray-700 divide-y divide-gray-700">
          <router-link
            v-for="u in results.users"
            :key="u.id"
            :to="`/admin/users/${u.id}`"
            class="block px-4 py-3 hover:bg-gray-700/50"
          >
            <p class="text-sm font-medium text-white">
              {{ u.name }}
            </p>
            <p class="text-xs text-gray-400">
              {{ u.email }}
            </p>
          </router-link>
        </div>
      </div>

      <div v-if="results.customers?.length">
        <h2 class="text-sm font-medium text-gray-400 uppercase mb-2">
          Customers
        </h2>
        <div class="bg-gray-800 rounded-lg border border-gray-700 divide-y divide-gray-700">
          <div
            v-for="c in results.customers"
            :key="c.id"
            class="px-4 py-3"
          >
            <p class="text-sm font-medium text-white">
              {{ c.name }}
            </p>
            <p class="text-xs text-gray-400">
              {{ c.email }} &middot; {{ c.tenant_name }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div
      v-else-if="query && !isLoading"
      class="text-sm text-gray-400"
    >
      No results for "{{ query }}".
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import { adminSearchApi } from '@/api';

interface SearchResults {
    tenants?: Array<{ id: number; name: string; slug: string }>;
    users?: Array<{ id: number; name: string; email: string }>;
    customers?: Array<{ id: number; name: string; email: string; tenant_name: string }>;
}

const query = ref('');
const results = ref<SearchResults>({});
const isLoading = ref(false);

const hasResults = computed(
    () =>
        (results.value.tenants?.length ?? 0) +
            (results.value.users?.length ?? 0) +
            (results.value.customers?.length ?? 0) >
        0,
);

async function search(): Promise<void> {
    if (!query.value.trim()) {
        results.value = {};
        return;
    }
    isLoading.value = true;
    try {
        const { data } = await adminSearchApi.search(query.value);
        results.value = data.data ?? data;
    } finally {
        isLoading.value = false;
    }
}

const debouncedSearch = useDebounceFn(search, 300);
</script>
