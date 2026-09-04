<template>
  <div
    class="overflow-hidden rounded-2xl border border-[#D0E7E6] bg-white shadow-sm"
  >
    <!-- Header -->
    <div
      class="border-b border-[#D0E7E6] bg-gradient-to-r from-[#293681] to-[#4274D9] px-5 py-5 sm:px-6"
    >
      <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="flex items-start gap-3">
          <!-- Icon -->
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15 text-white ring-1 ring-white/20"
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
                d="M8 10h8M8 14h5m7-2a8 8 0 01-8 8H5l-3 2 1.5-4.5A8 8 0 1119 12z"
              />
            </svg>
          </div>

          <div>
            <h3
              class="text-base font-extrabold tracking-tight text-white sm:text-lg"
            >
              Ruang Kolaborasi & Diskusi
            </h3>

            <p class="mt-1 max-w-2xl text-xs leading-relaxed text-white/75">
              Semua komentar dapat dilihat oleh Brand melalui Secure Public
              Link.
            </p>
          </div>
        </div>

        <!-- Total Comments -->
        <div
          class="inline-flex w-fit items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold text-white ring-1 ring-white/20"
        >
          <span class="h-1.5 w-1.5 rounded-full bg-[#95CCDD]"></span>
          <span>{{ comments.length }}</span>
          <span class="font-medium text-white/75">Komentar</span>
        </div>
      </div>
    </div>

    <!-- Comments Area -->
    <div class="bg-[#F8FBFC] px-4 py-5 sm:px-6">
      <div class="max-h-[28rem] space-y-4 overflow-y-auto pr-1 sm:pr-2">
        <!-- Comment -->
        <div
          v-for="c in comments"
          :key="c.id"
          class="group rounded-2xl border p-4 text-sm shadow-sm transition-all duration-200 hover:shadow-md"
          :class="
            c.author_type === 'Admin'
              ? 'ml-0 border-[#95CCDD] bg-white sm:ml-10'
              : 'mr-0 border-[#D0E7E6] bg-[#F1FAF9] sm:mr-10'
          "
        >
          <!-- Comment Header -->
          <div
            class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="flex min-w-0 items-center gap-2">
              <!-- Author Type -->
              <span
                class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider"
                :class="
                  c.author_type === 'Admin'
                    ? 'bg-[#D0E7E6] text-[#293681]'
                    : 'bg-[#95CCDD]/30 text-[#293681]'
                "
              >
                <span
                  class="h-1.5 w-1.5 rounded-full"
                  :class="
                    c.author_type === 'Admin'
                      ? 'bg-[#4274D9]'
                      : 'bg-[#293681]'
                  "
                ></span>

                {{ c.author_type }}
              </span>

              <!-- Author -->
              <span class="truncate font-bold text-[#293681]">
                {{ c.author_name }}
              </span>

              <span
                v-if="c.author_position"
                class="hidden truncate text-xs text-gray-400 sm:inline"
              >
                ({{ c.author_position }})
              </span>
            </div>

            <!-- Date -->
            <span
              class="shrink-0 text-[11px] font-medium text-gray-400"
            >
              {{ formatDateTime(c.created_at) }}
            </span>
          </div>

          <!-- Comment Body -->
          <div
            class="rounded-xl border border-gray-100 bg-white/70 px-3.5 py-3"
          >
            <p
              class="whitespace-pre-line text-sm leading-6 text-gray-700"
            >
              {{ c.body }}
            </p>
          </div>
        </div>

        <!-- Empty State -->
        <div
          v-if="comments.length === 0"
          class="flex min-h-[220px] flex-col items-center justify-center rounded-2xl border border-dashed border-[#95CCDD] bg-white px-6 text-center"
        >
          <div
            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#D0E7E6] text-[#293681]"
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
                stroke-width="1.8"
                d="M8 10h8M8 14h5m7-2a8 8 0 01-8 8H5l-3 2 1.5-4.5A8 8 0 1119 12z"
              />
            </svg>
          </div>

          <h4 class="mt-4 text-sm font-bold text-[#293681]">
            Belum ada komentar
          </h4>

          <p class="mt-1 max-w-sm text-xs leading-relaxed text-gray-400">
            Belum ada diskusi pada campaign ini. Gunakan kolom di bawah untuk
            memulai komunikasi dengan tim Brand.
          </p>
        </div>
      </div>
    </div>

    <!-- Comment Composer -->
    <div class="border-t border-[#D0E7E6] bg-white p-4 sm:p-5">
      <div
        class="rounded-2xl border border-[#D0E7E6] bg-[#F8FBFC] p-4"
      >
        <!-- Composer Header -->
        <div
          class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
        >
          <div class="flex items-center gap-2">
            <div
              class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#D0E7E6] text-[#293681]"
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
                class="block text-xs font-extrabold uppercase tracking-wider text-[#293681]"
              >
                Tulis Komentar
              </label>

              <span class="text-[11px] text-gray-400">
                Sebagai Admin
              </span>
            </div>
          </div>
        </div>

        <!-- Textarea -->
        <textarea
          v-model="newBody"
          rows="4"
          placeholder="Tulis instruksi, klarifikasi harga, atau balasan untuk tim Brand..."
          class="block w-full resize-none rounded-xl border border-[#95CCDD] bg-white px-3.5 py-3 text-sm text-gray-700 shadow-sm outline-none transition-all placeholder:text-gray-400 focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
        ></textarea>

        <!-- Composer Footer -->
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
              Pesan akan langsung tersinkronisasi ke tampilan Brand Reviewer.
            </span>
          </div>

          <!-- Send Button -->
          <button
            v-if="$can('campaign.update')"
            @click="handleSend"
            :disabled="!newBody.trim() || loading"
            type="button"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#293681] px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#4274D9] hover:shadow-md focus:outline-none focus:ring-4 focus:ring-[#4274D9]/20 disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto"
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
                d="M22 2L11 13"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M22 2l-7 20-4-9-9-4 20-7z"
              />
            </svg>

            <span>
              {{ loading ? 'Mengirim...' : 'Kirim Balasan' }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useSecureLink } from '../composables/useSecureLink';
import axios from 'axios';

const props = defineProps({
  campaignId: { type: [String, Number], required: true }
});

const emit = defineEmits(['updated']);
const { loading, postCampaignComment } = useSecureLink();

const comments = ref([]);
const newBody = ref('');

const loadComments = async () => {
  try {
    const res = await axios.get(
      `/api/v1/admin/campaigns/${props.campaignId}`
    );

    if (res.data?.success && res.data.data?.campaign) {
      comments.value =
        res.data.data.campaign.comments || [];
    } else {
      comments.value = [];
    }
  } catch (err) {
    console.error(
      'Failed to load campaign comments:',
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
    const newComment = await postCampaignComment(
      props.campaignId,
      newBody.value.trim()
    );

    comments.value.push(newComment);
    newBody.value = '';

    emit('updated');
  } catch (err) {
    console.error(
      'Gagal mengirim komentar:',
      err
    );

    alert(
      'Gagal mengirim komentar: ' +
        (err.response?.data?.message || err.message)
    );
  }
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '-';

  return new Date(dateStr).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

defineExpose({ loadComments });
</script>
