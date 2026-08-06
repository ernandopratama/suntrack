<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Promotions</h1>
        <p class="mt-1 text-sm text-gray-500">Manage all promotions across campaigns and brands.</p>
      </div>
      <button @click="openCreateModal"
        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none transition-colors">
        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        New Promotion
      </button>
    </div>

    <!-- DataTable -->
    <DataTable
      :columns="columns"
      :data="promotions"
      :loading="loading"
      @search="handleSearch"
    >
      <template #actions>
        <div class="flex items-center space-x-3">
          <!-- Status Filter -->
          <select v-model="filters.status" @change="fetchData"
            class="block pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
            <option value="">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Partially Approved">Partially Approved</option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
          </select>
        </div>
      </template>

      <!-- Custom Cells -->
      <template #cell-code="{ row }">
        <span class="font-mono text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-1 rounded">{{ row.code }}</span>
      </template>
      <template #cell-name="{ row }">
        <div class="font-medium text-gray-900">{{ row.name }}</div>
        <div class="text-xs text-gray-400">{{ row.campaign?.name || 'Standalone' }}</div>
      </template>
      <template #cell-status="{ row }">
        <StatusBadge :status="row.status" />
      </template>
      <template #cell-actions="{ row }">
        <div class="flex space-x-3 text-sm">
          <router-link :to="`/promotions/${row.id}`" class="text-blue-600 hover:text-blue-900 font-medium transition-colors">View</router-link>
          <button @click="openEditModal(row)" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">Edit</button>
          <!-- <button @click="confirmDelete(row)" class="text-red-600 hover:text-red-900 font-medium transition-colors">Delete</button> -->
        </div>
      </template>

      <!-- Pagination -->
      <template #pagination>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <p class="text-sm text-gray-700">
            Page <span class="font-medium">{{ pagination.current_page }}</span> of
            <span class="font-medium">{{ pagination.last_page }}</span>
            ({{ pagination.total }} results)
          </p>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <button @click="changePage(-1)" :disabled="pagination.current_page === 1"
              class="relative inline-flex items-center px-3 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-40">
              &larr; Prev
            </button>
            <button @click="changePage(1)" :disabled="pagination.current_page === pagination.last_page"
              class="relative inline-flex items-center px-3 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-40">
              Next &rarr;
            </button>
          </nav>
        </div>
      </template>
    </DataTable>

    <!-- Promotion Form Modal -->
    <PromotionForm :is-open="isModalOpen" :promotion="selectedPromotion" @close="closeModal" @saved="fetchData" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import StatusBadge from '../components/StatusBadge.vue';
import PromotionForm from '../components/PromotionForm.vue';
import { usePromotions } from '../composables/usePromotions';

const { promotions, loading, pagination, fetchPromotions, deletePromotion } = usePromotions();

const columns = [
  { key: 'code', label: 'Code', sortable: false },
  { key: 'name', label: 'Name / Campaign', sortable: true },
  { key: 'start_date', label: 'Start Date', sortable: true },
  { key: 'end_date', label: 'End Date', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'actions', label: '', sortable: false },
];

const filters = ref({ search: '', status: '' });
const isModalOpen = ref(false);
const selectedPromotion = ref(null);

onMounted(() => fetchData());

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

const openCreateModal = () => { selectedPromotion.value = null; isModalOpen.value = true; };

const openEditModal = (row) => { selectedPromotion.value = row; isModalOpen.value = true; };
const closeModal = () => { isModalOpen.value = false; };
</script>
