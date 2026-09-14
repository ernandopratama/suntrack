<template>
  <div class="space-y-6">
    <div><p class="text-xs font-bold uppercase tracking-wider text-violet-600">Super Admin</p><h1 class="mt-2 text-2xl font-extrabold text-content sm:text-3xl">Secure Link PMS</h1><p class="mt-1 text-sm text-content-muted">Daftar seluruh tautan laporan performa yang pernah dibuat.</p></div>
    <div class="flex gap-3 rounded-2xl border border-default bg-surface p-4"><input v-model="search" type="search" placeholder="Cari laporan atau brand" class="min-w-0 flex-1 rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" @keyup.enter="load" /><button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white" @click="load">Cari</button></div>
    <div v-if="message" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ message }}</div>
    <div v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>
    <div class="overflow-x-auto rounded-2xl border border-default bg-surface shadow-sm">
      <table class="min-w-full divide-y divide-default">
        <thead class="bg-surface-muted text-left text-xs uppercase tracking-wide text-content-muted"><tr><th class="px-5 py-3">Laporan</th><th class="px-5 py-3">Periode</th><th class="px-5 py-3">PIC</th><th class="px-5 py-3">Link</th><th class="px-5 py-3">Akses</th><th class="px-5 py-3 text-right">Aksi</th></tr></thead>
        <tbody class="divide-y divide-default">
          <tr v-if="loading"><td colspan="6" class="px-5 py-10 text-center text-sm text-content-muted">Memuat Secure Link...</td></tr>
          <tr v-else-if="!links.length"><td colspan="6" class="px-5 py-10 text-center text-sm text-content-muted">Belum ada Secure Link PMS.</td></tr>
          <tr v-for="link in links" :key="link.id" class="text-sm text-content-soft">
            <td class="px-5 py-4"><p class="font-bold text-content">{{ link.title }}</p><p class="mt-1 text-xs text-content-muted">{{ link.brand }} · {{ link.report_type }}</p></td>
            <td class="whitespace-nowrap px-5 py-4">{{ link.period_start }} – {{ link.period_end }}</td><td class="px-5 py-4">{{ link.pic || '-' }}</td>
            <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold" :class="link.status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ link.status }}</span><p class="mt-2 text-xs text-content-muted">Berlaku sampai {{ formatExpiry(link.expires_at) }}</p><p class="mt-1 text-xs text-content-muted">Dibuat {{ formatDateTime(link.created_at) }}</p></td>
            <td class="px-5 py-4"><p class="font-bold text-content">{{ link.view_count }} kali</p><p class="text-xs text-content-muted">{{ formatDateTime(link.last_accessed_at) }}</p></td>
            <td class="px-5 py-4">
              <div class="flex justify-end gap-2">
                <div class="group relative">
                  <button type="button" aria-label="Salin link" title="Salin link" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-blue-300 text-blue-600 transition hover:bg-blue-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 focus:ring-offset-surface" @click="copy(link.url)">
                    <i class="fa-regular fa-copy" aria-hidden="true"></i>
                  </button>
                  <span role="tooltip" class="pointer-events-none invisible absolute bottom-full left-1/2 z-30 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-semibold text-white opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">Salin link</span>
                </div>

                <div v-if="link.status === 'Active'" class="group relative">
                  <a :href="link.url" target="_blank" rel="noopener" aria-label="Buka link" title="Buka link" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-300 text-emerald-600 transition hover:bg-emerald-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-surface">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                  </a>
                  <span role="tooltip" class="pointer-events-none invisible absolute bottom-full left-1/2 z-30 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-semibold text-white opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">Buka link</span>
                </div>

                <div v-if="link.status === 'Active'" class="group relative">
                  <button type="button" aria-label="Nonaktifkan link" title="Nonaktifkan link" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-rose-300 text-rose-600 transition hover:bg-rose-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2 focus:ring-offset-surface" @click="revoke(link)">
                    <i class="fa-solid fa-link-slash" aria-hidden="true"></i>
                  </button>
                  <span role="tooltip" class="pointer-events-none invisible absolute bottom-full left-1/2 z-30 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-semibold text-white opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">Nonaktifkan link</span>
                </div>

                <div v-else-if="link.report_status === 'published'" class="group relative">
                  <button type="button" aria-label="Aktifkan link" title="Aktifkan link" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-violet-300 text-violet-600 transition hover:bg-violet-600 hover:text-white focus:outline-none focus:ring-2 focus:ring-violet-400 focus:ring-offset-2 focus:ring-offset-surface" @click="activate(link)">
                    <i class="fa-solid fa-link" aria-hidden="true"></i>
                  </button>
                  <span role="tooltip" class="pointer-events-none invisible absolute bottom-full left-1/2 z-30 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg bg-slate-900 px-2.5 py-1.5 text-[11px] font-semibold text-white opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">Aktifkan link</span>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../utils/api';

const links = ref([]); const loading = ref(false); const search = ref(''); const error = ref(''); const message = ref('');
const load = async () => { loading.value = true; error.value = ''; try { const response = await api.get('/admin/performance-report-links', { params: { search: search.value, per_page: 100 } }); links.value = response.data.data.links.data || []; } catch (exception) { error.value = exception.response?.data?.message || 'Secure Link tidak dapat dimuat.'; } finally { loading.value = false; } };
const copy = async url => { await navigator.clipboard.writeText(url); message.value = 'Secure Link berhasil disalin.'; };
const revoke = async link => { if (!window.confirm(`Nonaktifkan Secure Link ${link.title}?`)) return; await api.delete(`/admin/performance-reports/${link.report_id}/secure-link`); message.value = 'Secure Link dinonaktifkan.'; await load(); };
const activate = async link => { await api.post(`/admin/performance-reports/${link.report_id}/secure-link`); message.value = 'Secure Link diaktifkan kembali.'; await load(); };
const formatDateTime = value => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short', timeZone: 'Asia/Jakarta' }).format(new Date(value)) : 'Belum pernah dibuka';
const formatExpiry = value => value ? formatDateTime(value) : 'Tanpa batas waktu';
onMounted(load);
</script>
