<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Notes</h1>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <div v-if="isLoading" class="p-6 text-center text-sm text-gray-500">Loading...</div>

      <div v-else-if="notes.length === 0" class="p-6 text-center text-sm text-gray-500">
        No notes yet. Notes will appear here when you add them to customers or other records.
      </div>

      <ul v-else class="divide-y divide-gray-200">
        <li v-for="note in notes" :key="note.id" class="p-4">
          <p class="text-sm text-gray-800 whitespace-pre-wrap">
            {{ note.body }}
          </p>
          <div class="mt-2 flex items-center gap-3 text-xs text-gray-400">
            <span>{{ formatDate(note.created_at) }}</span>
            <span class="text-gray-300">|</span>
            <span>{{ formatNoteableType(note.noteable_type) }} #{{ note.noteable_id }}</span>
          </div>
        </li>
      </ul>

      <!-- Pagination -->
      <div
        v-if="pagination.lastPage > 1"
        class="px-4 py-3 border-t border-gray-200 flex items-center justify-between"
      >
        <span class="text-xs text-gray-500"
          >Page {{ pagination.currentPage }} of {{ pagination.lastPage }}</span
        >
        <div class="flex gap-2">
          <BaseButton
            :disabled="pagination.currentPage <= 1"
            variant="secondary"
            size="sm"
            class="px-3 py-1 text-sm border rounded-md disabled:opacity-50"
            @click="loadNotes(pagination.currentPage - 1)"
          >
            Previous
          </BaseButton>
          <BaseButton
            :disabled="pagination.currentPage >= pagination.lastPage"
            variant="secondary"
            size="sm"
            class="px-3 py-1 text-sm border rounded-md disabled:opacity-50"
            @click="loadNotes(pagination.currentPage + 1)"
          >
            Next
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { noteApi } from '@/api';
import BaseButton from '@/components/ui/BaseButton.vue';
import type { Note } from '@/api/types';

const notes = ref<Note[]>([]);
const isLoading = ref(true);
const pagination = reactive({ currentPage: 1, lastPage: 1 });

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

function formatNoteableType(type: string): string {
  return type.split('\\').pop() ?? type;
}

async function loadNotes(page = 1): Promise<void> {
  isLoading.value = true;
  try {
    const { data } = await noteApi.list({ page });
    notes.value = data.data;
    pagination.currentPage = data.current_page;
    pagination.lastPage = data.last_page;
  } finally {
    isLoading.value = false;
  }
}

onMounted(() => loadNotes());
</script>
