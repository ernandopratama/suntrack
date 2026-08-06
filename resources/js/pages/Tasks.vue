<template>
  <div>
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Tasks</h1>
        <p class="mt-1 text-sm text-gray-500">Manage campaign tasks and track progress.</p>
      </div>
      <div>
        <button @click="openCreateModal" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
          <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
          New Task
        </button>
      </div>
    </div>

    <DataTable :columns="columns" :data="tasks" :loading="loading" @search="handleSearch">
      <template #cell-no="{ row, idx }">
        <span class="text-sm text-gray-500">{{ (pagination.current_page - 1) * (pagination.per_page || 15) + idx + 1 }}</span>
      </template>
      <template #cell-name="{ row }">
        <div class="font-medium text-gray-900">{{ row.name }}</div>
      </template>
      <template #cell-campaign="{ row }">
        <span class="text-sm text-gray-600">{{ row.campaign?.name || '-' }}</span>
      </template>
      <template #cell-progress_status="{ row }">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
          :class="statusClass(row.progress_status)"
        >{{ statusLabel(row.progress_status) }}</span>
      </template>
      <template #cell-deadline="{ row }">
        <span class="text-sm text-gray-600">{{ row.deadline ? row.deadline.slice(0, 10) : '-' }}</span>
      </template>
      <template #cell-actions="{ row }">
        <div class="flex space-x-3 text-sm">
          <button @click="openEditModal(row)" class="text-blue-600 hover:text-blue-900 font-medium">Edit</button>
          <button @click="confirmDelete(row)" class="text-red-600 hover:text-red-900 font-medium">Delete</button>
        </div>
      </template>

      <template #pagination>
        <div class="flex-1 flex justify-between sm:hidden">
          <button @click="prevPage" :disabled="pagination.current_page === 1">Previous</button>
          <button @click="nextPage" :disabled="pagination.current_page === pagination.last_page">Next</button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <p class="text-sm text-gray-700">Page {{ pagination.current_page }} of {{ pagination.last_page }} ({{ pagination.total }} total tasks)</p>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <button @click="prevPage" :disabled="pagination.current_page === 1" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">&larr;</button>
            <button @click="nextPage" :disabled="pagination.current_page === pagination.last_page" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">&rarr;</button>
          </nav>
        </div>
      </template>
    </DataTable>

    <!-- Delete Confirmation -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-[9999] flex items-center justify-center">
      <div class="absolute inset-0 bg-black/60" @click="showDeleteModal = false"></div>
      <div class="relative bg-white rounded-xl p-6 shadow-xl max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-gray-900 mb-2">Delete Task</h3>
        <p class="text-sm text-gray-600 mb-4">Are you sure you want to delete <span class="font-semibold">{{ taskToDelete?.name }}</span>?</p>
        <div class="flex justify-end gap-3">
          <button @click="showDeleteModal = false" class="rounded-lg px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200">Cancel</button>
          <button @click="deleteTaskAction" class="rounded-lg px-4 py-2 text-white bg-red-600 hover:bg-red-700">Delete</button>
        </div>
      </div>
    </div>

    <TaskForm :is-open="isModalOpen" :task="selectedTask" @close="closeModal" @saved="fetchData" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import TaskForm from '../components/TaskForm.vue';
import { useTasks } from '../composables/useTasks';

const { tasks, loading, pagination, fetchTasks, deleteTask } = useTasks();

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

const fetchData = () => fetchTasks({ page: pagination.value.current_page, search: searchQuery.value });
const handleSearch = (q) => { searchQuery.value = q; pagination.value.current_page = 1; fetchData(); };
const prevPage = () => { if (pagination.value.current_page > 1) { pagination.value.current_page--; fetchData(); } };
const nextPage = () => { if (pagination.value.current_page < pagination.value.last_page) { pagination.value.current_page++; fetchData(); } };
const openCreateModal = () => { selectedTask.value = null; isModalOpen.value = true; };
const openEditModal = (row) => { selectedTask.value = row; isModalOpen.value = true; };
const closeModal = () => { isModalOpen.value = false; };
const confirmDelete = (row) => { taskToDelete.value = row; showDeleteModal.value = true; };
const deleteTaskAction = async () => {
  if (!taskToDelete.value) return;
  await deleteTask(taskToDelete.value.id);
  showDeleteModal.value = false;
  taskToDelete.value = null;
  fetchData();
};

const statusClass = (status) => {
  const map = { NotStarted: 'bg-gray-100 text-gray-700', InProgress: 'bg-blue-100 text-blue-700', Completed: 'bg-green-100 text-green-700', OnHold: 'bg-yellow-100 text-yellow-700' };
  return map[status] || 'bg-gray-100 text-gray-700';
};
const statusLabel = (status) => {
  const map = { NotStarted: 'Not Started', InProgress: 'In Progress', Completed: 'Completed', OnHold: 'On Hold' };
  return map[status] || status;
};
</script>