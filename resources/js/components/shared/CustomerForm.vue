<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <BaseInput id="name" v-model="name" label="Name" :error="errorFor('name')" v-bind="nameAttrs" />

    <BaseInput
      id="email"
      v-model="email"
      label="Email"
      type="email"
      :error="errorFor('email')"
      v-bind="emailAttrs"
    />

    <BaseInput
      id="phone"
      v-model="phone"
      label="Phone"
      :error="errorFor('phone')"
      v-bind="phoneAttrs"
    />

    <BaseInput
      id="company"
      v-model="company"
      label="Company"
      :error="errorFor('company')"
      v-bind="companyAttrs"
    />

    <div>
      <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
      <select
        id="status"
        v-model="status"
        v-bind="statusAttrs"
        class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
      >
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>

    <BaseButton type="submit" :loading="loading">
      {{ initial ? 'Update' : 'Create' }}
    </BaseButton>
  </form>
</template>

<script setup lang="ts">
import { watchEffect } from 'vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { Customer } from '@/api/types';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { customerSchema, type CustomerFormValues } from '@/api/schemas/customer';

const props = withDefaults(
  defineProps<{
    initial?: Customer | null;
    loading?: boolean;
    errors?: Record<string, string>;
  }>(),
  {
    initial: null,
    loading: false,
    errors: () => ({}),
  }
);

const emit = defineEmits<{
  submit: [data: Record<string, string>];
}>();

const {
  handleSubmit,
  errors: clientErrors,
  defineField,
  resetForm,
} = useForm<CustomerFormValues>({
  validationSchema: toTypedSchema(customerSchema),
  initialValues: {
    name: '',
    email: '',
    phone: '',
    company: '',
    status: 'active',
  },
});

const [name, nameAttrs] = defineField('name');
const [email, emailAttrs] = defineField('email');
const [phone, phoneAttrs] = defineField('phone');
const [company, companyAttrs] = defineField('company');
const [status, statusAttrs] = defineField('status');

const onSubmit = handleSubmit((values) => {
  emit('submit', values as unknown as Record<string, string>);
});

function errorFor(field: keyof CustomerFormValues): string {
  return props.errors?.[field] ?? clientErrors.value[field] ?? '';
}

watchEffect(() => {
  if (!props.initial) {
    return;
  }

  resetForm({
    values: {
      name: props.initial.name,
      email: props.initial.email ?? '',
      phone: props.initial.phone ?? '',
      company: props.initial.company ?? '',
      status: props.initial.status,
    },
  });
});
</script>
