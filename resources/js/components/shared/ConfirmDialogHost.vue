<template>
  <Dialog :open="state.open" @update:open="handleOpenChange">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ state.options?.title }}</DialogTitle>
        <DialogDescription v-if="state.options?.description">
          {{ state.options.description }}
        </DialogDescription>
      </DialogHeader>

      <DialogFooter class="gap-2">
        <Button variant="outline" @click="close(false)">
          {{ state.options?.cancelText ?? 'Cancel' }}
        </Button>
        <Button
          :variant="state.options?.destructive ? 'destructive' : 'default'"
          @click="close(true)"
        >
          {{ state.options?.confirmText ?? 'Confirm' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { useConfirmDialog } from '@/composables/useConfirmDialog';

const { state, close } = useConfirmDialog();

function handleOpenChange(open: boolean): void {
  if (!open) {
    close(false);
  }
}
</script>
