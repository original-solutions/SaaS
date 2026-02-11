<template>
  <ImpersonationBanner />
  <div class="min-h-screen bg-gray-50" :class="{ 'pt-10': authStore.isImpersonating }">
    <!-- Top navigation -->
    <nav class="bg-white border-b border-gray-200">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
          <div class="flex items-center gap-8">
            <router-link to="/" class="text-xl font-bold text-gray-900"> SaaS </router-link>
            <div class="hidden sm:flex items-center gap-4">
              <router-link
                v-for="item in navItems"
                :key="item.to"
                :to="item.to"
                class="text-sm text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md transition-colors"
                active-class="text-gray-900 bg-gray-100"
              >
                {{ item.label }}
              </router-link>
            </div>
          </div>

          <div class="flex items-center gap-4">
            <!-- Tenant Switcher -->
            <TenantSwitcher v-if="tenantStore.all.length > 1" />

            <!-- User Menu -->
            <div ref="menuRef" class="relative">
              <button
                class="flex items-center gap-2 text-sm text-gray-700 hover:text-gray-900"
                @click="showMenu = !showMenu"
              >
                <span>{{ authStore.user?.name }}</span>
                <ChevronDown class="h-4 w-4" />
              </button>

              <div
                v-if="showMenu"
                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg border border-gray-200 py-1 z-50"
              >
                <router-link
                  to="/settings"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                  @click="showMenu = false"
                >
                  Settings
                </router-link>
                <router-link
                  v-if="authStore.isSuperAdmin"
                  to="/admin"
                  class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                  @click="showMenu = false"
                >
                  Admin Panel
                </router-link>
                <hr class="my-1" />
                <button
                  class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50"
                  @click="handleLogout"
                >
                  Sign out
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page content -->
    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { onClickOutside } from '@vueuse/core';
import { ChevronDown } from 'lucide-vue-next';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';
import TenantSwitcher from '@/components/shared/TenantSwitcher.vue';
import ImpersonationBanner from '@/components/shared/ImpersonationBanner.vue';

const authStore = useAuthStore();
const tenantStore = useTenantStore();
const router = useRouter();

const showMenu = ref(false);
const menuRef = ref<HTMLElement | null>(null);

onClickOutside(menuRef, () => {
  showMenu.value = false;
});

const navItems = [
  { to: '/', label: 'Dashboard' },
  { to: '/customers', label: 'Customers' },
];

async function handleLogout(): Promise<void> {
  showMenu.value = false;
  await authStore.logout();
  router.push({ name: 'login' });
}
</script>
