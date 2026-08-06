<template>
  <ModalForm :is-open="isOpen" :title="isEdit ? 'Edit Brand' : 'Create Brand'" @close="closeModal">
    <form id="brand-form" @submit.prevent="submit" class="space-y-4">
      <div v-if="typeof error === 'string'" class="bg-red-50 p-4 rounded-md">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>

      <!-- Company Dropdown -->
      <div>
        <label class="block text-sm font-medium text-gray-700">
          Company <span class="text-red-500">*</span>
        </label>
        <select
          v-model="form.company_id"
          required
          :disabled="isEdit"
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
          @change="onCompanyChange"
        >
          <option value="" disabled>Select company...</option>
          <option
            v-for="company in companies"
            :key="company.id"
            :value="company.id"
          >
            {{ company.name }}
          </option>
        </select>
        <p v-if="hasError('company_id')" class="mt-1 text-xs text-red-600">{{ getError('company_id') }}</p>
      </div>

      <!-- Brand Name -->
      <div>
        <label class="block text-sm font-medium text-gray-700">
          Brand Name <span class="text-red-500">*</span>
        </label>
        <input
          type="text"
          v-model="form.name"
          required
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
          placeholder="Enter brand name"
        />
        <p v-if="hasError('name')" class="mt-1 text-xs text-red-600">{{ getError('name') }}</p>
      </div>

      <!-- Existing Brands in Selected Company -->
      <div
        v-if="selectedCompanyBrands.length > 0"
        class="rounded-md bg-gray-50 p-3"
      >
        <p class="text-xs font-medium text-gray-500 mb-2">
          Existing brands in this company:
        </p>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="brand in selectedCompanyBrands"
            :key="brand.id"
            class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-blue-100 text-blue-700"
          >
            {{ brand.name }}
          </span>
        </div>
      </div>
    </form>

    <template #footer>
      <div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse w-full">
        <button
          type="submit"
          form="brand-form"
          :disabled="loading"
          class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm transition-colors"
        >
          {{ loading ? 'Saving...' : 'Save Brand' }}
        </button>
        <button
          type="button"
          @click="closeModal"
          class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors"
        >
          Cancel
        </button>
      </div>
    </template>
  </ModalForm>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import ModalForm from './ModalForm.vue';
import { useBrands } from '../composables/useBrands';
import { useCompanies } from '../composables/useCompanies';
import { useAuthStore } from '../stores/auth';

const props = defineProps({
  isOpen: { type: Boolean, required: true },
  brand: { type: Object, default: null }
});

const emit = defineEmits(['close', 'saved']);

const { createBrand, updateBrand, loading, error } = useBrands();
const { companies, fetchCompanies } = useCompanies();
const authStore = useAuthStore();

const isEdit = ref(false);
const form = ref({
  name: '',
  company_id: ''
});

// Computed: brands that belong to the selected company
const selectedCompanyBrands = computed(() => {
  if (!form.value.company_id) return [];
  const company = companies.value.find(c => c.id === form.value.company_id);
  return company ? company.brands : [];
});

// Fetch companies on mount
onMounted(() => {
  fetchCompanies();
});

watch(() => props.isOpen, async (newVal) => {
  if (newVal) {
    await fetchCompanies(); // panggil dulu

    if (props.brand) {
      isEdit.value = true;
      form.value = {
        name: props.brand.name,
        company_id: props.brand.company_id || authStore.user?.company_id || ''
      };
    } else {
      isEdit.value = false;
      form.value = {
        name: '',
        company_id: authStore.user?.company_id || ''
      };
    }
    error.value = null;
  }
});

const onCompanyChange = () => {
  // Optional: auto-fill brand name prefix based on company
};

const hasError = (field) => {
  return error.value && typeof error.value === 'object' && error.value[field];
};

const getError = (field) => {
  return hasError(field) ? error.value[field][0] : '';
};

const submit = async () => {
  let success = false;
  if (isEdit.value) {
    success = await updateBrand(props.brand.id, {
      name: form.value.name
    });
  } else {
    success = await createBrand({
      name: form.value.name,
      company_id: form.value.company_id
    });
  }

  if (success) {
    emit('saved');
    closeModal();
  }
};

const closeModal = () => {
  emit('close');
};
</script>