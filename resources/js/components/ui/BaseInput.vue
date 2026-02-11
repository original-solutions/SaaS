<template>
  <div :class="wrapperClass">
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
    </label>
    <Input
      v-bind="inputAttrs"
      :id="id"
      :type="type"
      :model-value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :class="inputClass"
      :aria-invalid="error ? 'true' : undefined"
      @update:model-value="$emit('update:modelValue', String($event))"
    />
    <p v-if="error" class="mt-1 text-sm text-red-600">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, useAttrs } from 'vue';
import { Input } from '@/components/ui/input';

defineOptions({
  inheritAttrs: false,
});

withDefaults(
  defineProps<{
    modelValue?: string;
    label?: string;
    type?: string;
    placeholder?: string;
    error?: string;
    disabled?: boolean;
    id?: string;
    inputClass?: HTMLAttributes['class'];
  }>(),
  {
    modelValue: '',
    type: 'text',
    disabled: false,
    label: '',
    placeholder: '',
    error: '',
    id: '',
    inputClass: undefined,
  }
);

defineEmits<{
  'update:modelValue': [value: string];
}>();

const attrs = useAttrs();

const wrapperClass = computed(() => attrs.class);

const inputAttrs = computed(() => {
  const { class: _class, ...rest } = attrs;

  return rest;
});
</script>
