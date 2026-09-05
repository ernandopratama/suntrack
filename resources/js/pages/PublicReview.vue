<template>
  <PublicLayout>
    <!-- =========================================================
         LOADING
    ========================================================== -->
    <div
      v-if="loading && !reviewData"
      class="min-h-[60vh] flex flex-col items-center justify-center px-4"
    >
      <div class="relative">
        <div
          class="w-14 h-14 rounded-full border-4 border-[#F7E49B]"
        ></div>
        <div
          class="absolute inset-0 w-14 h-14 rounded-full border-4 border-transparent border-t-[#BA5A5A] animate-spin"
        ></div>
      </div>

      <p class="mt-5 text-sm font-semibold text-[#52605E]">
        Memuat data peninjauan dan persetujuan...
      </p>

      <p class="mt-1 text-xs text-[#899492]">
        Mohon tunggu sebentar
      </p>
    </div>

    <!-- =========================================================
         EXPIRED / REVOKED / NOT FOUND
    ========================================================== -->
    <div
      v-else-if="linkStatus !== 'Active'"
      class="max-w-2xl mx-auto py-12 px-4"
    >
      <div
        class="bg-white rounded-[28px] p-8 sm:p-10 border border-[#E3E9E6] shadow-[0_20px_60px_rgba(41,51,49,0.08)] text-center"
      >
        <div
          class="w-20 h-20 mx-auto rounded-3xl flex items-center justify-center text-3xl mb-6"
          :class="
            linkStatus === 'Expired'
              ? 'bg-[#F7E49B]/45 text-[#79651A]'
              : 'bg-[#BA5A5A]/10 text-[#BA5A5A]'
          "
        >
          {{ linkStatus === 'Expired' ? '⏰' : '🔒' }}
        </div>

        <span
          class="inline-flex px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-[0.15em] mb-3"
          :class="
            linkStatus === 'Expired'
              ? 'bg-[#F7E49B]/40 text-[#79651A]'
              : 'bg-[#BA5A5A]/10 text-[#BA5A5A]'
          "
        >
          Secure Public Link
        </span>

        <h2 class="text-2xl sm:text-3xl font-extrabold text-[#293331] mb-3">
          {{
            linkStatus === 'Expired'
              ? 'Tautan Sudah Kedaluwarsa'
              : linkStatus === 'Revoked'
                ? 'Tautan Telah Dinonaktifkan'
                : 'Tautan Tidak Ditemukan'
          }}
        </h2>

        <p class="text-[#687572] mb-7 leading-relaxed text-sm sm:text-base">
          {{
            errorMessage ||
              'Tautan publik ini tidak lagi dapat diakses untuk peninjauan. Hal ini dapat terjadi karena masa berlaku tautan telah habis atau tautan telah ditarik kembali oleh Admin sistem.'
          }}
        </p>

        <div
          class="bg-[#F8FAF9] rounded-2xl p-5 border border-[#E3E9E6] text-sm text-[#687572] mb-7 text-left"
        >
          <div class="flex gap-3">
            <div
              class="w-9 h-9 shrink-0 rounded-xl bg-[#86BCBD]/20 text-[#315F60] flex items-center justify-center"
            >
              ?
            </div>

            <div>
              <p class="font-bold text-[#293331] mb-1">
                Bantuan & Tindak Lanjut
              </p>

              <p class="leading-relaxed">
                Silakan hubungi kontak Admin SunTrack atau Brand Manager Anda
                untuk meminta pembuatan tautan peninjauan baru.
              </p>
            </div>
          </div>
        </div>

        <a
          href="mailto:admin@suntrack.app"
          class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-[#BA5A5A] text-white font-bold text-sm hover:bg-[#A84F4F] transition shadow-lg shadow-[#BA5A5A]/20"
        >
          Hubungi Admin System
        </a>
      </div>
    </div>

    <div v-else-if="reviewData && isDelivery" class="mx-auto max-w-5xl space-y-6 px-4 pb-12">
      <section class="rounded-3xl border border-[#E3E9E6] bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#315F60]">
              {{ reviewData.type === 'Task' ? 'Task Delivery' : 'Performance Report' }}
            </p>
            <h1 class="mt-2 text-2xl font-extrabold text-[#293331] sm:text-3xl">{{ reviewData.name }}</h1>
            <p class="mt-2 text-sm text-[#687572]">{{ reviewData.brand?.name }} · {{ reviewData.pic?.name || 'SUNTRACK' }}</p>
          </div>
          <span class="rounded-full bg-[#86BCBD]/20 px-4 py-2 text-xs font-bold uppercase text-[#315F60]">
            {{ String(reviewData.status || '').replaceAll('_', ' ') }}
          </span>
        </div>

        <template v-if="reviewData.type === 'Task'">
          <div class="mt-7 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-[#F8FAF9] p-4"><p class="text-xs text-[#899492]">Priority</p><p class="mt-1 font-bold capitalize text-[#293331]">{{ reviewData.priority }}</p></div>
            <div class="rounded-2xl bg-[#F8FAF9] p-4"><p class="text-xs text-[#899492]">Deadline</p><p class="mt-1 font-bold text-[#293331]">{{ formatDate(reviewData.deadline) }}</p></div>
            <div class="rounded-2xl bg-[#F8FAF9] p-4"><p class="text-xs text-[#899492]">Completed</p><p class="mt-1 font-bold text-[#293331]">{{ formatDate(reviewData.completed_at) }}</p></div>
          </div>
          <div class="mt-6 space-y-4 text-sm leading-7 text-[#52605E]">
            <div v-if="reviewData.description"><h2 class="font-bold text-[#293331]">Instruksi</h2><p>{{ reviewData.description }}</p></div>
            <div v-if="reviewData.completion_summary"><h2 class="font-bold text-[#293331]">Ringkasan Hasil</h2><p>{{ reviewData.completion_summary }}</p></div>
            <div v-if="reviewData.completion_details"><h2 class="font-bold text-[#293331]">Detail Pekerjaan</h2><p>{{ reviewData.completion_details }}</p></div>
          </div>
        </template>

        <template v-else>
          <p class="mt-5 text-sm font-semibold text-[#52605E]">Periode {{ reviewData.period_start }} — {{ reviewData.period_end }} · Versi {{ reviewData.version }}</p>
          <div v-if="reviewData.executive_summary" class="mt-5 rounded-2xl bg-[#F8FAF9] p-5 text-sm leading-7 text-[#52605E]">{{ reviewData.executive_summary }}</div>
          <div class="prose mt-6 max-w-none text-[#52605E]" v-html="reviewData.content"></div>
        </template>
      </section>

      <section class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-[#E3E9E6] bg-white p-6">
          <h2 class="text-lg font-extrabold text-[#293331]">Attachment</h2>
          <div v-if="!reviewData.attachments?.length" class="mt-4 text-sm text-[#899492]">Tidak ada attachment.</div>
          <a
            v-for="attachment in reviewData.attachments || []"
            :key="attachment.id"
            :href="`/api/v1/public/review/${token}/attachments/${attachment.id}/download`"
            class="mt-3 flex items-center justify-between rounded-xl border border-[#E3E9E6] p-3 text-sm font-semibold text-[#315F60] hover:bg-[#F8FAF9]"
          >
            <span class="truncate">{{ attachment.original_name }}</span><span class="ml-3 text-xs">Unduh</span>
          </a>
        </div>

        <div class="rounded-3xl border border-[#E3E9E6] bg-white p-6">
          <h2 class="text-lg font-extrabold text-[#293331]">Diskusi</h2>
          <div class="mt-4 max-h-72 space-y-3 overflow-y-auto">
            <div v-for="comment in reviewData.comments || []" :key="comment.id" class="rounded-xl bg-[#F8FAF9] p-3">
              <div class="flex justify-between gap-3 text-xs"><strong class="text-[#293331]">{{ comment.author_name }}</strong><span class="text-[#899492]">{{ formatDate(comment.created_at) }}</span></div>
              <p class="mt-2 text-sm text-[#52605E]">{{ comment.body }}</p>
            </div>
          </div>
          <form class="mt-5 space-y-3" @submit.prevent="handleDeliveryComment">
            <input v-model="reviewerIdentity.name" required maxlength="150" placeholder="Nama Anda" class="w-full rounded-xl border border-[#D5DDDA] px-3.5 py-2.5 text-sm" />
            <textarea v-model="newCommentBody" required maxlength="2000" rows="3" placeholder="Tulis komentar" class="w-full rounded-xl border border-[#D5DDDA] px-3.5 py-2.5 text-sm"></textarea>
            <button :disabled="loading" class="rounded-xl bg-[#315F60] px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50">Kirim Komentar</button>
          </form>
        </div>
      </section>
    </div>

    <!-- =========================================================
         MAIN CONTENT
    ========================================================== -->
    <div
      v-else-if="reviewData"
      class="space-y-7 pb-10"
    >
      <!-- =======================================================
           REVIEWER IDENTITY
      ======================================================== -->
      <div
        class="relative overflow-hidden rounded-[24px] p-5 sm:p-6 text-white shadow-[0_16px_40px_rgba(41,51,49,0.12)]"
        style="background: linear-gradient(135deg, #293331 0%, #3d4b48 100%)"
      >
        <div
          class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-[#86BCBD]/15"
        ></div>

        <div
          class="absolute -right-5 -bottom-20 w-40 h-40 rounded-full bg-[#F7E49B]/10"
        ></div>

        <div
          class="relative flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5"
        >
          <div class="flex items-center gap-4 min-w-0">
            <div
              class="w-12 h-12 shrink-0 rounded-2xl bg-white/10 border border-white/10 flex items-center justify-center text-xl"
            >
              👤
            </div>

            <div class="min-w-0">
              <p
                class="text-[10px] uppercase font-bold tracking-[0.18em] text-[#86BCBD]"
              >
                Reviewer Identity
              </p>

              <p class="text-sm sm:text-base font-bold mt-1 truncate">
                {{
                  isIdentified()
                    ? `${reviewerIdentity.name} (${reviewerIdentity.position || 'Reviewer'})`
                    : 'Belum Teridentifikasi'
                }}
              </p>

              <p
                v-if="reviewerIdentity.companyName"
                class="text-xs text-[#F7E49B] mt-0.5"
              >
                {{ reviewerIdentity.companyName }}
              </p>
            </div>
          </div>

          <button
            @click="openIdentityModal(true)"
            class="relative px-4 py-2.5 bg-white/10 hover:bg-white/15 text-white text-xs sm:text-sm font-bold rounded-xl border border-white/15 transition flex items-center gap-2"
          >
            <span>
              {{ isIdentified() ? 'Ubah Identitas' : 'Identifikasi Diri' }}
            </span>
            <span>
              <img
                src="/images/pencil.webp"
                alt="Statistics"
                class="w-6 h-6 object-contain"
              />
            </span>
          </button>
        </div>
      </div>

      <!-- =======================================================
           PROMOTION INFORMATION
      ======================================================== -->
      <div
        class="relative overflow-hidden bg-white rounded-[24px] p-6 sm:p-8 border border-[#E3E9E6] shadow-[0_8px_30px_rgba(41,51,49,0.05)]"
      >
        <div
          class="absolute -right-20 -top-20 w-64 h-64 rounded-full bg-[#F7E49B]/20"
        ></div>

        <div
          class="relative flex flex-col md:flex-row md:items-center justify-between gap-5 border-b border-[#EDF1EF] pb-6 mb-6"
        >
          <div>
            <div class="flex flex-wrap items-center gap-2 mb-3">
              <span
                v-if="reviewData.code"
                class="px-3 py-1.5 rounded-lg bg-[#86BCBD]/15 text-[#315F60] border border-[#86BCBD]/30 text-xs font-mono font-bold tracking-wider"
              >
                {{ reviewData.code }}
              </span>

              <div class="relative">
                <select
                  v-if="reviewData"
                  :value="reviewData.status"
                  @change="handleStatusChange"
                  :disabled="loading"
                  class="appearance-none min-w-[180px] pl-4 pr-10 py-2 rounded-full text-[11px] font-bold border cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#86BCBD]/40 transition disabled:opacity-60 disabled:cursor-not-allowed"
                  :class="getStatusBadgeClass(reviewData.status)"
                >
                  <option
                    v-for="status in availableStatusOptions"
                    :key="status"
                    :value="status"
                  >
                    {{ status }}
                  </option>
                </select>

                <svg
                  class="absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 pointer-events-none"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </div>

            </div>

            <h1
              class="text-2xl sm:text-3xl font-extrabold text-[#293331] tracking-tight"
            >
              {{ reviewData.name }}
            </h1>

            <p
              v-if="reviewData.brand"
              class="text-sm text-[#788480] mt-2"
            >
              Brand:
              <span class="font-bold text-[#293331]">
                {{ reviewData.brand.name }}
              </span>
            </p>
          </div>

          <div
            class="bg-[#F8FAF9] px-5 py-4 rounded-2xl border border-[#E3E9E6] min-w-fit"
          >
            <span
              class="block text-[10px] uppercase tracking-wider font-bold text-[#899492] mb-1"
            >
              Periode Promosi
            </span>

            <span class="font-bold text-sm text-[#293331]">
              {{ formatDate(reviewData.start_date) }}
              <span class="text-[#BA5A5A] mx-1">→</span>
              {{ formatDate(reviewData.end_date) }}
            </span>
          </div>
        </div>

        <div
          v-if="reviewData.description"
          class="text-[#687572] text-sm leading-relaxed"
        >
          <p
            class="font-bold text-[#293331] text-[10px] uppercase tracking-wider mb-2"
          >
            Deskripsi Promosi
          </p>

          <p>{{ reviewData.description }}</p>
        </div>
      </div>

      <!-- =======================================================
           CAMPAIGN
      ======================================================== -->
      <div
        v-if="reviewData.campaign"
        class="rounded-[22px] p-5 border border-[#86BCBD]/30 bg-[#86BCBD]/10 shadow-sm"
      >
        <div
          class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
        >
          <div class="flex items-center gap-4">
            <div
              class="w-11 h-11 rounded-xl bg-[#86BCBD] text-[#293331] flex items-center justify-center font-bold text-lg shadow-sm"
            >
              🎯
            </div>

            <div>
              <span
                class="text-[10px] font-extrabold uppercase tracking-wider text-[#315F60]"
              >
                Terhubung ke Campaign
              </span>

              <h3 class="text-lg font-extrabold text-[#293331] mt-0.5">
                {{ reviewData.campaign.name }}
              </h3>
            </div>
          </div>

          <div
            class="text-xs sm:text-sm text-[#52605E] font-medium bg-white px-4 py-2.5 rounded-xl border border-[#86BCBD]/30"
          >
            Periode:
            <span class="font-bold text-[#293331]">
              {{ formatDate(reviewData.campaign?.start_date) }}
              -
              {{ formatDate(reviewData.campaign?.end_date) }}
            </span>
          </div>
        </div>
      </div>

      <!-- =======================================================
           APPROVAL SUMMARY
      ======================================================== -->
      <div
        class="bg-white rounded-[24px] p-6 sm:p-8 border border-[#E3E9E6] shadow-[0_8px_30px_rgba(41,51,49,0.05)]"
      >
        <div
          class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6"
        >
          <div>
            <h2
              class="text-lg font-extrabold text-[#293331] flex items-center gap-2"
            >
            <span
              class="w-8 h-8 rounded-lg bg-[#86BCBD]/15 flex items-center justify-center overflow-hidden"
            >
              <img
                src="/images/stat.webp"
                alt="Statistics"
                class="w-6 h-6 object-contain"
              />
            </span>
              <span>Ringkasan Persetujuan</span>
            </h2>

            <p class="text-xs text-[#899492] mt-1">
              Pantau status persetujuan seluruh variant produk
            </p>
          </div>

          <div
            class="flex items-center gap-4 text-xs text-[#687572] bg-[#F8FAF9] px-4 py-2.5 rounded-xl border border-[#E3E9E6]"
          >
            <div>
              <span class="block text-[#899492] mb-0.5">
                Terakhir Diperbarui
              </span>

              <span class="font-bold text-[#293331]">
                {{ formatDateTime(reviewData.approval_summary.last_updated) }}
              </span>
            </div>

            <div
              v-if="reviewData.approval_summary.last_reviewer"
              class="border-l border-[#DDE4E1] pl-4"
            >
              <span class="block text-[#899492] mb-0.5">
                Reviewer Terakhir
              </span>

              <span class="font-bold text-[#293331]">
                {{ reviewData.approval_summary.last_reviewer.name }}
              </span>
            </div>
          </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-7">
          <div
            class="rounded-2xl p-5 border border-[#E3E9E6] bg-[#F8FAF9] text-center"
          >
            <span
              class="block text-2xl sm:text-3xl font-extrabold text-[#293331]"
            >
              {{ reviewData.approval_summary.total_variants }}
            </span>

            <span
              class="text-[10px] font-bold text-[#899492] uppercase tracking-wider mt-1 block"
            >
              Total Variant
            </span>
          </div>

          <div
            class="rounded-2xl p-5 border border-[#A4CE8B]/40 bg-[#A4CE8B]/15 text-center"
          >
            <span
              class="block text-2xl sm:text-3xl font-extrabold text-[#426332]"
            >
              {{ reviewData.approval_summary.approved }}
            </span>

            <span
              class="text-[10px] font-bold text-[#527842] uppercase tracking-wider mt-1 block"
            >
              Approved
            </span>
          </div>

          <div
            class="rounded-2xl p-5 border border-[#F7E49B]/70 bg-[#F7E49B]/30 text-center"
          >
            <span
              class="block text-2xl sm:text-3xl font-extrabold text-[#79651A]"
            >
              {{ reviewData.approval_summary.pending }}
            </span>

            <span
              class="text-[10px] font-bold text-[#79651A] uppercase tracking-wider mt-1 block"
            >
              Pending
            </span>
          </div>

          <div
            class="rounded-2xl p-5 border border-[#BA5A5A]/20 bg-[#BA5A5A]/10 text-center"
          >
            <span
              class="block text-2xl sm:text-3xl font-extrabold text-[#BA5A5A]"
            >
              {{ reviewData.approval_summary.rejected }}
            </span>

            <span
              class="text-[10px] font-bold text-[#BA5A5A] uppercase tracking-wider mt-1 block"
            >
              Rejected
            </span>
          </div>
        </div>

        <!-- Progress -->
        <div>
          <div
            class="flex items-center justify-between text-xs font-bold text-[#52605E] mb-2"
          >
            <span>Tingkat Penyelesaian Review</span>

            <span class="text-[#BA5A5A]">
              {{ reviewData.approval_summary.completion_percentage }}%
            </span>
          </div>

          <div
            class="w-full h-3 bg-[#EEF2F0] rounded-full overflow-hidden flex"
          >
            <div
              class="bg-[#A4CE8B] h-full transition-all duration-500"
              :style="{
                width: `${(reviewData.approval_summary.approved / Math.max(reviewData.approval_summary.total_variants, 1)) * 100}%`
              }"
            ></div>

            <div
              class="bg-[#BA5A5A] h-full transition-all duration-500"
              :style="{
                width: `${(reviewData.approval_summary.rejected / Math.max(reviewData.approval_summary.total_variants, 1)) * 100}%`
              }"
            ></div>
          </div>

          <div class="flex items-center gap-4 mt-3 text-[10px] font-semibold">
            <span class="flex items-center gap-1.5 text-[#527842]">
              <span class="w-2 h-2 rounded-full bg-[#A4CE8B]"></span>
              Approved
            </span>

            <span class="flex items-center gap-1.5 text-[#BA5A5A]">
              <span class="w-2 h-2 rounded-full bg-[#BA5A5A]"></span>
              Rejected
            </span>
          </div>
        </div>
      </div>

      <!-- =======================================================
           VARIANTS & APPROVAL
      ======================================================== -->
      <div
        class="bg-white rounded-[24px] p-6 sm:p-8 border border-[#E3E9E6] shadow-[0_8px_30px_rgba(41,51,49,0.05)]"
      >
        <div
          class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6"
        >
          <div>
            <h2
              class="text-lg font-extrabold text-[#293331] flex items-center gap-2"
            >
            <span
              class="w-8 h-8 rounded-lg bg-[#86BCBD]/15 flex items-center justify-center overflow-hidden"
            >
              <img
                src="/images/product.webp"
                alt="Statistics"
                class="w-6 h-6 object-contain"
              />
            </span>

              <span>Daftar Produk & Variant</span>
            </h2>

            <p class="text-xs text-[#899492] mt-1">
              Tinjau harga dan berikan persetujuan per item maupun batch.
            </p>
          </div>

          <button
            @click="showHistoryModal = true"
            class="px-4 py-2.5 bg-[#F8FAF9] hover:bg-[#EEF2F0] text-[#52605E] font-bold rounded-xl text-xs transition flex items-center gap-2 border border-[#E3E9E6]"
          >
            <span>📜</span>
            <span>
              Riwayat Approval
              ({{ reviewData.approval_histories?.length || 0 }})
            </span>
          </button>
        </div>

        <!-- Batch Toolbar -->
        <div
          v-if="reviewData.variants && reviewData.variants.length > 0"
          class="flex flex-wrap items-center justify-between gap-4 p-4 bg-[#86BCBD]/10 border border-[#86BCBD]/25 rounded-2xl mb-5"
        >
          <div class="flex items-center gap-2 text-xs font-bold text-[#52605E]">
            <span>
              {{ selectedVariantIds.length }} dari
              {{ reviewData.variants.length }} variant terpilih
            </span>

            <button
              v-if="selectedVariantIds.length > 0"
              @click="selectedVariantIds = []"
              class="text-[#BA5A5A] hover:underline text-[10px]"
            >
              Reset
            </button>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <button
              @click="handleBatchAction('approve_selected')"
              :disabled="selectedVariantIds.length === 0 || batchLoading"
              class="px-3.5 py-2 bg-[#A4CE8B] hover:bg-[#94C27A] disabled:bg-[#DDE4E1] text-[#304A27] font-bold text-xs rounded-xl transition"
            >
              ✓ Setujui Terpilih ({{ selectedVariantIds.length }})
            </button>

            <button
              @click="handleBatchAction('reject_selected')"
              :disabled="selectedVariantIds.length === 0 || batchLoading"
              class="px-3.5 py-2 bg-[#BA5A5A] hover:bg-[#A84F4F] disabled:bg-[#DDE4E1] text-white font-bold text-xs rounded-xl transition"
            >
              ✕ Tolak Terpilih ({{ selectedVariantIds.length }})
            </button>

            <span class="hidden sm:block text-[#B8C2BF]">|</span>

            <button
              @click="handleBatchAction('approve_all')"
              :disabled="batchLoading"
              class="px-3.5 py-2 bg-white hover:bg-[#F2F7EF] text-[#527842] border border-[#A4CE8B] font-bold text-xs rounded-xl transition"
            >
              ✓ Setujui Semua
            </button>

            <button
              @click="handleBatchAction('reject_all')"
              :disabled="batchLoading"
              class="px-3.5 py-2 bg-white hover:bg-[#FCF1F1] text-[#BA5A5A] border border-[#D99A9A] font-bold text-xs rounded-xl transition"
            >
              ✕ Tolak Semua
            </button>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-2xl border border-[#E3E9E6]">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr
                class="border-b border-[#E3E9E6] text-[10px] font-extrabold text-[#899492] uppercase tracking-wider bg-[#F8FAF9]"
              >
                <th class="py-4 px-3 w-10 text-center">
                  <input
                    type="checkbox"
                    @change="toggleSelectAll"
                    :checked="isAllSelected"
                    class="rounded border-[#C7D0CD] text-[#BA5A5A] focus:ring-[#BA5A5A]"
                  />
                </th>

                <th class="py-4 px-4">Produk & Variant</th>
                <th class="py-4 px-4">Harga Normal</th>
                <th class="py-4 px-4">Harga Promo</th>
                <th class="py-4 px-4">Stok / Limit</th>
                <th class="py-4 px-4 text-center">Status</th>
                <th class="py-4 px-4 text-right">Aksi</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-[#EDF1EF] text-sm">
              <tr
                v-for="variant in reviewData.variants"
                :key="variant.id"
                class="hover:bg-[#F8FAF9] transition"
                :class="{
                  'bg-[#86BCBD]/10': selectedVariantIds.includes(variant.id)
                }"
              >
                <td class="py-4 px-3 text-center">
                  <input
                    type="checkbox"
                    :value="variant.id"
                    v-model="selectedVariantIds"
                    class="rounded border-[#C7D0CD] text-[#BA5A5A] focus:ring-[#BA5A5A]"
                  />
                </td>

                <td class="py-4 px-4">
                  <span class="block font-bold text-[#293331]">
                    {{ variant.product_name }}
                  </span>

                  <span class="text-xs text-[#899492]">
                    {{ variant.name }}

                    <span
                      v-if="variant.sku"
                      class="font-mono text-[#A5AEAB]"
                    >
                      ({{ variant.sku }})
                    </span>
                  </span>

                  <p
                    v-if="variant.rejection_notes"
                    class="mt-2 text-xs text-[#BA5A5A] bg-[#BA5A5A]/10 px-2.5 py-1.5 rounded-lg border border-[#BA5A5A]/20 inline-block"
                  >
                    ⚠️ Catatan: {{ variant.rejection_notes }}
                  </p>
                </td>

                <td class="py-4 px-4 font-mono text-[#687572]">
                  {{ formatCurrency(variant.normal_price_snapshot) }}
                </td>

                <td class="py-4 px-4 font-mono font-bold text-[#527842]">
                  <span class="block">
                    {{ formatCurrency(variant.campaign_price) }}
                  </span>

                  <span
                    v-if="variant.discount_price < variant.normal_price_snapshot"
                    class="text-xs font-normal text-[#A5AEAB] line-through"
                  >
                    {{ formatCurrency(variant.discount_price) }}
                  </span>
                </td>

                <td class="py-4 px-4 text-[#687572]">
                  <span class="block font-medium">
                    {{ variant.promotion_stock }} unit
                  </span>

                  <span
                    v-if="variant.purchase_limit > 0"
                    class="text-xs text-[#A5AEAB]"
                  >
                    Max {{ variant.purchase_limit }}/user
                  </span>
                </td>

                <td class="py-4 px-4 text-center">
                  <span
                    class="px-2.5 py-1 rounded-full text-[10px] font-bold inline-block"
                    :class="getVariantStatusBadgeClass(variant.approval_status)"
                  >
                    {{ variant.approval_status }}
                  </span>
                </td>

                <td class="py-4 px-4 text-right">
                  <div class="inline-flex flex-wrap justify-end gap-2">
                    <button
                      @click="handleAction('Approved', variant)"
                      class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap"
                      :class="
                        variant.approval_status === 'Approved'
                          ? 'bg-[#A4CE8B] text-[#304A27]'
                          : 'bg-[#A4CE8B]/15 text-[#527842] hover:bg-[#A4CE8B]/30 border border-[#A4CE8B]/50'
                      "
                    >
                      ✓
                      {{
                        variant.approval_status === 'Approved'
                          ? 'Disetujui'
                          : 'Approve'
                      }}
                    </button>

                    <button
                      @click="handleAction('Rejected', variant)"
                      class="px-3 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap"
                      :class="
                        variant.approval_status === 'Rejected'
                          ? 'bg-[#BA5A5A] text-white'
                          : 'bg-[#BA5A5A]/10 text-[#BA5A5A] hover:bg-[#BA5A5A]/15 border border-[#BA5A5A]/25'
                      "
                    >
                      ✕
                      {{
                        variant.approval_status === 'Rejected'
                          ? 'Ditolak'
                          : 'Reject'
                      }}
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="reviewData.variants.length === 0">
                <td
                  colspan="7"
                  class="py-12 text-center text-[#899492] text-sm"
                >
                  Belum ada variant yang dipetakan ke promosi ini.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- =======================================================
           CAMPAIGN TASKS
      ======================================================== -->
      <div
        v-if="
          reviewData.type === 'Campaign' &&
          reviewData.tasks &&
          reviewData.tasks.length
        "
        class="bg-white rounded-[24px] p-6 sm:p-8 border border-[#E3E9E6] shadow-[0_8px_30px_rgba(41,51,49,0.05)]"
      >
        <div class="mb-6">
          <h2
            class="text-lg font-extrabold text-[#293331] flex items-center gap-2"
          >
            <span
              class="w-8 h-8 rounded-lg bg-[#86BCBD]/15 flex items-center justify-center overflow-hidden"
            >
              <img
                src="/images/task.webp"
                alt="Statistics"
                class="w-6 h-6 object-contain"
              />
            </span>
            <span>Daftar Task Campaign</span>
          </h2>

          <p class="text-xs text-[#899492] mt-1">
            Perbarui status pengerjaan dan kirim hasil visual jika dibutuhkan.
          </p>
        </div>

        <div class="space-y-4">
          <div
            v-for="task in reviewData.tasks"
            :key="task.id"
            class="rounded-2xl bg-[#F8FAF9] border border-[#E3E9E6] p-5"
          >
            <div
              class="flex flex-col sm:flex-row sm:items-start justify-between gap-4"
            >
              <div class="min-w-0 flex-1">
                <p
                  class="font-bold text-[#293331] flex items-center gap-2 flex-wrap"
                >
                  <span>{{ task.name }}</span>

                  <span
                    v-if="task.requires_visual"
                    class="px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase tracking-wider bg-[#86BCBD]/20 text-[#315F60] border border-[#86BCBD]/30"
                  >
                    Butuh Visual
                  </span>

                  <span
                    class="px-2.5 py-0.5 rounded-full text-[9px] font-bold"
                    :class="getTaskStatusBadgeClass(task.progress_status)"
                  >
                    {{ getTaskStatusLabel(task.progress_status) }}
                  </span>
                </p>

                <p class="text-xs text-[#899492] mt-2">
                  <span
                    v-if="task.visual_type"
                    class="font-medium"
                  >
                    Jenis Visual: {{ task.visual_type }}
                  </span>

                  <span
                    v-if="task.deadline"
                    class="ml-2"
                  >
                    ⏰ Deadline: {{ formatDate(task.deadline) }}
                  </span>
                </p>

                <p
                  v-if="task.creative_brief"
                  class="text-xs text-[#687572] mt-2 leading-relaxed"
                >
                  Brief:
                  {{
                    Array.isArray(task.creative_brief)
                      ? task.creative_brief.join(', ')
                      : task.creative_brief
                  }}
                </p>
              </div>

              <div class="shrink-0 rounded-xl border border-[#D5DDDA] bg-white px-3.5 py-2 text-xs font-medium text-[#687572]">
                Status dikelola oleh Tim dan PIC SUNTRACK
              </div>
            </div>

            <!-- Visual Submission -->
            <div
              v-if="task.requires_visual"
              class="mt-5 pt-5 border-t border-[#E3E9E6]"
            >
              <div
                v-if="task.visual_link || task.visual_file_url"
                class="mb-4 p-4 rounded-xl bg-[#A4CE8B]/15 border border-[#A4CE8B]/40 text-xs text-[#426332]"
              >
                <p class="font-bold mb-2">
                  Visual sudah dikirim:
                  {{ task.visual_file_name || 'via Link' }}
                </p>

                <p
                  v-if="task.visual_link"
                  class="mb-1"
                >
                  🔗 Link:
                  <a
                    :href="task.visual_link"
                    target="_blank"
                    rel="noopener"
                    class="underline font-semibold"
                  >
                    {{ task.visual_link }}
                  </a>
                </p>

                <p
                  v-if="task.visual_file_url"
                  class="mb-1"
                >
                  🖼️ File:
                  <a
                    :href="task.visual_file_url"
                    target="_blank"
                    rel="noopener"
                    class="underline font-semibold"
                  >
                    {{ task.visual_file_name || 'Lihat gambar' }}
                  </a>
                </p>

                <p
                  v-if="task.submitted_by"
                  class="text-[#527842] mt-2"
                >
                  Dikirim oleh: {{ task.submitted_by }}
                  {{
                    task.submitted_at
                      ? 'pada ' + formatDateTime(task.submitted_at)
                      : ''
                  }}
                </p>

                <button
                  v-if="task.visual_file_url || task.visual_link"
                  @click="handleDeleteVisual(task)"
                  class="mt-3 px-3 py-1.5 text-[#BA5A5A] bg-white border border-[#BA5A5A]/25 rounded-lg text-xs font-bold hover:bg-[#BA5A5A]/10"
                >
                  Hapus Visual
                </button>
              </div>

              <form @submit.prevent="handleSubmitVisual(task)">
                <label
                  class="block text-xs font-bold text-[#52605E] mb-1.5"
                >
                  Link Google Drive / URL Visual
                </label>

                <input
                  type="url"
                  v-model="taskVisualLinks[task.id]"
                  placeholder="https://drive.google.com/..."
                  class="w-full rounded-xl border border-[#D5DDDA] bg-white px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#86BCBD]/30 focus:border-[#86BCBD] mb-3"
                />

                <label
                  class="block text-xs font-bold text-[#52605E] mb-1.5"
                >
                  Atau unggah gambar
                  <span class="font-normal text-[#899492]">
                    (JPG/PNG/WEBP/GIF, maks 5MB)
                  </span>
                </label>

                <input
                  type="file"
                  accept="image/*"
                  @change="(e) => onTaskFileChange(e, task.id)"
                  class="block w-full text-sm text-[#687572] file:mr-3 file:rounded-lg file:border-0 file:bg-[#F7E49B]/40 file:px-3 file:py-2 file:text-[#79651A] file:font-bold mb-3"
                />

                <div
                  v-if="taskFileErrors[task.id]"
                  class="text-[#BA5A5A] text-xs mb-2"
                >
                  {{ taskFileErrors[task.id] }}
                </div>

                <div
                  v-if="taskVisualPreviews[task.id]"
                  class="mb-3"
                >
                  <img
                    :src="taskVisualPreviews[task.id]"
                    alt="Preview"
                    class="max-h-40 rounded-xl border border-[#D5DDDA]"
                  />
                </div>

                <button
                  type="button"
                  @click="handleSubmitVisual(task)"
                  :disabled="
                    taskBusy(task.id) ||
                    (!taskVisualFiles[task.id] &&
                      !taskVisualLinks[task.id]) ||
                    taskFileErrors[task.id]
                  "
                  class="px-4 py-2.5 rounded-xl bg-[#BA5A5A] hover:bg-[#A84F4F] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-xs transition shadow-sm"
                >
                  Kirim Visual
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- =======================================================
           COMMENTS
      ======================================================== -->
      <div
        class="bg-white rounded-[24px] p-6 sm:p-8 border border-[#E3E9E6] shadow-[0_8px_30px_rgba(41,51,49,0.05)]"
      >
        <h2
          class="text-lg font-extrabold text-[#293331] flex items-center gap-2 mb-6"
        >
            <span
              class="w-8 h-8 rounded-lg bg-[#86BCBD]/15 flex items-center justify-center overflow-hidden"
            >
              <img
                src="/images/chat.webp"
                alt="Statistics"
                class="w-6 h-6 object-contain"
              />
            </span>

          <span>Diskusi & Komentar</span>

          <span
            class="text-[10px] font-bold text-[#687572] bg-[#F1F4F3] px-2 py-1 rounded-full"
          >
            {{ reviewData.comments?.length || 0 }}
          </span>
        </h2>

        <div
          class="space-y-4 mb-6 max-h-96 overflow-y-auto pr-2"
        >
          <div
            v-for="comment in reviewData.comments"
            :key="comment.id"
            class="p-4 rounded-2xl border transition"
            :class="
              comment.author_type === 'Admin'
                ? 'bg-[#86BCBD]/10 border-[#86BCBD]/25 ml-4 sm:ml-8'
                : 'bg-[#F8FAF9] border-[#E3E9E6] mr-4 sm:mr-8'
            "
          >
            <div
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2"
            >
              <div class="flex items-center gap-2 flex-wrap">
                <span
                  class="px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase tracking-wider"
                  :class="
                    comment.author_type === 'Admin'
                      ? 'bg-[#86BCBD] text-[#293331]'
                      : 'bg-[#F7E49B] text-[#79651A]'
                  "
                >
                  {{ comment.author_type }}
                </span>

                <span class="font-bold text-[#293331] text-sm">
                  {{ comment.author_name }}
                </span>

                <span
                  v-if="comment.author_position"
                  class="text-xs text-[#899492]"
                >
                  ({{ comment.author_position }})
                </span>
              </div>

              <span class="text-xs text-[#A0AAA7]">
                {{ formatDateTime(comment.created_at) }}
              </span>
            </div>

            <p
              class="text-sm text-[#52605E] whitespace-pre-line leading-relaxed"
            >
              {{ comment.body }}
            </p>
          </div>

          <div
            v-if="!reviewData.comments || reviewData.comments.length === 0"
            class="py-10 text-center bg-[#F8FAF9] rounded-2xl border border-dashed border-[#D5DDDA] text-[#899492] text-sm"
          >
            Belum ada komentar diskusi.
          </div>
        </div>

        <div
          class="bg-[#F8FAF9] rounded-2xl p-4 border border-[#E3E9E6]"
        >
          <label
            class="block text-[10px] font-extrabold uppercase tracking-wider text-[#687572] mb-2"
          >
            Tulis Komentar atau Feedback
          </label>

          <textarea
            v-model="newCommentBody"
            rows="3"
            placeholder="Tulis pesan, pertanyaan, atau catatan kolaborasi untuk Admin..."
            class="w-full rounded-xl border border-[#D5DDDA] bg-white p-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#86BCBD]/25 focus:border-[#86BCBD] mb-3"
          ></textarea>

          <div
            class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
          >
            <span class="text-xs text-[#899492]">
              Posting sebagai:
              <strong class="text-[#293331]">
                {{
                  isIdentified()
                    ? reviewerIdentity.name
                    : 'Belum Teridentifikasi'
                }}
              </strong>
            </span>

            <button
              @click="handlePostComment"
              :disabled="!newCommentBody.trim() || loading"
              class="px-5 py-2.5 bg-[#BA5A5A] hover:bg-[#A84F4F] disabled:opacity-50 text-white font-bold text-xs rounded-xl transition shadow-sm"
            >
              Kirim Komentar
            </button>
          </div>
        </div>
      </div>

      <!-- =======================================================
           ACTIVITY TIMELINE
      ======================================================== -->
      <div
        class="bg-white rounded-[24px] p-6 sm:p-8 border border-[#E3E9E6] shadow-[0_8px_30px_rgba(41,51,49,0.05)]"
      >
        <h2
          class="text-lg font-extrabold text-[#293331] flex items-center gap-2 mb-7"
        >
            <span
              class="w-8 h-8 rounded-lg bg-[#86BCBD]/15 flex items-center justify-center overflow-hidden"
            >
              <img
                src="/images/activity.webp"
                alt="Statistics"
                class="w-6 h-6 object-contain"
              />
            </span>

          <span>Kronologi Aktivitas</span>
        </h2>

        <div
          class="relative pl-7 border-l-2 border-[#E3E9E6] space-y-7"
        >
          <div
            v-for="log in latestTimeline"
            :key="log.id"
            class="relative group"
          >
            <div
              class="absolute -left-[35px] top-0 w-4 h-4 rounded-full border-[3px] border-white shadow-sm"
              :class="
                log.actor_type === 'Admin'
                  ? 'bg-[#86BCBD]'
                  : 'bg-[#F7E49B]'
              "
            ></div>

            <div
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-1"
            >
              <span
                class="font-bold text-[#293331] text-sm flex items-center gap-2"
              >
                <span>{{ log.action }}</span>

                <span
                  class="px-1.5 py-0.5 rounded text-[8px] font-extrabold uppercase"
                  :class="
                    log.actor_type === 'Admin'
                      ? 'bg-[#86BCBD]/20 text-[#315F60]'
                      : 'bg-[#F7E49B]/50 text-[#79651A]'
                  "
                >
                  {{ log.actor_type }}
                </span>
              </span>

              <span class="text-xs text-[#A0AAA7]">
                {{ formatDateTime(log.created_at) }}
              </span>
            </div>

            <p
              class="text-xs sm:text-sm text-[#687572] leading-relaxed"
            >
              {{ log.description }}
            </p>

            <p
              v-if="log.actor_name"
              class="text-xs text-[#A0AAA7] mt-1"
            >
              Oleh: {{ log.actor_name }}

              <span v-if="log.actor_position">
                ({{ log.actor_position }})
              </span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- =========================================================
         IDENTITY MODAL
    ========================================================== -->
    <div
      v-if="showIdentityModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#293331]/65 backdrop-blur-sm"
    >
      <div
        class="bg-white rounded-[26px] max-w-md w-full p-6 sm:p-7 shadow-2xl border border-[#E3E9E6]"
      >
        <div
          class="flex items-center justify-between pb-4 border-b border-[#EDF1EF] mb-5"
        >
          <div>
            <span
              class="text-[10px] font-extrabold uppercase tracking-wider text-[#BA5A5A]"
            >
              Reviewer
            </span>

            <h3 class="text-lg font-extrabold text-[#293331] mt-1">
              {{
                isIdentified()
                  ? 'Ubah Identitas Reviewer'
                  : 'Identifikasi Reviewer'
              }}
            </h3>
          </div>

          <button
            @click="showIdentityModal = false"
            class="w-8 h-8 rounded-lg text-[#899492] hover:bg-[#F1F4F3] hover:text-[#293331] transition"
          >
            ✕
          </button>
        </div>

        <p class="text-xs text-[#687572] mb-5 leading-relaxed">
          Lengkapi identitas Anda untuk pencatatan audit trail pada setiap
          aktivitas review.
        </p>

        <form
          @submit.prevent="submitIdentityForm"
          class="space-y-4"
        >
          <div>
            <label
              class="block text-xs font-bold text-[#52605E] mb-1.5"
            >
              Nama Lengkap
              <span class="text-[#BA5A5A]">*</span>
            </label>

            <input
              v-model="identityForm.name"
              type="text"
              required
              placeholder="Contoh: Budi Santoso"
              class="w-full rounded-xl border border-[#D5DDDA] px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#86BCBD]/25 focus:border-[#86BCBD]"
            />
          </div>

          <div>
            <label
              class="block text-xs font-bold text-[#52605E] mb-1.5"
            >
              Jabatan / Posisi
              <span class="text-[#899492] font-normal">
                (Opsional)
              </span>
            </label>

            <input
              v-model="identityForm.position"
              type="text"
              placeholder="Brand Manager / Marketing Director"
              class="w-full rounded-xl border border-[#D5DDDA] px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#86BCBD]/25 focus:border-[#86BCBD]"
            />
          </div>

          <div>
            <label
              class="block text-xs font-bold text-[#52605E] mb-1.5"
            >
              Nama Perusahaan / Brand
            </label>

            <input
              v-model="identityForm.companyName"
              type="text"
              disabled
              class="w-full rounded-xl border border-[#E3E9E6] bg-[#F1F4F3] px-3.5 py-2.5 text-sm text-[#899492]"
            />
          </div>

          <div>
            <label
              class="block text-xs font-bold text-[#52605E] mb-1.5"
            >
              Nomor WhatsApp
              <span class="text-[#899492] font-normal">
                (Opsional)
              </span>
            </label>

            <input
              v-model="identityForm.whatsappNumber"
              type="text"
              placeholder="081234567890"
              class="w-full rounded-xl border border-[#D5DDDA] px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#86BCBD]/25 focus:border-[#86BCBD]"
            />
          </div>

          <div
            class="flex items-center justify-end gap-3 pt-4 border-t border-[#EDF1EF]"
          >
            <button
              type="button"
              @click="showIdentityModal = false"
              class="px-4 py-2.5 rounded-xl border border-[#D5DDDA] text-xs font-bold text-[#687572] hover:bg-[#F8FAF9] transition"
            >
              Batal
            </button>

            <button
              type="submit"
              :disabled="!identityForm.name.trim() || loading"
              class="px-5 py-2.5 rounded-xl bg-[#BA5A5A] hover:bg-[#A84F4F] disabled:opacity-50 text-white text-xs font-bold transition shadow-sm"
            >
              Simpan Identitas
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- =========================================================
         REJECTION MODAL
    ========================================================== -->
    <div
      v-if="showRejectModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#293331]/65 backdrop-blur-sm"
    >
      <div
        class="bg-white rounded-[26px] max-w-md w-full p-6 sm:p-7 shadow-2xl border border-[#E3E9E6]"
      >
        <div
          class="flex items-center justify-between pb-4 border-b border-[#EDF1EF] mb-5"
        >
          <div>
            <span
              class="text-[10px] font-extrabold uppercase tracking-wider text-[#BA5A5A]"
            >
              Approval Review
            </span>

            <h3 class="text-lg font-extrabold text-[#BA5A5A] mt-1">
              Tolak Variant Produk
            </h3>
          </div>

          <button
            @click="showRejectModal = false"
            class="w-8 h-8 rounded-lg text-[#899492] hover:bg-[#F1F4F3]"
          >
            ✕
          </button>
        </div>

        <p class="text-xs text-[#687572] mb-4">
          Anda akan menolak variant:
          <strong class="text-[#293331]">
            {{ selectedVariant?.product_name }} -
            {{ selectedVariant?.name }}
          </strong>
        </p>

        <form
          @submit.prevent="executeReject"
          class="space-y-4"
        >
          <div>
            <label
              class="block text-xs font-bold text-[#52605E] mb-1.5"
            >
              Catatan Penolakan
              <span class="text-[#BA5A5A]">*</span>
            </label>

            <textarea
              v-model="rejectionNoteInput"
              rows="4"
              required
              placeholder="Jelaskan alasan penolakan..."
              class="w-full rounded-xl border border-[#D99A9A] p-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#BA5A5A]/20 focus:border-[#BA5A5A]"
            ></textarea>

            <p class="text-[10px] text-[#BA5A5A] mt-1.5">
              Alasan penolakan wajib disertakan.
            </p>
          </div>

          <div
            class="flex items-center justify-end gap-3 pt-4 border-t border-[#EDF1EF]"
          >
            <button
              type="button"
              @click="showRejectModal = false"
              class="px-4 py-2.5 rounded-xl border border-[#D5DDDA] text-xs font-bold text-[#687572]"
            >
              Batal
            </button>

            <button
              type="submit"
              :disabled="!rejectionNoteInput.trim() || loading"
              class="px-5 py-2.5 rounded-xl bg-[#BA5A5A] hover:bg-[#A84F4F] disabled:opacity-50 text-white text-xs font-bold transition"
            >
              Konfirmasi Penolakan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- =========================================================
         APPROVAL HISTORY
    ========================================================== -->
    <div
      v-if="showHistoryModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#293331]/65 backdrop-blur-sm"
    >
      <div
        class="bg-white rounded-[26px] max-w-2xl w-full p-6 sm:p-7 shadow-2xl border border-[#E3E9E6] max-h-[85vh] flex flex-col"
      >
        <div
          class="flex items-center justify-between pb-4 border-b border-[#EDF1EF] mb-4"
        >
          <div>
            <span
              class="text-[10px] font-extrabold uppercase tracking-wider text-[#86BCBD]"
            >
              Audit Trail
            </span>

            <h3 class="text-lg font-extrabold text-[#293331] mt-1">
              Riwayat Approval
            </h3>
          </div>

          <button
            @click="showHistoryModal = false"
            class="w-8 h-8 rounded-lg text-[#899492] hover:bg-[#F1F4F3]"
          >
            ✕
          </button>
        </div>

        <div
          class="flex-grow overflow-y-auto space-y-3 pr-2"
        >
          <div
            v-for="hist in reviewData.approval_histories"
            :key="hist.id"
            class="p-4 rounded-2xl bg-[#F8FAF9] border border-[#E3E9E6]"
          >
            <div
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 font-bold text-[#293331] mb-2"
            >
              <span>
                {{ hist.variant_name }}

                <span
                  v-if="hist.variant_sku"
                  class="font-mono font-normal text-[#A0AAA7]"
                >
                  ({{ hist.variant_sku }})
                </span>
              </span>

              <span class="text-xs text-[#A0AAA7] font-normal">
                {{ formatDateTime(hist.created_at) }}
              </span>
            </div>

            <div class="flex items-center gap-2 my-3 text-xs">
              <span
                class="px-2.5 py-1 rounded-lg bg-[#E7ECEA] font-semibold text-[#687572]"
              >
                {{ hist.old_status }}
              </span>

              <span class="text-[#A0AAA7]">→</span>

              <span
                class="px-2.5 py-1 rounded-lg font-bold"
                :class="
                  hist.new_status === 'Approved'
                    ? 'bg-[#A4CE8B]/25 text-[#426332]'
                    : 'bg-[#BA5A5A]/10 text-[#BA5A5A]'
                "
              >
                {{ hist.new_status }}
              </span>
            </div>

            <p
              v-if="hist.notes"
              class="text-xs text-[#BA5A5A] bg-[#BA5A5A]/10 p-3 rounded-xl border border-[#BA5A5A]/20"
            >
              📝 {{ hist.notes }}
            </p>

            <p
              class="text-[10px] text-[#899492] mt-3 border-t border-[#E3E9E6] pt-2"
            >
              Reviewer:
              <strong class="text-[#52605E]">
                {{ hist.reviewer_name }}
              </strong>

              <span v-if="hist.reviewer_position">
                ({{ hist.reviewer_position }})
              </span>

              <span v-if="hist.company_name">
                • {{ hist.company_name }}
              </span>
            </p>
          </div>

          <div
            v-if="
              !reviewData.approval_histories ||
              reviewData.approval_histories.length === 0
            "
            class="py-10 text-center text-[#899492] text-sm"
          >
            Belum ada riwayat perubahan status approval.
          </div>
        </div>

        <div
          class="pt-4 border-t border-[#EDF1EF] text-right mt-4"
        >
          <button
            @click="showHistoryModal = false"
            class="px-5 py-2.5 bg-[#293331] hover:bg-[#3D4B48] text-white font-bold text-xs rounded-xl transition"
          >
            Tutup Riwayat
          </button>
        </div>
      </div>
    </div>

    <!-- =========================================================
         TOASTS
    ========================================================== -->
    <div
      class="fixed top-4 right-4 z-[60] flex flex-col items-end gap-2 max-w-sm w-[calc(100%-2rem)]"
    >
      <transition-group name="toast" tag="div">
        <div
          v-for="t in toasts"
          :key="t.id"
          class="w-full px-4 py-3 rounded-xl shadow-lg flex items-start gap-3 border"
          :class="{
            'bg-[#A4CE8B] text-[#304A27] border-[#94C27A]':
              t.type === 'success',
            'bg-[#BA5A5A] text-white border-[#A84F4F]':
              t.type === 'error',
            'bg-[#293331] text-white border-[#3D4B48]':
              t.type === 'info'
          }"
        >
          <div
            class="flex-1 text-sm"
            v-html="t.message"
          ></div>

          <button
            @click="removeToast(t.id)"
            class="opacity-70 hover:opacity-100"
          >
            ✕
          </button>
        </div>
      </transition-group>
    </div>

    <!-- =========================================================
         CONFIRM MODAL
    ========================================================== -->
    <div
      v-if="confirmModal.show"
      class="fixed inset-0 z-[55] flex items-center justify-center p-4 bg-[#293331]/65 backdrop-blur-sm"
    >
      <div
        class="bg-white rounded-[24px] max-w-md w-full p-6 shadow-2xl border border-[#E3E9E6]"
      >
        <div
          class="w-10 h-10 rounded-xl bg-[#F7E49B]/40 flex items-center justify-center mb-4"
        >
          ?
        </div>

        <div class="text-lg font-extrabold text-[#293331] mb-2">
          Konfirmasi
        </div>

        <p class="text-sm text-[#687572] mb-6 leading-relaxed">
          {{ confirmModal.message }}
        </p>

        <div class="flex justify-end gap-3">
          <button
            @click="confirmCancel"
            class="px-4 py-2.5 rounded-xl border border-[#D5DDDA] text-xs font-bold text-[#687572] hover:bg-[#F8FAF9]"
          >
            Batal
          </button>

          <button
            @click="confirmOk"
            class="px-5 py-2.5 rounded-xl bg-[#BA5A5A] hover:bg-[#A84F4F] text-white text-xs font-bold"
          >
            Ya, Lanjutkan
          </button>
        </div>
      </div>
    </div>

    <!-- =========================================================
         PROMPT MODAL
    ========================================================== -->
    <div
      v-if="promptModal.show"
      class="fixed inset-0 z-[55] flex items-center justify-center p-4 bg-[#293331]/65 backdrop-blur-sm"
    >
      <div
        class="bg-white rounded-[24px] max-w-md w-full p-6 shadow-2xl border border-[#E3E9E6]"
      >
        <div
          class="w-10 h-10 rounded-xl bg-[#F7E49B]/40 flex items-center justify-center mb-4"
        >
          ✎
        </div>

        <div class="text-lg font-extrabold text-[#293331] mb-2">
          {{ promptModal.title || 'Masukkan' }}
        </div>

        <p
          v-if="promptModal.message"
          class="text-sm text-[#687572] mb-4"
        >
          {{ promptModal.message }}
        </p>

        <input
          v-model="promptModal.value"
          type="text"
          class="w-full rounded-xl border border-[#D5DDDA] px-3.5 py-2.5 text-sm mb-5 focus:outline-none focus:ring-2 focus:ring-[#86BCBD]/25 focus:border-[#86BCBD]"
        />

        <div class="flex justify-end gap-3">
          <button
            @click="promptCancel"
            class="px-4 py-2.5 rounded-xl border border-[#D5DDDA] text-xs font-bold text-[#687572]"
          >
            Batal
          </button>

          <button
            @click="promptOk"
            class="px-5 py-2.5 rounded-xl bg-[#BA5A5A] hover:bg-[#A84F4F] text-white text-xs font-bold"
          >
            Kirim
          </button>
        </div>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import { useRoute } from 'vue-router';
import PublicLayout from '../layouts/PublicLayout.vue';
import { usePublicReview } from '../composables/usePublicReview';

const route = useRoute();
const token = route.params.token;

const {
  loading,
  error,
  reviewData,
  linkStatus,
  errorMessage,
  reviewerIdentity,
  isIdentified,
  saveIdentity,
  fetchReviewData,
  updateReviewStatus,
  submitApproval,
  submitBatchApproval,
  submitComment,
  updateTaskProgress,
  submitTaskVisual,
  deleteTaskVisual,
} = usePublicReview();

const showIdentityModal = ref(false);
const showRejectModal = ref(false);
const showHistoryModal = ref(false);
const selectedVariant = ref(null);
const rejectionNoteInput = ref('');
const newCommentBody = ref('');
const selectedVariantIds = ref([]);
const batchLoading = ref(false);
const pendingAction = ref(null);
const isDelivery = computed(() => ['Task', 'PerformanceReport'].includes(reviewData.value?.type));

const handleDeliveryComment = async () => {
  if (!reviewerIdentity.name?.trim() || !newCommentBody.value.trim()) return;
  localStorage.setItem('suntrack_reviewer_name', reviewerIdentity.name.trim());
  await submitComment(token, newCommentBody.value.trim());
  newCommentBody.value = '';
};

const taskBusyIds = ref(new Set());
const taskVisualFiles = ref({});
const taskVisualLinks = ref({});
const taskVisualPreviews = ref({});
const taskFileErrors = ref({});

const taskBusy = (taskId) => taskBusyIds.value.has(taskId);

const handleStatusChange = async (event) => {
  const newStatus = event.target.value;

  if (!reviewData.value) return;

  const oldStatus = reviewData.value.status;

  if (newStatus === oldStatus) return;

  try {
    loading.value = true;

    await updateReviewStatus(token, newStatus);

    // Ambil ulang data dari database
    await fetchReviewData(token);

    showToast(
      'success',
      `Status berhasil diubah menjadi <strong>${newStatus}</strong>.`
    );
  } catch (err) {
    console.error('handleStatusChange error:', err);

    // Kembalikan ke data database
    await fetchReviewData(token);

    showToast(
      'error',
      err.response?.data?.message ||
      err.message ||
      'Gagal mengubah status.'
    );
  } finally {
    loading.value = false;
  }
};

const handleTaskStatus = async (task, status) => {
  if (!isIdentified()) {
    pendingAction.value = { type: 'task_progress', taskId: task.id, status };
    openIdentityModal(true);
    return;
  }

  // If task requires visual and marking as Completed, ensure visual submitted (existing or pending)
    if (task.requires_visual && status === 'Completed') {
    const existing = task.visual_link || task.visual_file_url;
    const pendingLink = (taskVisualLinks.value[task.id] || '').trim();
    const pendingFile = taskVisualFiles.value[task.id] || null;
    if (!existing && !pendingLink && !pendingFile) {
      showToast('error', 'Task ini membutuhkan visual. Silakan isi Link atau unggah gambar terlebih dahulu.');
      return;
    }
    // If there's a pending local file/link (not yet submitted), submit it first
    if (!existing && (pendingLink || pendingFile)) {
      const ok = await handleSubmitVisual(task);
      if (!ok) return; // abort if upload failed
      // Note: submitTaskVisual updates reviewData via composable
    }
  }

    try {
    taskBusyIds.value = new Set(taskBusyIds.value).add(task.id);
    await updateTaskProgress(token, task.id, status);
  } catch (err) {
    console.error('handleTaskStatus error', err.response || err);
    const msg = err.response?.data?.message || err.message || 'Gagal memperbarui status task.';
    showToast('error', msg);
  } finally {
    const s = new Set(taskBusyIds.value);
    s.delete(task.id);
    taskBusyIds.value = s;
  }
};

const handleSubmitVisual = async (task) => {
  if (!isIdentified()) {
    pendingAction.value = { type: 'task_visual', taskId: task.id };
    openIdentityModal(true);
    return false;
  }
  const link = (taskVisualLinks.value[task.id] || '').trim();
  const file = taskVisualFiles.value[task.id] || null;
  if (!link && !file) {
    showToast('error', 'Isi Link Google Drive atau pilih gambar terlebih dahulu.');
    return false;
  }
  if (taskFileErrors.value[task.id]) {
    showToast('error', taskFileErrors.value[task.id]);
    return false;
  }
  try {
    taskBusyIds.value = new Set(taskBusyIds.value).add(task.id);
    const fd = new FormData();
    fd.append('visual_link', link);
    fd.append('reviewer_name', reviewerIdentity.name);
    fd.append('reviewer_position', reviewerIdentity.position);
    if (file) fd.append('visual_file', file);
    await submitTaskVisual(token, task.id, fd);
    taskVisualLinks.value[task.id] = '';
    taskVisualFiles.value[task.id] = null;
    if (taskVisualPreviews.value[task.id]) {
      URL.revokeObjectURL(taskVisualPreviews.value[task.id]);
      taskVisualPreviews.value[task.id] = null;
    }
    taskFileErrors.value[task.id] = null;
    return true;
  } catch (err) {
    console.error('handleSubmitVisual error', err.response || err);
    showToast('error', err.response?.data?.message || err.message || 'Gagal mengirim visual task.');
    return false;
  } finally {
    const s = new Set(taskBusyIds.value);
    s.delete(task.id);
    taskBusyIds.value = s;
  }
};

const handleDeleteVisual = async (task) => {
  if (!isIdentified()) {
    pendingAction.value = { type: 'delete_visual', taskId: task.id };
    openIdentityModal(true);
    return;
  }
  const ok = await showConfirm('Yakin ingin menghapus visual ini?');
  if (!ok) return;
  try {
    taskBusyIds.value = new Set(taskBusyIds.value).add(task.id);
    await deleteTaskVisual(token, task.id);
  } catch (err) {
    console.error('handleDeleteVisual error', err.response || err);
    showToast('error', err.response?.data?.message || err.message || 'Gagal menghapus visual task.');
  } finally {
    const s = new Set(taskBusyIds.value);
    s.delete(task.id);
    taskBusyIds.value = s;
  }
};

const latestTimeline = computed(() => {
  return [...(reviewData.value?.timeline || [])]
    .slice(-10)
    .reverse();
});

const isAllSelected = computed(() => {
  return (
    reviewData.value?.variants?.length > 0 &&
    selectedVariantIds.value.length === reviewData.value.variants.length
  );
});

// Validate file (type and size)
const validateFile = (file) => {
  if (!file) return 'File tidak ditemukan.';
  const allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
  if (!allowed.includes(file.type)) return 'Tipe file tidak didukung. Gunakan JPG, PNG, WEBP atau GIF.';
  const max = 5 * 1024 * 1024; // 5MB
  if (file.size > max) return 'Ukuran file terlalu besar. Maksimum 5MB.';
  return null;
};

const onTaskFileChange = (e, taskId) => {
  const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
  if (!file) {
    if (taskVisualPreviews.value[taskId]) {
      URL.revokeObjectURL(taskVisualPreviews.value[taskId]);
      taskVisualPreviews.value[taskId] = null;
    }
    taskVisualFiles.value[taskId] = null;
    taskFileErrors.value[taskId] = null;
    return;
  }
  const err = validateFile(file);
  if (err) {
    taskVisualFiles.value[taskId] = null;
    taskFileErrors.value[taskId] = err;
    if (taskVisualPreviews.value[taskId]) {
      URL.revokeObjectURL(taskVisualPreviews.value[taskId]);
      taskVisualPreviews.value[taskId] = null;
    }
    return;
  }
  // valid
  taskVisualFiles.value[taskId] = file;
  taskFileErrors.value[taskId] = null;
  if (taskVisualPreviews.value[taskId]) {
    URL.revokeObjectURL(taskVisualPreviews.value[taskId]);
  }
  taskVisualPreviews.value[taskId] = URL.createObjectURL(file);
};

onUnmounted(() => {
  // revoke all object URLs
  Object.values(taskVisualPreviews.value || {}).forEach((url) => {
    if (url) URL.revokeObjectURL(url);
  });
});

const hasVisualForTask = (task) => {
  if (!task) return false;
  const existing = task.visual_link || task.visual_file_url;
  const pendingLink = (taskVisualLinks.value[task.id] || '').trim();
  const pendingFile = taskVisualFiles.value[task.id] || null;
  return Boolean(existing || pendingLink || pendingFile);
};

// When a task requires visual and is already Completed, lock further changes
const taskIsLocked = (task) => {
  if (!task) return false;
  if (!task.requires_visual) return false;
  const hasVisual = Boolean(task.visual_link || task.visual_file_url);
  return task.progress_status === 'Completed' && hasVisual;
};

// Toasts
const toasts = ref([]);
let nextToastId = 1;
const showToast = (type, message, timeout = 4000) => {
  const id = nextToastId++;
  toasts.value.push({ id, type, message });
  if (timeout > 0) setTimeout(() => removeToast(id), timeout);
};
const removeToast = (id) => {
  toasts.value = toasts.value.filter((t) => t.id !== id);
};

// Confirm modal (promise-based)
const confirmModal = reactive({ show: false, message: '', resolve: null });
const showConfirm = (message) => {
  return new Promise((resolve) => {
    confirmModal.message = message;
    confirmModal.show = true;
    confirmModal.resolve = resolve;
  });
};
const confirmOk = () => {
  if (confirmModal.resolve) confirmModal.resolve(true);
  confirmModal.show = false;
};
const confirmCancel = () => {
  if (confirmModal.resolve) confirmModal.resolve(false);
  confirmModal.show = false;
};

// Prompt modal (promise-based)
const promptModal = reactive({ show: false, title: '', message: '', value: '', resolve: null });
const showPrompt = (message, title = '') => {
  return new Promise((resolve) => {
    promptModal.title = title;
    promptModal.message = message;
    promptModal.value = '';
    promptModal.show = true;
    promptModal.resolve = resolve;
  });
};
const promptOk = () => {
  if (promptModal.resolve) promptModal.resolve(promptModal.value);
  promptModal.show = false;
};
const promptCancel = () => {
  if (promptModal.resolve) promptModal.resolve(null);
  promptModal.show = false;
};

const toggleSelectAll = (e) => {
  selectedVariantIds.value = e.target.checked
    ? reviewData.value.variants.map((v) => v.id)
    : [];
};

const identityForm = reactive({
  name: reviewerIdentity.name || '',
  position: reviewerIdentity.position || '',
  companyName: reviewerIdentity.companyName || '',
  whatsappNumber: reviewerIdentity.whatsappNumber || '',
});

onMounted(async () => {
  await fetchReviewData(token);
  if (isIdentified()) {
    await saveIdentity(token, reviewerIdentity);
  }
});

const openIdentityModal = (open) => {
  if (open) {
    identityForm.name = reviewerIdentity.name || '';
    identityForm.position = reviewerIdentity.position || '';
    identityForm.companyName = reviewData.value?.brand?.name || reviewerIdentity.companyName || '';
    identityForm.whatsappNumber = reviewerIdentity.whatsappNumber || '';
  }
  showIdentityModal.value = open;
};

const submitIdentityForm = async () => {
  if (!identityForm.name || !identityForm.name.trim()) {
    showToast('error', 'Nama wajib diisi.');
    return;
  }
  const success = await saveIdentity(token, identityForm);
  if (success) {
    showIdentityModal.value = false;
    await fetchReviewData(token);
  }
};

const handleAction = async (action, variant) => {
  if (!isIdentified()) {
    pendingAction.value = { type: 'approve', variant, action };
    openIdentityModal(true);
    return;
  }
  if (action === 'Approved') {
    await executeApprove(variant.id);
  } else {
    confirmRejectVariant(variant);
  }
};

const executeApprove = async (variantId) => {
  try {
    await submitApproval(token, variantId, 'Approved');
  } catch (err) {
    showToast('error', err.message || 'Gagal menyetujui variant.');
  }
};

const confirmRejectVariant = (variant) => {
  selectedVariant.value = variant;
  rejectionNoteInput.value = variant.rejection_notes || '';
  showRejectModal.value = true;
};

const executeReject = async () => {
  if (!rejectionNoteInput.value || !rejectionNoteInput.value.trim()) {
    showToast('error', 'Catatan penolakan wajib diisi.');
    return;
  }
  try {
    await submitApproval(
      token,
      selectedVariant.value.id,
      'Rejected',
      rejectionNoteInput.value
    );
    showRejectModal.value = false;
    selectedVariant.value = null;
    rejectionNoteInput.value = '';
  } catch (err) {
    showToast('error', err.message || 'Gagal menolak variant.');
  }
};

const handleBatchAction = (action) => {
  if (!isIdentified()) {
    pendingAction.value = { type: 'batch', action };
    openIdentityModal(true);
    return;
  }
  if (action === 'reject_selected' || action === 'reject_all') {
    showPrompt('Masukkan catatan penolakan untuk batch ini (wajib):', 'Catatan Penolakan')
      .then((notes) => {
        if (notes === null) return;
        if (!notes.trim()) {
          showToast('error', 'Catatan penolakan tidak boleh kosong.');
          return;
        }
        executeBatchAction(action, notes.trim());
      });
  } else {
    showConfirm('Apakah Anda yakin ingin menyetujui batch ini?')
      .then((ok) => {
        if (ok) executeBatchAction(action);
      });
  }
};

const executeBatchAction = async (action, notes = '') => {
  batchLoading.value = true;
  try {
    await submitBatchApproval(token, action, selectedVariantIds.value, notes);
    selectedVariantIds.value = [];
  } catch (err) {
    showToast('error', err.message || 'Gagal memproses batch approval.');
  } finally {
    batchLoading.value = false;
  }
};

const handlePostComment = async () => {
  if (!newCommentBody.value || !newCommentBody.value.trim()) return;
  if (!isIdentified()) {
    pendingAction.value = { type: 'comment' };
    openIdentityModal(true);
    return;
  }
  await executeComment();
};

const executeComment = async () => {
  try {
    await submitComment(token, newCommentBody.value);
    newCommentBody.value = '';
  } catch (err) {
    showToast('error', err.message || 'Gagal mengirim komentar.');
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

const formatCurrency = (val) => {
  if (val === undefined || val === null) return 'Rp 0';
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(val);
};

const statusOptions = [
  // 'Draft',
  // 'Active',
  'Approved',
  'Partially Approved',
  'Rejected',
  // 'Completed',
];

const availableStatusOptions = computed(() => {
  const currentStatus = reviewData.value?.status;

  if (
    currentStatus &&
    !statusOptions.includes(currentStatus)
  ) {
    return [currentStatus, ...statusOptions];
  }

  return statusOptions;
});

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Approved':
    case 'Completed':
      return 'bg-[#A4CE8B]/25 text-[#3F6935] border-[#A4CE8B]';

    case 'Rejected':
      return 'bg-[#BA5A5A]/10 text-[#9B4141] border-[#BA5A5A]/40';

    case 'Partially Approved':
      return 'bg-[#F7E49B]/40 text-[#806A19] border-[#F7E49B]';

    case 'Active':
      return 'bg-[#86BCBD]/20 text-[#356B6C] border-[#86BCBD]';

    case 'Draft':
      return 'bg-[#F7E49B]/40 text-[#806A19] border-[#F7E49B]';

    default:
      return 'bg-slate-100 text-slate-700 border-slate-200';
  }
};

const getTaskStatusLabel = (status) => {
  const map = {
    pending: 'Menunggu',
    assigned: 'Ditugaskan',
    in_progress: 'Sedang Dikerjakan',
    waiting_review: 'Menunggu Review',
    revision: 'Revisi',
    completed: 'Selesai',
    on_hold: 'Ditunda',
    cancelled: 'Dibatalkan',
  };
  return map[status] || status || 'Belum Dikerjakan';
};

const getTaskStatusBadgeClass = (status) => {
  switch (status) {
    case 'completed':
      return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
    case 'in_progress':
      return 'bg-blue-100 text-blue-800 border border-blue-200';
    case 'revision':
      return 'bg-amber-100 text-amber-800 border border-amber-200';
    case 'on_hold':
      return 'bg-slate-100 text-slate-700 border border-slate-200';
    default:
      return 'bg-slate-100 text-slate-700 border border-slate-200';
  }
};

const getVariantStatusBadgeClass = (status) => {
  switch (status) {
    case 'Approved':
      return 'bg-emerald-100 text-emerald-800';
    case 'Rejected':
      return 'bg-rose-100 text-rose-800';
    default:
      return 'bg-amber-100 text-amber-800';
  }
};

</script>

