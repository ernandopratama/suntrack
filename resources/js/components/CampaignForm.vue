<template>
  <ModalForm
    :is-open="isOpen"
    :title="isEdit ? 'Ubah Kampanye' : 'Buat Kampanye'"
    @close="closeModal"
  >
    <form
      id="campaign-form"
      class="space-y-6"
      @submit.prevent="submit"
    >
      <!-- Global Error -->
      <div
        v-if="typeof error === 'string'"
        class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5"
      >
        <div
          class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600"
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
              d="M12 9v3.75m0 3.75h.007M10.29 3.86l-7.5 13A2 2 0 004.53 20h14.94a2 2 0 001.74-3.14l-7.5-13a2 2 0 00-3.42 0z"
            />
          </svg>
        </div>

        <div>
          <p class="text-xs font-bold uppercase tracking-wide text-rose-700">
            Tidak dapat menyimpan
          </p>
          <p class="mt-0.5 text-sm leading-5 text-rose-600">
            {{ error }}
          </p>
        </div>
      </div>

      <!-- Campaign Information -->
      <div>
        <div class="mb-4 flex items-center gap-3">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600"
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
                d="M13 10V3L4 14h7v7l9-11h-7z"
              />
            </svg>
          </div>

          <div>
            <h3 class="text-sm font-bold text-gray-900">
              Informasi Kampanye
            </h3>
            <p class="text-xs text-gray-500">
              Tentukan identitas kampanye dan brand terkait.
            </p>
          </div>
        </div>

        <div class="space-y-5">
          <!-- Brand Searchable Dropdown -->
          <div class="relative">
            <label
              class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600"
            >
              Brand
              <span class="text-rose-500">*</span>
            </label>

            <div class="relative">
              <div
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400"
              >
                <svg
                  class="h-4.5 w-4.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="m21 21-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"
                  />
                </svg>
              </div>

              <input
                ref="brandInputRef"
                type="text"
                :value="brandDisplay"
                @input="onBrandSearch"
                @focus="brandDropdownOpen = true"
                @blur="onBrandBlur"
                placeholder="Cari dan pilih brand..."
                class="block w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-10 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 hover:bg-white focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10"
              />

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
                    stroke-width="1.8"
                    d="m6 9 6 6 6-6"
                  />
                </svg>
              </div>
            </div>

            <!-- Dropdown -->
            <div
              v-if="brandDropdownOpen && filteredBrands.length > 0"
              class="absolute left-0 right-0 z-[100] mt-2 max-h-56 overflow-y-auto rounded-xl border border-gray-200 bg-white p-1.5 shadow-2xl shadow-gray-900/10"
            >
              <button
                v-for="b in filteredBrands"
                :key="b.id"
                type="button"
                class="flex w-full items-center rounded-lg px-3 py-2.5 text-left text-sm transition"
                :class="
                  selectedBrandId === b.id
                    ? 'bg-blue-50 text-blue-700'
                    : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'
                "
                @mousedown.prevent="selectBrand(b)"
              >
                <span
                  class="mr-3 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                  :class="
                    selectedBrandId === b.id
                      ? 'bg-blue-100 text-blue-700'
                      : 'bg-gray-100 text-gray-500'
                  "
                >
                  {{ b.name?.charAt(0)?.toUpperCase() }}
                </span>

                <span class="flex-1 truncate font-medium">
                  {{ b.name }}
                </span>

                <svg
                  v-if="selectedBrandId === b.id"
                  class="ml-2 h-4 w-4 text-blue-600"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="m5 12 4 4L19 6"
                  />
                </svg>
              </button>
            </div>

            <div
              v-else-if="brandDropdownOpen && brandSearch && !filteredBrands.length"
              class="absolute left-0 right-0 z-[100] mt-2 rounded-xl border border-gray-200 bg-white p-5 text-center shadow-2xl"
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
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="m21 21-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"
                  />
                </svg>
              </div>

              <p class="text-xs font-semibold text-gray-700">
                Brand tidak ditemukan
              </p>
              <p class="mt-0.5 text-[11px] text-gray-400">
                Coba gunakan nama brand lain.
              </p>
            </div>

            <p
              v-if="hasError('brand_id')"
              class="mt-1.5 text-xs font-medium text-rose-600"
            >
              {{ getError('brand_id') }}
            </p>
          </div>

          <!-- Campaign Name -->
          <div>
            <label
              class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600"
            >
              Nama Kampanye
              <span class="text-rose-500">*</span>
            </label>

            <input
              v-model="form.name"
              type="text"
              required
              placeholder="Masukkan nama kampanye"
              class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 hover:bg-white focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10"
            />

            <p
              v-if="hasError('name')"
              class="mt-1.5 text-xs font-medium text-rose-600"
            >
              {{ getError('name') }}
            </p>
          </div>

          <!-- Description -->
          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
              <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Tujuan</label>
              <input v-model="form.objective" type="text" placeholder="Masukkan tujuan kampanye" class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10" />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Prioritas</label>
              <select v-model="form.priority" class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10">
                <option value="normal">Normal</option>
                <option value="mid">Menengah</option>
                <option value="urgent">Mendesak</option>
              </select>
            </div>
          </div>

          <!-- Description -->
          <div>
            <div class="mb-1.5 flex items-center justify-between">
              <label
                class="block text-xs font-bold uppercase tracking-wide text-gray-600"
              >
                Deskripsi
              </label>

              <span class="text-[11px] font-medium text-gray-400">
                Opsional
              </span>
            </div>

            <textarea
              v-model="form.description"
              rows="4"
              placeholder="Jelaskan tujuan kampanye, detail promosi, atau catatan lainnya..."
              class="block w-full resize-none rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm leading-6 text-gray-900 outline-none transition placeholder:text-gray-400 hover:border-gray-300 hover:bg-white focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10"
            />
          </div>
        </div>
      </div>

      <!-- Schedule & Status -->
      <div class="border-t border-gray-100 pt-6">
        <div class="mb-4 flex items-center gap-3">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"
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
                d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"
              />
            </svg>
          </div>

          <div>
            <h3 class="text-sm font-bold text-gray-900">
              Jadwal dan Status
            </h3>
            <p class="text-xs text-gray-500">
              Atur tanggal kampanye dan status alur kerja saat ini.
            </p>
          </div>
        </div>

        <div class="space-y-5">
          <!-- Dates -->
          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
              <label
                class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600"
              >
                Tanggal Mulai
              </label>

              <input
                v-model="form.start_date"
                type="date"
                class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 outline-none transition hover:border-gray-300 hover:bg-white focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10"
              />
            </div>

            <div>
              <label
                class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600"
              >
                Tanggal Selesai
              </label>

              <input
                v-model="form.end_date"
                type="date"
                class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 outline-none transition hover:border-gray-300 hover:bg-white focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10"
              />
            </div>
          </div>

          <!-- Deadline + Status -->
          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
              <label
                class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600"
              >
                Tenggat
              </label>

              <div class="relative">
                <div
                  class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-amber-500"
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
                      d="M12 8v4l2.5 2.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                </div>

                <input
                  v-model="form.deadline"
                  type="date"
                  class="block w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-3.5 text-sm text-gray-900 outline-none transition hover:border-gray-300 hover:bg-white focus:border-amber-500 focus:bg-white focus:ring-4 focus:ring-amber-500/10"
                />
              </div>
            </div>

            <div>
              <label
                class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600"
              >
                Status
                <span class="text-rose-500">*</span>
              </label>

              <select
                v-model="form.status"
                required
                class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm font-medium text-gray-900 outline-none transition hover:border-gray-300 hover:bg-white focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-500/10"
              >
                <option value="draft">Draf</option>
                <option value="assigned">Ditugaskan</option>
                <option value="in_progress">Sedang Berjalan</option>
                <option value="waiting_review">Menunggu Peninjauan</option>
                <option value="revision">Revisi</option>
                <option value="approved">Disetujui</option>
                <option value="completed">Selesai</option>
                <option value="cancelled">Dibatalkan</option>
              </select>

              <p
                v-if="hasError('status')"
                class="mt-1.5 text-xs font-medium text-rose-600"
              >
                {{ getError('status') }}
              </p>
            </div>
          </div>

          <!-- Status Preview -->
          <div
            class="flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-4 py-3"
          >
            <div>
              <p class="text-xs font-bold text-gray-700">
                Status Kampanye Saat Ini
              </p>
              <p class="mt-0.5 text-[11px] text-gray-400">
                Status ini akan disimpan bersama data kampanye.
              </p>
            </div>

            <span
              class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
              :class="{
                'bg-gray-100 text-gray-600': form.status === 'draft',
                'bg-amber-100 text-amber-700':
                  form.status === 'waiting_review' || form.status === 'revision',
                'bg-blue-100 text-blue-700':
                  form.status === 'assigned' || form.status === 'approved',
                'bg-emerald-100 text-emerald-700':
                  form.status === 'in_progress',
                'bg-indigo-100 text-indigo-700':
                  form.status === 'completed',
                'bg-rose-100 text-rose-700':
                  form.status === 'cancelled'
              }"
            >
              <span
                class="h-1.5 w-1.5 rounded-full bg-current"
              ></span>
              {{ statusLabel(form.status) }}
            </span>
          </div>

          <div v-if="canManageOwnership">
            <div>
              <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">PIC</label>
              <select v-model="form.pic_id" class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900">
                <option :value="null">Pilih PIC</option>
                <option v-for="pic in pics" :key="pic.id" :value="pic.id">{{ pic.name }}</option>
              </select>
            </div>
          </div>

          <div v-if="isEdit && form.status !== props.campaign?.status">
            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-gray-600">Catatan Perubahan Status</label>
            <textarea v-model="form.transition_note" rows="2" placeholder="Wajib untuk revisi, konfirmasi penyelesaian, atau pembatalan" class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900" />
          </div>
        </div>
      </div>
    </form>

    <template #footer>
      <div
        class="flex w-full flex-col-reverse gap-2 border-t border-gray-100 pt-4 sm:flex-row sm:justify-end"
      >
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100"
          @click="closeModal"
        >
          Batal
        </button>

        <button
          v-if="isEdit ? $can('campaign.update') : $can('campaign.create')"
          type="submit"
          form="campaign-form"
          :disabled="loading"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm shadow-blue-600/20 transition hover:bg-blue-700 hover:shadow-md focus:outline-none focus:ring-4 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-60"
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
              stroke-width="1.8"
              d="M5 12l4 4L19 6"
            />
          </svg>

          {{ loading ? 'Menyimpan...' : isEdit ? 'Perbarui Kampanye' : 'Simpan Kampanye' }}
        </button>
      </div>
    </template>
  </ModalForm>
</template>

<script setup>
import { reactive, ref, watch, computed } from 'vue';
import ModalForm from './ModalForm.vue';
import { useCampaigns } from '../composables/useCampaigns';
import { useBrands } from '../composables/useBrands';
import { useWorkflowOptions } from '../composables/useWorkflowOptions';
import { useAuthStore } from '../stores/auth';

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
const { pics, fetchWorkflowOptions } = useWorkflowOptions();
const authStore = useAuthStore();
const canManageOwnership = computed(() => authStore.hasRole('Super Admin') || authStore.hasRole('Admin'));

const isEdit = ref(false);
const selectedBrandId = ref(null);
const brandSearch = ref('');
const brandDropdownOpen = ref(false);
const brandInputRef = ref(null);

const defaultForm = () => ({
  name: '',
  objective: '',
  description: '',
  priority: 'normal',
  start_date: '',
  end_date: '',
  deadline: '',
  status: 'draft',
  brand_id: null,
  pic_id: null,
  transition_note: ''
});

const form = reactive(defaultForm());

const brandDisplay = computed(() => {
  if (!selectedBrandId.value) return '';

  const found = brands.value.find(
    b => b.id === selectedBrandId.value
  );

  return found ? found.name : '';
});

const filteredBrands = computed(() => {
  if (!brandSearch.value) return brands.value;

  const q = brandSearch.value.toLowerCase();

  return brands.value.filter(
    b => b.name.toLowerCase().includes(q)
  );
});

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      fetchBrands({
        page: 1,
        per_page: 100
      });
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
    brandDropdownOpen.value = false;

    if (props.campaign) {
      isEdit.value = true;

      Object.assign(
        form,
        props.campaign
      );

      if (props.campaign.brand_id) {
        selectedBrandId.value =
          props.campaign.brand_id;
        fetchWorkflowOptions(props.campaign.brand_id);
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
  form.brand_id = null;
};

const onBrandBlur = () => {
  setTimeout(() => {
    brandDropdownOpen.value = false;
  }, 200);
};

const selectBrand = (brand) => {
  selectedBrandId.value = brand.id;
  form.brand_id = brand.id;
  brandSearch.value = brand.name;
  brandDropdownOpen.value = false;
  fetchWorkflowOptions(brand.id);
};

const statusLabel = (status) => ({
  draft: 'Draf', assigned: 'Ditugaskan', in_progress: 'Sedang Berjalan',
  waiting_review: 'Menunggu Peninjauan', revision: 'Revisi', approved: 'Disetujui',
  completed: 'Selesai', cancelled: 'Dibatalkan'
}[status] || status);

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
    error.value = 'Silakan pilih brand.';
    return;
  }

  let success = false;

  try {
    if (isEdit.value) {
      success = await updateCampaign(
        props.campaign.id,
        { ...form }
      );
    } else {
      success = await createCampaign({
        ...form
      });
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
  brandDropdownOpen.value = false;
  emit('close');
};
</script>
