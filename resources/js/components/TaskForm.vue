<template>
  <ModalForm
    :is-open="isOpen"
    :title="isEdit ? 'Ubah Task' : 'Buat Task'"
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
            Task tidak dapat disimpan
          </p>
          <p class="mt-0.5 text-xs text-rose-600">
            {{ error }}
          </p>
        </div>
      </div>

      <div v-if="!campaignId" class="rounded-xl border border-[#D0E7E6] bg-[#D0E7E6]/30 px-4 py-3">
        <label class="flex items-start gap-3" :class="isEdit ? 'cursor-not-allowed opacity-75' : 'cursor-pointer'">
          <input
            v-model="form.is_personal"
            type="checkbox"
            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#4274D9] focus:ring-[#4274D9]"
            :disabled="isEdit"
            @change="handlePersonalChange"
          />
          <span>
            <span class="block text-sm font-bold text-[#293681]">Task pribadi</span>
            <span class="mt-0.5 block text-[11px] leading-5 text-gray-500">
              Hanya dapat dilihat dan dikelola oleh Anda. PIC dan pelaksana otomatis menggunakan akun Anda.
            </span>
          </span>
        </label>
      </div>

      <!-- Brand -->
      <div v-if="!form.is_personal">
        <label class="mb-1.5 block text-xs font-bold text-gray-700">Brand <span class="text-rose-500">*</span></label>
        <select v-model="form.brand_id" required @change="handleBrandChange" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-700">
          <option value="" disabled>Pilih brand...</option>
          <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
        </select>
        <p v-if="hasError('brand_id')" class="mt-1.5 text-xs font-medium text-rose-600">{{ getError('brand_id') }}</p>
      </div>

      <!-- Campaign -->
      <div v-if="!campaignId && !form.is_personal">
        <label class="mb-1.5 block text-xs font-bold text-gray-700">
          Kampanye <span class="text-gray-400">(opsional)</span>
        </label>

        <div class="relative">
          <select
            v-model="form.campaign_id"
            class="block w-full appearance-none rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 pr-10 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all duration-200 hover:border-gray-300 focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
          >
            <option value="">
              Task mandiri tanpa kampanye
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
        <label class="mb-1.5 block text-xs font-bold text-gray-700">Deskripsi / Instruksi</label>
        <textarea v-model="form.description" rows="3" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700" placeholder="Jelaskan pekerjaan yang perlu diselesaikan" />
      </div>

      <!-- Task Name -->
      <div>
        <label class="mb-1.5 block text-xs font-bold text-gray-700">
          Nama Task
          <span class="text-rose-500">*</span>
        </label>

        <input
          type="text"
          v-model="form.name"
          required
          placeholder="Masukkan nama task..."
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
              <option value="pending">Menunggu</option>
              <option value="assigned">Ditugaskan</option>
              <option value="in_progress">Sedang Dikerjakan</option>
              <option value="on_hold">Ditunda</option>
              <option value="waiting_review">Menunggu Peninjauan</option>
              <option value="revision">Revisi</option>
              <option value="completed">Selesai</option>
              <option value="cancelled">Dibatalkan</option>
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
            Tenggat
          </label>

          <div class="relative">
            <input
              type="datetime-local"
              v-model="form.deadline"
              class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all duration-200 hover:border-gray-300 focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
            />

            <div
              class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-[#4274D9]"
            >
              <i class="fa-regular fa-calendar text-sm"></i>
            </div>
          </div>
          <p v-if="hasError('deadline')" class="mt-1.5 text-xs font-medium text-rose-600">{{ getError('deadline') }}</p>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1.5 block text-xs font-bold text-gray-700">Prioritas</label>
          <select v-model="form.priority" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700">
            <option value="normal">Normal</option><option value="mid">Menengah</option><option value="urgent">Mendesak</option>
          </select>
        </div>
        <div v-if="canManageOwnership && !form.is_personal">
          <label class="mb-1.5 block text-xs font-bold text-gray-700">PIC</label>
          <select v-model="form.pic_id" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700">
            <option :value="null">Pilih PIC</option><option v-for="pic in pics" :key="pic.id" :value="pic.id">{{ pic.name }}</option>
          </select>
        </div>
        <div v-if="canManageOwnership && !form.is_personal">
          <label class="mb-1.5 block text-xs font-bold text-gray-700">Pelaksana Tim</label>
          <select v-model="form.assignee_id" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700">
            <option :value="null">Pilih pelaksana</option><option v-for="member in teamMembers" :key="member.id" :value="member.id">{{ member.name }}</option>
          </select>
        </div>
      </div>

      <div class="rounded-xl border border-violet-100 bg-violet-50/50 px-4 py-4">
        <label class="flex cursor-pointer items-start gap-3">
          <input
            v-model="recurrenceEnabled"
            type="checkbox"
            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500"
          />
          <span>
            <span class="block text-sm font-bold text-violet-900">Ulangi task ini</span>
            <span class="mt-0.5 block text-[11px] leading-5 text-violet-700/70">
              Sistem membuat task periode berikutnya sesuai jadwal yang dipilih.
            </span>
          </span>
        </label>

        <div v-if="recurrenceEnabled" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-700">Pola Pengulangan</label>
            <select v-model="form.recurrence_type" @change="syncRecurrenceDefaults" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700">
              <option value="daily">Harian</option>
              <option value="weekly">Mingguan</option>
              <option value="monthly">Bulanan</option>
            </select>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-700">Waktu Pengulangan</label>
            <input v-model="form.recurrence_time" type="time" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700" />
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-700">Ulangi Setiap</label>
            <div class="flex items-center gap-2">
              <input v-model.number="form.recurrence_interval" type="number" min="1" max="365" class="block min-w-0 flex-1 rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700" />
              <span class="min-w-16 text-xs font-semibold text-gray-500">{{ recurrenceUnitLabel }}</span>
            </div>
          </div>

          <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-700">Pengulangan Berakhir</label>
            <select v-model="recurrenceEndMode" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700">
              <option value="never">Tidak pernah</option>
              <option value="date">Pada tanggal tertentu</option>
              <option value="count">Setelah beberapa pengulangan</option>
            </select>
          </div>

          <div v-if="form.recurrence_type === 'weekly'" class="sm:col-span-2">
            <label class="mb-2 block text-xs font-bold text-gray-700">Hari Pengulangan</label>
            <div class="grid grid-cols-4 gap-2 sm:grid-cols-7">
              <label v-for="day in weekdayOptions" :key="day.value" class="cursor-pointer">
                <input v-model="form.recurrence_weekdays" type="checkbox" :value="day.value" class="peer sr-only" />
                <span class="flex h-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-xs font-bold text-gray-500 transition peer-checked:border-violet-500 peer-checked:bg-violet-600 peer-checked:text-white">
                  {{ day.short }}
                </span>
              </label>
            </div>
          </div>

          <div v-if="form.recurrence_type === 'monthly'">
            <label class="mb-1.5 block text-xs font-bold text-gray-700">Tanggal Pengulangan</label>
            <select v-model="form.recurrence_month_day" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700">
              <option v-for="day in 31" :key="day" :value="String(day)">Tanggal {{ day }}</option>
              <option value="last">Hari terakhir bulan</option>
            </select>
          </div>

          <div v-if="recurrenceEndMode === 'date'">
            <label class="mb-1.5 block text-xs font-bold text-gray-700">Berakhir Pada</label>
            <input v-model="form.recurrence_ends_at" type="datetime-local" :min="form.deadline || undefined" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700" />
          </div>

          <div v-if="recurrenceEndMode === 'count'">
            <label class="mb-1.5 block text-xs font-bold text-gray-700">Jumlah Pengulangan</label>
            <input v-model.number="form.recurrence_max_occurrences" type="number" min="1" max="1000" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700" />
            <p class="mt-1 text-[11px] text-gray-500">Tidak termasuk task pertama.</p>
          </div>

          <label class="flex cursor-pointer items-center gap-2 sm:col-span-2">
            <input v-model="form.recurrence_notify" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-violet-600 focus:ring-violet-500" />
            <span class="text-xs font-semibold text-gray-700">Berikan pemberitahuan saat task periode berikutnya dibuat</span>
          </label>

          <div class="rounded-lg border border-violet-100 bg-white/70 px-3 py-2.5 text-[11px] leading-5 text-violet-800 sm:col-span-2">
            <strong>Pengingat tenggat:</strong>
            {{ form.recurrence_type === 'daily' ? '1 hari dan 1 jam sebelumnya.' : '3 hari dan 1 hari sebelumnya.' }}
          </div>

          <p v-if="hasError('recurrence_ends_at')" class="text-xs font-medium text-rose-600 sm:col-span-2">{{ getError('recurrence_ends_at') }}</p>
          <p v-if="hasError('recurrence_type')" class="text-xs font-medium text-rose-600 sm:col-span-2">{{ getError('recurrence_type') }}</p>
          <p v-if="hasError('recurrence_weekdays')" class="text-xs font-medium text-rose-600 sm:col-span-2">{{ getError('recurrence_weekdays') }}</p>
          <p v-if="hasError('recurrence_month_day')" class="text-xs font-medium text-rose-600 sm:col-span-2">{{ getError('recurrence_month_day') }}</p>
          <p v-if="hasError('recurrence_max_occurrences')" class="text-xs font-medium text-rose-600 sm:col-span-2">{{ getError('recurrence_max_occurrences') }}</p>
        </div>
      </div>

      <div v-if="isEdit">
        <label class="mb-1.5 block text-xs font-bold text-gray-700">Ringkasan Penyelesaian</label>
        <textarea v-model="form.completion_summary" rows="2" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700" />
      </div>

      <div v-if="isEdit && form.progress_status !== props.task?.progress_status">
        <label class="mb-1.5 block text-xs font-bold text-gray-700">Catatan Perubahan Status</label>
        <textarea v-model="form.transition_note" rows="2" class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm text-gray-700" placeholder="Wajib untuk penundaan, revisi, atau pembatalan" />
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
              Memerlukan Visual
            </span>

            <span class="block text-[11px] text-gray-500">
              Task ini memerlukan materi visual.
            </span>
          </div>
        </label>
      </div>

      <!-- Visual Type -->
      <div v-if="form.requires_visual">
        <label class="mb-1.5 block text-xs font-bold text-gray-700">
          Jenis Visual
        </label>

        <input
          type="text"
          v-model="form.visual_type"
          placeholder="Contoh: banner, poster, atau video"
          class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-700 placeholder:text-gray-400 shadow-sm outline-none transition-all duration-200 hover:border-gray-300 focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
        />

        <p class="mt-1.5 text-[11px] text-gray-400">
          Tentukan jenis materi visual yang diperlukan untuk task ini.
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
          Batal
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
            {{ loading ? 'Menyimpan...' : 'Simpan Task' }}
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
  is_personal: false,
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
  deadline: '',
  recurrence_type: '',
  recurrence_interval: 1,
  recurrence_time: '',
  recurrence_weekdays: [],
  recurrence_month_day: '',
  recurrence_ends_at: '',
  recurrence_max_occurrences: null,
  recurrence_notify: true
});

const recurrenceEndMode = ref('never');
const weekdayOptions = [
  { value: 1, short: 'Sen' },
  { value: 2, short: 'Sel' },
  { value: 3, short: 'Rab' },
  { value: 4, short: 'Kam' },
  { value: 5, short: 'Jum' },
  { value: 6, short: 'Sab' },
  { value: 7, short: 'Min' },
];
const recurrenceUnitLabel = computed(() => ({
  daily: 'hari',
  weekly: 'minggu',
  monthly: 'bulan',
}[form.recurrence_type] || 'periode'));

const recurrenceEnabled = computed({
  get: () => Boolean(form.recurrence_type),
  set: (enabled) => {
    form.recurrence_type = enabled ? (form.recurrence_type || 'daily') : '';
    if (enabled) syncRecurrenceDefaults();
    if (!enabled) {
      form.recurrence_interval = 1;
      form.recurrence_time = '';
      form.recurrence_weekdays = [];
      form.recurrence_month_day = '';
      form.recurrence_ends_at = '';
      form.recurrence_max_occurrences = null;
      recurrenceEndMode.value = 'never';
      form.recurrence_notify = true;
    }
  }
});

const toDateTimeLocal = (value) => value
  ? String(value).replace(' ', 'T').slice(0, 16)
  : '';

const deadlineParts = () => {
  const value = form.deadline ? new Date(form.deadline) : new Date();
  const javascriptDay = value.getDay();

  return {
    time: `${String(value.getHours()).padStart(2, '0')}:${String(value.getMinutes()).padStart(2, '0')}`,
    weekday: javascriptDay === 0 ? 7 : javascriptDay,
    monthDay: String(value.getDate()),
  };
};

const syncRecurrenceDefaults = () => {
  const defaults = deadlineParts();
  form.recurrence_interval = Math.max(1, Number(form.recurrence_interval) || 1);
  if (!form.recurrence_time) form.recurrence_time = defaults.time;
  if (form.recurrence_type === 'weekly' && !form.recurrence_weekdays.length) {
    form.recurrence_weekdays = [defaults.weekday];
  }
  if (form.recurrence_type === 'monthly' && !form.recurrence_month_day) {
    form.recurrence_month_day = defaults.monthDay;
  }
};

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
    form.is_personal = Boolean(props.task.is_personal);
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
    form.deadline = toDateTimeLocal(props.task.deadline);
    form.recurrence_type = props.task.recurrence_type || '';
    form.recurrence_interval = props.task.recurrence_interval || 1;
    form.recurrence_time = String(props.task.recurrence_time || '').slice(0, 5);
    form.recurrence_weekdays = [...(props.task.recurrence_weekdays || [])];
    form.recurrence_month_day = props.task.recurrence_month_day || '';
    form.recurrence_ends_at = toDateTimeLocal(props.task.recurrence_ends_at);
    form.recurrence_max_occurrences = props.task.recurrence_max_occurrences || null;
    recurrenceEndMode.value = form.recurrence_max_occurrences
      ? 'count'
      : (form.recurrence_ends_at ? 'date' : 'never');
    form.recurrence_notify = props.task.recurrence_notify ?? true;
    if (form.recurrence_type) syncRecurrenceDefaults();
  } else {
    isEdit.value = false;
    form.name = '';
    form.description = '';
    form.is_personal = false;
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
    form.recurrence_type = '';
    form.recurrence_interval = 1;
    form.recurrence_time = '';
    form.recurrence_weekdays = [];
    form.recurrence_month_day = '';
    form.recurrence_ends_at = '';
    form.recurrence_max_occurrences = null;
    recurrenceEndMode.value = 'never';
    form.recurrence_notify = true;
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

const handlePersonalChange = () => {
  if (!form.is_personal) return;

  form.brand_id = '';
  form.campaign_id = '';
  form.pic_id = null;
  form.assignee_id = null;
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

  const payload = {
    ...form,
    brand_id: form.is_personal ? null : (form.brand_id || null),
    campaign_id: form.is_personal ? null : (form.campaign_id || null),
    deadline: form.deadline || null,
    recurrence_type: form.recurrence_type || null,
    recurrence_interval: form.recurrence_type ? form.recurrence_interval : 1,
    recurrence_time: form.recurrence_type ? (form.recurrence_time || null) : null,
    recurrence_weekdays: form.recurrence_type === 'weekly' ? form.recurrence_weekdays : null,
    recurrence_month_day: form.recurrence_type === 'monthly' ? (form.recurrence_month_day || null) : null,
    recurrence_ends_at: form.recurrence_type && recurrenceEndMode.value === 'date' ? (form.recurrence_ends_at || null) : null,
    recurrence_max_occurrences: form.recurrence_type && recurrenceEndMode.value === 'count'
      ? (form.recurrence_max_occurrences || null)
      : null
  };

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
