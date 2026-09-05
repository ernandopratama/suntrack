<template>
  <div class="space-y-6">
    <div v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ error }}</div>

    <section class="rounded-2xl border border-default bg-surface p-4">
      <div class="flex items-center justify-between gap-3">
        <div><h3 class="font-bold text-content">Attachment</h3><p class="text-xs text-content-muted">Maksimum 5 file, masing-masing 10 MB.</p></div>
        <label v-if="canUpdate" class="cursor-pointer rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white">
          Upload<input type="file" multiple class="hidden" @change="uploadFiles" />
        </label>
      </div>
      <div class="mt-4 space-y-2">
        <p v-if="!attachments.length" class="text-sm text-content-muted">Belum ada attachment.</p>
        <div v-for="file in attachments" :key="file.id" class="flex items-center justify-between gap-3 rounded-xl bg-surface-muted p-3 text-sm">
          <button type="button" class="min-w-0 truncate text-left font-semibold text-blue-700" @click="download(file)">{{ file.original_name }}</button>
          <button v-if="canUpdate && (isManager || file.uploaded_by === auth.user?.id)" type="button" class="text-xs font-bold text-rose-600" @click="removeFile(file)">Hapus</button>
        </div>
      </div>
    </section>

    <section class="rounded-2xl border border-default bg-surface p-4">
      <div class="flex items-center justify-between"><h3 class="font-bold text-content">Diskusi</h3><span v-if="unreadCount" class="rounded-full bg-rose-100 px-2 py-1 text-xs font-bold text-rose-700">{{ unreadCount }} baru</span></div>
      <div class="mt-4 max-h-80 space-y-3 overflow-y-auto">
        <p v-if="!comments.length" class="text-sm text-content-muted">Belum ada pesan.</p>
        <article v-for="comment in comments" :key="comment.id" class="rounded-xl p-3" :class="comment.parent_id ? 'ml-6 border-l-2 border-blue-300 bg-blue-50/40' : 'bg-surface-muted'">
          <div class="flex justify-between gap-3 text-xs"><strong class="text-content">{{ comment.author_name }}</strong><span class="text-content-muted">{{ formatDate(comment.created_at) }}</span></div>
          <p class="mt-2 whitespace-pre-wrap text-sm text-content-soft">{{ comment.body }}</p>
          <div class="mt-2 flex flex-wrap gap-2">
            <button v-if="canUpdate" type="button" class="text-xs font-bold text-blue-700" @click="replyTo = comment">Balas</button>
            <button v-for="file in comment.attachments || []" :key="file.id" type="button" class="text-xs font-semibold text-blue-600" @click="downloadCommentFile(file)">{{ file.original_name }}</button>
          </div>
        </article>
      </div>
      <form v-if="canUpdate" class="mt-4 space-y-3" @submit.prevent="sendMessage">
        <div v-if="replyTo" class="flex justify-between rounded-lg bg-blue-50 px-3 py-2 text-xs text-blue-700"><span>Membalas {{ replyTo.author_name }}</span><button type="button" @click="replyTo = null">Batal</button></div>
        <textarea v-model="message" required maxlength="2000" rows="3" class="w-full rounded-xl border border-default bg-surface px-3 py-2 text-sm text-content" placeholder="Tulis pesan"></textarea>
        <div class="flex items-center justify-between gap-3">
          <input ref="commentFiles" type="file" multiple class="block min-w-0 text-xs text-content-muted" />
          <button :disabled="loading" class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-bold text-white disabled:opacity-50">Kirim</button>
        </div>
      </form>
    </section>

    <section v-if="isManager" class="rounded-2xl border border-default bg-surface p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h3 class="font-bold text-content">Secure Link</h3><p class="text-xs text-content-muted">{{ eligible ? 'Siap dibagikan kepada Client.' : eligibilityText }}</p></div>
        <div class="flex gap-2">
          <button v-if="eligible" type="button" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white" @click="createLink">{{ secureLink ? 'Regenerate' : 'Buat Link' }}</button>
          <button v-if="secureLink?.status === 'Active'" type="button" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-bold text-white" @click="revokeLink">Revoke</button>
        </div>
      </div>
      <div v-if="secureLink" class="mt-4 rounded-xl bg-surface-muted p-3 text-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><a :href="secureLink.url" target="_blank" class="truncate font-semibold text-blue-700">{{ secureLink.url }}</a><span class="text-xs font-bold">{{ secureLink.status }} · {{ secureLink.view_count }} view</span></div>
        <button type="button" class="mt-3 text-xs font-bold text-blue-700" @click="loadAccessLogs">Lihat Access Log</button>
      </div>
      <div v-if="accessLogs.length" class="mt-3 max-h-40 space-y-2 overflow-y-auto text-xs text-content-muted">
        <div v-for="log in accessLogs" :key="log.id" class="rounded-lg border border-default p-2">{{ formatDate(log.accessed_at) }} · {{ log.ip_address || 'IP tidak tersedia' }}</div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../utils/api';
import { useAuthStore } from '../stores/auth';

const props = defineProps({
  entityType: { type: String, required: true },
  entity: { type: Object, required: true },
  canUpdate: { type: Boolean, default: false },
});

const auth = useAuthStore();
const comments = ref([]);
const attachments = ref([]);
const secureLink = ref(null);
const accessLogs = ref([]);
const unreadCount = ref(0);
const message = ref('');
const replyTo = ref(null);
const commentFiles = ref(null);
const loading = ref(false);
const error = ref('');
const base = computed(() => props.entityType === 'task' ? `/admin/tasks/${props.entity.id}` : `/admin/performance-reports/${props.entity.id}`);
const isManager = computed(() => auth.hasRole('Super Admin') || auth.hasRole('Admin'));
const eligible = computed(() => props.entityType === 'task' ? props.entity.progress_status === 'completed' : props.entity.status === 'published');
const eligibilityText = computed(() => props.entityType === 'task' ? 'Task harus completed.' : 'Report harus published.');
const formatDate = value => value ? new Date(value).toLocaleString('id-ID') : '-';

const load = async () => {
  loading.value = true;
  error.value = '';
  try {
    const [commentResponse, attachmentResponse] = await Promise.all([
      api.get(`${base.value}/comments`),
      api.get(`${base.value}/attachments`),
    ]);
    comments.value = commentResponse.data.data.comments || [];
    unreadCount.value = commentResponse.data.data.unread_count || 0;
    attachments.value = attachmentResponse.data.data.attachments || [];
    if (unreadCount.value) await api.post(`${base.value}/comments/read`);
    if (isManager.value) {
      const linkResponse = await api.get(`${base.value}/secure-link`);
      secureLink.value = linkResponse.data.data;
    }
  } catch (exception) {
    error.value = exception.response?.data?.message || 'Data kolaborasi tidak dapat dimuat.';
  } finally {
    loading.value = false;
  }
};

const sendMessage = async () => {
  const payload = new FormData();
  payload.append('body', message.value);
  if (replyTo.value) payload.append('parent_id', replyTo.value.id);
  for (const file of commentFiles.value?.files || []) payload.append('attachments[]', file);
  await api.post(`${base.value}/comments`, payload);
  message.value = '';
  replyTo.value = null;
  if (commentFiles.value) commentFiles.value.value = '';
  await load();
};

const uploadFiles = async event => {
  const payload = new FormData();
  for (const file of event.target.files || []) payload.append('files[]', file);
  if (!payload.has('files[]')) return;
  await api.post(`${base.value}/attachments`, payload);
  event.target.value = '';
  await load();
};

const removeFile = async file => {
  await api.delete(`${base.value}/attachments/${file.id}`);
  await load();
};

const download = file => window.open(`/api/v1${base.value}/attachments/${file.id}/download`, '_blank');
const downloadCommentFile = file => download(file);
const createLink = async () => {
  const response = await api.post(`${base.value}/secure-link`);
  secureLink.value = response.data.data;
};
const revokeLink = async () => {
  const response = await api.delete(`${base.value}/secure-link`);
  secureLink.value = response.data.data;
};
const loadAccessLogs = async () => {
  const response = await api.get(`${base.value}/secure-link/access-logs`);
  accessLogs.value = response.data.data.data || [];
};

onMounted(load);
</script>
