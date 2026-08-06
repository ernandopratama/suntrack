<template>
  <ModalForm
    :is-open="isOpen"
    :title="isEdit ? 'Edit Campaign' : 'Create Campaign'"
    @close="closeModal"
  >
    <form
      id="campaign-form"
      class="space-y-4"
      @submit.prevent="submit"
    >
      <!-- Global Error -->
      <div
        v-if="typeof error === 'string'"
        class="rounded-md bg-red-50 p-4"
      >
        <p class="text-sm text-red-700">
          {{ error }}
        </p>
      </div>

      <!-- Brand Searchable Dropdown -->
      <div class="relative">
        <label class="block text-sm font-medium text-gray-700">
          Brand
          <span class="text-red-500">*</span>
        </label>

        <input
          ref="brandInputRef"
          type="text"
          :value="brandDisplay"
          @input="onBrandSearch"
          @focus="brandDropdownOpen = true"
          @blur="onBrandBlur"
          placeholder="Search brand..."
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
        />

        <!-- Dropdown -->
        <div
          v-if="brandDropdownOpen && filteredBrands.length > 0"
          class="absolute z-10 mt-1 w-full rounded-md border border-gray-300 bg-white shadow-lg max-h-48 overflow-y-auto"
        >
          <button
            type="button"
            v-for="b in filteredBrands"
            :key="b.id"
            class="w-full px-3 py-2 text-left text-sm hover:bg-blue-50 hover:text-blue-700 focus:bg-blue-50 focus:text-blue-700"
            :class="{ 'bg-blue-50 text-blue-700': selectedBrandId === b.id }"
            @mousedown.prevent="selectBrand(b)"
          >
            {{ b.name }}
          </button>
        </div>

        <p
          v-if="hasError('brand_id')"
          class="mt-1 text-xs text-red-600"
        >
          {{ getError('brand_id') }}
        </p>
      </div>

      <!-- Name -->
      <div>
        <label class="block text-sm font-medium text-gray-700">
          Name
          <span class="text-red-500">*</span>
        </label>

        <input
          v-model="form.name"
          type="text"
          required
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
        >

        <p
          v-if="hasError('name')"
          class="mt-1 text-xs text-red-600"
        >
          {{ getError('name') }}
        </p>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700">
          Description
        </label>

        <textarea
          v-model="form.description"
          rows="3"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
        />
      </div>

      <!-- Dates -->
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">
            Start Date
          </label>

          <input
            v-model="form.start_date"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">
            End Date
          </label>

          <input
            v-model="form.end_date"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
          >
        </div>
      </div>

      <!-- Deadline + Status -->
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="block text-sm font-medium text-gray-700">
            Deadline
          </label>

          <input
            v-model="form.deadline"
            type="date"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">
            Status
            <span class="text-red-500">*</span>
          </label>

          <select
            v-model="form.status"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
          >
            <option value="Draft">Draft</option>
            <option value="Waiting Approval">Waiting Approval</option>
            <option value="Approved">Approved</option>
            <option value="Running">Running</option>
            <option value="Finished">Finished</option>
            <option value="Cancelled">Cancelled</option>
          </select>

          <p
            v-if="hasError('status')"
            class="mt-1 text-xs text-red-600"
          >
            {{ getError('status') }}
          </p>
        </div>
      </div>
    </form>

    <template #footer>
      <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <button
          type="button"
          class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          @click="closeModal"
        >
          Cancel
        </button>

        <button
          type="submit"
          form="campaign-form"
          :disabled="loading"
          class="inline-flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
        >
          {{ loading ? 'Saving...' : 'Save Campaign' }}
        </button>
      </div>
    </template>
  </ModalForm>
</template>

<script setup>
import { reactive, ref, watch, computed, onMounted } from 'vue';
import ModalForm from './ModalForm.vue';
import { useCampaigns } from '../composables/useCampaigns';
import { useBrands } from '../composables/useBrands';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  campaign: {
    type: Object,
    default: null
  }
});

const emit = defineEmits([
  'close',
  'saved'
]);

const {
  createCampaign,
  updateCampaign,
  loading,
  error
} = useCampaigns();

const { brands, fetchBrands } = useBrands();

const isEdit = ref(false);
const selectedBrandId = ref(null);
const brandSearch = ref('');
const brandDropdownOpen = ref(false);
const brandInputRef = ref(null);

const defaultForm = () => ({
  name: '',
  description: '',
  start_date: '',
  end_date: '',
  deadline: '',
  status: 'Draft',
  brand_id: null
});

const form = reactive(defaultForm());

// Computed: brand display text
const brandDisplay = computed(() => {
  if (!selectedBrandId.value) return '';
  const found = brands.value.find(b => b.id === selectedBrandId.value);
  return found ? found.name : '';
});

// Computed: filtered brands for dropdown
const filteredBrands = computed(() => {
  if (!brandSearch.value) return brands.value;
  const q = brandSearch.value.toLowerCase();
  return brands.value.filter(b => b.name.toLowerCase().includes(q));
});

// Fetch brands when modal opens
watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      fetchBrands({ page: 1, per_page: 100 });
    }
  }
);

watch(
  () => [props.isOpen, props.campaign],
  ([open]) => {
    if (!open) return;

    Object.assign(form, defaultForm());
    selectedBrandId.value = null;
    brandSearch.value = '';

    if (props.campaign) {
      isEdit.value = true;
      Object.assign(form, props.campaign);
      if (props.campaign.brand_id) {
        selectedBrandId.value = props.campaign.brand_id;
      }
    } else {
      isEdit.value = false;
    }

    if (error.value) {
      error.value = null;
    }
  },
  {
    immediate: true
  }
);

const onBrandSearch = (e) => {
  brandSearch.value = e.target.value;
  brandDropdownOpen.value = true;
  selectedBrandId.value = null;
};

const onBrandBlur = () => {
  // Delay to allow click on dropdown item
  setTimeout(() => {
    brandDropdownOpen.value = false;
  }, 200);
};

const selectBrand = (brand) => {
  selectedBrandId.value = brand.id;
  form.brand_id = brand.id;
  brandSearch.value = brand.name;
  brandDropdownOpen.value = false;
};

const hasError = (field) => {
  return (
    error.value &&
    typeof error.value === 'object' &&
    error.value[field]
  );
};

const getError = (field) => {
  if (!hasError(field)) return '';
  return Array.isArray(error.value[field])
    ? error.value[field][0]
    : error.value[field];
};

const submit = async () => {
  if (!form.brand_id) {
    error.value = 'Please select a brand.';
    return;
  }

  let success = false;
  try {
    if (isEdit.value) {
      success = await updateCampaign(props.campaign.id, { ...form });
    } else {
      success = await createCampaign({ ...form });
    }

    if (success) {
      emit('saved');
      closeModal();
    }
  } finally {
    // no-op
  }
};

const closeModal = () => {
  emit('close');
};
</script>