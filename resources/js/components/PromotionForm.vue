<template>
  <ModalForm
    :is-open="isOpen"
    :title="isEdit ? 'Edit Promotion' : 'Create Promotion'"
    @close="closeModal"
  >
    <form
      id="promotion-form"
      @submit.prevent="submit"
      class="space-y-5"
    >
      <!-- Error Alert -->
      <div
        v-if="typeof error === 'string' && error"
        class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3"
      >
        <div
          class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600"
        >
          <i class="fa-solid fa-circle-exclamation text-xs"></i>
        </div>

        <div>
          <p class="text-xs font-bold text-rose-700">
            Unable to save promotion
          </p>

          <p class="mt-0.5 text-xs text-rose-600">
            {{ error }}
          </p>
        </div>
      </div>

      <!-- Promotion Name -->
      <div>
        <label
          class="mb-1.5 block text-xs font-bold text-[#293681]"
        >
          Promotion Name
          <span class="text-rose-500">*</span>
        </label>

        <div class="relative">
          <span
            class="pointer-events-none absolute inset-y-0 left-0 flex w-10 items-center justify-center text-[#4274D9]"
          >
            <i class="fa-solid fa-tag text-xs"></i>
          </span>

          <input
            type="text"
            v-model="form.name"
            required
            placeholder="Enter promotion name"
            class="block w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm font-medium text-gray-800 shadow-sm outline-none transition-all placeholder:text-gray-400 hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
          />
        </div>

        <p
          v-if="hasError('name')"
          class="mt-1.5 text-xs font-medium text-rose-600"
        >
          {{ getError('name') }}
        </p>
      </div>

      <!-- Description -->
      <div>
        <label
          class="mb-1.5 block text-xs font-bold text-[#293681]"
        >
          Description
        </label>

        <textarea
          v-model="form.description"
          rows="3"
          placeholder="Optional: Describe the purpose or terms of this promotion."
          class="block w-full resize-none rounded-xl border border-gray-200 bg-white px-3.5 py-3 text-sm font-medium text-gray-800 shadow-sm outline-none transition-all placeholder:text-gray-400 hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
        ></textarea>

        <p class="mt-1.5 text-[11px] text-gray-400">
          Provide additional information about this promotion.
        </p>
      </div>

      <!-- Campaign -->
      <div>
        <label
          class="mb-1.5 block text-xs font-bold text-[#293681]"
        >
          Link to Campaign
          <span class="ml-1 text-[10px] font-semibold text-gray-400">
            (Optional)
          </span>
        </label>

        <div class="relative">
          <span
            class="pointer-events-none absolute inset-y-0 left-0 z-10 flex w-10 items-center justify-center text-[#4274D9]"
          >
            <i class="fa-solid fa-bullhorn text-xs"></i>
          </span>

          <select
            v-model="form.campaign_id"
            class="block w-full appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-10 text-sm font-medium text-gray-800 shadow-sm outline-none transition-all hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
          >
            <option :value="null">
              Standalone (No Campaign)
            </option>

            <option
              v-for="c in campaigns"
              :key="c.id"
              :value="c.id"
            >
              {{ c.name }}
            </option>
          </select>

          <span
            class="pointer-events-none absolute inset-y-0 right-0 flex w-10 items-center justify-center text-gray-400"
          >
            <i class="fa-solid fa-chevron-down text-[10px]"></i>
          </span>
        </div>
      </div>

      <!-- Date Section -->
      <div
        class="rounded-2xl border border-[#D0E7E6] bg-[#D0E7E6]/30 p-4"
      >
        <div class="mb-3 flex items-center gap-2">
          <div
            class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-[#4274D9] shadow-sm"
          >
            <i class="fa-regular fa-calendar text-xs"></i>
          </div>

          <div>
            <h3 class="text-xs font-extrabold text-[#293681]">
              Promotion Period
            </h3>

            <p class="text-[10px] text-gray-500">
              Set the active period for this promotion.
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <!-- Start Date -->
          <div>
            <label
              class="mb-1.5 block text-xs font-bold text-gray-600"
            >
              Start Date
            </label>

            <input
              type="date"
              v-model="form.start_date"
              class="block w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
            />
          </div>

          <!-- End Date -->
          <div>
            <label
              class="mb-1.5 block text-xs font-bold text-gray-600"
            >
              End Date
            </label>

            <input
              type="date"
              v-model="form.end_date"
              class="block w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
            />
          </div>
        </div>
      </div>

      <!-- Status -->
      <div>
        <label
          class="mb-1.5 block text-xs font-bold text-[#293681]"
        >
          Status
          <span class="text-rose-500">*</span>
        </label>

        <div class="relative">
          <select
            v-model="form.status"
            required
            class="block w-full appearance-none rounded-xl border border-gray-200 bg-white py-2.5 pl-3.5 pr-10 text-sm font-semibold text-gray-700 shadow-sm outline-none transition-all hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
          >
            <option value="Pending">Pending</option>
            <option value="Partially Approved">
              Partially Approved
            </option>
            <option value="Approved">Approved</option>
            <option value="Rejected">Rejected</option>
          </select>

          <span
            class="pointer-events-none absolute inset-y-0 right-0 flex w-10 items-center justify-center text-gray-400"
          >
            <i class="fa-solid fa-chevron-down text-[10px]"></i>
          </span>
        </div>

        <!-- Current Status Preview -->
        <div class="mt-2">
          <span
            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-bold"
            :class="{
              'bg-amber-100 text-amber-700':
                form.status === 'Pending',

              'bg-[#95CCDD]/40 text-[#293681]':
                form.status === 'Partially Approved',

              'bg-emerald-100 text-emerald-700':
                form.status === 'Approved',

              'bg-rose-100 text-rose-700':
                form.status === 'Rejected'
            }"
          >
            <span
              class="h-1.5 w-1.5 rounded-full bg-current"
            ></span>

            {{ form.status }}
          </span>
        </div>
      </div>
    </form>

    <!-- Footer -->
    <template #footer>
      <div
        class="flex w-full flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between"
      >
        <!-- Delete -->
        <button
          v-if="isEdit && $can('promotion.delete')"
          type="button"
          @click="confirmDelete"
          class="inline-flex items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-extrabold text-rose-600 transition-all duration-200 hover:border-rose-300 hover:bg-rose-100 focus:outline-none disabled:cursor-not-allowed disabled:opacity-60"
        >
          <i class="fa-regular fa-trash-can text-[11px]"></i>
          <span>Delete Promotion</span>
        </button>

        <div
          class="flex w-full flex-col-reverse gap-2 sm:w-auto sm:flex-row"
        >
          <!-- Cancel -->
          <button
            type="button"
            @click="closeModal"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-600 shadow-sm transition-all duration-200 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800 focus:outline-none"
          >
            Cancel
          </button>

          <!-- Save -->
          <button
            v-if="isEdit ? $can('promotion.update') : $can('promotion.create')"
            type="submit"
            form="promotion-form"
            :disabled="loading"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#293681] px-5 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#4274D9] hover:shadow-md focus:outline-none focus:ring-4 focus:ring-[#4274D9]/20 disabled:cursor-not-allowed disabled:opacity-60"
          >
            <i
              v-if="!loading"
              class="fa-solid fa-check text-[10px]"
            ></i>

            <i
              v-else
              class="fa-solid fa-spinner animate-spin text-[10px]"
            ></i>

            <span>
              {{ loading ? 'Saving...' : 'Save Promotion' }}
            </span>
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
  defaultCampaignId: {
    type: String,
    default: null
  },
});

const emit = defineEmits(['close', 'saved']);

const {
  createPromotion,
  updatePromotion,
  deletePromotion,
  loading,
  error
} = usePromotions();

const {
  campaigns,
  fetchCampaigns
} = useCampaigns();

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
    await fetchCampaigns({
      per_page: 100
    });

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

const hasError = (field) =>
  error.value &&
  typeof error.value === 'object' &&
  error.value[field];

const getError = (field) =>
  hasError(field)
    ? error.value[field][0]
    : '';

const submit = async () => {
  let result = false;

  if (isEdit.value) {
    result = await updatePromotion(
      props.promotion.id,
      form.value
    );
  } else {
    result = await createPromotion(form.value);
  }

  if (result) {
    emit('saved', result);
    closeModal();
  }
};

const confirmDelete = async () => {
  if (
    confirm(
      `Are you sure you want to delete promotion "${props.promotion.name}"? This action cannot be undone.`
    )
  ) {
    const success = await deletePromotion(
      props.promotion.id
    );

    if (success) {
      emit('saved');
      closeModal();
    }
  }
};

const closeModal = () => emit('close');
</script>
