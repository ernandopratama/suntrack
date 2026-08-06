<template>
  <ModalForm :is-open="isOpen" :title="isEdit ? 'Edit Promotion' : 'Create Promotion'" @close="closeModal">
    <form id="promotion-form" @submit.prevent="submit" class="space-y-4 mt-2">
      
      <!-- Error Alert -->
      <div v-if="typeof error === 'string' && error" class="bg-red-50 border border-red-200 p-3 rounded-md">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Promotion Name <span class="text-red-500">*</span></label>
        <input type="text" v-model="form.name" required
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        <p v-if="hasError('name')" class="mt-1 text-xs text-red-600">{{ getError('name') }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Description</label>
        <textarea v-model="form.description" rows="3"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
          placeholder="Optional: Describe the purpose or terms of this promotion."></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Link to Campaign <span class="text-gray-400 text-xs">(Optional)</span></label>
        <select v-model="form.campaign_id"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
          <option :value="null">Standalone (No Campaign)</option>
          <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">Start Date</label>
          <input type="date" v-model="form.start_date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">End Date</label>
          <input type="date" v-model="form.end_date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
        <select v-model="form.status" required
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
          <option value="Pending">Pending</option>
          <option value="Partially Approved">Partially Approved</option>
          <option value="Approved">Approved</option>
          <option value="Rejected">Rejected</option>
        </select>
      </div>
    </form>
    <template #footer>
      <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-between">
        <button
          v-if="isEdit"
          type="button"
          @click="confirmDelete"
          class="inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
        >
          Delete
        </button>
        <div class="flex flex-col-reverse gap-2 sm:flex-row">
          <button
            type="button"
            class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none"
            @click="closeModal"
          >
            Cancel
          </button>
          <button
            type="submit"
            form="promotion-form"
            :disabled="loading"
            class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
          >
            {{ loading ? 'Saving...' : 'Save Promotion' }}
          </button>
        </div>
      </div>
    </template>
  </ModalForm>
</template>

<script setup>
import { ref, watch } from 'vue';
import ModalForm from './ModalForm.vue';
import { usePromotions } from '../composables/usePromotions';
import { useCampaigns } from '../composables/useCampaigns';

const props = defineProps({
  isOpen: { type: Boolean, required: true },
  promotion: { type: Object, default: null },
  defaultCampaignId: { type: String, default: null }, // Pre-fill when creating from campaign detail
});

const emit = defineEmits(['close', 'saved']);

const { createPromotion, updatePromotion, deletePromotion, loading, error } = usePromotions();

const { campaigns, fetchCampaigns } = useCampaigns();

const isEdit = ref(false);
const form = ref({
  name: '',
  description: '',
  campaign_id: null,
  start_date: '',
  end_date: '',
  status: 'Pending',
});

watch(() => props.isOpen, async (newVal) => {
  if (newVal) {
    // Load campaigns for the dropdown
    await fetchCampaigns({ per_page: 100 });

    if (props.promotion) {
      isEdit.value = true;
      form.value = {
        name: props.promotion.name,
        description: props.promotion.description || '',
        campaign_id: props.promotion.campaign?.id || null,
        start_date: props.promotion.start_date || '',
        end_date: props.promotion.end_date || '',
        status: props.promotion.status,
      };
    } else {
      isEdit.value = false;
      form.value = {
        name: '',
        description: '',
        campaign_id: props.defaultCampaignId || null,
        start_date: '',
        end_date: '',
        status: 'Pending',
      };
    }
    error.value = null;
  }
});

const hasError = (field) => error.value && typeof error.value === 'object' && error.value[field];
const getError = (field) => hasError(field) ? error.value[field][0] : '';

const submit = async () => {
  let result = false;
  if (isEdit.value) {
    result = await updatePromotion(props.promotion.id, form.value);
  } else {
    result = await createPromotion(form.value);
  }

  if (result) {
    emit('saved', result);
    closeModal();
  }
};

const confirmDelete = async () => {
  if (confirm(`Are you sure you want to delete promotion "${props.promotion.name}"? This action cannot be undone.`)) {
    const success = await deletePromotion(props.promotion.id);
    if (success) {
      emit('saved'); // Refresh data
      closeModal();
    }
  }
};

const closeModal = () => emit('close');
</script>
