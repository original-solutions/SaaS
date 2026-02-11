<template>
  <div class="bg-card rounded-lg border border-border overflow-hidden">
    <table class="min-w-full divide-y divide-border">
      <thead class="bg-muted/40">
        <tr>
          <th
            v-for="col in columns"
            :key="col.key"
            :class="[
              'px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider select-none',
              col.sortable ? 'cursor-pointer' : '',
              col.headerClass,
            ]"
            @click="col.sortable ? $emit('sort', col.key) : undefined"
          >
            <span class="inline-flex items-center gap-1">
              {{ col.label ?? '' }}
              <span
                v-if="col.sortable && sortKey === col.key"
                class="text-muted-foreground/70"
              >
                {{ sortDir === 'asc' ? '↑' : '↓' }}
              </span>
            </span>
          </th>
        </tr>
      </thead>

      <tbody class="divide-y divide-border">
        <tr v-if="loading">
          <td
            :colspan="columns.length"
            class="px-6 py-8 text-center text-sm text-muted-foreground"
          >
            Loading...
          </td>
        </tr>
        <tr v-else-if="rows.length === 0">
          <td
            :colspan="columns.length"
            class="px-6 py-8 text-center text-sm text-muted-foreground"
          >
            {{ emptyText }}
          </td>
        </tr>
        <tr
          v-for="row in rows"
          v-else
          :key="getRowKey(row)"
          class="hover:bg-muted/30"
        >
          <td
            v-for="col in columns"
            :key="col.key"
            :class="['px-6 py-4 text-sm text-foreground', col.class]"
          >
            <slot
              :name="`cell-${col.key}`"
              :row="row"
              :value="row[col.key]"
            >
              {{ row[col.key] ?? '—' }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
export type DataTableColumn = {
    key: string;
    label?: string;
    sortable?: boolean;
    class?: string;
    headerClass?: string;
};

const props = withDefaults(
    defineProps<{
        rows: Array<Record<string, any>>;
        columns: DataTableColumn[];
        loading?: boolean;
        emptyText?: string;
        sortKey?: string;
        sortDir?: 'asc' | 'desc';
        rowKey?: string | ((row: Record<string, any>) => string | number);
    }>(),
    {
        loading: false,
        emptyText: 'No results found.',
        sortKey: undefined,
        sortDir: undefined,
        rowKey: 'id',
    },
);

defineEmits<{
    sort: [key: string];
}>();

function getRowKey(row: Record<string, any>): string | number {
    if (typeof props.rowKey === 'function') {
        return props.rowKey(row);
    }

    const key = props.rowKey;

    if (typeof row[key] === 'string' || typeof row[key] === 'number') {
        return row[key];
    }

    return JSON.stringify(row);
}
</script>
