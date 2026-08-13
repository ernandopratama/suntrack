<template>
  <div
    class="overflow-hidden rounded-2xl border border-[#D0E7E6] bg-white shadow-sm"
  >
    <!-- Header -->
    <div
      class="flex flex-col gap-4 border-b border-[#D0E7E6] bg-gradient-to-r from-[#D0E7E6]/40 via-white to-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="flex items-start gap-3">
        <!-- Icon -->
        <div
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#293681] text-white shadow-sm"
        >
          <svg
            class="h-5 w-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M8 10h8M8 14h5m7-2a8 8 0 01-8 8H5l-3 2 1.5-4.5A8 8 0 0112 4a8 8 0 018 8z"
            />
          </svg>
        </div>

        <div>
          <h3
            class="text-base font-extrabold tracking-tight text-[#293681]"
          >
            Ruang Kolaborasi & Diskusi
          </h3>

          <p class="mt-1 max-w-2xl text-xs leading-relaxed text-gray-500">
            Semua komentar di sini akan terlihat oleh Brand melalui
            Secure Public Link.
          </p>
        </div>
      </div>

      <!-- Total Comments -->
      <div
        class="inline-flex w-fit items-center gap-2 rounded-full border border-[#95CCDD]/60 bg-white px-3 py-1.5 shadow-sm"
      >
        <span
          class="h-1.5 w-1.5 rounded-full bg-[#4274D9]"
        ></span>

        <span class="text-xs font-bold text-[#293681]">
          {{ comments.length }}
        </span>

        <span class="text-xs font-medium text-gray-500">
          Komentar
        </span>
      </div>
    </div>

    <!-- Comments Thread -->
    <div class="px-5 py-5">
      <div
        class="max-h-[420px] space-y-4 overflow-y-auto pr-1"
      >
        <!-- Comment -->
        <div
          v-for="c in comments"
          :key="c.id"
          class="group relative rounded-2xl border p-4 text-sm transition-all duration-200 hover:shadow-sm"
          :class="
            c.author_type === 'Admin'
              ? 'ml-0 border-[#95CCDD]/70 bg-[#D0E7E6]/30 sm:ml-12'
              : 'mr-0 border-gray-200 bg-gray-50 sm:mr-12'
          "
        >
          <!-- Top -->
          <div
            class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="flex min-w-0 items-center gap-2">
              <!-- Author Badge -->
              <span
                class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider"
                :class="
                  c.author_type === 'Admin'
                    ? 'bg-[#293681] text-white'
                    : 'bg-[#95CCDD]/50 text-[#293681]'
                "
              >
                <span
                  class="h-1.5 w-1.5 rounded-full bg-current"
                ></span>

                {{ c.author_type }}
              </span>

              <span
                class="truncate text-xs font-extrabold text-gray-800"
              >
                {{ c.author_name }}
              </span>

              <span
                v-if="c.author_position"
                class="hidden text-xs text-gray-400 sm:inline"
              >
                {{ c.author_position }}
              </span>
            </div>

            <span
              class="shrink-0 text-[11px] font-medium text-gray-400"
            >
              {{ formatDateTime(c.created_at) }}
            </span>
          </div>

          <!-- Message -->
          <div
            class="rounded-xl bg-white/70 px-3.5 py-3"
          >
            <p
              class="whitespace-pre-line text-sm leading-relaxed text-gray-700"
            >
              {{ c.body }}
            </p>
          </div>
        </div>

        <!-- Empty -->
        <div
          v-if="comments.length === 0"
          class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-[#95CCDD] bg-[#D0E7E6]/20 px-6 py-12 text-center"
        >
          <div
            class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-[#4274D9] shadow-sm ring-1 ring-[#95CCDD]/50"
          >
            <svg
              class="h-6 w-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.7"
                d="M8 10h8M8 14h5m7-2a8 8 0 01-8 8H5l-3 2 1.5-4.5A8 8 0 0112 4a8 8 0 018 8z"
              />
            </svg>
          </div>

          <h4
            class="text-sm font-extrabold text-[#293681]"
          >
            Belum ada komentar
          </h4>

          <p class="mt-1 max-w-sm text-xs leading-relaxed text-gray-400">
            Belum ada diskusi pada promosi ini. Tulis komentar pertama
            untuk memulai kolaborasi dengan Brand.
          </p>
        </div>
      </div>
    </div>

    <!-- Divider -->
    <div class="px-5">
      <div class="h-px bg-[#D0E7E6]"></div>
    </div>

    <!-- Comment Form -->
    <div class="bg-gray-50/70 px-5 py-5">
      <div class="mb-3 flex items-center gap-2">
        <div
          class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#293681] text-white"
        >
          <svg
            class="h-3.5 w-3.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M12 20h9M16.5 3.5a2.121 2.121 0 013 3L8 18l-4 1 1-4L16.5 3.5z"
            />
          </svg>
        </div>

        <div>
          <label
            class="block text-xs font-extrabold text-[#293681]"
          >
            Tulis Komentar
          </label>

          <span class="text-[11px] text-gray-400">
            Komentar sebagai Admin
          </span>
        </div>
      </div>

      <!-- Textarea -->
      <textarea
        v-model="newBody"
        rows="4"
        placeholder="Tulis instruksi, klarifikasi harga, atau balasan untuk tim Brand..."
        class="block w-full resize-none rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 shadow-sm outline-none transition-all placeholder:text-gray-400 focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
      ></textarea>

      <!-- Bottom -->
      <div
        class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="flex items-start gap-2">
          <svg
            class="mt-0.5 h-3.5 w-3.5 shrink-0 text-[#4274D9]"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M13 16h-1v-4h-1m1-4h.01M12 21a9 9 0 100-18 9 9 0 000 18z"
            />
          </svg>

          <span class="text-[11px] leading-relaxed text-gray-400">
            Pesan akan langsung tersinkronisasi ke tampilan
            Brand Reviewer.
          </span>
        </div>

        <!-- Send Button -->
        <button
          type="button"
          @click="handleSend"
          :disabled="!newBody.trim() || loading"
          class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#293681] px-5 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#4274D9] hover:shadow-md focus:outline-none focus:ring-4 focus:ring-[#4274D9]/20 disabled:cursor-not-allowed disabled:opacity-40"
        >
          <svg
            v-if="!loading"
            class="h-4 w-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M22 2L11 13"
            />
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.8"
              d="M22 2l-7 20-4-9-9-4 20-7z"
            />
          </svg>

          <svg
            v-else
            class="h-4 w-4 animate-spin"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="3"
            ></circle>

            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8v3a5 5 0 00-5 5H4z"
            ></path>
          </svg>

          <span>
            {{ loading ? 'Mengirim...' : 'Kirim Balasan' }}
          </span>
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
  promotionId: {
    type: [String, Number],
    required: true
  }
});

const emit = defineEmits(['updated']);

const {
  loading,
  postPromotionComment
} = useSecureLink();

const comments = ref([]);
const newBody = ref('');

const loadComments = async () => {
  try {
    const res = await axios.get(
      `/api/v1/admin/promotions/${props.promotionId}`
    );

    if (
      res.data?.success &&
      res.data.data?.promotion
    ) {
      comments.value =
        res.data.data.promotion.comments || [];
    } else if (res.data?.data?.comments) {
      comments.value =
        res.data.data.comments || [];
    } else {
      comments.value = [];
    }
  } catch (err) {
    console.error(
      'Failed to load comments:',
      err
    );

    comments.value = [];
  }
};

onMounted(async () => {
  await loadComments();
});

const handleSend = async () => {
  if (!newBody.value.trim()) return;

  try {
    const newComment =
      await postPromotionComment(
        props.promotionId,
        newBody.value.trim()
      );

    comments.value.push(newComment);
    newBody.value = '';

    emit('updated');
  } catch (err) {
    alert(
      'Gagal mengirim komentar: ' +
        (err.response?.data?.message ||
          err.message)
    );
  }
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '-';

  return new Date(dateStr).toLocaleString(
    'id-ID',
    {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }
  );
};

defineExpose({
  loadComments
});
</script>