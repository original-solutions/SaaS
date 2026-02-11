<template>
    <div>
        <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 mb-1">
            {{ label }}
        </label>
        <input
            :id="id"
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :disabled="disabled"
            :class="[
                'block w-full rounded-md border px-3 py-2 text-sm shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-0',
                error
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                    : 'border-gray-300 focus:border-gray-500 focus:ring-gray-500',
                disabled ? 'bg-gray-50 text-gray-500' : 'bg-white',
            ]"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />
        <p v-if="error" class="mt-1 text-sm text-red-600">
            {{ error }}
        </p>
    </div>
</template>

<script setup lang="ts">
withDefaults(
    defineProps<{
        modelValue?: string;
        label?: string;
        type?: string;
        placeholder?: string;
        error?: string;
        disabled?: boolean;
        id?: string;
    }>(),
    {
        modelValue: '',
        type: 'text',
        disabled: false,
        label: '',
        placeholder: '',
        error: '',
        id: '',
    },
);

defineEmits<{
    'update:modelValue': [value: string];
}>();
</script>
