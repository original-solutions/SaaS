<template>
  <NotificationToast />
  <ConfirmDialogHost />
  <router-view />
</template>

<script setup lang="ts">
import NotificationToast from '@/components/ui/NotificationToast.vue';
import ConfirmDialogHost from '@/components/shared/ConfirmDialogHost.vue';
</script>
