<template>
  <div class="flex flex-col">
    <div class="shadow border-b border-gray-200 sm:rounded-lg">

      <!-- Toolbar (fixed, tidak ikut scroll) -->
      <div v-if="withToolbar" class="bg-white px-4 py-3 flex items-center justify-between border-b border-gray-200 sm:px-6">
        <div class="flex-1 min-w-0">
          <input v-if="searchable" type="text" v-model="searchQuery" @input="$emit('search', searchQuery)" placeholder="Search..." class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md">
        </div>
        <div class="ml-4 flex-shrink-0 flex items-center space-x-3">
          <slot name="actions"></slot>
        </div>
      </div>

      <!-- Scrollable Table Container -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th v-for="col in columns" :key="col.key" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" :class="{'cursor-pointer hover:bg-gray-100': col.sortable}" @click="col.sortable ? sortBy(col.key) : null">
                <slot :name="`head-${col.key}`">
                  {{ col.label }}
                </slot>
                <span v-if="col.sortable && sortKey === col.key">
                  {{ sortOrder === 'asc' ? '↑' : '↓' }}
                </span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="loading" class="animate-pulse">
              <td :colspan="columns.length" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Loading...</td>
            </tr>
            <tr v-else-if="!data.length">
              <td :colspan="columns.length" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No data found.</td>
            </tr>
            <tr v-else v-for="(row, idx) in data" :key="idx" class="hover:bg-gray-50 transition-colors">
              <td v-for="col in columns" :key="col.key" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <slot :name="`cell-${col.key}`" :row="row" :idx="idx">
                  {{ row[col.key] }}
                </slot>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <slot name="pagination"></slot>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  columns: { type: Array, required: true },
  data: { type: Array, required: true },
  loading: { type: Boolean, default: false },
  searchable: { type: Boolean, default: true },
  withToolbar: { type: Boolean, default: true },
  pagination: { type: Boolean, default: true },
});

const emit = defineEmits(['search', 'sort']);

const searchQuery = ref('');
const sortKey = ref('');
const sortOrder = ref('asc');

const sortBy = (key) => {
  if (sortKey.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortOrder.value = 'asc';
  }
  emit('sort', { key: sortKey.value, order: sortOrder.value });
};
</script>