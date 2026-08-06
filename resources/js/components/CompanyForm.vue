<template>
  <ModalForm :is-open="isOpen" :title="isEdit ? 'Edit Company' : 'Create Company'" @close="closeModal">
    <form id="company-form" @submit.prevent="submit" class="space-y-4">
      <div v-if="typeof error === 'string'" class="rounded-md bg-red-50 p-4">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Company Name <span class="text-red-500">*</span></label>
        <input type="text" v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" placeholder="Enter company name" />
        <p v-if="hasError('name')" class="mt-1 text-xs text-red-600">{{ getError('name') }}</p>
      </div>
    </form>

    <template #footer>
      <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <button type="button" class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="closeModal">Cancel</button>
        <button type="submit" form="company-form" :disabled="loading" class="inline-flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60">
          {{ loading ? 'Saving...' : 'Save Company' }}
        </button>
      </div>
    </template>
  </ModalForm>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import ModalForm from './ModalForm.vue';
import { useCompanies } from '../composables/useCompanies';

const props = defineProps({
  isOpen: { type: Boolean, required: true },
  company: { type: Object, default: null }
});

const emit = defineEmits(['close', 'saved']);

const { createCompany, updateCompany, loading, error } = useCompanies();

const isEdit = ref(false);
const form = reactive({ name: '' });

watch(() => [props.isOpen, props.company], ([open]) => {
  if (!open) return;
  if (props.company) {
    isEdit.value = true;
    form.name = props.company.name;
  } else {
    isEdit.value = false;
    form.name = '';
  }
  error.value = null;
}, { immediate: true });

const hasError = (field) => error.value && typeof error.value === 'object' && error.value[field];
const getError = (field) => hasError(field) ? (Array.isArray(error.value[field]) ? error.value[field][0] : error.value[field]) : '';

const submit = async () => {
  let success = false;
  if (isEdit.value) {
    success = await updateCompany(props.company.id, { name: form.name });
  } else {
    success = await createCompany({ name: form.name });
  }
  if (success) {
    emit('saved');
    closeModal();
  }
};

const closeModal = () => emit('close');
</script>