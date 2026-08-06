<template>
  <div>
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">
          Campaigns
        </h1>
        <p class="mt-1 text-sm text-gray-500">
          Manage your promotional campaigns across all brands.
        </p>
      </div>

      <button
        type="button"
        @click="openCreateModal"
        class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
      >
        <svg
          class="-ml-1 mr-2 h-5 w-5"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
          />
        </svg>

        New Campaign
      </button>
    </div>

    <!-- Table -->
    <DataTable
      :columns="columns"
      :data="campaigns"
      :loading="loading"
      @search="handleSearch"
      @sort="handleSort"
    >
      <template #actions>
        <select
          v-model="statusFilter"
          @change="handleFilter"
          class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-sm focus:border-blue-500 focus:ring-blue-500"
        >
          <option value="">All Statuses</option>
          <option value="Draft">Draft</option>
          <option value="Waiting Approval">Waiting Approval</option>
          <option value="Approved">Approved</option>
          <option value="Running">Running</option>
          <option value="Finished">Finished</option>
          <option value="Cancelled">Cancelled</option>
        </select>
      </template>

      <template #cell-name="{ row }">
        <div class="font-medium text-gray-900">
          {{ row.name }}
        </div>
      </template>

      <template #cell-status="{ row }">
        <StatusBadge :status="row.status" />
      </template>

      <template #cell-actions="{ row }">
        <div class="flex space-x-3 text-sm">
          <router-link
            :to="`/campaigns/${row.id}`"
            class="font-medium text-blue-600 hover:text-blue-800"
          >
            View
          </router-link>

          <button
            type="button"
            @click="openEditModal(row)"
            class="font-medium text-gray-600 hover:text-gray-900"
          >
            Edit
          </button>
        </div>
      </template>

      <template #pagination>
        <div class="flex flex-1 justify-between sm:hidden">
          <button
            @click="prevPage"
            :disabled="pagination.current_page === 1"
            class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
          >
            Previous
          </button>

          <button
            @click="nextPage"
            :disabled="pagination.current_page === pagination.last_page"
            class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
          >
            Next
          </button>
        </div>

        <div
          class="hidden flex-1 items-center justify-between sm:flex"
        >
          <div>
            <p class="text-sm text-gray-700">
              Showing page
              <span class="font-medium">
                {{ pagination.current_page }}
              </span>
              of
              <span class="font-medium">
                {{ pagination.last_page }}
              </span>
              ({{ pagination.total }} total results)
            </p>
          </div>

          <nav
            class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm"
          >
            <button
              @click="prevPage"
              :disabled="pagination.current_page === 1"
              class="relative inline-flex items-center rounded-l-md border border-gray-300 bg-white px-2 py-2 text-gray-500 hover:bg-gray-50 disabled:opacity-50"
            >
              ←
            </button>

            <button
              @click="nextPage"
              :disabled="pagination.current_page === pagination.last_page"
              class="relative inline-flex items-center rounded-r-md border border-gray-300 bg-white px-2 py-2 text-gray-500 hover:bg-gray-50 disabled:opacity-50"
            >
              →
            </button>
          </nav>
        </div>
      </template>
    </DataTable>

    <!-- Modal -->
    <CampaignForm
      :key="selectedCampaign ? selectedCampaign.id : 'create'"
      :is-open="isModalOpen"
      :campaign="selectedCampaign"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import StatusBadge from '../components/StatusBadge.vue';
import CampaignForm from '../components/CampaignForm.vue';
import { useCampaigns } from '../composables/useCampaigns';

const {
  campaigns,
  loading,
  pagination,
  fetchCampaigns
} = useCampaigns();

const columns = [
  {
    key: 'name',
    label: 'Campaign Name',
    sortable: true
  },
  {
    key: 'start_date',
    label: 'Start Date',
    sortable: true
  },
  {
    key: 'deadline',
    label: 'Deadline',
    sortable: true
  },
  {
    key: 'status',
    label: 'Status',
    sortable: true
  },
  {
    key: 'actions',
    label: '',
    sortable: false
  }
];

const searchQuery = ref('');
const statusFilter = ref('');

const isModalOpen = ref(false);
const selectedCampaign = ref(null);

onMounted(() => {
  fetchData();
});

const fetchData = async () => {
  await fetchCampaigns({
    page: pagination.value.current_page || 1,
    search: searchQuery.value,
    status: statusFilter.value
  });
};

const handleSearch = (value) => {
  searchQuery.value = value;
  pagination.value.current_page = 1;
  fetchData();
};

const handleSort = () => {
  fetchData();
};

const handleFilter = () => {
  pagination.value.current_page = 1;
  fetchData();
};

const prevPage = () => {
  if (pagination.value.current_page <= 1) return;

  pagination.value.current_page--;
  fetchData();
};

const nextPage = () => {
  if (pagination.value.current_page >= pagination.value.last_page) return;

  pagination.value.current_page++;
  fetchData();
};

const openCreateModal = () => {
  selectedCampaign.value = null;
  isModalOpen.value = true;
};

const openEditModal = (campaign) => {
  selectedCampaign.value = { ...campaign };
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedCampaign.value = null;
};

const handleSaved = () => {
  closeModal();
  fetchData();
};
</script>