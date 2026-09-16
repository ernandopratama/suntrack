<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-blue-600">PMS</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-content sm:text-3xl">Laporan Performa</h1>
        <p class="mt-1 text-sm text-content-muted">Buat laporan Daily, Weekly, atau Monthly dan bagikan melalui Secure Link.</p>
      </div>
      <button v-if="$can('performance-report.create')" type="button" @click="openCreate" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-700">
        <i class="fa-solid fa-plus mr-2"></i>Buat Laporan
      </button>
    </div>

    <div class="grid gap-3 rounded-2xl border border-default bg-surface p-4 sm:grid-cols-4">
      <input v-model="filters.search" type="search" placeholder="Cari judul laporan" class="rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content sm:col-span-2" @keyup.enter="loadReports" />
      <select v-model="filters.report_type" class="rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" @change="loadReports">
        <option value="">Semua jenis</option><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option>
      </select>
      <select v-model="filters.status" class="rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" @change="loadReports">
        <option value="">Semua status</option><option value="draft">Draft</option><option value="published">Published</option>
      </select>
    </div>

    <div v-if="displayError && !formOpen" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ displayError }}</div>
    <div v-if="notice" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ notice }}</div>

    <div class="overflow-x-auto rounded-2xl border border-default bg-surface shadow-sm">
      <table class="min-w-full divide-y divide-default">
        <thead class="bg-surface-muted text-left text-xs uppercase tracking-wide text-content-muted">
          <tr><th class="px-5 py-3">Laporan</th><th class="px-5 py-3">Brand</th><th class="px-5 py-3">Periode</th><th class="px-5 py-3">PIC</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Aksi</th></tr>
        </thead>
        <tbody class="divide-y divide-default">
          <tr v-if="loading"><td colspan="6" class="px-5 py-10 text-center text-sm text-content-muted">Memuat laporan...</td></tr>
          <tr v-else-if="!reports.length"><td colspan="6" class="px-5 py-10 text-center text-sm text-content-muted">Belum ada laporan performa.</td></tr>
          <tr v-for="report in reports" :key="report.id" class="text-sm text-content-soft">
            <td class="px-5 py-4"><p class="font-bold text-content">{{ report.title }}</p><p class="mt-1 text-xs uppercase text-content-muted">{{ report.report_type }} · v{{ report.version }}</p></td>
            <td class="px-5 py-4">{{ report.brand?.name || '-' }}</td>
            <td class="whitespace-nowrap px-5 py-4">{{ formatDate(report.period_start) }} – {{ formatDate(report.period_end) }}</td>
            <td class="px-5 py-4">{{ report.pic?.name || '-' }}</td>
            <td class="px-5 py-4"><StatusBadge :status="report.status" /></td>
            <td class="px-5 py-4"><div class="flex justify-end gap-2">
              <div v-if="report.secure_link?.status === 'Active'" class="group relative">
                <button type="button" aria-label="Salin Secure Link" title="Salin Secure Link" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 text-emerald-700 transition hover:bg-emerald-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-emerald-400" @click="copyLink(report.secure_link.url)"><i class="fa-solid fa-link" aria-hidden="true"></i></button>
                <span role="tooltip" class="pointer-events-none invisible absolute bottom-full right-0 z-30 mb-2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-semibold text-white opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">Salin Secure Link</span>
              </div>
              <div v-if="report.can_update" class="group relative">
                <button type="button" aria-label="Edit laporan" title="Edit laporan" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 text-blue-700 transition hover:bg-blue-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-400" @click="openEdit(report)"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></button>
                <span role="tooltip" class="pointer-events-none invisible absolute bottom-full right-0 z-30 mb-2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-semibold text-white opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">Edit laporan</span>
              </div>
              <div v-if="report.can_update && report.status !== 'published'" class="group relative">
                <button type="button" aria-label="Publikasikan laporan" title="Publikasikan laporan" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-violet-300 text-violet-600 transition hover:bg-violet-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-violet-400" @click="publishExisting(report)"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button>
                <span role="tooltip" class="pointer-events-none invisible absolute bottom-full right-0 z-30 mb-2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-semibold text-white opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">Publikasikan laporan</span>
              </div>
              <div v-if="report.can_delete" class="group relative">
                <button type="button" aria-label="Hapus laporan" title="Hapus laporan" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 text-rose-700 transition hover:bg-rose-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-rose-400" @click="remove(report)"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                <span role="tooltip" class="pointer-events-none invisible absolute bottom-full right-0 z-30 mb-2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-semibold text-white opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">Hapus laporan</span>
              </div>
            </div></td>
          </tr>
        </tbody>
      </table>
    </div>

    <Teleport to="body">
      <div v-if="formOpen" class="fixed inset-0 z-[9999] overflow-y-auto bg-slate-950/60 p-3 backdrop-blur-sm sm:p-6">
        <div class="mx-auto max-w-6xl overflow-hidden rounded-3xl bg-surface shadow-2xl">
          <div class="flex items-start justify-between border-b border-default px-5 py-4 sm:px-7">
            <div><p class="text-xs font-bold uppercase tracking-wider text-blue-600">{{ selectedId ? 'Edit laporan' : 'Laporan baru' }}</p><h2 class="mt-1 text-xl font-extrabold text-content">{{ form.title || 'Laporan Performa' }}</h2></div>
            <button type="button" class="rounded-xl p-2 text-content-muted hover:bg-surface-muted" @click="closeForm"><i class="fa-solid fa-xmark"></i></button>
          </div>

          <div v-if="displayError" data-testid="pms-modal-error" class="mx-5 mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 sm:mx-7">{{ displayError }}</div>

          <form ref="reportForm" id="pms-report-form" class="max-h-[calc(100vh-11rem)] space-y-7 overflow-y-auto px-5 py-6 sm:px-7" @submit.prevent="submit(false)">
            <section>
              <h3 class="mb-4 text-sm font-extrabold uppercase tracking-wide text-content">Informasi Laporan</h3>
              <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="text-xs font-bold text-content-soft">Jenis Laporan
                  <select v-model="form.report_type" required class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" @change="syncPeriodEnd"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select>
                </label>
                <label class="text-xs font-bold text-content-soft">Brand
                  <select v-model="form.brand_id" required class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content"><option value="" disabled>Pilih brand</option><option v-for="brand in availableBrands" :key="brand.id" :value="brand.id">{{ brand.name }}</option></select>
                </label>
                <label class="text-xs font-bold text-content-soft">PIC
                  <input :value="authStore.user?.name || '-'" disabled class="mt-1 block w-full rounded-xl border border-default bg-surface-muted px-3 py-2.5 text-sm text-content-muted" />
                </label>
                <label class="text-xs font-bold text-content-soft sm:col-span-2 lg:col-span-3">Judul
                  <input v-model="form.title" required maxlength="255" class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" placeholder="Contoh: Daily Report Suurlemon" />
                </label>
                <label class="text-xs font-bold text-content-soft">Tanggal Mulai<input v-model="form.period_start" type="date" required class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" @change="syncPeriodEnd" /></label>
                <label class="text-xs font-bold text-content-soft">Tanggal Selesai<input v-model="form.period_end" type="date" required :min="form.period_start" class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" /></label>
                <div class="rounded-xl bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-700">{{ periodHint }}</div>
              </div>
            </section>

            <section>
              <h3 class="mb-4 text-sm font-extrabold uppercase tracking-wide text-content">Data Performa</h3>
              <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label v-for="field in metricFields" :key="field.key" class="text-xs font-bold text-content-soft">{{ field.label }}
                  <span v-if="field.currency" class="relative mt-1 block">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-bold text-blue-600">Rp.</span>
                    <input
                      type="tel"
                      inputmode="numeric"
                      pattern="[0-9.]*"
                      autocomplete="off"
                      enterkeyhint="next"
                      :value="formatRupiahInput(form[field.key])"
                      required
                      placeholder="0"
                      class="block w-full touch-manipulation rounded-xl border border-default bg-surface py-2.5 pl-12 pr-3 text-base font-semibold text-content sm:text-sm"
                      @input="updateCurrencyField(field.key, $event)"
                    />
                  </span>
                  <input v-else v-model.number="form[field.key]" type="number" min="0" :step="field.step" required class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" />
                </label>
              </div>
              <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="metric in calculatedMetrics" :key="metric.label" class="rounded-xl bg-surface-muted p-4"><p class="text-xs text-content-muted">{{ metric.label }}</p><p class="mt-1 text-lg font-extrabold text-content">{{ metric.value }}</p></div>
              </div>
            </section>

            <section class="space-y-5">
              <h3 class="text-sm font-extrabold uppercase tracking-wide text-content">Isi Laporan</h3>
              <div v-for="editorField in editorFields" :key="editorField.key">
                <p class="text-xs font-bold text-content-soft">{{ editorField.label }}</p>
                <RichTextEditor v-model="form[editorField.key]" class="mt-1" :label="editorField.label" :placeholder="editorField.placeholder" />
              </div>
            </section>

            <section>
              <div class="mb-4 flex items-center justify-between"><div><h3 class="text-sm font-extrabold uppercase tracking-wide text-content">Gambar dan Catatan</h3><p class="mt-1 text-xs text-content-muted">JPG, PNG, atau WEBP; maksimum 10 MB per gambar.</p></div><label class="cursor-pointer rounded-xl border border-blue-200 px-4 py-2 text-xs font-bold text-blue-700 hover:bg-blue-50"><i class="fa-solid fa-image mr-2"></i>Tambah Gambar<input type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @change="addImages" /></label></div>
              <div v-if="!mediaItems.length" class="rounded-2xl border border-dashed border-default p-8 text-center text-sm text-content-muted">Belum ada gambar laporan.</div>
              <div class="space-y-4">
                <div v-for="(item, index) in mediaItems" :key="item.localKey || item.id" class="grid gap-4 rounded-2xl border border-default p-4 lg:grid-cols-[260px_1fr]">
                  <div><img :src="item.preview || item.url" :alt="item.title || item.original_name" class="h-44 w-full rounded-xl bg-surface-muted object-contain" /><p class="mt-2 truncate text-xs text-content-muted">{{ item.original_name }}</p></div>
                  <div class="space-y-3"><div class="flex gap-3"><input v-model="item.title" maxlength="255" placeholder="Judul gambar" class="min-w-0 flex-1 rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" /><button type="button" class="h-10 w-10 shrink-0 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100" @click="removeMedia(index)"><i class="fa-solid fa-trash"></i></button></div><RichTextEditor v-model="item.notes" label="Catatan gambar" placeholder="Tulis catatan untuk gambar ini..." /></div>
                </div>
              </div>
            </section>

            <section>
              <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div><h3 class="text-sm font-extrabold uppercase tracking-wide text-content">Lampiran PDF</h3><p class="mt-1 text-xs text-content-muted">Opsional; maksimum 5 file baru dan 10 MB per file.</p></div>
                <label class="cursor-pointer self-start rounded-xl border border-violet-200 px-4 py-2 text-xs font-bold text-violet-700 hover:bg-violet-50"><i class="fa-solid fa-file-pdf mr-2"></i>Tambah PDF<input type="file" accept="application/pdf,.pdf" multiple class="hidden" @change="addAttachments" /></label>
              </div>
              <div v-if="!attachmentItems.length" class="rounded-2xl border border-dashed border-default p-8 text-center text-sm text-content-muted">Tidak ada lampiran PDF.</div>
              <div v-else class="space-y-3">
                <div v-for="(attachment, index) in attachmentItems" :key="attachment.localKey || attachment.id" class="flex items-center gap-3 rounded-2xl border border-default bg-surface p-3.5">
                  <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-lg text-rose-600"><i class="fa-solid fa-file-pdf"></i></span>
                  <div class="min-w-0 flex-1"><p class="truncate text-sm font-bold text-content">{{ attachment.original_name }}</p><p class="mt-0.5 text-xs text-content-muted">{{ formatFileSize(attachment.size) }} · {{ attachment.id ? 'Tersimpan' : 'Siap diunggah' }}</p></div>
                  <button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100" :aria-label="`Hapus ${attachment.original_name}`" @click="removeAttachment(index)"><i class="fa-solid fa-trash"></i></button>
                </div>
              </div>
            </section>
          </form>

          <div class="flex flex-col-reverse gap-3 border-t border-default bg-surface-muted px-5 py-4 sm:flex-row sm:justify-end sm:px-7">
            <button type="button" class="rounded-xl border border-default bg-surface px-5 py-2.5 text-sm font-bold text-content-soft" @click="closeForm">Batal</button>
            <button form="pms-report-form" :disabled="loading || savingAssets" class="rounded-xl border border-blue-200 bg-surface px-5 py-2.5 text-sm font-bold text-blue-700 disabled:opacity-50">{{ selectedId ? 'Simpan Perubahan' : 'Simpan Draft' }}</button>
            <button v-if="form.status !== 'published'" type="button" :disabled="loading || savingAssets" class="rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50" @click="submit(true)"><i class="fa-solid fa-paper-plane mr-2"></i>Publish & Salin Link</button>
            <button v-else-if="form.secure_link?.url" type="button" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white" @click="copyLink(form.secure_link.url)"><i class="fa-solid fa-link mr-2"></i>Salin Secure Link</button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import RichTextEditor from '../components/RichTextEditor.vue';
import StatusBadge from '../components/StatusBadge.vue';
import { usePerformanceReports } from '../composables/usePerformanceReports';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const { reports, reportOptions, loading, error, fetchReports, fetchReport, fetchReportOptions, saveReport, publishReport, uploadMedia, updateMedia, deleteMedia, uploadAttachments, deleteAttachment, deleteReport } = usePerformanceReports();
const formOpen = ref(false);
const reportForm = ref(null);
const selectedId = ref(null);
const mediaItems = ref([]);
const removedMediaIds = ref([]);
const attachmentItems = ref([]);
const removedAttachmentIds = ref([]);
const savingAssets = ref(false);
const localError = ref('');
const notice = ref('');
const filters = reactive({ search: '', report_type: '', status: '' });
const emptyForm = () => ({ brand_id: '', report_type: 'daily', title: '', period_start: '', period_end: '', turnover: 0, order_count: 0, ad_spend: 0, ad_sales: 0, executive_summary: '', content: '', findings: '', action_plan: '', status: 'draft', secure_link: null });
const form = reactive(emptyForm());
const REPORT_DURATION_DAYS = { daily: 1, weekly: 7, monthly: 30 };
const metricFields = [
  { key: 'turnover', label: 'Omset / Penjualan Toko', currency: true },
  { key: 'order_count', label: 'Jumlah Pesanan Toko', step: '1' },
  { key: 'ad_spend', label: 'Budget Ads Terpakai (Biaya Iklan)', currency: true },
  { key: 'ad_sales', label: 'Penjualan dari Iklan', currency: true },
];
const editorFields = [
  { key: 'executive_summary', label: 'Ringkasan Laporan', placeholder: 'Tuliskan ringkasan performa...' },
  { key: 'content', label: 'Analisis Performa', placeholder: 'Jelaskan hasil dan analisis performa...' },
  { key: 'findings', label: 'Temuan dan Kendala', placeholder: 'Tuliskan temuan atau kendala...' },
  { key: 'action_plan', label: 'Rencana Tindak Lanjut', placeholder: 'Tuliskan tindak lanjut yang disarankan...' },
];

const availableBrands = computed(() => reportOptions.value.brands.filter(brand => brand.report_types.includes(form.report_type)));
const periodHint = computed(() => ({ daily: 'Tanggal selesai otomatis sama dengan tanggal mulai.', weekly: 'Tanggal selesai otomatis diisi untuk periode 7 hari.', monthly: 'Tanggal selesai otomatis diisi untuk periode 30 hari.' }[form.report_type]));
const displayError = computed(() => localError.value || (typeof error.value === 'string' ? error.value : error.value ? Object.values(error.value).flat()[0] : ''));
const ratio = (top, bottom) => Number(bottom) > 0 ? Number(top) / Number(bottom) : null;
const percent = value => value === null ? '-' : `${(value * 100).toFixed(2)}%`;
const formatRupiahInput = value => {
  if (value === null || value === undefined || value === '') return '';
  const number = Number(String(value).replace(/\D/g, ''));
  return Number.isFinite(number) ? new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(number) : '';
};
const updateCurrencyField = (field, event) => {
  const digits = event.target.value.replace(/\D/g, '');
  const numericValue = digits === '' ? null : Number(digits);
  form[field] = numericValue;
  event.target.value = formatRupiahInput(numericValue);
};
const syncPeriodEnd = () => {
  if (!form.period_start) {
    form.period_end = '';
    return;
  }

  const duration = REPORT_DURATION_DAYS[form.report_type] || 1;
  const [year, month, day] = form.period_start.split('-').map(Number);
  const endDate = new Date(Date.UTC(year, month - 1, day + duration - 1));
  form.period_end = endDate.toISOString().slice(0, 10);
};
const calculatedMetrics = computed(() => [
  { label: 'ROAS', value: ratio(form.ad_sales, form.ad_spend)?.toFixed(2) ?? '-' },
  { label: 'ACOS', value: percent(ratio(form.ad_spend, form.ad_sales)) },
  { label: 'Kontribusi Iklan', value: percent(ratio(form.ad_sales, form.turnover)) },
  { label: 'Rata-rata Pesanan', value: formatCurrency(ratio(form.turnover, form.order_count)) },
]);

watch(() => form.report_type, () => {
  if (form.brand_id && !availableBrands.value.some(brand => brand.id === form.brand_id)) form.brand_id = '';
});

onMounted(async () => Promise.all([fetchReportOptions(), loadReports()]));
const loadReports = () => fetchReports({ ...filters, per_page: 50 });
const resetMessages = () => { localError.value = ''; notice.value = ''; };
const openCreate = () => { resetMessages(); selectedId.value = null; Object.assign(form, emptyForm()); mediaItems.value = []; removedMediaIds.value = []; attachmentItems.value = []; removedAttachmentIds.value = []; formOpen.value = true; };
const openEdit = async report => {
  resetMessages();
  const details = await fetchReport(report.id);
  if (!details) return;
  selectedId.value = details.id;
  Object.assign(form, emptyForm(), details);
  mediaItems.value = (details.media || []).map(item => ({ ...item }));
  removedMediaIds.value = [];
  attachmentItems.value = (details.attachments || []).map(item => ({ ...item }));
  removedAttachmentIds.value = [];
  formOpen.value = true;
};
const closeForm = () => { mediaItems.value.forEach(item => item.preview && URL.revokeObjectURL(item.preview)); formOpen.value = false; };
const addImages = event => {
  for (const file of Array.from(event.target.files || [])) {
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 10 * 1024 * 1024) { localError.value = `${file.name} tidak memenuhi format atau batas 10 MB.`; continue; }
    mediaItems.value.push({ localKey: `${Date.now()}-${Math.random()}`, file, preview: URL.createObjectURL(file), original_name: file.name, title: '', notes: '', sort_order: mediaItems.value.length });
  }
  event.target.value = '';
};
const removeMedia = index => {
  const [item] = mediaItems.value.splice(index, 1);
  if (item.id) removedMediaIds.value.push(item.id);
  if (item.preview) URL.revokeObjectURL(item.preview);
};
const addAttachments = event => {
  const selectedFiles = Array.from(event.target.files || []);
  const pendingCount = attachmentItems.value.filter(item => item.file).length;
  const availableSlots = Math.max(0, 5 - pendingCount);

  for (const file of selectedFiles.slice(0, availableSlots)) {
    const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
    if (!isPdf || file.size > 10 * 1024 * 1024) {
      localError.value = `${file.name} harus berupa PDF dengan ukuran maksimal 10 MB.`;
      continue;
    }
    attachmentItems.value.push({ localKey: `${Date.now()}-${Math.random()}`, file, original_name: file.name, size: file.size });
  }
  if (selectedFiles.length > availableSlots) localError.value = 'Maksimal 5 lampiran PDF baru dalam satu kali penyimpanan.';
  event.target.value = '';
};
const removeAttachment = index => {
  const [attachment] = attachmentItems.value.splice(index, 1);
  if (attachment.id) removedAttachmentIds.value.push(attachment.id);
};
const reportPayload = () => Object.fromEntries(['brand_id', 'report_type', 'title', 'period_start', 'period_end', 'turnover', 'order_count', 'ad_spend', 'ad_sales', 'executive_summary', 'content', 'findings', 'action_plan'].map(key => [key, form[key]]));
const syncMedia = async reportId => {
  for (const mediaId of removedMediaIds.value) await deleteMedia(reportId, mediaId);
  for (let index = 0; index < mediaItems.value.length; index += 1) {
    const item = { ...mediaItems.value[index], sort_order: index };
    if (item.id) await updateMedia(reportId, item); else await uploadMedia(reportId, item);
  }
};
const syncAttachments = async reportId => {
  for (const attachmentId of removedAttachmentIds.value) await deleteAttachment(reportId, attachmentId);
  const newFiles = attachmentItems.value.filter(item => item.file).map(item => item.file);
  if (newFiles.length) await uploadAttachments(reportId, newFiles);
};
const syncAssets = async reportId => {
  savingAssets.value = true;
  try {
    await syncMedia(reportId);
    await syncAttachments(reportId);
  } finally { savingAssets.value = false; }
};
const validateReportForm = () => {
  if (reportForm.value?.checkValidity()) return true;
  reportForm.value?.reportValidity();
  localError.value = 'Lengkapi seluruh data laporan yang wajib diisi.';
  return false;
};
const submit = async publishAfter => {
  resetMessages();
  if (!validateReportForm()) return;
  const saved = await saveReport(selectedId.value, reportPayload());
  if (!saved) return;
  try { await syncAssets(saved.id); } catch (exception) { localError.value = exception.response?.data?.message || 'Sebagian gambar atau lampiran gagal disimpan. Laporan utama sudah tersimpan.'; return; }
  let result = saved;
  if (publishAfter) result = await publishReport(saved.id);
  if (!result) return;
  if (publishAfter && result.secure_link?.url) await copyLink(result.secure_link.url, false);
  notice.value = publishAfter ? 'Laporan dipublikasikan dan Secure Link disalin.' : 'Laporan berhasil disimpan.';
  closeForm();
  await loadReports();
};
const publishExisting = async report => {
  resetMessages();
  const published = await publishReport(report.id);
  if (published?.secure_link?.url) { await copyLink(published.secure_link.url, false); notice.value = 'Laporan dipublikasikan dan Secure Link disalin.'; await loadReports(); }
};
const copyLink = async (url, showNotice = true) => {
  try { await navigator.clipboard.writeText(url); } catch { const input = document.createElement('textarea'); input.value = url; document.body.appendChild(input); input.select(); document.execCommand('copy'); input.remove(); }
  if (showNotice) notice.value = 'Secure Link berhasil disalin.';
};
const remove = async report => {
  const confirmed = window.confirm(`Hapus ${report.title} secara permanen? Secure Link, gambar, dan data terkait akan ikut dihapus.`);
  if (!confirmed || !await deleteReport(report.id)) return;
  notice.value = `Laporan ${report.title} berhasil dihapus permanen.`;
  await loadReports();
};
function formatCurrency(value) { return value === null || !Number.isFinite(Number(value)) ? '-' : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value); }
function formatFileSize(bytes) { if (!Number.isFinite(Number(bytes))) return '-'; if (bytes < 1024) return `${bytes} B`; if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`; return `${(bytes / (1024 * 1024)).toFixed(1)} MB`; }
function formatDate(value) { if (!value) return '-'; return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta' }).format(new Date(`${value}T00:00:00+07:00`)); }
</script>
