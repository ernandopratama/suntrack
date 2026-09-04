<template>
  <div class="space-y-6">

    <!-- Page Header -->
    <div
      class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
      <div>
        <!-- Section Label -->
        <div class="flex items-center gap-2">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]"
          >
            <svg
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M9 14.25l2.25 2.25L15 12.75M12 3.75l7.5 3.75v5.625c0 4.313-3.167 7.896-7.5 8.625-4.333-.729-7.5-4.312-7.5-8.625V7.5L12 3.75z"
              />
            </svg>
          </div>

          <span
            class="rounded-full bg-[#D0E7E6] px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-[#293681]"
          >
            Promotion Management
          </span>
        </div>

        <!-- Title -->
        <h1
          class="mt-3 text-2xl font-extrabold tracking-tight text-[#293681] sm:text-3xl"
        >
          Promotions
        </h1>

        <p class="mt-1 text-sm text-gray-500">
          Manage all promotions across campaigns and brands.
        </p>
      </div>

      <!-- Create Promotion -->
      <button
        v-if="$can('promotion.create')"
        @click="openCreateModal"
        type="button"
        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#4274D9] px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#293681] hover:shadow-md focus:outline-none focus:ring-4 focus:ring-[#95CCDD]/40 sm:w-auto"
      >
        <span
          class="flex h-5 w-5 items-center justify-center rounded-md bg-white/15"
        >
          <svg
            class="h-3 w-3"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 6v12m6-6H6"
            />
          </svg>
        </span>

        <span>New Promotion</span>
      </button>
    </div>

    <!-- DataTable -->
    <DataTable
      :columns="columns"
      :data="promotions"
      :loading="loading"
      @search="handleSearch"
    >

      <!-- Toolbar Actions -->
      <template #actions>
        <div class="flex items-center gap-2">

          <!-- Status Label -->
          <span
            class="hidden text-xs font-bold text-gray-400 sm:inline"
          >
            Status
          </span>

          <!-- Status Filter -->
          <div class="relative">
            <select
              v-model="filters.status"
              @change="fetchData"
              class="block min-w-[190px] appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-3.5 pr-10 text-xs font-semibold text-gray-700 shadow-sm outline-none transition-all hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/20"
            >
              <option value="">All Statuses</option>
              <option value="Pending">Pending</option>
              <option value="Partially Approved">
                Partially Approved
              </option>
              <option value="Approved">Approved</option>
              <option value="Rejected">Rejected</option>
            </select>

            <!-- Select Icon -->
            <div
              class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400"
            >
              <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 9l-7 7-7-7"
                />
              </svg>
            </div>
          </div>
        </div>
      </template>

      <!-- Code -->
      <template #cell-code="{ row }">
        <span
          class="inline-flex items-center rounded-lg bg-[#D0E7E6] px-2.5 py-1 font-mono text-[11px] font-bold tracking-wide text-[#293681]"
        >
          {{ row.code }}
        </span>
      </template>

      <!-- Name / Campaign -->
      <template #cell-name="{ row }">
        <div class="min-w-[200px]">
          <div class="font-bold text-gray-900">
            {{ row.name }}
          </div>

          <div
            class="mt-0.5 flex items-center gap-1 text-[11px] text-gray-400"
          >
            <svg
              class="h-3 w-3"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M3 7.5A2.5 2.5 0 015.5 5h13A2.5 2.5 0 0121 7.5v9a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 013 16.5v-9z"
              />
            </svg>

            <span>
              {{ row.campaign?.name || 'Standalone' }}
            </span>
          </div>
        </div>
      </template>

      <!-- Start Date -->
      <template #cell-start_date="{ row }">
        <div class="flex items-center gap-2 text-sm">
          <div
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#D0E7E6] text-[#4274D9]"
          >
            <svg
              class="h-3.5 w-3.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-width="1.8"
                d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"
              />
            </svg>
          </div>

          <span class="font-medium text-gray-600">
            {{ row.start_date || '—' }}
          </span>
        </div>
      </template>

      <!-- End Date -->
      <template #cell-end_date="{ row }">
        <div class="flex items-center gap-2 text-sm">
          <div
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#95CCDD]/20 text-[#4274D9]"
          >
            <svg
              class="h-3.5 w-3.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-width="1.8"
                d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>

          <span class="font-medium text-gray-600">
            {{ row.end_date || '—' }}
          </span>
        </div>
      </template>

      <!-- Status -->
      <template #cell-status="{ row }">
        <span
          class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
          :class="{
            'bg-gray-100 text-gray-600':
              row.status === 'Pending',

            'bg-amber-100 text-amber-700':
              row.status === 'Partially Approved',

            'bg-[#D0E7E6] text-[#293681]':
              row.status === 'Approved',

            'bg-rose-100 text-rose-700':
              row.status === 'Rejected'
          }"
        >
          <span
            class="h-1.5 w-1.5 rounded-full bg-current"
          ></span>

          {{ row.status }}
        </span>
      </template>

      <!-- Actions -->
      <template #cell-actions="{ row }">
        <div class="flex items-center justify-end gap-2">

          <!-- View -->
          <router-link
            :to="`/promotions/${row.id}`"
            class="inline-flex items-center gap-1.5 rounded-lg border border-[#95CCDD]/50 bg-[#D0E7E6]/40 px-3 py-2 text-xs font-bold text-[#293681] transition-all duration-200 hover:border-[#95CCDD] hover:bg-[#D0E7E6] hover:text-[#293681]"
            title="View Promotion"
          >
            <svg
              class="h-4 w-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
              />

              <circle
                cx="12"
                cy="12"
                r="3"
                stroke-width="1.8"
              />
            </svg>

            <span>View</span>
          </router-link>

          <!-- Edit -->
          <button
            v-if="$can('promotion.update')"
            @click="openEditModal(row)"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600 shadow-sm transition-all duration-200 hover:border-[#95CCDD] hover:bg-[#D0E7E6]/30 hover:text-[#293681]"
            title="Edit Promotion"
          >
            <svg
              class="h-4 w-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"
              />
            </svg>

            <span>Edit</span>
          </button>
        </div>
      </template>

      <!-- Pagination -->
      <template #pagination>
        <div
          class="hidden flex-1 items-center justify-between sm:flex"
        >
          <!-- Pagination Information -->
          <p class="text-xs text-gray-500">
            Page
            <span class="font-bold text-[#293681]">
              {{ pagination.current_page }}
            </span>

            of

            <span class="font-bold text-[#293681]">
              {{ pagination.last_page }}
            </span>

            <span class="mx-1 text-gray-300">•</span>

            <span class="font-semibold text-gray-700">
              {{ pagination.total }}
            </span>

            results
          </p>

          <!-- Pagination Buttons -->
          <nav
            class="flex items-center gap-1"
            aria-label="Pagination"
          >
            <!-- Previous -->
            <button
              @click="changePage(-1)"
              :disabled="pagination.current_page === 1"
              class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-bold text-gray-500 shadow-sm transition-all hover:border-[#95CCDD] hover:bg-[#D0E7E6]/30 hover:text-[#293681] disabled:cursor-not-allowed disabled:opacity-40"
            >
              <svg
                class="mr-1.5 h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 19l-7-7 7-7"
                />
              </svg>

              Previous
            </button>

            <!-- Current Page -->
            <div
              class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-[#4274D9] px-3 text-xs font-bold text-white shadow-sm"
            >
              {{ pagination.current_page }}
            </div>

            <!-- Next -->
            <button
              @click="changePage(1)"
              :disabled="
                pagination.current_page === pagination.last_page
              "
              class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-xs font-bold text-gray-500 shadow-sm transition-all hover:border-[#95CCDD] hover:bg-[#D0E7E6]/30 hover:text-[#293681] disabled:cursor-not-allowed disabled:opacity-40"
            >
              Next

              <svg
                class="ml-1.5 h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>
          </nav>
        </div>
      </template>
    </DataTable>

    <!-- Promotion Form Modal -->
    <PromotionForm
      :is-open="isModalOpen"
      :promotion="selectedPromotion"
      @close="closeModal"
      @saved="fetchData"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import PromotionForm from '../components/PromotionForm.vue';
import { usePromotions } from '../composables/usePromotions';

const {
  promotions,
  loading,
  pagination,
  fetchPromotions,
  deletePromotion
} = usePromotions();

const columns = [
  {
    key: 'code',
    label: 'Code',
    sortable: false
  },
  {
    key: 'name',
    label: 'Name / Campaign',
    sortable: true
  },
  {
    key: 'start_date',
    label: 'Start Date',
    sortable: true
  },
  {
    key: 'end_date',
    label: 'End Date',
    sortable: true
  },
  {
    key: 'status',
    label: 'Status',
    sortable: true
  },
  {
    key: 'actions',
    label: 'Actions',
    sortable: false
  },
];

const filters = ref({
  search: '',
  status: ''
});

const isModalOpen = ref(false);
const selectedPromotion = ref(null);

onMounted(() => {
  fetchData();
});

const fetchData = () => {
  fetchPromotions({
    page: pagination.value.current_page,
    search: filters.value.search,
    status: filters.value.status,
  });
};

const handleSearch = (q) => {
  filters.value.search = q;
  pagination.value.current_page = 1;
  fetchData();
};

const changePage = (dir) => {
  pagination.value.current_page += dir;
  fetchData();
};

const confirmDelete = async (row) => {
  if (confirm(`Delete promotion "${row.name}"?`)) {
    await deletePromotion(row.id);
    fetchData();
  }
};

const openCreateModal = () => {
  selectedPromotion.value = null;
  isModalOpen.value = true;
};

const openEditModal = (row) => {
  selectedPromotion.value = row;
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};
</script>
