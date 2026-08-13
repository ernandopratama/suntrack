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
                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a3 3 0 006 0M9 5h6"
              />
            </svg>
          </div>

          <span
            class="rounded-full bg-[#D0E7E6] px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider text-[#293681]"
          >
            Task Management
          </span>
        </div>

        <!-- Title -->
        <h1
          class="mt-3 text-2xl font-extrabold tracking-tight text-[#293681] sm:text-3xl"
        >
          Tasks
        </h1>

        <p class="mt-1 text-sm text-gray-500">
          Manage campaign tasks and track progress.
        </p>
      </div>

      <!-- Create Task -->
      <button
        @click="openCreateModal"
        type="button"
        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#4274D9] px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#293681] hover:shadow-md sm:w-auto"
      >
        <span
          class="flex h-5 w-5 items-center justify-center rounded-md bg-white/15"
        >
          <svg
            class="h-3.5 w-3.5"
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

        <span>New Task</span>
      </button>
    </div>

    <!-- Main Table -->
    <div
      class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
      <DataTable
        :columns="columns"
        :data="tasks"
        :loading="loading"
        @search="handleSearch"
      >
        <!-- No -->
        <template #cell-no="{ row, idx }">
          <span
            class="inline-flex h-7 min-w-7 items-center justify-center rounded-lg bg-[#D0E7E6] px-2 text-xs font-bold text-[#293681]"
          >
            {{
              (pagination.current_page - 1) *
                (pagination.per_page || 15) +
              idx +
              1
            }}
          </span>
        </template>

        <!-- Task Name -->
        <template #cell-name="{ row }">
          <div class="min-w-[180px]">
            <div class="flex items-center gap-3">
              <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]"
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
                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a3 3 0 006 0M9 5h6"
                  />
                </svg>
              </div>

              <div class="min-w-0">
                <div
                  class="truncate text-sm font-bold text-gray-900"
                >
                  {{ row.name }}
                </div>

                <div class="mt-0.5 text-[11px] text-gray-400">
                  Campaign Task
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- Campaign -->
        <template #cell-campaign="{ row }">
          <div class="flex items-center gap-2">
            <span
              class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#95CCDD]/30 text-[#4274D9]"
            >
              <svg
                class="h-3.5 w-3.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.8"
                  d="M9 5h6m-8 4h10m-9 4h8m-7 4h6"
                />
              </svg>
            </span>

            <span class="text-sm font-semibold text-gray-600">
              {{ row.campaign?.name || '-' }}
            </span>
          </div>
        </template>

        <!-- Status -->
        <template #cell-progress_status="{ row }">
          <span
            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
            :class="statusClass(row.progress_status)"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
            {{ statusLabel(row.progress_status) }}
          </span>
        </template>

        <!-- Deadline -->
        <template #cell-deadline="{ row }">
          <div class="flex items-center gap-2">
            <span
              class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#95CCDD]/30 text-[#4274D9]"
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
            </span>

            <span class="text-sm font-medium text-gray-600">
              {{ row.deadline ? row.deadline.slice(0, 10) : '-' }}
            </span>
          </div>
        </template>

        <!-- Actions -->
        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-2">
            <!-- Edit -->
            <button
              @click="openEditModal(row)"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-[#95CCDD] bg-[#D0E7E6]/50 px-3 py-2 text-xs font-bold text-[#293681] transition-all duration-200 hover:border-[#4274D9] hover:bg-[#D0E7E6] hover:text-[#293681]"
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
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"
                />
              </svg>

              <span>Edit</span>
            </button>

            <!-- Delete -->
            <button
              @click="confirmDelete(row)"
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-600 transition-all duration-200 hover:border-rose-200 hover:bg-rose-100 hover:text-rose-700"
            >
              <svg
                class="h-3.5 w-3.5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.8"
                  d="M6 7h12M10 11v6m4-6v6M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m3 0v12a1 1 0 01-1 1H7a1 1 0 01-1-1V7h12z"
                />
              </svg>

              <span>Delete</span>
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
              type="button"
              class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 shadow-sm transition-all hover:border-[#95CCDD] hover:bg-[#D0E7E6]/40 hover:text-[#293681] disabled:cursor-not-allowed disabled:opacity-40"
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

            <span
              class="rounded-lg bg-[#D0E7E6] px-3 py-2 text-xs font-bold text-[#293681]"
            >
              {{ pagination.current_page }} /
              {{ pagination.last_page }}
            </span>

            <button
              @click="nextPage"
              :disabled="
                pagination.current_page === pagination.last_page
              "
              type="button"
              class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 shadow-sm transition-all hover:border-[#95CCDD] hover:bg-[#D0E7E6]/40 hover:text-[#293681] disabled:cursor-not-allowed disabled:opacity-40"
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
          </div>

          <!-- Desktop -->
          <div
            class="hidden flex-1 items-center justify-between sm:flex"
          >
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
              total tasks
            </p>

            <nav
              class="flex items-center gap-1"
              aria-label="Pagination"
            >
              <button
                @click="prevPage"
                :disabled="pagination.current_page === 1"
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition-all hover:border-[#95CCDD] hover:bg-[#D0E7E6] hover:text-[#293681] disabled:cursor-not-allowed disabled:opacity-40"
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
                    d="M15 19l-7-7 7-7"
                  />
                </svg>
              </button>

              <div
                class="flex h-9 min-w-9 items-center justify-center rounded-lg bg-[#293681] px-3 text-xs font-bold text-white"
              >
                {{ pagination.current_page }}
              </div>

              <button
                @click="nextPage"
                :disabled="
                  pagination.current_page === pagination.last_page
                "
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition-all hover:border-[#95CCDD] hover:bg-[#D0E7E6] hover:text-[#293681] disabled:cursor-not-allowed disabled:opacity-40"
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
                    d="M9 5l7 7-7 7"
                  />
                </svg>
              </button>
            </nav>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Delete Confirmation -->
    <div
      v-if="showDeleteModal"
      class="fixed inset-0 z-[9999] flex items-center justify-center"
    >
      <div
        class="absolute inset-0 bg-[#293681]/50 backdrop-blur-sm"
        @click="showDeleteModal = false"
      ></div>

      <div
        class="relative mx-4 w-full max-w-md overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
      >
        <!-- Modal Header -->
        <div class="border-b border-gray-100 px-6 py-5">
          <div class="flex items-center gap-3">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600"
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
                  d="M12 9v4m0 4h.01M10.3 3.6L2.7 17a2 2 0 001.74 3h15.12a2 2 0 001.74-3L13.7 3.6a2 2 0 00-3.4 0z"
                />
              </svg>
            </div>

            <div>
              <h3 class="text-base font-extrabold text-[#293681]">
                Delete Task
              </h3>

              <p class="mt-0.5 text-xs text-gray-400">
                This action cannot be undone.
              </p>
            </div>
          </div>
        </div>

        <!-- Modal Body -->
        <div class="px-6 py-5">
          <p class="text-sm leading-6 text-gray-600">
            Are you sure you want to delete
            <span class="font-bold text-gray-900">
              {{ taskToDelete?.name }}
            </span>
            ?
          </p>
        </div>

        <!-- Modal Footer -->
        <div
          class="flex justify-end gap-3 border-t border-gray-100 bg-gray-50/70 px-6 py-4"
        >
          <button
            @click="showDeleteModal = false"
            type="button"
            class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 shadow-sm transition-all hover:border-[#95CCDD] hover:bg-[#D0E7E6]/40 hover:text-[#293681]"
          >
            Cancel
          </button>

          <button
            @click="deleteTaskAction"
            type="button"
            class="rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-rose-700 hover:shadow-md"
          >
            Delete Task
          </button>
        </div>
      </div>
    </div>

    <!-- Task Form -->
    <TaskForm
      :is-open="isModalOpen"
      :task="selectedTask"
      @close="closeModal"
      @saved="fetchData"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import TaskForm from '../components/TaskForm.vue';
import { useTasks } from '../composables/useTasks';

const {
  tasks,
  loading,
  pagination,
  fetchTasks,
  deleteTask
} = useTasks();

const columns = [
  { key: 'no', label: 'No', sortable: false },
  { key: 'name', label: 'Task Name', sortable: true },
  { key: 'campaign', label: 'Campaign', sortable: false },
  { key: 'progress_status', label: 'Status', sortable: true },
  { key: 'deadline', label: 'Deadline', sortable: true },
  { key: 'actions', label: '', sortable: false },
];

const searchQuery = ref('');
const isModalOpen = ref(false);
const selectedTask = ref(null);
const showDeleteModal = ref(false);
const taskToDelete = ref(null);

onMounted(() => fetchData());

const fetchData = () =>
  fetchTasks({
    page: pagination.value.current_page,
    search: searchQuery.value
  });

const handleSearch = (q) => {
  searchQuery.value = q;
  pagination.value.current_page = 1;
  fetchData();
};

const prevPage = () => {
  if (pagination.value.current_page > 1) {
    pagination.value.current_page--;
    fetchData();
  }
};

const nextPage = () => {
  if (
    pagination.value.current_page <
    pagination.value.last_page
  ) {
    pagination.value.current_page++;
    fetchData();
  }
};

const openCreateModal = () => {
  selectedTask.value = null;
  isModalOpen.value = true;
};

const openEditModal = (row) => {
  selectedTask.value = row;
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const confirmDelete = (row) => {
  taskToDelete.value = row;
  showDeleteModal.value = true;
};

const deleteTaskAction = async () => {
  if (!taskToDelete.value) return;

  await deleteTask(taskToDelete.value.id);

  showDeleteModal.value = false;
  taskToDelete.value = null;

  fetchData();
};

const statusClass = (status) => {
  const map = {
    NotStarted: 'bg-gray-100 text-gray-600',
    InProgress: 'bg-[#D0E7E6] text-[#293681]',
    Completed: 'bg-emerald-100 text-emerald-700',
    OnHold: 'bg-amber-100 text-amber-700'
  };

  return map[status] || 'bg-gray-100 text-gray-600';
};

const statusLabel = (status) => {
  const map = {
    NotStarted: 'Not Started',
    InProgress: 'In Progress',
    Completed: 'Completed',
    OnHold: 'On Hold'
  };

  return map[status] || status;
};
</script>