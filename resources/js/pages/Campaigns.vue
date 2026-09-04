<template>
  <div class="space-y-6">

    <!-- Page Header -->
    <div
      class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >
      <div>
        <div class="flex items-center gap-2">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
          >
            <svg
              class="h-5 w-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622C17.176 19.29 21 14.591 21 9c0-1.017-.127-2.004-.364-2.951"
              />
            </svg>
          </div>

          <span
            class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-blue-600"
          >
            Campaign Management
          </span>
        </div>

        <div class="mt-3 flex items-center gap-2">
          <h1
            class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl"
          >
            Campaigns
          </h1>

          <!-- Total Campaign -->
          <span
            class="inline-flex min-w-[28px] items-center justify-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-600"
          >
            {{ pagination.total || 0 }}
          </span>
        </div>

        <p class="mt-1 text-sm text-gray-500">
          Manage your promotional campaigns across all brands.
        </p>
      </div>

      <!-- Create Campaign -->
      <button
        v-if="$can('campaign.create')"
        @click="openCreateModal"
        type="button"
        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow-sm hover:shadow-md transition-all duration-200"
      >
        <span
          class="w-5 h-5 rounded-md bg-white/15 flex items-center justify-center"
        >
          <i class="fa-solid fa-plus text-[9px]"></i>
        </span>

        <span>New Campaign</span>
      </button>
    </div>

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :data="campaigns"
      :loading="loading"
      @search="handleSearch"
      @sort="handleSort"
    >

      <!-- Status Filter -->
      <template #actions>
        <div class="flex items-center gap-2">

          <span
            class="hidden text-xs font-semibold text-gray-400 sm:inline"
          >
            Status
          </span>

          <div class="relative">
            <select
              v-model="statusFilter"
              @change="handleFilter"
              class="block min-w-[180px] appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-3.5 pr-10 text-xs font-semibold text-gray-700 shadow-sm outline-none transition-all hover:border-gray-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
            >
              <option value="">All Statuses</option>
              <option value="Draft">Draft</option>
              <option value="Waiting Approval">
                Waiting Approval
              </option>
              <option value="Approved">Approved</option>
              <option value="Running">Running</option>
              <option value="Finished">Finished</option>
              <option value="Cancelled">Cancelled</option>
            </select>

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

      <!-- Campaign Name -->
      <template #cell-name="{ row }">
        <div class="min-w-[220px]">
          <div class="flex items-center gap-3">

            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
            >
              <svg
                class="h-4.5 w-4.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-width="1.8"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a3 3 0 006 0M9 5h6"
                />
              </svg>
            </div>

            <div>
              <div class="font-bold text-gray-900">
                {{ row.name }}
              </div>

              <div class="mt-0.5 text-[11px] text-gray-400">
                Promotional Campaign
              </div>
            </div>

          </div>
        </div>
      </template>

      <!-- Start Date -->
      <template #cell-start_date="{ row }">
        <div class="flex items-center gap-2 text-sm">

          <svg
            class="h-4 w-4 shrink-0 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-width="1.8"
              stroke-linecap="round"
              d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"
            />
          </svg>

          <span class="font-medium text-gray-600">
            {{ row.start_date }}
          </span>

        </div>
      </template>

      <!-- Deadline -->
      <template #cell-deadline="{ row }">
        <div class="flex items-center gap-2 text-sm">

          <svg
            class="h-4 w-4 shrink-0 text-amber-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-width="1.8"
              stroke-linecap="round"
              d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>

          <span class="font-semibold text-gray-700">
            {{ row.deadline }}
          </span>

        </div>
      </template>

      <!-- Status -->
      <template #cell-status="{ row }">
        <StatusBadge :status="row.status" />
      </template>

      <!-- Actions -->
      <template #cell-actions="{ row }">
        <div class="flex items-center justify-end gap-2">

          <!-- View -->
          <router-link
            :to="`/campaigns/${row.id}`"
            class="inline-flex items-center gap-1.5 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-600 transition-all duration-200 hover:border-blue-200 hover:bg-blue-100 hover:text-blue-700"
            title="View Campaign"
          >
            <svg
              class="h-4 w-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
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
            v-if="$can('campaign.update')"
            type="button"
            @click="openEditModal(row)"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600 shadow-sm transition-all duration-200 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
            title="Edit Campaign"
          >
            <svg
              class="h-4 w-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"
              />
            </svg>

            <span>Edit</span>
          </button>

        </div>
      </template>

      <!-- Pagination -->
      <template #pagination>

        <!-- Mobile -->
        <div
          class="flex flex-1 items-center justify-between gap-3 sm:hidden"
        >
          <button
            @click="prevPage"
            :disabled="pagination.current_page === 1"
            class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
          >
            <svg
              class="mr-1.5 h-4 w-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15 19l-7-7 7-7"
              />
            </svg>

            Previous
          </button>

          <span
            class="rounded-lg bg-gray-50 px-3 py-2 text-xs font-bold text-gray-500"
          >
            {{ pagination.current_page }} /
            {{ pagination.last_page }}
          </span>

          <button
            @click="nextPage"
            :disabled="
              pagination.current_page === pagination.last_page
            "
            class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 shadow-sm transition-all hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
          >
            Next

            <svg
              class="ml-1.5 h-4 w-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 5l7 7-7 7"
              />
            </svg>
          </button>
        </div>

        <!-- Desktop -->
        <div
          class="hidden flex-1 items-center justify-between sm:flex"
        >
          <div>
            <p class="text-xs text-gray-500">
              Showing
              <span class="font-bold text-gray-700">
                page {{ pagination.current_page }}
              </span>

              of

              <span class="font-bold text-gray-700">
                {{ pagination.last_page }}
              </span>

              <span class="mx-1 text-gray-300">•</span>

              <span class="font-semibold text-gray-700">
                {{ pagination.total }}
              </span>

              total results
            </p>
          </div>

          <nav
            class="flex items-center gap-1"
            aria-label="Pagination"
          >
            <button
              @click="prevPage"
              :disabled="pagination.current_page === 1"
              class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition-all hover:bg-gray-50 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-40"
            >
              <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M15 19l-7-7 7-7"
                />
              </svg>
            </button>

            <div
              class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-blue-50 px-3 text-xs font-bold text-blue-600"
            >
              {{ pagination.current_page }}
            </div>

            <button
              @click="nextPage"
              :disabled="
                pagination.current_page === pagination.last_page
              "
              class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition-all hover:bg-gray-50 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-40"
            >
              <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>
          </nav>
        </div>

      </template>
    </DataTable>

    <!-- Campaign Form Modal -->
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
    label: 'Actions',
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
