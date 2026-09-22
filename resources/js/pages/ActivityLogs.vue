<template>
  <div class="space-y-6">
    <header class="relative overflow-hidden rounded-3xl border border-default bg-surface p-6 shadow-sm sm:p-8">
      <div class="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-[#4274D9]/10 blur-3xl"></div>
      <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
          <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#293681] text-white shadow-lg shadow-[#293681]/20">
            <i class="fa-solid fa-clock-rotate-left text-lg"></i>
          </div>
          <div>
            <p class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-[#4274D9]">Audit Sistem</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-content sm:text-3xl">Log Aktivitas</h1>
            <p class="mt-1 text-sm leading-6 text-content-soft">Pantau pelaku, target, waktu, dan detail perubahan dalam cakupan akses Anda.</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:w-auto">
          <div class="min-w-28 rounded-2xl border border-default bg-surface-muted px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-content-muted">Total Log</p>
            <p class="mt-1 text-xl font-black text-content">{{ pagination.total }}</p>
          </div>
          <div class="min-w-28 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3">
            <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Ditampilkan</p>
            <p class="mt-1 text-xl font-black text-emerald-800">{{ logs.length }}</p>
          </div>
        </div>
      </div>
    </header>

    <section class="rounded-3xl border border-default bg-surface p-4 shadow-sm sm:p-5">
      <div class="flex flex-wrap gap-2">
        <button
          v-for="shortcut in secureLinkShortcuts"
          :key="shortcut.value || 'all'"
          type="button"
          class="inline-flex items-center gap-2 rounded-xl border px-3.5 py-2 text-xs font-bold transition"
          :class="filters.action === shortcut.value
            ? shortcut.activeClass
            : 'border-default bg-surface text-content-soft hover:bg-surface-muted'"
          @click="applyActionFilter(shortcut.value)"
        >
          <i :class="shortcut.icon"></i>
          {{ shortcut.label }}
        </button>
      </div>

      <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_280px_auto]">
        <label class="relative block">
          <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-content-muted">
            <i class="fa-solid fa-magnifying-glass text-xs"></i>
          </span>
          <input
            v-model="filters.search"
            type="search"
            placeholder="Cari aktivitas, aktor, target, atau ID..."
            class="w-full rounded-xl border border-default bg-surface-muted py-3 pl-10 pr-4 text-sm text-content outline-none transition focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
            @input="scheduleFetch"
          />
        </label>

        <select
          v-model="filters.action"
          class="rounded-xl border border-default bg-surface px-3.5 py-3 text-sm text-content outline-none transition focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
          @change="fetchLogs(1)"
        >
          <option value="">Semua tindakan</option>
          <option v-for="action in actions" :key="action" :value="action">{{ actionLabel(action) }}</option>
        </select>

        <button
          v-if="hasActiveFilters"
          type="button"
          class="inline-flex items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-bold text-rose-700 transition hover:bg-rose-100"
          @click="resetFilters"
        >
          <i class="fa-solid fa-filter-circle-xmark"></i>
          Reset
        </button>
      </div>
    </section>

    <div v-if="error" class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700">{{ error }}</div>

    <section class="overflow-hidden rounded-3xl border border-default bg-surface shadow-sm">
      <div v-if="loading" class="px-6 py-20 text-center">
        <i class="fa-solid fa-spinner animate-spin text-2xl text-[#4274D9]"></i>
        <p class="mt-3 text-sm font-semibold text-content-muted">Memuat log aktivitas...</p>
      </div>

      <div v-else-if="!logs.length" class="px-6 py-20 text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-surface-muted text-content-muted">
          <i class="fa-solid fa-clock text-xl"></i>
        </span>
        <p class="mt-4 text-sm font-bold text-content">Log tidak ditemukan</p>
        <p class="mt-1 text-xs text-content-muted">Ubah pencarian atau filter yang digunakan.</p>
      </div>

      <div v-else class="divide-y divide-default">
        <article
          v-for="log in logs"
          :key="log.id"
          class="group grid gap-4 px-5 py-5 transition hover:bg-surface-muted/70 sm:grid-cols-[48px_minmax(0,1fr)_auto] sm:items-start sm:px-6"
        >
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl" :class="actionStyle(log.action).iconClass">
            <i :class="actionStyle(log.action).icon"></i>
          </div>

          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide" :class="actionStyle(log.action).badgeClass">
                {{ actionLabel(log.action) }}
              </span>
              <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-muted px-2.5 py-1 text-[10px] font-bold text-content-muted">
                <i class="fa-solid fa-cube text-[9px]"></i>
                {{ targetLabel(log.target_type) }}
              </span>
            </div>

            <p class="mt-3 break-words text-sm font-semibold leading-6 text-content">{{ log.description }}</p>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-content-muted">
              <span class="inline-flex items-center gap-1.5"><i class="fa-regular fa-user"></i>{{ log.actor_name || 'Sistem' }}</span>
              <span class="inline-flex items-center gap-1.5"><i class="fa-regular fa-calendar"></i>{{ formatDate(log.created_at) }}</span>
              <span v-if="log.actor_position" class="inline-flex items-center gap-1.5"><i class="fa-solid fa-briefcase text-[10px]"></i>{{ log.actor_position }}</span>
            </div>
          </div>

          <button
            type="button"
            class="inline-flex h-10 items-center justify-center gap-2 self-center rounded-xl border border-[#4274D9]/25 bg-[#4274D9]/5 px-3.5 text-xs font-bold text-[#293681] transition hover:border-[#4274D9]/50 hover:bg-[#4274D9]/10"
            title="Lihat detail log"
            @click="openDetail(log)"
          >
            <i class="fa-regular fa-eye"></i>
            <span>Detail</span>
          </button>
        </article>
      </div>

      <footer class="flex flex-col gap-3 border-t border-default px-5 py-4 text-xs text-content-muted sm:flex-row sm:items-center sm:justify-between">
        <span>Menampilkan {{ logs.length }} dari {{ pagination.total }} aktivitas</span>
        <div class="flex items-center justify-between gap-2 sm:justify-end">
          <button type="button" :disabled="pagination.current_page <= 1 || loading" class="rounded-lg border border-default px-3 py-2 font-semibold transition hover:bg-surface-muted disabled:cursor-not-allowed disabled:opacity-40" @click="fetchLogs(pagination.current_page - 1)">Sebelumnya</button>
          <span class="rounded-lg bg-surface-muted px-3 py-2 font-bold text-content-soft">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button type="button" :disabled="pagination.current_page >= pagination.last_page || loading" class="rounded-lg border border-default px-3 py-2 font-semibold transition hover:bg-surface-muted disabled:cursor-not-allowed disabled:opacity-40" @click="fetchLogs(pagination.current_page + 1)">Berikutnya</button>
        </div>
      </footer>
    </section>

    <Teleport to="body">
      <div
        v-if="selectedLog"
        class="fixed inset-0 z-[10010] flex items-center justify-center overflow-y-auto bg-slate-950/60 px-4 py-4 backdrop-blur-sm sm:py-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="activity-detail-title"
        @click.self="closeDetail"
      >
        <section class="relative flex max-h-[calc(100dvh-2rem)] w-full max-w-2xl flex-col overflow-hidden rounded-3xl border border-default bg-surface shadow-2xl sm:max-h-[calc(100dvh-3rem)]">
          <header class="relative shrink-0 overflow-hidden border-b border-default bg-gradient-to-br from-[#293681] via-[#35479D] to-[#4274D9] px-5 py-4 text-white sm:px-6 sm:py-4">
            <div class="absolute -right-14 -top-20 h-52 w-52 rounded-full bg-white/15 blur-2xl"></div>
            <div class="relative flex items-start gap-4 pr-12">
              <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/20">
                <i :class="actionStyle(selectedLog.action).icon"></i>
              </span>
              <div class="min-w-0">
                <p class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-blue-100">Detail Log Aktivitas</p>
                <h2 id="activity-detail-title" class="mt-1 break-words text-lg font-black sm:text-xl">{{ actionLabel(selectedLog.action) }}</h2>
                <p class="mt-1 text-xs text-blue-100">{{ formatDate(selectedLog.created_at) }}</p>
              </div>
            </div>
            <button type="button" class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white transition hover:bg-white/20" aria-label="Tutup modal" @click="closeDetail">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </header>

          <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4 sm:p-5">
            <section class="rounded-2xl border border-default bg-surface-muted p-4 sm:p-5">
              <p class="text-[10px] font-extrabold uppercase tracking-[0.16em] text-content-muted">Keterangan Aktivitas</p>
              <p class="mt-2 break-words text-sm font-semibold leading-7 text-content">{{ selectedLog.description }}</p>
            </section>

            <div class="grid gap-4 sm:grid-cols-2">
              <section class="rounded-2xl border border-default p-4">
                <div class="flex items-center gap-2 text-[#4274D9]"><i class="fa-solid fa-user-shield"></i><h3 class="text-xs font-extrabold uppercase tracking-wider">Pelaku</h3></div>
                <dl class="mt-4 space-y-3 text-sm">
                  <div><dt class="text-xs text-content-muted">Nama</dt><dd class="mt-0.5 break-words font-bold text-content">{{ selectedLog.actor_name || 'Sistem' }}</dd></div>
                  <div><dt class="text-xs text-content-muted">Jenis</dt><dd class="mt-0.5 font-semibold text-content-soft">{{ selectedLog.actor_type || '-' }}</dd></div>
                  <div v-if="selectedLog.actor_position"><dt class="text-xs text-content-muted">Jabatan</dt><dd class="mt-0.5 font-semibold text-content-soft">{{ selectedLog.actor_position }}</dd></div>
                  <div v-if="selectedLog.actor_id"><dt class="text-xs text-content-muted">ID Pelaku</dt><dd class="mt-0.5 break-all font-mono text-xs text-content-soft">{{ selectedLog.actor_id }}</dd></div>
                </dl>
              </section>

              <section class="rounded-2xl border border-default p-4">
                <div class="flex items-center gap-2 text-emerald-600"><i class="fa-solid fa-crosshairs"></i><h3 class="text-xs font-extrabold uppercase tracking-wider">Target</h3></div>
                <dl class="mt-4 space-y-3 text-sm">
                  <div><dt class="text-xs text-content-muted">Jenis Target</dt><dd class="mt-0.5 font-bold text-content">{{ targetLabel(selectedLog.target_type) }}</dd></div>
                  <div><dt class="text-xs text-content-muted">ID Target</dt><dd class="mt-0.5 break-all font-mono text-xs text-content-soft">{{ selectedLog.target_id || '-' }}</dd></div>
                  <div><dt class="text-xs text-content-muted">ID Log</dt><dd class="mt-0.5 break-all font-mono text-xs text-content-soft">{{ selectedLog.id }}</dd></div>
                </dl>
              </section>
            </div>

            <section v-if="propertyEntries.length" class="rounded-2xl border border-default p-4 sm:p-5">
              <div class="flex items-center gap-2 text-amber-600"><i class="fa-solid fa-circle-info"></i><h3 class="text-xs font-extrabold uppercase tracking-wider">Informasi Tambahan</h3></div>
              <dl class="mt-4 grid gap-3 sm:grid-cols-2">
                <div v-for="entry in propertyEntries" :key="entry.key" class="min-w-0 rounded-xl bg-surface-muted px-3.5 py-3">
                  <dt class="text-[10px] font-extrabold uppercase tracking-wide text-content-muted">{{ propertyLabel(entry.key) }}</dt>
                  <dd class="mt-1 break-words whitespace-pre-wrap text-xs font-semibold leading-5 text-content-soft">{{ formatPropertyValue(entry.value) }}</dd>
                </div>
              </dl>
            </section>
          </div>

          <footer class="flex shrink-0 justify-end border-t border-default bg-surface-muted px-4 py-3 sm:px-5">
            <button type="button" class="rounded-xl bg-[#293681] px-5 py-2 text-sm font-bold text-white transition hover:bg-[#202B68]" @click="closeDetail">Tutup</button>
          </footer>
        </section>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import api from '../utils/api';

const logs = ref([]);
const loading = ref(false);
const error = ref('');
const selectedLog = ref(null);
const filters = reactive({ search: '', action: '' });
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 });
const actions = ref([]);
let searchTimer;

const secureLinkShortcuts = [
  { label: 'Semua Aktivitas', value: '', icon: 'fa-solid fa-layer-group', activeClass: 'border-[#293681] bg-[#293681] text-white' },
  { label: 'Secure Link Dilihat', value: 'Secure Link Viewed', icon: 'fa-solid fa-link', activeClass: 'border-cyan-600 bg-cyan-600 text-white' },
  { label: 'Reviewer Teridentifikasi', value: 'Reviewer Identified', icon: 'fa-solid fa-user-check', activeClass: 'border-emerald-600 bg-emerald-600 text-white' },
];

const actionLabels = {
  Created: 'Dibuat', Updated: 'Diperbarui', Deleted: 'Dihapus', Login: 'Masuk', Logout: 'Keluar', Approved: 'Disetujui', Rejected: 'Ditolak',
  'Secure Link Viewed': 'Secure Link Dilihat', 'Reviewer Identified': 'Reviewer Teridentifikasi',
};

const targetLabels = {
  Campaign: 'Kampanye', Promotion: 'Promosi', Task: 'Task', PerformanceReport: 'Laporan Performa', Brand: 'Brand', Company: 'Perusahaan', Product: 'Produk', Variant: 'Varian', User: 'User',
};

const propertyLabels = {
  ip: 'Alamat IP', ip_address: 'Alamat IP', user_agent: 'Perangkat / Browser', view_count: 'Jumlah Dilihat', company_name: 'Perusahaan / Brand Reviewer', whatsapp_number: 'Nomor WhatsApp',
  old_status: 'Status Sebelumnya', new_status: 'Status Baru', old_ownership: 'Kepemilikan Sebelumnya', new_ownership: 'Kepemilikan Baru', secure_link_id: 'ID Secure Link', expires_at: 'Masa Berlaku', priority: 'Prioritas', next_reminder_at: 'Pengingat Berikutnya',
};

const hasActiveFilters = computed(() => Boolean(filters.search || filters.action));
const propertyEntries = computed(() => Object.entries(selectedLog.value?.properties || {}).map(([key, value]) => ({ key, value })));

const fetchLogs = async (page = 1) => {
  loading.value = true;
  error.value = '';
  try {
    const response = await api.get('/admin/activity-logs', { params: { page, search: filters.search || undefined, action: filters.action || undefined } });
    const result = response.data.data.activity_logs;
    logs.value = result.data || [];
    actions.value = response.data.data.filter_options?.actions || [];
    Object.assign(pagination, result.meta || result);
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Log aktivitas tidak dapat dimuat.';
  } finally {
    loading.value = false;
  }
};

const scheduleFetch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchLogs(1), 250);
};

const applyActionFilter = (action) => {
  filters.action = action;
  fetchLogs(1);
};

const resetFilters = () => {
  filters.search = '';
  filters.action = '';
  fetchLogs(1);
};

const openDetail = (log) => { selectedLog.value = log; };
const closeDetail = () => { selectedLog.value = null; };
const actionLabel = (action) => actionLabels[action] || action || 'Aktivitas';
const targetLabel = (target) => targetLabels[target] || target || 'Sistem';
const propertyLabel = (key) => propertyLabels[key] || key.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase());

const actionStyle = (action) => {
  if (action === 'Secure Link Viewed') return { icon: 'fa-solid fa-link', iconClass: 'bg-cyan-50 text-cyan-700', badgeClass: 'bg-cyan-50 text-cyan-700' };
  if (action === 'Reviewer Identified') return { icon: 'fa-solid fa-user-check', iconClass: 'bg-emerald-50 text-emerald-700', badgeClass: 'bg-emerald-50 text-emerald-700' };
  if (/Deleted|Rejected|Failed|Cancelled/i.test(action || '')) return { icon: 'fa-solid fa-triangle-exclamation', iconClass: 'bg-rose-50 text-rose-700', badgeClass: 'bg-rose-50 text-rose-700' };
  if (/Created|Approved|Completed|Published/i.test(action || '')) return { icon: 'fa-solid fa-circle-check', iconClass: 'bg-emerald-50 text-emerald-700', badgeClass: 'bg-emerald-50 text-emerald-700' };
  if (/Login|Logout|Access/i.test(action || '')) return { icon: 'fa-solid fa-shield-halved', iconClass: 'bg-violet-50 text-violet-700', badgeClass: 'bg-violet-50 text-violet-700' };
  return { icon: 'fa-solid fa-bolt', iconClass: 'bg-blue-50 text-blue-700', badgeClass: 'bg-blue-50 text-blue-700' };
};

const formatPropertyValue = (value) => {
  if (value === null || value === undefined || value === '') return '-';
  if (typeof value === 'boolean') return value ? 'Ya' : 'Tidak';
  if (typeof value === 'object') return JSON.stringify(value, null, 2);
  return String(value);
};

const formatDate = (value) => value ? new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-';
const handleKeydown = (event) => { if (event.key === 'Escape' && selectedLog.value) closeDetail(); };

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
  fetchLogs();
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
  clearTimeout(searchTimer);
});
</script>
