<template>
  <div class="space-y-6">
    <header class="rounded-3xl border border-default bg-surface p-6 shadow-sm sm:p-8">
      <div class="flex items-center gap-4">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-soft text-brand-strong">
          <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-content">Activity Logs</h1>
          <p class="mt-1 text-sm text-content-soft">Riwayat aktivitas sesuai cakupan Company dan Brand Anda.</p>
        </div>
      </div>
    </header>

    <section class="overflow-hidden rounded-3xl border border-default bg-surface shadow-sm">
      <div class="grid gap-3 border-b border-default p-4 sm:grid-cols-[1fr_220px] sm:p-5">
        <input
          v-model="filters.search"
          type="search"
          placeholder="Cari deskripsi aktivitas..."
          class="rounded-xl border border-default bg-surface-muted px-3 py-2.5 text-sm text-content outline-none focus:border-brand"
          @input="scheduleFetch"
        />
        <select
          v-model="filters.action"
          class="rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content outline-none focus:border-brand"
          @change="fetchLogs(1)"
        >
          <option value="">Semua tindakan</option>
          <option v-for="action in actions" :key="action" :value="action">{{ action }}</option>
        </select>
      </div>

      <div v-if="error" class="border-b border-rose-200 bg-rose-50 px-5 py-3 text-sm text-rose-700">{{ error }}</div>

      <div v-if="loading" class="px-6 py-14 text-center text-sm text-content-muted">Memuat aktivitas...</div>
      <div v-else-if="!logs.length" class="px-6 py-14 text-center">
        <i class="fa-solid fa-clock text-2xl text-content-muted"></i>
        <p class="mt-3 text-sm font-semibold text-content">Belum ada aktivitas</p>
        <p class="mt-1 text-xs text-content-muted">Aktivitas dalam cakupan Anda akan tampil di sini.</p>
      </div>
      <div v-else class="divide-y divide-default">
        <article v-for="log in logs" :key="log.id" class="flex gap-4 px-5 py-4 hover:bg-surface-muted">
          <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-brand-strong">
            <i class="fa-solid fa-bolt text-xs"></i>
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <span class="rounded-full bg-surface-muted px-2 py-1 text-[11px] font-semibold text-content-soft">{{ log.action }}</span>
              <span class="text-xs text-content-muted">{{ log.target_type }}</span>
            </div>
            <p class="mt-2 text-sm text-content">{{ log.description }}</p>
            <p class="mt-1 text-xs text-content-muted">{{ log.actor_name || 'System' }} · {{ formatDate(log.created_at) }}</p>
          </div>
        </article>
      </div>

      <footer class="flex items-center justify-between border-t border-default px-5 py-4 text-xs text-content-muted">
        <span>{{ pagination.total }} aktivitas</span>
        <div class="flex items-center gap-2">
          <button
            type="button"
            :disabled="pagination.current_page <= 1"
            class="rounded-lg border border-default px-3 py-2 disabled:opacity-40"
            @click="fetchLogs(pagination.current_page - 1)"
          >
            Sebelumnya
          </button>
          <span>{{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button
            type="button"
            :disabled="pagination.current_page >= pagination.last_page"
            class="rounded-lg border border-default px-3 py-2 disabled:opacity-40"
            @click="fetchLogs(pagination.current_page + 1)"
          >
            Berikutnya
          </button>
        </div>
      </footer>
    </section>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '../utils/api';

const logs = ref([]);
const loading = ref(false);
const error = ref('');
const filters = reactive({ search: '', action: '' });
const pagination = reactive({ current_page: 1, last_page: 1, total: 0 });
const actions = ['Created', 'Updated', 'Deleted', 'Login', 'Logout', 'Approved', 'Rejected'];
let searchTimer;

const fetchLogs = async (page = 1) => {
  loading.value = true;
  error.value = '';
  try {
    const response = await api.get('/admin/activity-logs', {
      params: { page, search: filters.search || undefined, action: filters.action || undefined },
    });
    const result = response.data.data.activity_logs;
    logs.value = result.data || [];
    Object.assign(pagination, result.meta || result);
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Activity Log tidak dapat dimuat.';
  } finally {
    loading.value = false;
  }
};

const scheduleFetch = () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => fetchLogs(1), 250);
};

const formatDate = (value) => value ? new Date(value).toLocaleString('id-ID') : '-';
onMounted(() => fetchLogs());
</script>
