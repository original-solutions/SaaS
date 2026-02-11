<template>
  <Button
    v-bind="$attrs"
    :type="props.type"
    :disabled="props.disabled || props.loading"
    :variant="mappedVariant"
    :size="props.size"
  >
    <Loader2
      v-if="props.loading"
      class="h-4 w-4 animate-spin"
    />
    <slot />
  </Button>
</template>

<script setup lang="ts">
import { Loader2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import type { ButtonVariants } from '@/components/ui/button';

const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'danger' | 'ghost';
        type?: 'button' | 'submit' | 'reset';
        size?: ButtonVariants['size'];
        loading?: boolean;
        disabled?: boolean;
    }>(),
    {
        variant: 'primary',
        type: 'button',
        size: 'default',
        loading: false,
        disabled: false,
    },
);

const mappedVariant = computed<ButtonVariants['variant']>(() => {
    switch (props.variant) {
        case 'secondary':
            return 'secondary';
        case 'danger':
            return 'destructive';
        case 'ghost':
            return 'ghost';
        case 'primary':
        default:
            return 'default';
    }
});
</script>
