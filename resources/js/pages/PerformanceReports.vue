<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Performance Management</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-content sm:text-3xl">Performance Reports</h1>
        <p class="mt-1 text-sm text-content-muted">Daily dan monthly report dengan review serta publishing terkontrol.</p>
      </div>
      <button v-if="$can('performance-report.create')" type="button" @click="openCreate" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700">
        <i class="fa-solid fa-plus mr-2"></i>New Report
      </button>
    </div>

    <div v-if="displayError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ displayError }}</div>

    <div class="overflow-x-auto rounded-2xl border border-default bg-surface shadow-sm">
      <table class="min-w-full divide-y divide-default">
        <thead class="bg-surface-muted text-left text-xs uppercase tracking-wide text-content-muted">
          <tr><th class="px-5 py-3">Report</th><th class="px-5 py-3">Brand</th><th class="px-5 py-3">Period</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Actions</th></tr>
        </thead>
        <tbody class="divide-y divide-default">
          <tr v-if="loading"><td colspan="5" class="px-5 py-10 text-center text-sm text-content-muted">Loading reports...</td></tr>
          <tr v-else-if="!reports.length"><td colspan="5" class="px-5 py-10 text-center text-sm text-content-muted">No performance report found.</td></tr>
          <tr v-for="report in reports" :key="report.id" class="text-sm text-content-soft">
            <td class="px-5 py-4"><p class="font-bold text-content">{{ report.title }}</p><p class="text-xs text-content-muted">{{ report.report_type }} · v{{ report.version }}</p></td>
            <td class="px-5 py-4">{{ report.brand?.name || '-' }}</td>
            <td class="px-5 py-4">{{ report.period_start }} — {{ report.period_end }}</td>
            <td class="px-5 py-4"><StatusBadge :status="report.status" /></td>
            <td class="px-5 py-4"><div class="flex justify-end gap-2">
              <button @click="collaborationReport = report" class="rounded-lg border border-emerald-200 px-3 py-1.5 text-xs font-bold text-emerald-700">Kolaborasi</button>
              <button v-if="$can('performance-report.update') && report.status !== 'published'" @click="openEdit(report)" class="rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-bold text-blue-700">Edit</button>
              <button v-if="$can('performance-report.update') && report.status === 'published'" @click="version(report)" class="rounded-lg border border-violet-200 px-3 py-1.5 text-xs font-bold text-violet-700">New Version</button>
              <button v-if="$can('performance-report.delete') && report.status !== 'published'" @click="remove(report)" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-bold text-rose-700">Delete</button>
            </div></td>
          </tr>
        </tbody>
      </table>
    </div>

    <ModalForm :is-open="modalOpen" :title="selected ? 'Edit Performance Report' : 'New Performance Report'" @close="modalOpen = false">
      <form id="performance-report-form" class="space-y-4" @submit.prevent="submit">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <label class="text-xs font-bold text-content-soft">Brand
            <select v-model="form.brand_id" required @change="loadOptions" class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content"><option value="" disabled>Select brand</option><option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option></select>
          </label>
          <label class="text-xs font-bold text-content-soft">PIC
            <select v-model="form.pic_id" required class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content"><option value="" disabled>Select PIC</option><option v-for="pic in pics" :key="pic.id" :value="pic.id">{{ pic.name }}</option></select>
          </label>
          <label class="text-xs font-bold text-content-soft">Type
            <select v-model="form.report_type" class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content"><option value="daily">Daily</option><option value="monthly">Monthly</option></select>
          </label>
          <label class="text-xs font-bold text-content-soft">Title
            <input v-model="form.title" required class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" />
          </label>
          <label class="text-xs font-bold text-content-soft">Period Start<input v-model="form.period_start" type="date" required class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" /></label>
          <label class="text-xs font-bold text-content-soft">Period End<input v-model="form.period_end" type="date" required class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" /></label>
        </div>
        <label class="block text-xs font-bold text-content-soft">Executive Summary<textarea v-model="form.executive_summary" rows="3" class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" /></label>
        <label class="block text-xs font-bold text-content-soft">Report Content<textarea v-model="form.content" rows="8" class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" /></label>
        <div v-if="selected" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <label class="text-xs font-bold text-content-soft">Next Status<select v-model="form.next_status" class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content"><option value="">No transition</option><option v-for="status in nextStatuses" :key="status" :value="status">{{ label(status) }}</option></select></label>
          <label v-if="form.next_status === 'revision'" class="text-xs font-bold text-content-soft">Review Note<textarea v-model="form.note" rows="2" class="mt-1 block w-full rounded-xl border border-default bg-surface px-3 py-2.5 text-sm text-content" /></label>
        </div>
      </form>
      <template #footer><button type="button" @click="modalOpen = false" class="rounded-xl border border-default px-4 py-2 text-sm text-content-soft">Cancel</button><button form="performance-report-form" :disabled="loading" class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-bold text-white disabled:opacity-50">Save</button></template>
    </ModalForm>

    <div v-if="collaborationReport" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-950/60" @click="collaborationReport = null"></div>
      <div class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-surface p-6 shadow-2xl">
        <div class="mb-5 flex items-center justify-between"><div><h2 class="text-lg font-bold text-content">{{ collaborationReport.title }}</h2><p class="text-xs text-content-muted">Attachment, diskusi, dan Secure Link</p></div><button type="button" @click="collaborationReport = null"><i class="fa-solid fa-xmark"></i></button></div>
        <CollaborationPanel entity-type="report" :entity="collaborationReport" :can-update="$can('performance-report.update')" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import ModalForm from '../components/ModalForm.vue';
import CollaborationPanel from '../components/CollaborationPanel.vue';
import StatusBadge from '../components/StatusBadge.vue';
import { useBrands } from '../composables/useBrands';
import { usePerformanceReports } from '../composables/usePerformanceReports';
import { useWorkflowOptions } from '../composables/useWorkflowOptions';
import { useAuthStore } from '../stores/auth';

const { brands, fetchBrands } = useBrands();
const { pics, fetchWorkflowOptions } = useWorkflowOptions();
const { reports, loading, error, fetchReports, saveReport, transitionReport, deleteReport, createVersion } = usePerformanceReports();
const modalOpen = ref(false);
const authStore = useAuthStore();
const selected = ref(null);
const collaborationReport = ref(null);
const emptyForm = () => ({ brand_id: '', pic_id: '', report_type: 'daily', title: '', period_start: '', period_end: '', executive_summary: '', content: '', next_status: '', note: '' });
const form = reactive(emptyForm());
const transitions = { draft: ['waiting_review'], waiting_review: ['revision', 'approved'], revision: ['waiting_review'], approved: ['published'], published: [] };
const nextStatuses = computed(() => (transitions[selected.value?.status] || []).filter(status => {
  if (['revision', 'approved'].includes(status)) return authStore.can('performance-report.review');
  if (status === 'published') return authStore.can('performance-report.publish');
  return selected.value?.author_id === authStore.user?.id || authStore.hasRole('Super Admin') || authStore.hasRole('Admin');
}));
const displayError = computed(() => typeof error.value === 'string' ? error.value : error.value ? Object.values(error.value).flat()[0] : '');
const label = status => status.replaceAll('_', ' ').replace(/\b\w/g, value => value.toUpperCase());

onMounted(() => Promise.all([fetchBrands({ per_page: 100 }), fetchReports()]));
const loadOptions = () => fetchWorkflowOptions(form.brand_id);
const openCreate = () => { selected.value = null; Object.assign(form, emptyForm()); modalOpen.value = true; };
const openEdit = report => { selected.value = report; Object.assign(form, emptyForm(), report, { next_status: '', note: '' }); loadOptions(); modalOpen.value = true; };
const submit = async () => {
  const { next_status, note, ...payload } = form;
  let report = await saveReport(selected.value?.id, payload);
  if (report && next_status) report = await transitionReport(report.id, next_status, note);
  if (report) { modalOpen.value = false; await fetchReports(); }
};
const remove = async report => { if (window.confirm(`Delete ${report.title}?`) && await deleteReport(report.id)) await fetchReports(); };
const version = async report => { const created = await createVersion(report.id); if (created) { await fetchReports(); openEdit(created); } };
</script>
