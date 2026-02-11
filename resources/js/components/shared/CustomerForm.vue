<template>
    <form @submit.prevent="$emit('submit', form)" class="space-y-4">
        <BaseInput v-model="form.name" label="Name" :error="errors.name" id="name" />

        <BaseInput
            v-model="form.email"
            label="Email"
            type="email"
            :error="errors.email"
            id="email"
        />

        <BaseInput v-model="form.phone" label="Phone" :error="errors.phone" id="phone" />

        <BaseInput v-model="form.company" label="Company" :error="errors.company" id="company" />

        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
                v-model="form.status"
                id="status"
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
import { reactive, watchEffect } from 'vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { Customer } from '@/api/types';

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
    },
);

defineEmits<{
    submit: [data: Record<string, string>];
}>();

const form = reactive({
    name: '',
    email: '',
    phone: '',
    company: '',
    status: 'active',
});

watchEffect(() => {
    if (props.initial) {
        form.name = props.initial.name;
        form.email = props.initial.email ?? '';
        form.phone = props.initial.phone ?? '';
        form.company = props.initial.company ?? '';
        form.status = props.initial.status;
    }
});
</script>
