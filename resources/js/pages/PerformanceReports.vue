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

    <div v-if="displayError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ displayError }}</div>
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
              <button v-if="report.secure_link?.status === 'Active'" type="button" class="rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-50" @click="copyLink(report.secure_link.url)"><i class="fa-solid fa-link mr-1"></i>Salin</button>
              <button v-if="report.can_update" type="button" class="rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-50" @click="openEdit(report)">Edit</button>
              <button v-if="report.can_update && report.status !== 'published'" type="button" class="rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-violet-700" @click="publishExisting(report)">Publish</button>
              <button v-if="report.can_delete && report.status !== 'published'" type="button" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-50" @click="remove(report)"><i class="fa-solid fa-trash"></i></button>
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

          <form id="pms-report-form" class="max-h-[calc(100vh-11rem)] space-y-7 overflow-y-auto px-5 py-6 sm:px-7" @submit.prevent="submit(false)">
            <section>
              <h3 class="mb-4 text-sm font-extrabold uppercase tracking-wide text-content">Informasi Laporan</h3>
              <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <label class="text-xs font-bold text-content-soft">Jenis Laporan
                  <select v-model="form.report_type" required class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option></select>
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
                <label class="text-xs font-bold text-content-soft">Tanggal Mulai<input v-model="form.period_start" type="date" required class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" /></label>
                <label class="text-xs font-bold text-content-soft">Tanggal Selesai<input v-model="form.period_end" type="date" required class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" /></label>
                <div class="rounded-xl bg-blue-50 px-4 py-3 text-xs leading-5 text-blue-700">{{ periodHint }}</div>
              </div>
            </section>

            <section>
              <h3 class="mb-4 text-sm font-extrabold uppercase tracking-wide text-content">Data Performa</h3>
              <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label v-for="field in metricFields" :key="field.key" class="text-xs font-bold text-content-soft">{{ field.label }}
                  <input v-model.number="form[field.key]" type="number" min="0" :step="field.step" required class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" />
                </label>
              </div>
              <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div v-for="metric in calculatedMetrics" :key="metric.label" class="rounded-xl bg-surface-muted p-4"><p class="text-xs text-content-muted">{{ metric.label }}</p><p class="mt-1 text-lg font-extrabold text-content">{{ metric.value }}</p></div>
              </div>
            </section>

            <section class="space-y-5">
              <h3 class="text-sm font-extrabold uppercase tracking-wide text-content">Isi Laporan</h3>
              <label v-for="editorField in editorFields" :key="editorField.key" class="block text-xs font-bold text-content-soft">{{ editorField.label }}
                <RichTextEditor v-model="form[editorField.key]" class="mt-1" :placeholder="editorField.placeholder" />
              </label>
            </section>

            <section>
              <div class="mb-4 flex items-center justify-between"><div><h3 class="text-sm font-extrabold uppercase tracking-wide text-content">Gambar dan Catatan</h3><p class="mt-1 text-xs text-content-muted">JPG, PNG, atau WEBP; maksimum 10 MB per gambar.</p></div><label class="cursor-pointer rounded-xl border border-blue-200 px-4 py-2 text-xs font-bold text-blue-700 hover:bg-blue-50"><i class="fa-solid fa-image mr-2"></i>Tambah Gambar<input type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @change="addImages" /></label></div>
              <div v-if="!mediaItems.length" class="rounded-2xl border border-dashed border-default p-8 text-center text-sm text-content-muted">Belum ada gambar laporan.</div>
              <div class="space-y-4">
                <div v-for="(item, index) in mediaItems" :key="item.localKey || item.id" class="grid gap-4 rounded-2xl border border-default p-4 lg:grid-cols-[260px_1fr]">
                  <div><img :src="item.preview || item.url" :alt="item.title || item.original_name" class="h-44 w-full rounded-xl bg-surface-muted object-contain" /><p class="mt-2 truncate text-xs text-content-muted">{{ item.original_name }}</p></div>
                  <div class="space-y-3"><div class="flex gap-3"><input v-model="item.title" maxlength="255" placeholder="Judul gambar" class="min-w-0 flex-1 rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" /><button type="button" class="h-10 w-10 shrink-0 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100" @click="removeMedia(index)"><i class="fa-solid fa-trash"></i></button></div><RichTextEditor v-model="item.notes" placeholder="Tulis catatan untuk gambar ini..." /></div>
                </div>
              </div>
            </section>
          </form>

          <div class="flex flex-col-reverse gap-3 border-t border-default bg-surface-muted px-5 py-4 sm:flex-row sm:justify-end sm:px-7">
            <button type="button" class="rounded-xl border border-default bg-surface px-5 py-2.5 text-sm font-bold text-content-soft" @click="closeForm">Batal</button>
            <button form="pms-report-form" :disabled="loading || savingMedia" class="rounded-xl border border-blue-200 bg-surface px-5 py-2.5 text-sm font-bold text-blue-700 disabled:opacity-50">{{ selectedId ? 'Simpan Perubahan' : 'Simpan Draft' }}</button>
            <button v-if="form.status !== 'published'" type="button" :disabled="loading || savingMedia" class="rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50" @click="submit(true)"><i class="fa-solid fa-paper-plane mr-2"></i>Publish & Salin Link</button>
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
const { reports, reportOptions, loading, error, fetchReports, fetchReport, fetchReportOptions, saveReport, publishReport, uploadMedia, updateMedia, deleteMedia, deleteReport } = usePerformanceReports();
const formOpen = ref(false);
const selectedId = ref(null);
const mediaItems = ref([]);
const removedMediaIds = ref([]);
const savingMedia = ref(false);
const localError = ref('');
const notice = ref('');
const filters = reactive({ search: '', report_type: '', status: '' });
const emptyForm = () => ({ brand_id: '', report_type: 'daily', title: '', period_start: '', period_end: '', turnover: 0, order_count: 0, ad_spend: 0, ad_sales: 0, executive_summary: '', content: '', findings: '', action_plan: '', status: 'draft', secure_link: null });
const form = reactive(emptyForm());
const metricFields = [
  { key: 'turnover', label: 'Omzet/Penjualan Toko', step: '0.01' },
  { key: 'order_count', label: 'Jumlah Pesanan', step: '1' },
  { key: 'ad_spend', label: 'Biaya Iklan', step: '0.01' },
  { key: 'ad_sales', label: 'Penjualan dari Iklan', step: '0.01' },
];
const editorFields = [
  { key: 'executive_summary', label: 'Ringkasan Laporan', placeholder: 'Tuliskan ringkasan performa...' },
  { key: 'content', label: 'Analisis Performa', placeholder: 'Jelaskan hasil dan analisis performa...' },
  { key: 'findings', label: 'Temuan dan Kendala', placeholder: 'Tuliskan temuan atau kendala...' },
  { key: 'action_plan', label: 'Rencana Tindak Lanjut', placeholder: 'Tuliskan tindak lanjut yang disarankan...' },
];

const availableBrands = computed(() => reportOptions.value.brands.filter(brand => brand.report_types.includes(form.report_type)));
const periodHint = computed(() => ({ daily: 'Daily harus menggunakan tanggal mulai dan selesai yang sama.', weekly: 'Weekly dapat mencakup sampai 7 hari.', monthly: 'Monthly dapat mencakup sampai 31 hari dalam bulan yang sama.' }[form.report_type]));
const displayError = computed(() => localError.value || (typeof error.value === 'string' ? error.value : error.value ? Object.values(error.value).flat()[0] : ''));
const ratio = (top, bottom) => Number(bottom) > 0 ? Number(top) / Number(bottom) : null;
const percent = value => value === null ? '-' : `${(value * 100).toFixed(2)}%`;
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
const openCreate = () => { resetMessages(); selectedId.value = null; Object.assign(form, emptyForm()); mediaItems.value = []; removedMediaIds.value = []; formOpen.value = true; };
const openEdit = async report => {
  resetMessages();
  const details = await fetchReport(report.id);
  if (!details) return;
  selectedId.value = details.id;
  Object.assign(form, emptyForm(), details);
  mediaItems.value = (details.media || []).map(item => ({ ...item }));
  removedMediaIds.value = [];
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
const reportPayload = () => Object.fromEntries(['brand_id', 'report_type', 'title', 'period_start', 'period_end', 'turnover', 'order_count', 'ad_spend', 'ad_sales', 'executive_summary', 'content', 'findings', 'action_plan'].map(key => [key, form[key]]));
const syncMedia = async reportId => {
  savingMedia.value = true;
  try {
    for (const mediaId of removedMediaIds.value) await deleteMedia(reportId, mediaId);
    for (let index = 0; index < mediaItems.value.length; index += 1) {
      const item = { ...mediaItems.value[index], sort_order: index };
      if (item.id) await updateMedia(reportId, item); else await uploadMedia(reportId, item);
    }
  } finally { savingMedia.value = false; }
};
const submit = async publishAfter => {
  resetMessages();
  const saved = await saveReport(selectedId.value, reportPayload());
  if (!saved) return;
  try { await syncMedia(saved.id); } catch (exception) { localError.value = exception.response?.data?.message || 'Sebagian gambar gagal disimpan. Laporan utama sudah tersimpan.'; return; }
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
const remove = async report => { if (window.confirm(`Hapus ${report.title}?`) && await deleteReport(report.id)) await loadReports(); };
function formatCurrency(value) { return value === null || !Number.isFinite(Number(value)) ? '-' : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value); }
function formatDate(value) { if (!value) return '-'; return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta' }).format(new Date(`${value}T00:00:00+07:00`)); }
</script>
