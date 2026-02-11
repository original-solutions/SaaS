<template>
  <div>
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-4">
        <router-link to="/customers" class="text-sm text-gray-500 hover:text-gray-700">
          &larr; Back
        </router-link>
        <h1 class="text-2xl font-bold text-gray-900">
          {{ customer?.name ?? 'Customer' }}
        </h1>
        <span
          v-if="customer"
          :class="[
            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
            customer.status === 'active'
              ? 'bg-green-100 text-green-800'
              : 'bg-gray-100 text-gray-800',
          ]"
        >
          {{ customer.status }}
        </span>
      </div>
      <div v-if="customer && tenantStore.canWrite" class="flex items-center gap-2">
        <BaseButton as-child variant="secondary" size="sm">
          <router-link :to="`/customers/${customer.id}/edit`"> Edit </router-link>
        </BaseButton>
        <BaseButton variant="danger" size="sm" @click="showDelete = true"> Delete </BaseButton>
      </div>
    </div>

    <div v-if="isLoading" class="text-sm text-gray-500">Loading...</div>

    <template v-else-if="customer">
      <!-- Details -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <!-- Info Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Details</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm text-gray-500">Name</dt>
                <dd class="text-sm font-medium text-gray-900">
                  {{ customer.name }}
                </dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Email</dt>
                <dd class="text-sm text-gray-900">
                  {{ customer.email ?? '—' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Phone</dt>
                <dd class="text-sm text-gray-900">
                  {{ customer.phone ?? '—' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Company</dt>
                <dd class="text-sm text-gray-900">
                  {{ customer.company ?? '—' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm text-gray-500">Created</dt>
                <dd class="text-sm text-gray-900">
                  {{ formatDate(customer.created_at) }}
                </dd>
              </div>
            </dl>
          </div>

          <!-- Tags -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Tags</h2>
            <div class="flex flex-wrap gap-2 mb-3">
              <span
                v-for="tag in tags"
                :key="tag.id"
                class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700"
              >
                {{ tag.name }}
                <BaseButton
                  v-if="tenantStore.canWrite"
                  variant="ghost"
                  size="sm"
                  class="h-5 w-5 p-0 text-indigo-400 hover:text-indigo-600"
                  @click="removeTag(tag.id)"
                >
                  &times;
                </BaseButton>
              </span>
              <span v-if="tags.length === 0" class="text-sm text-gray-400">No tags</span>
            </div>
            <div v-if="tenantStore.canWrite" class="flex gap-2 mt-2">
              <BaseSelect
                v-model="selectedTagId"
                :options="tagOptions"
                placeholder="Add tag..."
                class="w-[200px]"
              />
              <BaseButton v-if="selectedTagId" variant="secondary" size="sm" @click="addTag">
                Add
              </BaseButton>
            </div>
          </div>

          <!-- Notes -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Notes</h2>
            <div v-if="notes.length === 0" class="text-sm text-gray-400 mb-3">No notes yet.</div>
            <div v-else class="space-y-3 mb-4">
              <div v-for="note in notes" :key="note.id" class="p-3 bg-gray-50 rounded-md">
                <p class="text-sm text-gray-800 whitespace-pre-wrap">
                  {{ note.body }}
                </p>
                <p class="text-xs text-gray-400 mt-1">
                  {{ formatDate(note.created_at) }}
                </p>
              </div>
            </div>
            <form v-if="tenantStore.canWrite" class="flex gap-2" @submit.prevent="addNote">
              <BaseInput
                id="new-note"
                v-model="newNote"
                v-bind="newNoteAttrs"
                placeholder="Add a note..."
                class="flex-1"
                :error="newNoteErrors.body"
              />
              <BaseButton type="submit" variant="primary" size="sm" :loading="addingNote">
                Add
              </BaseButton>
            </form>
          </div>

          <!-- Files -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-3">Files</h2>
            <div v-if="files.length === 0" class="text-sm text-gray-400 mb-3">
              No files attached.
            </div>
            <ul v-else class="space-y-2 mb-3">
              <li
                v-for="file in files"
                :key="file.id"
                class="flex items-center justify-between p-2 bg-gray-50 rounded-md"
              >
                <div class="flex items-center gap-2">
                  <svg
                    class="h-4 w-4 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                    />
                  </svg>
                  <span class="text-sm text-gray-800">{{ file.name }}</span>
                  <span class="text-xs text-gray-400">({{ formatFileSize(file.size) }})</span>
                </div>
                <div class="flex gap-2">
                  <BaseButton
                    variant="ghost"
                    size="sm"
                    class="text-xs text-indigo-600 hover:text-indigo-800"
                    @click="downloadFile(file.id)"
                  >
                    Download
                  </BaseButton>
                  <BaseButton
                    v-if="tenantStore.canWrite"
                    variant="ghost"
                    size="sm"
                    class="text-xs text-red-600 hover:text-red-800"
                    @click="deleteFile(file.id)"
                  >
                    Remove
                  </BaseButton>
                </div>
              </li>
            </ul>
            <div v-if="tenantStore.canWrite">
              <BaseButton
                as-child
                variant="ghost"
                class="px-3 py-2 border border-dashed border-gray-300 text-sm text-gray-600 hover:border-gray-400"
              >
                <label class="inline-flex items-center gap-2 cursor-pointer">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 4v16m8-8H4"
                    />
                  </svg>
                  Upload File
                  <input type="file" class="hidden" @change="uploadFile" />
                </label>
              </BaseButton>
            </div>
          </div>
        </div>

        <!-- Sidebar: Activity Timeline -->
        <div class="space-y-6">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Activity</h2>
            <div v-if="activities.length === 0" class="text-sm text-gray-400">No activity yet.</div>
            <div v-else class="relative">
              <div class="absolute left-3 top-0 bottom-0 w-px bg-gray-200" />
              <div
                v-for="activity in activities"
                :key="activity.id"
                class="relative pl-8 pb-4 last:pb-0"
              >
                <div
                  class="absolute left-1.5 top-1 h-3 w-3 rounded-full border-2 border-gray-300 bg-white"
                />
                <p class="text-sm text-gray-700">
                  {{ activity.description }}
                </p>
                <p class="text-xs text-gray-400 mt-0.5">
                  {{ formatRelative(activity.created_at) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Delete confirmation -->
    <div v-if="showDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/20">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Customer</h3>
        <p class="text-sm text-gray-600 mb-4">Are you sure? This action cannot be undone.</p>
        <div class="flex justify-end gap-2">
          <BaseButton variant="ghost" size="sm" @click="showDelete = false"> Cancel </BaseButton>
          <BaseButton variant="danger" size="sm" :loading="deleting" @click="deleteCustomer">
            Delete
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { customerApi, noteApi, fileApi, tagApi, activityApi } from '@/api';
import { useTenantStore } from '@/stores/tenant';
import { useNotificationStore } from '@/stores/notification';
import BaseButton from '@/components/ui/BaseButton.vue';
import BaseInput from '@/components/ui/BaseInput.vue';
import BaseSelect from '@/components/ui/BaseSelect.vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { createNoteSchema } from '@/api/schemas/notes';
import type { Customer, Tag, Note, Activity } from '@/api/types';
import type { File as AppFile } from '@/api/types';
import type { AxiosError } from 'axios';
import type { ApiError } from '@/api/types';
import { mapLaravelErrors } from '@/lib/laravelErrors';

const route = useRoute();
const router = useRouter();
const tenantStore = useTenantStore();
const notifications = useNotificationStore();

const customerId = Number(route.params.id);
const customer = ref<Customer | null>(null);
const isLoading = ref(true);
const tags = ref<Tag[]>([]);
const availableTags = ref<Tag[]>([]);
const selectedTagId = ref<number | string>('');

const tagOptions = computed(() => [
  { value: '', label: 'Add tag...' },
  ...availableTags.value.map((t) => ({ value: String(t.id), label: t.name })),
]);
const notes = ref<Note[]>([]);
const files = ref<AppFile[]>([]);
const activities = ref<Activity[]>([]);
const addingNote = ref(false);
const showDelete = ref(false);
const deleting = ref(false);

const {
  handleSubmit: handleAddNoteSubmit,
  defineField: defineNewNoteField,
  errors: newNoteErrors,
  setErrors: setNewNoteErrors,
  resetForm: resetNewNoteForm,
} = useForm({
  validationSchema: toTypedSchema(createNoteSchema),
  initialValues: {
    body: '',
  },
});

const [newNote, newNoteAttrs] = defineNewNoteField('body');

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function formatRelative(dateStr: string): string {
  const seconds = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
  if (seconds < 60) return 'just now';
  if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
  if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
  return `${Math.floor(seconds / 86400)}d ago`;
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / 1048576).toFixed(1)} MB`;
}

async function loadCustomer(): Promise<void> {
  try {
    const { data } = await customerApi.show(customerId);
    customer.value = data.data;
  } finally {
    isLoading.value = false;
  }
}

async function loadTags(): Promise<void> {
  try {
    const { data } = await tagApi.list();
    availableTags.value = data.data;
  } catch {
    /* silent */
  }
}

async function loadNotes(): Promise<void> {
  try {
    const { data } = await noteApi.list({
      noteable_type: 'App\\Models\\Customer',
      noteable_id: customerId,
    });
    notes.value = data.data;
  } catch {
    /* silent */
  }
}

async function loadFiles(): Promise<void> {
  try {
    const { data } = await fileApi.list({
      fileable_type: 'App\\Models\\Customer',
      fileable_id: customerId,
    });
    files.value = data.data;
  } catch {
    /* silent */
  }
}

async function loadActivity(): Promise<void> {
  try {
    const { data } = await activityApi.list({
      subject_type: 'App\\Models\\Customer',
      subject_id: customerId,
    });
    activities.value = data.data;
  } catch {
    /* silent */
  }
}

const addNote = handleAddNoteSubmit(async (values) => {
  setNewNoteErrors({});
  addingNote.value = true;
  try {
    const { data } = await noteApi.create({
      noteable_type: 'App\\Models\\Customer',
      noteable_id: customerId,
      body: values.body,
    });
    notes.value.push(data.data);
    resetNewNoteForm();
  } catch (err) {
    const axiosError = err as AxiosError<ApiError>;

    if (axiosError.response?.status === 422) {
      setNewNoteErrors(mapLaravelErrors(axiosError.response.data.errors));
      return;
    }

    notifications.error('Failed to add note.');
  } finally {
    addingNote.value = false;
  }
});

async function addTag(): Promise<void> {
  const tagId = Number(selectedTagId.value);
  if (!tagId) return;
  try {
    await tagApi.attach(tagId, {
      taggable_type: 'App\\Models\\Customer',
      taggable_id: customerId,
    });
    const tag = availableTags.value.find((t) => t.id === tagId);
    if (tag) tags.value.push(tag);
    selectedTagId.value = '';
  } catch {
    notifications.error('Failed to add tag.');
  }
}

async function removeTag(tagId: number): Promise<void> {
  try {
    await tagApi.detach(tagId, {
      taggable_type: 'App\\Models\\Customer',
      taggable_id: customerId,
    });
    tags.value = tags.value.filter((t) => t.id !== tagId);
  } catch {
    notifications.error('Failed to remove tag.');
  }
}

async function uploadFile(event: Event): Promise<void> {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('file', file);
  formData.append('fileable_type', 'App\\Models\\Customer');
  formData.append('fileable_id', String(customerId));

  try {
    const { data } = await fileApi.upload(formData);
    files.value.push(data.data);
    notifications.success('File uploaded.');
  } catch {
    notifications.error('Failed to upload file.');
  }
  target.value = '';
}

async function downloadFile(fileId: number): Promise<void> {
  try {
    const { data } = await fileApi.download(fileId);
    const url = URL.createObjectURL(data);
    const a = document.createElement('a');
    a.href = url;
    a.download = '';
    a.click();
    URL.revokeObjectURL(url);
  } catch {
    notifications.error('Failed to download file.');
  }
}

async function deleteFile(fileId: number): Promise<void> {
  try {
    await fileApi.delete(fileId);
    files.value = files.value.filter((f) => f.id !== fileId);
  } catch {
    notifications.error('Failed to remove file.');
  }
}

async function deleteCustomer(): Promise<void> {
  deleting.value = true;
  try {
    await customerApi.delete(customerId);
    notifications.success('Customer deleted.');
    router.push('/customers');
  } catch {
    notifications.error('Failed to delete customer.');
  } finally {
    deleting.value = false;
  }
}

onMounted(() => {
  loadCustomer();
  loadTags();
  loadNotes();
  loadFiles();
  loadActivity();
});
</script>
