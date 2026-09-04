<template>
  <div class="space-y-6">
    <header class="rounded-3xl border border-default bg-surface p-6 shadow-sm sm:p-8">
      <div class="flex items-center gap-4">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-soft text-brand-strong">
          <i class="fa-solid fa-file-export"></i>
        </div>
        <div>
          <h1 class="text-2xl font-bold tracking-tight text-content">Export Data</h1>
          <p class="mt-1 text-sm text-content-soft">Unduh laporan sesuai permission dan cakupan data Anda.</p>
        </div>
      </div>
    </header>

    <section class="rounded-3xl border border-default bg-surface p-6 shadow-sm">
      <div class="grid gap-5 sm:grid-cols-2">
        <label class="space-y-2">
          <span class="text-sm font-semibold text-content">Jenis laporan</span>
          <select v-model="reportType" class="w-full rounded-xl border border-default bg-surface px-3 py-3 text-sm text-content">
            <option v-for="item in reportTypes" :key="item.value" :value="item.value">{{ item.label }}</option>
          </select>
        </label>
        <label class="space-y-2">
          <span class="text-sm font-semibold text-content">Format</span>
          <select v-model="format" class="w-full rounded-xl border border-default bg-surface px-3 py-3 text-sm text-content">
            <option value="csv">CSV</option>
            <option value="excel">Excel</option>
            <option value="pdf">PDF</option>
          </select>
        </label>
      </div>

      <div class="mt-6 rounded-2xl bg-surface-muted p-4 text-sm text-content-soft">
        Backend menerapkan scope Company dan Brand sebelum file dibuat. Filter URL tidak dapat membuka data di luar assignment.
      </div>

      <div v-if="error" class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>

      <button
        type="button"
        :disabled="downloading"
        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-brand px-5 py-3 text-sm font-semibold text-white disabled:opacity-50"
        @click="download"
      >
        <i class="fa-solid fa-download text-xs"></i>
        {{ downloading ? 'Menyiapkan file...' : 'Unduh Laporan' }}
      </button>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '../utils/api';

const reportType = ref('campaign');
const format = ref('csv');
const downloading = ref(false);
const error = ref('');
const reportTypes = [
  { value: 'campaign', label: 'Campaign' },
  { value: 'promotion', label: 'Promotion' },
  { value: 'approval', label: 'Approval' },
  { value: 'product', label: 'Product & Variant' },
  { value: 'activity', label: 'Activity Log' },
];

const download = async () => {
  downloading.value = true;
  error.value = '';
  try {
    const response = await api.get('/admin/dashboard/export', {
      params: { type: reportType.value, format: format.value },
      responseType: 'blob',
    });
    const url = URL.createObjectURL(response.data);
    const link = document.createElement('a');
    link.href = url;
    const extension = format.value === 'excel' ? 'xls' : format.value;
    link.download = `suntrack_${reportType.value}_report.${extension}`;
    link.click();
    URL.revokeObjectURL(url);
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Laporan tidak dapat dibuat.';
  } finally {
    downloading.value = false;
  }
};
</script>
