<template>
  <ModalForm
    :is-open="isOpen"
    :title="isEdit ? 'Edit Task' : 'Create Task'"
    @close="closeModal"
  >
    <form id="task-form" @submit.prevent="submit" class="space-y-5">

      <!-- Error -->
      <div
        v-if="typeof error === 'string'"
        class="flex items-start gap-3 rounded-xl border border-rose-100 bg-rose-50 px-4 py-3"
      >
        <div
          class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600"
        >
          <i class="fa-solid fa-circle-exclamation text-xs"></i>
        </div>

        <div>
          <p class="text-xs font-bold text-rose-700">
            Unable to save task
          </p>
          <p class="mt-0.5 text-xs text-rose-600">
            {{ error }}
          </p>
        </div>
      </div>

      <!-- Brand -->
      <div>
        <label class="mb-1.5 block text-xs font-bold text-gray-700">Brand <span class="text-rose-500">*</span></label>
        <select v-model="form.brand_id" required @change="handleBrandChange" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-700">
          <option value="" disabled>Select brand...</option>
          <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
        </select>
        <p v-if="hasError('brand_id')" class="mt-1.5 text-xs font-medium text-rose-600">{{ getError('brand_id') }}</p>
      </div>

      <!-- Campaign -->
      <div v-if="!campaignId">
        <label class="mb-1.5 block text-xs font-bold text-gray-700">
          Campaign <span class="text-gray-400">(optional)</span>
        </label>

        <div class="relative">
          <select
            v-model="form.campaign_id"
            class="block w-full appearance-none rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 pr-10 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all duration-200 hover:border-gray-300 focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
          >
            <option value="">
              Standalone task
            </option>

            <option
              v-for="c in availableCampaigns"
              :key="c.id"
              :value="c.id"
            >
              {{ c.name }}
            </option>
          </select>

          <div
            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400"
          >
            <i class="fa-solid fa-chevron-down text-[10px]"></i>
          </div>
        </div>
      </div>

      <div>
        <label class="mb-1.5 block text-xs font-bold text-gray-700">Description / Instruction</label>
        <textarea v-model="form.description" rows="3" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700" placeholder="Describe the work to complete" />
      </div>

      <!-- Task Name -->
      <div>
        <label class="mb-1.5 block text-xs font-bold text-gray-700">
          Task Name
          <span class="text-rose-500">*</span>
        </label>

        <input
          type="text"
          v-model="form.name"
          required
          placeholder="Enter task name..."
          class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-700 placeholder:text-gray-400 shadow-sm outline-none transition-all duration-200 hover:border-gray-300 focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
        />

        <p
          v-if="hasError('name')"
          class="mt-1.5 text-xs font-medium text-rose-600"
        >
          {{ getError('name') }}
        </p>
      </div>

      <!-- Status + Deadline -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

        <!-- Status -->
        <div>
          <label class="mb-1.5 block text-xs font-bold text-gray-700">
            Status
            <span class="text-rose-500">*</span>
          </label>

          <div class="relative">
            <select
              v-model="form.progress_status"
              class="block w-full appearance-none rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 pr-10 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all duration-200 hover:border-gray-300 focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
            >
              <option value="pending">Pending</option>
              <option value="assigned">Assigned</option>
              <option value="in_progress">In Progress</option>
              <option value="on_hold">On Hold</option>
              <option value="waiting_review">Waiting Review</option>
              <option value="revision">Revision</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>

            <div
              class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400"
            >
              <i class="fa-solid fa-chevron-down text-[10px]"></i>
            </div>
          </div>
        </div>

        <!-- Deadline -->
        <div>
          <label class="mb-1.5 block text-xs font-bold text-gray-700">
            Deadline
          </label>

          <div class="relative">
            <input
              type="date"
              v-model="form.deadline"
              class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all duration-200 hover:border-gray-300 focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
            />

            <div
              class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-[#4274D9]"
            >
              <i class="fa-regular fa-calendar text-sm"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1.5 block text-xs font-bold text-gray-700">Priority</label>
          <select v-model="form.priority" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700">
            <option value="normal">Normal</option><option value="mid">Mid</option><option value="urgent">Urgent</option>
          </select>
        </div>
        <div v-if="canManageOwnership">
          <label class="mb-1.5 block text-xs font-bold text-gray-700">PIC</label>
          <select v-model="form.pic_id" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700">
            <option :value="null">Select PIC</option><option v-for="pic in pics" :key="pic.id" :value="pic.id">{{ pic.name }}</option>
          </select>
        </div>
        <div v-if="canManageOwnership">
          <label class="mb-1.5 block text-xs font-bold text-gray-700">Assignee Tim</label>
          <select v-model="form.assignee_id" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700">
            <option :value="null">Select assignee</option><option v-for="member in teamMembers" :key="member.id" :value="member.id">{{ member.name }}</option>
          </select>
        </div>
      </div>

      <div v-if="isEdit">
        <label class="mb-1.5 block text-xs font-bold text-gray-700">Completion Summary</label>
        <textarea v-model="form.completion_summary" rows="2" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700" />
      </div>

      <div v-if="isEdit && form.progress_status !== props.task?.progress_status">
        <label class="mb-1.5 block text-xs font-bold text-gray-700">Transition Note</label>
        <textarea v-model="form.transition_note" rows="2" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700" placeholder="Required for hold, revision, or cancellation" />
      </div>

      <!-- Requires Visual -->
      <div
        class="rounded-xl border border-[#D0E7E6] bg-[#D0E7E6]/40 px-4 py-3"
      >
        <label class="flex cursor-pointer items-center gap-3">

          <div class="relative flex items-center">
            <input
              type="checkbox"
              v-model="form.requires_visual"
              id="requires_visual"
              class="peer sr-only"
            />

            <div
              class="flex h-5 w-5 items-center justify-center rounded-md border border-gray-300 bg-white transition-all duration-200 peer-checked:border-[#4274D9] peer-checked:bg-[#4274D9]"
            >
              <i
                class="fa-solid fa-check scale-0 text-[10px] text-white transition-transform duration-150 peer-checked:scale-100"
              ></i>
            </div>
          </div>

          <div>
            <span class="block text-sm font-bold text-[#293681]">
              Requires Visual
            </span>

            <span class="block text-[11px] text-gray-500">
              This task requires visual content.
            </span>
          </div>
        </label>
      </div>

      <!-- Visual Type -->
      <div v-if="form.requires_visual">
        <label class="mb-1.5 block text-xs font-bold text-gray-700">
          Visual Type
        </label>

        <input
          type="text"
          v-model="form.visual_type"
          placeholder="e.g. Banner, Poster, Video"
          class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-700 placeholder:text-gray-400 shadow-sm outline-none transition-all duration-200 hover:border-gray-300 focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
        />

        <p class="mt-1.5 text-[11px] text-gray-400">
          Specify the type of visual asset required for this task.
        </p>
      </div>
    </form>

    <!-- Footer -->
    <template #footer>
      <div
        class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end"
      >

        <!-- Cancel -->
        <button
          type="button"
          @click="closeModal"
          class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-600 shadow-sm transition-all duration-200 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-900"
        >
          Cancel
        </button>

        <!-- Save -->
        <button
          v-if="isEdit ? $can('task.update') : $can('task.create')"
          type="submit"
          form="task-form"
          :disabled="loading"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#4274D9] px-5 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#293681] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
        >
          <i
            v-if="loading"
            class="fa-solid fa-spinner animate-spin text-[11px]"
          ></i>

          <i
            v-else
            class="fa-solid fa-check text-[11px]"
          ></i>

          <span>
            {{ loading ? 'Saving...' : 'Save Task' }}
          </span>
        </button>

      </div>
    </template>
  </ModalForm>
</template>

<script setup>
import { computed, reactive, ref, watch, onMounted } from 'vue';
import ModalForm from './ModalForm.vue';
import { useTasks } from '../composables/useTasks';
import { useCampaigns } from '../composables/useCampaigns';
import { useBrands } from '../composables/useBrands';
import { useWorkflowOptions } from '../composables/useWorkflowOptions';
import { useAuthStore } from '../stores/auth';

const props = defineProps({
  isOpen: { type: Boolean, required: true },
  task: { type: Object, default: null },
  campaignId: { type: String, default: null }
});

const emit = defineEmits(['close', 'saved']);

const { createTask, updateTask, loading, error } = useTasks();
const { campaigns, fetchCampaigns } = useCampaigns();
const { brands, fetchBrands } = useBrands();
const { pics, teamMembers, fetchWorkflowOptions } = useWorkflowOptions();
const authStore = useAuthStore();
const canManageOwnership = computed(() => authStore.hasRole('Super Admin') || authStore.hasRole('Admin'));
const availableCampaigns = computed(() => campaigns.value.filter(campaign => !form.brand_id || campaign.brand_id === form.brand_id));

const isEdit = ref(false);

const form = reactive({
  name: '',
  description: '',
  brand_id: '',
  campaign_id: props.campaignId || '',
  pic_id: null,
  assignee_id: null,
  progress_status: 'pending',
  priority: 'normal',
  completion_summary: '',
  transition_note: '',
  requires_visual: false,
  visual_type: '',
  deadline: ''
});

onMounted(async () => {
  await Promise.all([fetchBrands({ per_page: 100 }), fetchCampaigns({ per_page: 100 })]);
  if (props.campaignId) {
    const campaign = campaigns.value.find(item => item.id === props.campaignId);
    if (campaign) {
      form.brand_id = campaign.brand_id;
      fetchWorkflowOptions(campaign.brand_id);
    }
  }
});

watch(() => props.isOpen, (open) => {
  if (!open) return;

  if (props.task) {
    isEdit.value = true;
    form.name = props.task.name;
    form.description = props.task.description || '';
    form.brand_id = props.task.brand_id || '';
    form.campaign_id = props.task.campaign_id;
    form.pic_id = props.task.pic_id || null;
    form.assignee_id = props.task.assignee_id || null;
    form.progress_status = props.task.progress_status || 'pending';
    form.priority = props.task.priority || 'normal';
    form.completion_summary = props.task.completion_summary || '';
    form.transition_note = '';
    form.requires_visual = props.task.requires_visual || false;
    form.visual_type = props.task.visual_type || '';
    form.deadline = props.task.deadline
      ? props.task.deadline.slice(0, 10)
      : '';
  } else {
    isEdit.value = false;
    form.name = '';
    form.description = '';
    form.brand_id = '';
    form.campaign_id = props.campaignId || '';
    form.progress_status = 'pending';
    form.priority = 'normal';
    form.pic_id = null;
    form.assignee_id = null;
    form.completion_summary = '';
    form.transition_note = '';
    form.requires_visual = false;
    form.visual_type = '';
    form.deadline = '';
    if (props.campaignId) {
      const campaign = campaigns.value.find(item => item.id === props.campaignId);
      form.brand_id = campaign?.brand_id || '';
    }
  }

  error.value = null;
  if (form.brand_id) fetchWorkflowOptions(form.brand_id);
});

const handleBrandChange = () => {
  if (form.campaign_id && !availableCampaigns.value.some(campaign => campaign.id === form.campaign_id)) {
    form.campaign_id = '';
  }
  fetchWorkflowOptions(form.brand_id);
};

const hasError = (field) =>
  error.value &&
  typeof error.value === 'object' &&
  error.value[field];

const getError = (field) =>
  hasError(field)
    ? (
        Array.isArray(error.value[field])
          ? error.value[field][0]
          : error.value[field]
      )
    : '';

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
