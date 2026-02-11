<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            'inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',
            variantClasses[variant],
        ]"
    >
        <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
        <slot />
    </button>
</template>

<script setup lang="ts">
import { Loader2 } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'danger' | 'ghost';
        type?: 'button' | 'submit' | 'reset';
        loading?: boolean;
        disabled?: boolean;
    }>(),
    {
        variant: 'primary',
        type: 'button',
        loading: false,
        disabled: false,
    },
);

const variantClasses: Record<string, string> = {
    primary: 'bg-gray-900 text-white hover:bg-gray-800 focus:ring-gray-900',
    secondary: 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-gray-500',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
    ghost: 'text-gray-700 hover:bg-gray-100 focus:ring-gray-500',
};
</script>
