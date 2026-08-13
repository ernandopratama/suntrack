<template>
  <div class="w-full">
    <!-- Main Table Container -->
    <div
      class="w-full overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
      <!-- Toolbar -->
      <div
        v-if="withToolbar"
        class="border-b border-gray-100 bg-white px-5 py-5 sm:px-6"
      >
        <div class="flex w-full flex-col gap-4">
          <!-- Search -->
          <div v-if="searchable" class="w-full">
            <div class="group relative w-full">
              <!-- Search Icon -->
              <div
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4"
              >
                <svg
                  class="h-5 w-5 text-gray-400 transition-colors group-focus-within:text-blue-500"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
                  />
                </svg>
              </div>

              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search data in this table..."
                @input="$emit('search', searchQuery)"
                class="block w-full rounded-xl border border-gray-200 bg-gray-50 py-3 pl-11 pr-20 text-sm text-gray-900 outline-none transition-all duration-200 placeholder:text-gray-400 hover:border-gray-300 hover:bg-white focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10"
              />

              <!-- ESC Badge -->
              <div
                class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
              >
                <span
                  class="hidden rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-gray-400 shadow-sm sm:inline-flex"
                >
                  ESC
                </span>
              </div>
            </div>

            <!-- Search Helper -->
            <div class="mt-2 flex items-center gap-2 px-1">
              <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
              <span class="text-xs font-medium text-gray-400">
                Search data in this table
              </span>
            </div>
          </div>

          <!-- Actions -->
          <div
            v-if="$slots.actions"
            class="flex w-full items-center justify-end"
          >
            <slot name="actions"></slot>
          </div>
        </div>
      </div>

      <!-- Scrollable Table Container -->
      <div class="w-full overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
          <thead class="bg-gray-50/80">
            <tr>
              <th
                v-for="col in columns"
                :key="col.key"
                scope="col"
                class="whitespace-nowrap px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-gray-500"
                :class="{
                  'cursor-pointer select-none hover:bg-gray-100':
                    col.sortable
                }"
                @click="col.sortable ? sortBy(col.key) : null"
              >
                <div class="flex items-center gap-2">
                  <slot :name="`head-${col.key}`">
                    {{ col.label }}
                  </slot>

                  <span
                    v-if="col.sortable"
                    class="inline-flex items-center"
                  >
                    <svg
                      v-if="sortKey !== col.key"
                      class="h-4 w-4 text-gray-300"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="m8 9 4-4 4 4m0 6-4 4-4-4"
                      />
                    </svg>

                    <svg
                      v-else-if="sortOrder === 'asc'"
                      class="h-4 w-4 text-blue-500"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="m5 15 7-7 7 7"
                      />
                    </svg>

                    <svg
                      v-else
                      class="h-4 w-4 text-blue-500"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="m19 9-7 7-7-7"
                      />
                    </svg>
                  </span>
                </div>
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100 bg-white">
            <!-- Loading -->
            <tr v-if="loading">
              <td
                :colspan="columns.length"
                class="px-6 py-12 text-center"
              >
                <div class="flex flex-col items-center justify-center">
                  <svg
                    class="h-7 w-7 animate-spin text-blue-500"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle
                      class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"
                    />
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                    />
                  </svg>

                  <span class="mt-3 text-sm font-medium text-gray-400">
                    Loading data...
                  </span>
                </div>
              </td>
            </tr>

            <!-- Empty -->
            <tr v-else-if="!data.length">
              <td
                :colspan="columns.length"
                class="px-6 py-14 text-center"
              >
                <div class="flex flex-col items-center justify-center">
                  <div
                    class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100"
                  >
                    <svg
                      class="h-6 w-6 text-gray-400"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="M20 13V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7m16 0v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5m16 0H4"
                      />
                    </svg>
                  </div>

                  <p class="mt-3 text-sm font-semibold text-gray-700">
                    No data found
                  </p>

                  <p class="mt-1 text-xs text-gray-400">
                    Try changing your search or filter.
                  </p>
                </div>
              </td>
            </tr>

            <!-- Data -->
            <tr
              v-else
              v-for="(row, idx) in data"
              :key="idx"
              class="group transition-colors duration-150 hover:bg-blue-50/30"
            >
              <td
                v-for="col in columns"
                :key="col.key"
                class="whitespace-nowrap px-6 py-4 text-sm text-gray-700"
              >
                <slot
                  :name="`cell-${col.key}`"
                  :row="row"
                  :idx="idx"
                >
                  {{ row[col.key] }}
                </slot>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Horizontal Scroll Indicator -->
      <div
        v-if="data.length"
        class="border-t border-gray-100 bg-gray-50/50 px-4 py-1.5 text-center text-[10px] font-medium uppercase tracking-wider text-gray-400"
      >
        Scroll horizontally to view more
      </div>

      <!-- Pagination -->
      <div
        v-if="pagination"
        class="border-t border-gray-100 bg-white px-5 py-4 sm:px-6"
      >
        <slot name="pagination"></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },

  data: {
    type: Array,
    required: true,
  },

  loading: {
    type: Boolean,
    default: false,
  },

  searchable: {
    type: Boolean,
    default: true,
  },

  withToolbar: {
    type: Boolean,
    default: true,
  },

  pagination: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['search', 'sort']);

const searchQuery = ref('');
const sortKey = ref('');
const sortOrder = ref('asc');

const sortBy = (key) => {
  if (sortKey.value === key) {
    sortOrder.value =
      sortOrder.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortKey.value = key;
    sortOrder.value = 'asc';
  }

  emit('sort', {
    key: sortKey.value,
    order: sortOrder.value,
  });
};
</script>