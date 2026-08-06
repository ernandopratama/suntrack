<template>
  <ModalForm :is-open="isOpen" :title="isEdit ? 'Edit Task' : 'Create Task'" @close="closeModal">
    <form id="task-form" @submit.prevent="submit" class="space-y-4">
      <div v-if="typeof error === 'string'" class="rounded-md bg-red-50 p-4">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>

      <!-- Campaign -->
      <div v-if="!campaignId">
        <label class="block text-sm font-medium text-gray-700">Campaign <span class="text-red-500">*</span></label>
        <select v-model="form.campaign_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
          <option value="" disabled>Select campaign...</option>
          <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>

      <!-- Name -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Task Name <span class="text-red-500">*</span></label>
        <input type="text" v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
        <p v-if="hasError('name')" class="mt-1 text-xs text-red-600">{{ getError('name') }}</p>
      </div>

      <!-- Status + Deadline -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
          <select v-model="form.progress_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            <option value="NotStarted">Not Started</option>
            <option value="InProgress">In Progress</option>
            <option value="Completed">Completed</option>
            <option value="OnHold">On Hold</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Deadline</label>
          <input type="date" v-model="form.deadline" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
        </div>
      </div>

      <!-- Visual -->
      <div class="flex items-center gap-2">
        <input type="checkbox" v-model="form.requires_visual" id="requires_visual" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
        <label for="requires_visual" class="text-sm font-medium text-gray-700">Requires Visual</label>
      </div>

      <div v-if="form.requires_visual">
        <label class="block text-sm font-medium text-gray-700">Visual Type</label>
        <input type="text" v-model="form.visual_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="e.g. Banner, Poster, Video" />
      </div>
    </form>

    <template #footer>
      <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <button type="button" class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="closeModal">Cancel</button>
        <button type="submit" form="task-form" :disabled="loading" class="inline-flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60">
          {{ loading ? 'Saving...' : 'Save Task' }}
        </button>
      </div>
    </template>
  </ModalForm>
</template>

<script setup>
import { reactive, ref, watch, onMounted } from 'vue';
import ModalForm from './ModalForm.vue';
import { useTasks } from '../composables/useTasks';
import { useCampaigns } from '../composables/useCampaigns';

const props = defineProps({
  isOpen: { type: Boolean, required: true },
  task: { type: Object, default: null },
  campaignId: { type: String, default: null }
});

const emit = defineEmits(['close', 'saved']);

const { createTask, updateTask, loading, error } = useTasks();
const { campaigns, fetchCampaigns } = useCampaigns();

const isEdit = ref(false);
const form = reactive({
  name: '',
  campaign_id: props.campaignId || '',
  progress_status: 'NotStarted',
  requires_visual: false,
  visual_type: '',
  deadline: ''
});

onMounted(() => {
  if (!props.campaignId) fetchCampaigns({ per_page: 100 });
});

watch(() => props.isOpen, (open) => {
  if (!open) return;
  if (props.task) {
    isEdit.value = true;
    form.name = props.task.name;
    form.campaign_id = props.task.campaign_id;
    form.progress_status = props.task.progress_status || 'NotStarted';
    form.requires_visual = props.task.requires_visual || false;
    form.visual_type = props.task.visual_type || '';
    form.deadline = props.task.deadline ? props.task.deadline.slice(0, 10) : '';
  } else {
    isEdit.value = false;
    form.name = '';
    form.campaign_id = props.campaignId || '';
    form.progress_status = 'NotStarted';
    form.requires_visual = false;
    form.visual_type = '';
    form.deadline = '';
  }
  error.value = null;
});

const hasError = (field) => error.value && typeof error.value === 'object' && error.value[field];
const getError = (field) => hasError(field) ? (Array.isArray(error.value[field]) ? error.value[field][0] : error.value[field]) : '';

const submit = async () => {
  let success = false;
  const payload = { ...form };
  if (isEdit.value) {
    success = await updateTask(props.task.id, payload);
  } else {
    success = await createTask(payload);
  }
  if (success) {
    emit('saved');
    closeModal();
  }
};

const closeModal = () => emit('close');
</script>