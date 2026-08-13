<template>
  <ModalForm
    :is-open="isOpen"
    :title="isEdit ? 'Edit Brand' : 'Create Brand'"
    @close="closeModal"
  >
    <form
      id="brand-form"
      @submit.prevent="submit"
      class="space-y-6"
    >
      <!-- Error Alert -->
      <div
        v-if="typeof error === 'string'"
        class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3.5"
      >
        <div
          class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600"
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
              d="M12 9v4m0 4h.01M10.29 3.86l-7.5 13A2 2 0 004.53 20h14.94a2 2 0 001.74-3.14l-7.5-13a2 2 0 00-3.42 0z"
            />
          </svg>
        </div>

        <div>
          <p class="text-xs font-bold uppercase tracking-wide text-red-700">
            Unable to save
          </p>
          <p class="mt-0.5 text-sm text-red-600">
            {{ error }}
          </p>
        </div>
      </div>

      <!-- Company Section -->
      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <label
            for="company"
            class="text-sm font-bold text-gray-800"
          >
            Company
            <span class="ml-0.5 text-red-500">*</span>
          </label>

          <span
            v-if="isEdit"
            class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-gray-500"
          >
            Locked
          </span>
        </div>

        <div class="relative">
          <div
            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400"
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
                d="M3 21h18M5 21V5a2 2 0 012-2h10a2 2 0 012 2v16M9 7h1m-1 4h1m4-4h1m-1 4h1M9 21v-5h6v5"
              />
            </svg>
          </div>

          <select
            id="company"
            v-model="form.company_id"
            required
            :disabled="isEdit"
            @change="onCompanyChange"
            class="block w-full appearance-none rounded-xl border border-gray-200 bg-gray-50 py-3 pl-11 pr-10 text-sm font-medium text-gray-800 outline-none transition-all focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-500"
            :class="
              hasError('company_id')
                ? 'border-red-300 focus:border-red-500 focus:ring-red-500/10'
                : ''
            "
          >
            <option value="" disabled>
              Select company...
            </option>

            <option
              v-for="company in companies"
              :key="company.id"
              :value="company.id"
            >
              {{ company.name }}
            </option>
          </select>

          <div
            class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400"
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

        <p
          v-if="hasError('company_id')"
          class="flex items-center gap-1.5 text-xs font-medium text-red-600"
        >
          <svg
            class="h-3.5 w-3.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-width="2"
              d="M12 9v3m0 3h.01"
            />
          </svg>
          {{ getError('company_id') }}
        </p>

        <p
          v-else-if="isEdit"
          class="text-[11px] text-gray-400"
        >
          Company tidak dapat diubah saat mengedit brand.
        </p>
      </div>

      <!-- Brand Name Section -->
      <div class="space-y-2">
        <label
          for="brand-name"
          class="text-sm font-bold text-gray-800"
        >
          Brand Name
          <span class="ml-0.5 text-red-500">*</span>
        </label>

        <div class="relative">
          <div
            class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400"
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
                d="M7 7h.01M3 6.2v5.1a2 2 0 00.59 1.41l6.7 6.7a2 2 0 002.82 0l5.1-5.1a2 2 0 000-2.82l-6.7-6.7A2 2 0 0010.1 4H5a2 2 0 00-2 2v.2z"
              />
            </svg>
          </div>

          <input
            id="brand-name"
            type="text"
            v-model="form.name"
            required
            placeholder="Enter brand name"
            class="block w-full rounded-xl border border-gray-200 bg-gray-50 py-3 pl-11 pr-4 text-sm font-medium text-gray-900 outline-none placeholder:text-gray-400 transition-all focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10"
            :class="
              hasError('name')
                ? 'border-red-300 focus:border-red-500 focus:ring-red-500/10'
                : ''
            "
          />
        </div>

        <p
          v-if="hasError('name')"
          class="flex items-center gap-1.5 text-xs font-medium text-red-600"
        >
          <svg
            class="h-3.5 w-3.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-width="2"
              d="M12 9v3m0 3h.01"
            />
          </svg>
          {{ getError('name') }}
        </p>
      </div>

      <!-- Existing Brands -->
      <div
        v-if="selectedCompanyBrands.length > 0"
        class="rounded-2xl border border-gray-200 bg-gray-50/70 p-4"
      >
        <div class="mb-3 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div
              class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-600"
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
                  d="M7 7h.01M3 6.2v5.1a2 2 0 00.59 1.41l6.7 6.7a2 2 0 002.82 0l5.1-5.1a2 2 0 000-2.82l-6.7-6.7A2 2 0 0010.1 4H5a2 2 0 00-2 2v.2z"
                />
              </svg>
            </div>

            <div>
              <p class="text-xs font-bold text-gray-800">
                Existing Brands
              </p>
              <p class="text-[11px] text-gray-400">
                Brands registered under this company
              </p>
            </div>
          </div>

          <span
            class="rounded-full bg-white px-2.5 py-1 text-[10px] font-bold text-gray-500 shadow-sm ring-1 ring-gray-200"
          >
            {{ selectedCompanyBrands.length }}
          </span>
        </div>

        <div class="flex flex-wrap gap-2">
          <span
            v-for="brand in selectedCompanyBrands"
            :key="brand.id"
            class="inline-flex items-center rounded-lg border border-blue-100 bg-white px-2.5 py-1.5 text-xs font-semibold text-blue-700 shadow-sm"
          >
            <span
              class="mr-2 h-1.5 w-1.5 rounded-full bg-blue-500"
            ></span>
            {{ brand.name }}
          </span>
        </div>
      </div>

      <!-- Empty Company State -->
      <div
        v-else-if="form.company_id"
        class="rounded-2xl border border-dashed border-gray-200 bg-gray-50/50 px-4 py-5 text-center"
      >
        <div
          class="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-gray-400"
        >
          <svg
            class="h-4 w-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-width="1.8"
              d="M7 7h.01M3 6.2v5.1a2 2 0 00.59 1.41l6.7 6.7a2 2 0 002.82 0l5.1-5.1a2 2 0 000-2.82l-6.7-6.7A2 2 0 0010.1 4H5a2 2 0 00-2 2v.2z"
            />
          </svg>
        </div>

        <p class="text-xs font-semibold text-gray-600">
          No existing brands
        </p>

        <p class="mt-0.5 text-[11px] text-gray-400">
          This will be the first brand for this company.
        </p>
      </div>
    </form>

    <template #footer>
      <div
        class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-end"
      >
        <button
          type="button"
          @click="closeModal"
          class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 transition-all hover:border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-500/10 sm:w-auto"
        >
          Cancel
        </button>

        <button
          type="submit"
          form="brand-form"
          :disabled="loading"
          class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
        >
          <svg
            v-if="loading"
            class="h-4 w-4 animate-spin"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
            />
          </svg>

          <svg
            v-else
            class="h-4 w-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M5 13l4 4L19 7"
            />
          </svg>

          <span>
            {{ loading ? "Saving..." : isEdit ? "Update Brand" : "Save Brand" }}
          </span>
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

  const company = companies.value.find(
    c => c.id === form.value.company_id
  );

  return company ? company.brands : [];
});

// Fetch companies on mount
onMounted(() => {
  fetchCompanies();
});

watch(
  () => props.isOpen,
  async (newVal) => {
    if (newVal) {
      await fetchCompanies();

      if (props.brand) {
        isEdit.value = true;

        form.value = {
          name: props.brand.name,
          company_id:
            props.brand.company_id ||
            authStore.user?.company_id ||
            ''
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
  }
);

const onCompanyChange = () => {
  // Optional: auto-fill brand name prefix based on company
};

const hasError = (field) => {
  return (
    error.value &&
    typeof error.value === 'object' &&
    error.value[field]
  );
};

const getError = (field) => {
  return hasError(field)
    ? error.value[field][0]
    : '';
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