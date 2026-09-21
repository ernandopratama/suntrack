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
            Manajemen Campaign
          </span>
        </div>

        <div class="mt-3 flex items-center gap-2">
          <h1
            class="text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl"
          >
            Campaign
          </h1>

          <!-- Total Campaign -->
          <span
            class="inline-flex min-w-[28px] items-center justify-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-600"
          >
            {{ pagination.total || 0 }}
          </span>
        </div>

        <p class="mt-1 text-sm text-gray-500">
          Kelola kampanye promosi untuk seluruh brand.
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

        <span>Buat Kampanye</span>
      </button>
    </div>

    <!-- Data Table -->
    <DataTable
      :columns="columns"
      :data="campaigns"
      :loading="loading"
      :labels="tableLabels"
      @search="handleSearch"
      @sort="handleSort"
    >

      <!-- Campaign Filters -->
      <template #actions>
        <div class="flex flex-wrap items-center gap-2">
          <div class="relative">
            <select
              v-model="monitoringFilter"
              @change="handleMonitoringFilter"
              class="block min-w-[190px] appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-3.5 pr-10 text-xs font-semibold text-gray-700 shadow-sm outline-none transition-all hover:border-gray-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
            >
              <option value="">Semua Kampanye</option>
              <option value="active">Aktif</option>
              <option value="completed">Selesai</option>
              <option value="approaching_deadline">Mendekati Tenggat</option>
              <option value="overdue">Terlambat</option>
              <option value="waiting_review">Menunggu Peninjauan</option>
              <option value="revision">Revisi</option>
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

          <div class="relative">
            <select
              v-model="statusFilter"
              @change="handleStatusFilter"
              class="block min-w-[160px] appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-3.5 pr-10 text-xs font-semibold text-gray-700 shadow-sm outline-none transition-all hover:border-gray-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
            >
              <option value="">Semua Status</option>
              <option value="draft">Draf</option>
              <option value="assigned">Ditugaskan</option>
              <option value="in_progress">Sedang Berjalan</option>
              <option value="waiting_review">Menunggu Peninjauan</option>
              <option value="revision">Revisi</option>
              <option value="approved">Disetujui</option>
              <option value="completed">Selesai</option>
              <option value="cancelled">Dibatalkan</option>
            </select>

            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
              <i class="fa-solid fa-chevron-down text-[10px]"></i>
            </div>
          </div>

          <div class="relative">
            <select
              v-model="priorityFilter"
              @change="handleFilter"
              class="block min-w-[140px] appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-3.5 pr-10 text-xs font-semibold text-gray-700 shadow-sm outline-none transition-all hover:border-gray-300 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10"
            >
              <option value="">Semua Prioritas</option>
              <option value="normal">Normal</option>
              <option value="mid">Menengah</option>
              <option value="urgent">Mendesak</option>
            </select>

            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
              <i class="fa-solid fa-chevron-down text-[10px]"></i>
            </div>
          </div>

          <button
            v-if="hasActiveFilters"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-xs font-bold text-gray-500 transition hover:bg-gray-50 hover:text-gray-800"
            @click="clearFilters"
          >
            <i class="fa-solid fa-xmark text-[10px]"></i>
            Bersihkan
          </button>
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
                {{ row.brand?.name || 'Brand tidak diketahui' }}
                <span v-if="row.pic?.name"> · PIC {{ row.pic.name }}</span>
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

          <div>
            <span class="font-semibold text-gray-700">
              {{ row.deadline || 'Belum ada tenggat' }}
            </span>

            <span
              v-if="row.deadline_state"
              class="mt-1 block w-fit rounded-full px-2 py-0.5 text-[10px] font-bold"
              :class="row.deadline_state === 'overdue'
                ? 'bg-rose-50 text-rose-700'
                : 'bg-amber-50 text-amber-700'"
            >
              {{ deadlineStateLabel(row.deadline_state) }}
            </span>
          </div>

        </div>
      </template>

      <!-- Priority -->
      <template #cell-priority="{ row }">
        <span
          class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide"
          :class="{
            'bg-gray-100 text-gray-600': row.priority === 'normal',
            'bg-amber-50 text-amber-700': row.priority === 'mid',
            'bg-rose-50 text-rose-700': row.priority === 'urgent'
          }"
        >
          {{ priorityLabel(row.priority) }}
        </span>
      </template>

      <!-- Status -->
      <template #cell-status="{ row }">
        <StatusBadge :status="row.status" :label="statusLabel(row.status)" />
      </template>

      <!-- Actions -->
      <template #cell-actions="{ row }">
        <div class="flex items-center justify-end gap-2">

          <!-- View -->
          <router-link
            :to="`/campaigns/${row.id}`"
            class="inline-flex items-center gap-1.5 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-600 transition-all duration-200 hover:border-blue-200 hover:bg-blue-100 hover:text-blue-700"
            title="Lihat Kampanye"
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

            <span>Lihat</span>
          </router-link>

          <!-- Edit -->
          <button
            v-if="$can('campaign.update')"
            type="button"
            @click="openEditModal(row)"
            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600 shadow-sm transition-all duration-200 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
            title="Ubah Kampanye"
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

            <span>Ubah</span>
          </button>

          <!-- Delete -->
          <button
            v-if="$can('campaign.delete')"
            type="button"
            @click="confirmDelete(row)"
            class="inline-flex items-center gap-1.5 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-xs font-bold text-red-600 transition-all duration-200 hover:bg-red-100 hover:text-red-700"
            title="Hapus Kampanye"
          >
            <i class="fa-solid fa-trash-can text-[11px]"></i>
            <span>Hapus</span>
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

            Sebelumnya
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
            Berikutnya

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
              Menampilkan
              <span class="font-bold text-gray-700">
                halaman {{ pagination.current_page }}
              </span>

              dari

              <span class="font-bold text-gray-700">
                {{ pagination.last_page }}
              </span>

              <span class="mx-1 text-gray-300">•</span>

              <span class="font-semibold text-gray-700">
                {{ pagination.total }}
              </span>

              hasil
            </p>
          </div>

          <nav
            class="flex items-center gap-1"
            aria-label="Paginasi"
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
import { computed, ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import StatusBadge from '../components/StatusBadge.vue';
import CampaignForm from '../components/CampaignForm.vue';
import { useCampaigns } from '../composables/useCampaigns';

const {
  campaigns,
  loading,
  pagination,
  fetchCampaigns,
  deleteCampaign
} = useCampaigns();

const columns = [
  {
    key: 'name',
    label: 'Nama Kampanye',
    sortable: true
  },
  {
    key: 'start_date',
    label: 'Tanggal Mulai',
    sortable: true
  },
  {
    key: 'deadline',
    label: 'Tenggat',
    sortable: true
  },
  {
    key: 'priority',
    label: 'Prioritas',
    sortable: true
  },
  {
    key: 'status',
    label: 'Status',
    sortable: true
  },
  {
    key: 'actions',
    label: 'Aksi',
    sortable: false
  }
];

const tableLabels = {
  searchPlaceholder: 'Cari data kampanye...',
  searchHelper: 'Cari data pada tabel kampanye',
  loading: 'Memuat data...',
  emptyTitle: 'Data kampanye tidak ditemukan',
  emptyDescription: 'Ubah kata pencarian atau filter.',
  scrollHint: 'Geser ke samping untuk melihat data lainnya',
};

const statusLabel = (status) => ({
  draft: 'Draf',
  assigned: 'Ditugaskan',
  in_progress: 'Sedang Berjalan',
  waiting_review: 'Menunggu Peninjauan',
  revision: 'Revisi',
  approved: 'Disetujui',
  completed: 'Selesai',
  cancelled: 'Dibatalkan',
}[status] || status);

const priorityLabel = (priority) => ({
  normal: 'Normal',
  mid: 'Menengah',
  urgent: 'Mendesak',
}[priority || 'normal']);

const deadlineStateLabel = (state) => ({
  approaching_deadline: 'Mendekati Tenggat',
  overdue: 'Terlambat',
}[state] || state);

const searchQuery = ref('');
const statusFilter = ref('');
const monitoringFilter = ref('');
const priorityFilter = ref('');
const sortBy = ref('created_at');
const sortDirection = ref('desc');
const hasActiveFilters = computed(() => Boolean(
  statusFilter.value || monitoringFilter.value || priorityFilter.value
));

const isModalOpen = ref(false);
const selectedCampaign = ref(null);

onMounted(() => {
  fetchData();
});

const fetchData = async () => {
  await fetchCampaigns({
    page: pagination.value.current_page || 1,
    search: searchQuery.value,
    status: statusFilter.value,
    monitoring: monitoringFilter.value,
    priority: priorityFilter.value,
    sort_by: sortBy.value,
    sort_direction: sortDirection.value
  });
};

const handleSearch = (value) => {
  searchQuery.value = value;
  pagination.value.current_page = 1;
  fetchData();
};

const handleSort = ({ key, order }) => {
  sortBy.value = key;
  sortDirection.value = order;
  pagination.value.current_page = 1;
  fetchData();
};

const handleFilter = () => {
  pagination.value.current_page = 1;
  fetchData();
};

const handleMonitoringFilter = () => {
  statusFilter.value = '';
  handleFilter();
};

const handleStatusFilter = () => {
  monitoringFilter.value = '';
  handleFilter();
};

const clearFilters = () => {
  statusFilter.value = '';
  monitoringFilter.value = '';
  priorityFilter.value = '';
  handleFilter();
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

const confirmDelete = async (campaign) => {
  if (!window.confirm(`Hapus kampanye "${campaign.name}"?`)) return;

  if (await deleteCampaign(campaign.id)) {
    if (campaigns.value.length === 1 && pagination.value.current_page > 1) {
      pagination.value.current_page--;
    }
    fetchData();
  }
};
</script>
