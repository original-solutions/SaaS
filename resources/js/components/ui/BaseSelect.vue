<template>
  <div :class="wrapperClass">
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
    </label>

    <Select
      :model-value="internalValue"
      :disabled="disabled"
      @update:model-value="onUpdate"
    >
      <SelectTrigger v-bind="triggerAttrs" :id="id" :class="triggerClass">
        <SelectValue :placeholder="placeholder" />
      </SelectTrigger>
      <SelectContent>
        <SelectItem
          v-for="option in options"
          :key="option.value || EMPTY_VALUE"
          :value="option.value || EMPTY_VALUE"
          :disabled="option.disabled"
        >
          {{ option.label }}
        </SelectItem>
      </SelectContent>
    </Select>

    <p v-if="error" class="mt-1 text-sm text-red-600">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, useAttrs } from 'vue';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

defineOptions({
  inheritAttrs: false,
});

type Option = {
  value: string;
  label: string;
  disabled?: boolean;
};

const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null;
    options: Option[];
    label?: string;
    placeholder?: string;
    error?: string;
    disabled?: boolean;
    id?: string;
    triggerClass?: HTMLAttributes['class'];
  }>(),
  {
    modelValue: '',
    label: '',
    placeholder: 'Select...',
    error: '',
    disabled: false,
    id: '',
    triggerClass: undefined,
  }
);

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const attrs = useAttrs();

const wrapperClass = computed(() => attrs.class);

const EMPTY_VALUE = '__base_select_empty__';

const hasEmptyOption = computed(() => props.options.some((o) => o.value === ''));

const internalValue = computed(() => {
  const external = String(props.modelValue ?? '');

  if (external === '' && hasEmptyOption.value) {
    return EMPTY_VALUE;
  }

  return external;
});

const triggerAttrs = computed(() => {
  const rest = { ...attrs } as Record<string, unknown>;

  delete rest.class;

  return rest;
});

function onUpdate(value: unknown): void {
  if (value === EMPTY_VALUE) {
    emit('update:modelValue', '');
    return;
  }

  emit('update:modelValue', String(value ?? ''));
}
</script>
