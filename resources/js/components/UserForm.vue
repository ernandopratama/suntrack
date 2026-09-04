<template>
  <ModalForm
    :is-open="isOpen"
    :title="isEdit ? 'Edit User & Hak Akses' : 'Tambah User'"
    @close="closeModal"
  >
    <form id="user-form" class="space-y-5" @submit.prevent="submit">
      <div
        v-if="errorMessage"
        class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
      >
        <p class="font-semibold">Unable to save user</p>
        <p class="mt-1 text-xs">{{ errorMessage }}</p>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <label class="space-y-1.5 sm:col-span-2">
          <span class="text-xs font-semibold text-content">Role</span>
          <select
            v-model="form.role"
            :disabled="!authStore.hasRole('Super Admin')"
            class="w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content outline-none focus:border-brand"
          >
            <option v-for="role in options.roles" :key="role" :value="role">
              {{ role }}
            </option>
          </select>
          <span v-if="!authStore.hasRole('Super Admin')" class="text-xs text-content-muted">
            Admin hanya dapat membuat dan mengedit user Tim.
          </span>
        </label>

        <label class="space-y-1.5 sm:col-span-2">
          <span class="text-xs font-semibold text-content">Nama</span>
          <input
            v-model="form.name"
            required
            class="w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content outline-none focus:border-brand"
          />
          <span v-if="fieldError('name')" class="text-xs text-rose-600">{{ fieldError('name') }}</span>
        </label>

        <label class="space-y-1.5">
          <span class="text-xs font-semibold text-content">Username</span>
          <input
            v-model="form.username"
            required
            autocomplete="username"
            class="w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm lowercase text-content outline-none focus:border-brand"
          />
          <span v-if="fieldError('username')" class="text-xs text-rose-600">{{ fieldError('username') }}</span>
        </label>

        <label class="space-y-1.5">
          <span class="text-xs font-semibold text-content">Email</span>
          <input
            v-model="form.email"
            required
            type="email"
            class="w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content outline-none focus:border-brand"
          />
          <span v-if="fieldError('email')" class="text-xs text-rose-600">{{ fieldError('email') }}</span>
        </label>

        <div class="space-y-1.5 sm:col-span-2">
          <label for="user-password" class="block text-xs font-semibold text-content">
            Password {{ isEdit ? '(opsional)' : '' }}
          </label>
          <div class="relative">
            <input
              id="user-password"
              v-model="form.password"
              :required="!isEdit"
              :type="showUserPassword ? 'text' : 'password'"
              autocomplete="new-password"
              class="w-full rounded-xl border border-default bg-surface py-2.5 pl-3 pr-11 text-sm text-content outline-none focus:border-brand"
            />
            <button
              type="button"
              class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-content-muted transition hover:text-brand"
              :aria-label="showUserPassword ? 'Sembunyikan password' : 'Tampilkan password'"
              :aria-pressed="showUserPassword"
              @click="showUserPassword = !showUserPassword"
            >
              <i :class="showUserPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
            </button>
          </div>
          <span v-if="fieldError('password')" class="text-xs text-rose-600">{{ fieldError('password') }}</span>
        </div>
      </div>

      <template v-if="form.role === 'Tim'">
        <section class="rounded-2xl border border-default bg-surface-muted p-4">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h3 class="text-sm font-semibold text-content">Permission Tim</h3>
              <p class="mt-1 text-xs text-content-muted">Aktifkan tindakan operasional yang dibutuhkan user.</p>
            </div>
            <button type="button" class="text-xs font-semibold text-brand" @click="toggleAllPermissions">
              {{ allPermissionsSelected ? 'Kosongkan' : 'Pilih semua' }}
            </button>
          </div>

          <div class="mt-4 grid gap-2 sm:grid-cols-2">
            <label
              v-for="permission in options.team_permissions"
              :key="permission"
              class="flex items-center gap-2 rounded-xl border border-default bg-surface px-3 py-2 text-xs text-content-soft"
            >
              <input v-model="form.permissions" type="checkbox" :value="permission" class="accent-blue-600" />
              <span>{{ permissionLabel(permission) }}</span>
            </label>
          </div>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
          <section class="rounded-2xl border border-default bg-surface p-4">
            <h3 class="text-sm font-semibold text-content">Company Assignment</h3>
            <p class="mt-1 text-xs text-content-muted">Company membuka seluruh Brand di bawahnya.</p>
            <div class="mt-3 max-h-44 space-y-2 overflow-y-auto pr-1">
              <label
                v-for="company in options.companies"
                :key="company.id"
                class="flex items-center gap-2 rounded-lg bg-surface-muted px-3 py-2 text-xs text-content-soft"
              >
                <input v-model="form.company_ids" type="checkbox" :value="company.id" class="accent-blue-600" />
                <span>{{ company.name }}</span>
              </label>
              <p v-if="!options.companies.length" class="text-xs text-content-muted">Belum ada Company.</p>
            </div>
          </section>

          <section class="rounded-2xl border border-default bg-surface p-4">
            <h3 class="text-sm font-semibold text-content">Brand Assignment</h3>
            <p class="mt-1 text-xs text-content-muted">Brand langsung tidak membuka Brand lain.</p>
            <div class="mt-3 max-h-44 space-y-2 overflow-y-auto pr-1">
              <label
                v-for="brand in options.brands"
                :key="brand.id"
                class="flex items-center gap-2 rounded-lg bg-surface-muted px-3 py-2 text-xs text-content-soft"
              >
                <input v-model="form.brand_ids" type="checkbox" :value="brand.id" class="accent-blue-600" />
                <span>{{ brand.name }} · {{ brand.company?.name || 'Tanpa Company' }}</span>
              </label>
              <p v-if="!options.brands.length" class="text-xs text-content-muted">Belum ada Brand.</p>
            </div>
          </section>
        </div>

        <section class="rounded-2xl border border-brand/20 bg-brand-soft p-4">
          <h3 class="text-sm font-semibold text-brand-strong">Ringkasan Cakupan</h3>
          <div class="mt-3 grid grid-cols-2 gap-3 text-center text-xs">
            <div class="rounded-xl bg-surface px-3 py-3">
              <strong class="block text-lg text-content">{{ form.company_ids.length }}</strong>
              <span class="text-content-muted">Company langsung</span>
            </div>
            <div class="rounded-xl bg-surface px-3 py-3">
              <strong class="block text-lg text-content">{{ form.brand_ids.length }}</strong>
              <span class="text-content-muted">Brand langsung</span>
            </div>
          </div>
          <p v-if="user?.effective_scope" class="mt-3 text-xs text-content-soft">
            Cakupan efektif tersimpan: {{ user.effective_scope.company_ids?.length || 0 }} Company,
            {{ user.effective_scope.brand_ids?.length || 0 }} Brand.
          </p>
        </section>
      </template>

      <section v-if="isEdit" class="rounded-2xl border border-default bg-surface p-4">
        <h3 class="text-sm font-semibold text-content">Riwayat Perubahan Akses</h3>
        <div v-if="user?.access_history?.length" class="mt-3 space-y-3">
          <div v-for="item in user.access_history" :key="item.id" class="border-l-2 border-brand pl-3">
            <p class="text-xs font-medium text-content">{{ item.description }}</p>
            <p class="mt-1 text-[11px] text-content-muted">
              {{ item.actor_name || 'System' }} · {{ formatDate(item.created_at) }}
            </p>
          </div>
        </div>
        <p v-else class="mt-2 text-xs text-content-muted">Belum ada perubahan akses tercatat.</p>
      </section>
    </form>

    <template #footer>
      <button
        type="button"
        class="rounded-xl border border-default bg-surface px-4 py-2.5 text-sm font-semibold text-content-soft"
        @click="closeModal"
      >
        Batal
      </button>
      <button
        type="submit"
        form="user-form"
        :disabled="saving || optionsLoading"
        class="rounded-xl bg-brand px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
      >
        {{ saving ? 'Menyimpan...' : 'Simpan User' }}
      </button>
    </template>
  </ModalForm>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import api from '../utils/api';
import { useAuthStore } from '../stores/auth';
import ModalForm from './ModalForm.vue';

const props = defineProps({
  isOpen: { type: Boolean, default: false },
  user: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);
const authStore = useAuthStore();
const saving = ref(false);
const optionsLoading = ref(false);
const errors = ref({});
const errorMessage = ref('');
const showUserPassword = ref(false);

const options = reactive({ roles: ['Tim'], team_permissions: [], companies: [], brands: [] });
const form = reactive({
  role: 'Tim',
  name: '',
  username: '',
  email: '',
  password: '',
  permissions: [],
  company_ids: [],
  brand_ids: [],
});

const isEdit = computed(() => Boolean(props.user?.id));
const allPermissionsSelected = computed(
  () => options.team_permissions.length > 0 && form.permissions.length === options.team_permissions.length,
);

watch(
  () => props.isOpen,
  async (open) => {
    if (!open) return;
    await loadOptions();
    resetForm();
  },
);

const loadOptions = async () => {
  optionsLoading.value = true;
  try {
    const response = await api.get('/admin/access/options');
    Object.assign(options, response.data.data);
  } finally {
    optionsLoading.value = false;
  }
};

const resetForm = () => {
  errors.value = {};
  errorMessage.value = '';
  showUserPassword.value = false;
  const source = props.user;
  form.role = source?.roles?.[0] || options.roles[0] || 'Tim';
  form.name = source?.name || '';
  form.username = source?.username || '';
  form.email = source?.email || '';
  form.password = '';
  form.permissions = source
    ? [...(source.direct_permissions || source.permissions || [])]
    : [...options.team_permissions];
  form.company_ids = [...(source?.company_ids || [])];
  form.brand_ids = [...(source?.brand_ids || [])];
};

const submit = async () => {
  saving.value = true;
  errors.value = {};
  errorMessage.value = '';

  const payload = {
    role: form.role,
    name: form.name,
    username: form.username,
    email: form.email,
    permissions: form.role === 'Tim' ? form.permissions : undefined,
    company_ids: form.role === 'Tim' ? form.company_ids : undefined,
    brand_ids: form.role === 'Tim' ? form.brand_ids : undefined,
  };
  if (form.password) payload.password = form.password;

  try {
    if (isEdit.value) {
      await api.put(`/admin/users/${props.user.id}`, payload);
    } else {
      await api.post('/admin/users', payload);
    }
    emit('saved');
    closeModal();
  } catch (error) {
    errors.value = error.response?.data?.errors || {};
    errorMessage.value = error.response?.data?.message || 'Data user tidak dapat disimpan.';
  } finally {
    saving.value = false;
  }
};

const toggleAllPermissions = () => {
  form.permissions = allPermissionsSelected.value ? [] : [...options.team_permissions];
};

const fieldError = (field) => errors.value?.[field]?.[0] || '';
const permissionLabel = (permission) => permission.replace('.', ' · ').replaceAll('-', ' ');
const formatDate = (value) => value ? new Date(value).toLocaleString('id-ID') : '-';
const closeModal = () => {
  showUserPassword.value = false;
  emit('close');
};
</script>
