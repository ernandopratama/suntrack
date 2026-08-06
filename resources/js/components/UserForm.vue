<template>
  <ModalForm :is-open="isOpen" :title="isEdit ? 'Edit User' : 'Create User'" @close="closeModal">
    <form id="user-form" @submit.prevent="submit" class="space-y-4">
      <div v-if="typeof error === 'string'" class="rounded-md bg-red-50 p-4">
        <p class="text-sm text-red-700">{{ error }}</p>
      </div>

      <!-- Type Selection -->
      <div v-if="!isEdit">
        <label class="block text-sm font-medium text-gray-700">User Type <span class="text-red-500">*</span></label>
        <div class="mt-2 grid grid-cols-2 gap-3">
          <button
            type="button"
            class="flex items-center justify-center px-4 py-3 border-2 rounded-lg text-sm font-medium transition-colors"
            :class="form.type === 'admin'
              ? 'border-blue-500 bg-blue-50 text-blue-700'
              : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'"
            @click="form.type = 'admin'"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Admin
          </button>
          <button
            type="button"
            class="flex items-center justify-center px-4 py-3 border-2 rounded-lg text-sm font-medium transition-colors"
            :class="form.type === 'company'
              ? 'border-blue-500 bg-blue-50 text-blue-700'
              : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'"
            @click="form.type = 'company'"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            Company
          </button>
        </div>
      </div>

      <!-- Type Badge (Edit mode) -->
      <div v-else>
        <label class="block text-sm font-medium text-gray-700">User Type</label>
        <span class="inline-flex items-center mt-1 px-3 py-1 rounded-full text-sm font-medium"
          :class="form.type === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-700'"
        >
          {{ form.type === 'admin' ? 'Admin' : 'Company' }}
        </span>
      </div>

      <!-- Name -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
        <input type="text" v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
        <p v-if="hasError('name')" class="mt-1 text-xs text-red-600">{{ getError('name') }}</p>
      </div>

      <!-- Email (hanya untuk Admin) -->
      <div v-if="form.type === 'admin'">
        <label class="block text-sm font-medium text-gray-700">Email <span class="text-red-500">*</span></label>
        <input type="email" v-model="form.email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
        <p v-if="hasError('email')" class="mt-1 text-xs text-red-600">{{ getError('email') }}</p>
      </div>

      <!-- Password (hanya untuk Admin) -->
      <div v-if="form.type === 'admin'">
        <label class="block text-sm font-medium text-gray-700">
          Password {{ isEdit ? '(kosongkan jika tidak diubah)' : '' }}
          <span v-if="!isEdit" class="text-red-500">*</span>
        </label>
        <input type="password" v-model="form.password" :required="!isEdit && form.type === 'admin'" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
        <p v-if="hasError('password')" class="mt-1 text-xs text-red-600">{{ getError('password') }}</p>
      </div>

      <!-- Info untuk Company user -->
      <div v-if="form.type === 'company' && !isEdit" class="rounded-md bg-blue-50 p-3">
        <p class="text-xs text-blue-700">
          <strong>Company user</strong> tidak perlu email & password. Mereka hanya bisa mengakses halaman public review melalui link yang dibagikan.
        </p>
      </div>
    </form>

    <template #footer>
      <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <button type="button" class="inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="closeModal">Cancel</button>
        <button type="submit" form="user-form" :disabled="loading" class="inline-flex justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60">
          {{ loading ? 'Saving...' : 'Save User' }}
        </button>
      </div>
    </template>
  </ModalForm>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import ModalForm from './ModalForm.vue';
import { useUsers } from '../composables/useUsers';
import { useAuthStore } from '../stores/auth';

const props = defineProps({
  isOpen: { type: Boolean, required: true },
  user: { type: Object, default: null }
});

const emit = defineEmits(['close', 'saved']);

const { createUser, updateUser, loading, error } = useUsers();
const authStore = useAuthStore();

const isEdit = ref(false);

const defaultForm = () => ({
  type: 'admin',
  name: '',
  email: '',
  password: '',
  company_id: authStore.user?.company_id || ''
});

const form = reactive(defaultForm());

watch(() => [props.isOpen, props.user], ([open]) => {
  if (!open) return;
  Object.assign(form, defaultForm());

  if (props.user) {
    isEdit.value = true;
    form.type = props.user.type || 'admin';
    form.name = props.user.name;
    form.email = props.user.email || '';
    form.password = '';
  } else {
    isEdit.value = false;
  }
  error.value = null;
}, { immediate: true });

const hasError = (field) => error.value && typeof error.value === 'object' && error.value[field];
const getError = (field) => hasError(field) ? (Array.isArray(error.value[field]) ? error.value[field][0] : error.value[field]) : '';

const submit = async () => {
  let success = false;
  const payload = {
    type: form.type,
    name: form.name,
    company_id: form.company_id
  };

  if (form.type === 'admin') {
    payload.email = form.email;
    if (form.password) payload.password = form.password;
  }

  if (isEdit.value) {
    success = await updateUser(props.user.id, payload);
  } else {
    success = await createUser(payload);
  }

  if (success) {
    emit('saved');
    closeModal();
  }
};

const closeModal = () => emit('close');
</script>