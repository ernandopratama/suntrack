<template>
  <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-xs space-y-6">
    <div class="flex items-center justify-between pb-4 border-b border-gray-100">
      <div>
        <h3 class="text-lg font-bold text-gray-900 flex items-center space-x-2">
          <span>💬</span>
          <span>Ruang Kolaborasi & Diskusi (Brand & Admin Comments)</span>
        </h3>
        <p class="text-xs text-gray-500 mt-0.5">Semua komentar yang dikirim di sini juga akan terlihat langsung oleh Brand melalui Secure Public Link.</p>
      </div>
      <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 font-bold text-xs">
        Total: {{ comments.length }} Komentar
      </span>
    </div>

    <!-- Comments Thread -->
    <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
      <div v-for="c in comments" :key="c.id" class="p-4 rounded-xl border transition text-sm"
           :class="c.author_type === 'Admin' ? 'bg-blue-50/40 border-blue-100 ml-4 sm:ml-12' : 'bg-amber-50/40 border-amber-100 mr-4 sm:mr-12'">
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center space-x-2">
            <span class="px-2 py-0.5 rounded text-2xs font-extrabold uppercase tracking-wider text-white"
                  :class="c.author_type === 'Admin' ? 'bg-blue-600' : 'bg-amber-500'">
              {{ c.author_type }}
            </span>
            <span class="font-bold text-gray-800">{{ c.author_name }}</span>
            <span v-if="c.author_position" class="text-xs text-gray-400">({{ c.author_position }})</span>
          </div>
          <span class="text-xs text-gray-400">{{ formatDateTime(c.created_at) }}</span>
        </div>
        <p class="text-gray-700 whitespace-pre-line leading-relaxed">{{ c.body }}</p>
      </div>
      <div v-if="comments.length === 0" class="py-12 text-center bg-gray-50 rounded-xl border border-dashed border-gray-200 text-gray-400 text-sm">
        Belum ada komentar atau diskusi pada promosi ini.
      </div>
    </div>

    <!-- Post Form as Admin -->
    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
      <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Tulis Komentar sebagai Admin</label>
      <textarea v-model="newBody" rows="3" placeholder="Tulis instruksi, klarifikasi harga, atau balasan untuk tim Brand..."
                class="w-full rounded-xl border border-gray-300 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 mb-3"></textarea>
      <div class="flex items-center justify-between">
        <span class="text-xs text-gray-400">Pesan ini langsung tersinkronisasi ke tampilan Brand Reviewer.</span>
        <button @click="handleSend" :disabled="!newBody.trim() || loading"
                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition shadow-2xs">
          Kirim Balasan 🚀
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useSecureLink } from '../composables/useSecureLink';
import axios from 'axios';

const props = defineProps({
  promotionId: { type: [String, Number], required: true }
});

const emit = defineEmits(['updated']);
const { loading, postPromotionComment } = useSecureLink();

const comments = ref([]);
const newBody = ref('');

const loadComments = async () => {
  try {
    // We can fetch via public review endpoint or via promotion detail if we include comments in promotion show! Wait! Let's check how to load comments: in PromotionController::show, does it return comments? Or we can fetch via a simple call or load from promotion! Wait! Let's check PromotionController::show!
    const res = await axios.get(`/api/v1/admin/promotions/${props.promotionId}`);
    if (res.data?.success && res.data.data?.promotion) {
      comments.value = res.data.data.promotion.comments || [];
    } else if (res.data?.data?.comments) {
      comments.value = res.data.data.comments || [];
    } else {
      comments.value = [];
    }
  } catch (err) {
    console.error('Failed to load comments:', err);
    comments.value = [];
  }
};

onMounted(async () => {
  // To ensure comments are always loaded cleanly, let's load them!
  await loadComments();
});

const handleSend = async () => {
  if (!newBody.value.trim()) return;
  try {
    const newComment = await postPromotionComment(props.promotionId, newBody.value.trim());
    comments.value.push(newComment);
    newBody.value = '';
    emit('updated');
  } catch (err) {
    alert('Gagal mengirim komentar: ' + (err.response?.data?.message || err.message));
  }
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

defineExpose({ loadComments });
</script>
