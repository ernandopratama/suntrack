<template>
  <PublicLayout>
    <div class="review-shell">
    <!-- =========================================================
         LOADING
    ========================================================== -->
    <div
      v-if="loading && !reviewData"
      class="min-h-[60vh] flex flex-col items-center justify-center px-4"
    >
      <div class="relative">
        <div
          class="w-14 h-14 rounded-full border-4 border-[#FFD41D]"
        ></div>
        <div
          class="absolute inset-0 w-14 h-14 rounded-full border-4 border-transparent border-t-[#D73535] animate-spin"
        ></div>
      </div>

      <p class="mt-5 text-sm font-semibold text-[#687370]">
        Memuat data peninjauan dan persetujuan...
      </p>

      <p class="mt-1 text-xs text-[#98A19E]">
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
        class="bg-white rounded-[28px] p-8 sm:p-10 border border-[#ECEEEC] shadow-[0_20px_60px_rgba(41,51,49,0.08)] text-center"
      >
        <div
          class="w-20 h-20 mx-auto rounded-3xl flex items-center justify-center text-3xl mb-6"
          :class="
            linkStatus === 'Expired'
              ? 'bg-[#FFD41D]/45 text-[#755700]'
              : 'bg-[#D73535]/10 text-[#D73535]'
          "
        >
          {{ linkStatus === 'Expired' ? '⏰' : '🔒' }}
        </div>

        <span
          class="inline-flex px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-[0.15em] mb-3"
          :class="
            linkStatus === 'Expired'
              ? 'bg-[#FFD41D]/40 text-[#755700]'
              : 'bg-[#D73535]/10 text-[#D73535]'
          "
        >
          Secure Public Link
        </span>

        <h2 class="text-2xl sm:text-3xl font-bold tracking-[-0.025em] text-[#46504D] mb-3">
          {{
            linkStatus === 'Expired'
              ? 'Tautan Sudah Kedaluwarsa'
              : linkStatus === 'Revoked'
                ? 'Tautan Telah Dinonaktifkan'
                : 'Tautan Tidak Ditemukan'
          }}
        </h2>

        <p class="text-[#77817E] mb-7 leading-relaxed text-sm sm:text-base">
          {{
            errorMessage ||
              'Tautan publik ini tidak lagi dapat diakses untuk peninjauan. Hal ini dapat terjadi karena masa berlaku tautan telah habis atau tautan telah ditarik kembali oleh Admin sistem.'
          }}
        </p>

        <div
          class="bg-[#FCFBF8] rounded-2xl p-5 border border-[#ECEEEC] text-sm text-[#77817E] mb-7 text-left"
        >
          <div class="flex gap-3">
            <div
              class="w-9 h-9 shrink-0 rounded-xl bg-[#FFA240]/20 text-[#9A4700] flex items-center justify-center"
            >
              ?
            </div>

            <div>
              <p class="font-bold text-[#46504D] mb-1">
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
          class="inline-flex items-center justify-center px-6 py-3 rounded-xl bg-[#D73535] text-white font-bold text-sm hover:bg-[#B92D2D] transition shadow-lg shadow-[#D73535]/20"
        >
          Hubungi Admin System
        </a>
      </div>
    </div>

    <div v-else-if="reviewData && isDelivery" class="delivery-page mx-auto max-w-6xl space-y-6 px-1 pb-12 sm:px-3">
      <section class="delivery-hero overflow-hidden rounded-[28px] px-5 py-6 text-white sm:px-8 sm:py-8">
        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div class="max-w-3xl">
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-white/90">
                <i :class="reviewData.type === 'Task' ? 'fa-solid fa-list-check' : 'fa-solid fa-chart-line'"></i>
                {{ reviewData.type === 'Task' ? 'Hasil Pekerjaan' : `${reportTypeLabel} Report` }}
              </span>
              <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-[11px] font-extrabold uppercase tracking-[0.1em]" :class="deliveryStatusClass">
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                {{ deliveryStatusLabel }}
              </span>
            </div>

            <h1 class="delivery-title mt-5 text-2xl font-extrabold leading-tight tracking-[-0.035em] text-white sm:text-4xl lg:text-[2.75rem]">{{ reviewData.name }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-white/75 sm:text-base">
              {{ reviewData.type === 'Task'
                ? 'Rangkuman hasil pekerjaan yang dapat ditinjau melalui tautan ini.'
                : `Laporan performa ${reviewData.brand?.name || 'brand'} untuk periode ${formatDate(reviewData.period_start)} sampai ${formatDate(reviewData.period_end)}.` }}
            </p>

            <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-xs font-semibold text-white/80 sm:text-sm">
              <span class="inline-flex items-center gap-2"><i class="fa-solid fa-tag text-[#FFD41D]"></i>{{ reviewData.brand?.name || 'Brand tidak tersedia' }}</span>
              <span class="inline-flex items-center gap-2"><i class="fa-solid fa-user-check text-[#FFA240]"></i>PIC: {{ reviewData.pic?.name || 'SUNTRACK' }}</span>
              <span v-if="reviewData.version" class="inline-flex items-center gap-2"><i class="fa-solid fa-code-branch text-[#FF8A8A]"></i>Versi {{ reviewData.version }}</span>
            </div>
          </div>

          <div v-if="reviewData.type === 'PerformanceReport'" class="min-w-[220px] rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-md">
            <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-white/55">Terakhir diperbarui</p>
            <p class="mt-1.5 text-sm font-bold text-white">{{ formatDateTime(reviewData.updated_at) }} WIB</p>
            <p class="mt-2 text-xs leading-5 text-white/60">Dibuat oleh {{ reviewData.creator?.name || reviewData.pic?.name || 'Tim SUNTRACK' }}</p>
          </div>
        </div>
      </section>

      <section class="review-card rounded-3xl border border-[#ECEEEC] bg-white p-5 shadow-sm sm:p-8">

        <template v-if="reviewData.type === 'Task'">
          <div class="mt-7 grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl bg-[#FCFBF8] p-4"><p class="text-xs text-[#98A19E]">Priority</p><p class="mt-1 font-bold capitalize text-[#46504D]">{{ reviewData.priority }}</p></div>
            <div class="rounded-2xl bg-[#FCFBF8] p-4"><p class="text-xs text-[#98A19E]">Deadline</p><p class="mt-1 font-bold text-[#46504D]">{{ formatDate(reviewData.deadline) }}</p></div>
            <div class="rounded-2xl bg-[#FCFBF8] p-4"><p class="text-xs text-[#98A19E]">Completed</p><p class="mt-1 font-bold text-[#46504D]">{{ formatDate(reviewData.completed_at) }}</p></div>
          </div>
          <div class="mt-6 space-y-4 text-sm leading-7 text-[#687370]">
            <div v-if="reviewData.description"><h2 class="font-bold text-[#46504D]">Instruksi</h2><p>{{ reviewData.description }}</p></div>
            <div v-if="reviewData.completion_summary"><h2 class="font-bold text-[#46504D]">Ringkasan Hasil</h2><p>{{ reviewData.completion_summary }}</p></div>
            <div v-if="reviewData.completion_details"><h2 class="font-bold text-[#46504D]">Detail Pekerjaan</h2><p>{{ reviewData.completion_details }}</p></div>
          </div>
        </template>

        <template v-else>
          <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-center gap-3">
              <span class="section-icon bg-[#FFF4E8] text-[#B45309]"><i class="fa-regular fa-calendar"></i></span>
              <div><p class="section-kicker">Tentang laporan</p><h2 class="section-title">Informasi Periode</h2></div>
            </div>
            <p class="text-xs text-[#929B98]">Data diperbarui {{ formatDateTime(reviewData.updated_at) }} WIB</p>
          </div>
          <div class="report-meta-grid mt-5 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl bg-[#FCFBF8] p-4"><p class="text-xs text-[#98A19E]">Periode</p><p class="mt-1 font-bold text-[#46504D]">{{ formatDate(reviewData.period_start) }} – {{ formatDate(reviewData.period_end) }}</p></div>
            <div class="rounded-2xl bg-[#FCFBF8] p-4"><p class="text-xs text-[#98A19E]">Dibuat</p><p class="mt-1 font-bold text-[#46504D]">{{ formatDateTime(reviewData.created_at) }} WIB</p></div>
            <div class="rounded-2xl bg-[#FCFBF8] p-4"><p class="text-xs text-[#98A19E]">Diperbarui</p><p class="mt-1 font-bold text-[#46504D]">{{ formatDateTime(reviewData.updated_at) }} WIB</p></div>
            <div class="rounded-2xl bg-[#FCFBF8] p-4"><p class="text-xs text-[#98A19E]">Dipublikasikan</p><p class="mt-1 font-bold text-[#46504D]">{{ formatDateTime(reviewData.published_at) }} WIB</p></div>
          </div>
          <div class="mt-8 flex items-end justify-between gap-4 border-t border-[#ECEEEC] pt-7">
            <div><p class="section-kicker">Angka utama</p><h2 class="section-title">Ringkasan Performa</h2></div>
            <p class="hidden max-w-sm text-right text-xs leading-5 text-[#929B98] sm:block">Nilai di bawah dihitung dari data pada periode laporan.</p>
          </div>
          <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article v-for="metric in primaryReportMetrics" :key="metric.label" class="metric-card" :class="metric.tone">
              <div class="flex items-start justify-between gap-3"><span class="metric-icon"><i :class="metric.icon"></i></span><span class="text-[10px] font-extrabold uppercase tracking-[0.12em] opacity-55">{{ reportTypeLabel }}</span></div>
              <p class="mt-5 text-xs font-semibold text-[#7D8885]">{{ metric.label }}</p>
              <p class="mt-1 break-words text-xl font-extrabold tracking-[-0.025em] text-[#343D3B] sm:text-2xl">{{ metric.value }}</p>
              <p class="mt-2 text-[11px] leading-5 text-[#929B98]">{{ metric.description }}</p>
            </article>
          </div>
          <div class="ratio-grid mt-5 grid overflow-hidden rounded-2xl border border-[#E8ECEA] bg-[#FAFBFA] sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="metric in secondaryReportMetrics" :key="metric.label" class="ratio-metric p-4 sm:p-5">
              <p class="text-xs font-bold text-[#6E7976]">{{ metric.label }}</p><p class="mt-1 text-xl font-extrabold text-[#3D4744]">{{ metric.value }}</p><p class="mt-1 text-[10px] leading-4 text-[#98A19E]">{{ metric.description }}</p>
            </div>
          </div>
          <div v-if="reviewData.executive_summary" class="summary-card mt-7 rounded-2xl border border-[#F1DFC9] bg-[#FFF9F1] p-5 sm:p-6">
            <div class="flex items-center gap-2 text-[#9A4700]"><i class="fa-solid fa-align-left"></i><h2 class="text-sm font-extrabold">Ringkasan Laporan</h2></div>
            <div class="report-prose prose mt-4 max-w-none text-sm leading-8 text-[#65706D] sm:text-base" v-html="reviewData.executive_summary"></div>
          </div>
          <div v-if="reportContentSections.length" class="mt-7 border-t border-[#ECEEEC] pt-7">
            <div class="flex items-center gap-3"><span class="section-icon bg-[#EEF7F5] text-[#236B61]"><i class="fa-solid fa-book-open"></i></span><div><p class="section-kicker">Penjelasan</p><h2 class="section-title">Pembahasan Laporan</h2></div></div>
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
              <article v-for="(section, index) in reportContentSections" :key="section.title" class="report-content-card">
                <div class="flex min-w-0 items-start gap-3"><span class="content-number">{{ String(index + 1).padStart(2, '0') }}</span><div class="min-w-0"><h3 class="break-words text-base font-extrabold text-[#3C4643]">{{ section.title }}</h3><p class="mt-1 break-words text-xs leading-5 text-[#929B98]">{{ section.description }}</p></div></div>
                <div class="report-prose prose mt-5 max-w-none text-sm leading-7 text-[#65706D]" v-html="section.html"></div>
              </article>
            </div>
          </div>
        </template>
      </section>

      <section v-if="reviewData.type === 'PerformanceReport' && reviewData.media?.length" class="review-card rounded-3xl border border-[#ECEEEC] bg-white p-5 shadow-sm sm:p-8">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
          <div class="flex items-center gap-3"><span class="section-icon bg-[#FFF0F0] text-[#D73535]"><i class="fa-regular fa-images"></i></span><div><p class="section-kicker">Bukti pendukung</p><h2 class="section-title">Dokumentasi Performa</h2></div></div>
          <p class="text-xs text-[#929B98]">{{ reviewData.media.length }} gambar dilampirkan</p>
        </div>
        <div class="mt-6 grid gap-5" :class="reviewData.media.length > 1 ? 'lg:grid-cols-2' : ''">
          <article v-for="(media, index) in reviewData.media" :key="media.id" class="media-card overflow-hidden rounded-2xl border border-[#E4E8E6]">
            <button
              type="button"
              class="media-visual group relative block w-full cursor-zoom-in overflow-hidden bg-[#F5F7F6] text-left"
              :aria-label="`Perbesar ${media.title || media.original_name || `gambar ${index + 1}`}`"
              @click="openMediaPreview(media)"
            >
              <img :src="media.url" :alt="media.title || media.original_name" loading="lazy" class="max-h-[680px] min-h-56 w-full object-contain transition duration-300 group-hover:scale-[1.015]" />
              <span class="absolute left-3 top-3 rounded-full bg-[#26302E]/80 px-2.5 py-1 text-[10px] font-bold text-white backdrop-blur">Gambar {{ index + 1 }}</span>
              <span class="media-zoom-indicator absolute bottom-3 right-3 flex h-10 w-10 items-center justify-center rounded-xl border border-white/30 bg-[#17211F]/80 text-sm text-white shadow-lg backdrop-blur transition group-hover:scale-105 group-hover:bg-[#17211F]" aria-hidden="true">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
              </span>
            </button>
            <div class="media-caption p-5 sm:p-6">
              <p class="text-[10px] font-extrabold uppercase tracking-[0.14em] text-[#B45309]">Keterangan gambar</p>
              <h3 class="media-caption-title mt-1.5 text-base font-extrabold">{{ media.title || media.original_name }}</h3>
              <div v-if="media.notes" class="media-caption-copy report-prose prose mt-3 max-w-none text-sm leading-7" v-html="media.notes"></div>
            </div>
          </article>
        </div>
      </section>

      <section class="grid gap-6 lg:grid-cols-[minmax(0,0.85fr)_minmax(0,1.15fr)]">
        <div class="review-card rounded-3xl border border-[#ECEEEC] bg-white p-5 sm:p-6">
          <div class="flex items-center gap-3"><span class="section-icon bg-[#EEF3FF] text-[#315EB8]"><i class="fa-solid fa-paperclip"></i></span><div><p class="section-kicker">Berkas</p><h2 class="section-title">Lampiran</h2></div></div>
          <div v-if="!reviewData.attachments?.length" class="mt-5 rounded-2xl border border-dashed border-[#DFE4E1] bg-[#FAFBFA] px-4 py-7 text-center text-sm text-[#929B98]">Tidak ada berkas tambahan.</div>
          <div
            v-for="attachment in reviewData.attachments || []"
            :key="attachment.id"
            class="attachment-row mt-3 flex items-center gap-3 rounded-2xl border border-[#E4E8E6] p-3.5"
          >
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#FFF0F0] text-[#D73535]"><i class="fa-solid fa-file-pdf"></i></span>
            <span class="min-w-0 flex-1"><strong class="block truncate text-sm text-[#46504D]">{{ attachment.original_name }}</strong><small class="mt-0.5 block text-[10px] text-[#98A19E]">Lampiran PDF · {{ formatAttachmentSize(attachment.size) }}</small></span>
            <div class="flex shrink-0 items-center gap-2">
              <a :href="`/api/v1/public/review/${token}/attachments/${attachment.id}/view`" target="_blank" rel="noopener noreferrer" class="attachment-action text-[#315EB8]" :aria-label="`Lihat ${attachment.original_name}`" title="Lihat PDF"><i class="fa-solid fa-eye"></i><span class="hidden sm:inline">Lihat</span></a>
              <a :href="`/api/v1/public/review/${token}/attachments/${attachment.id}/download`" class="attachment-action text-[#236B61]" :aria-label="`Unduh ${attachment.original_name}`" title="Unduh PDF"><i class="fa-solid fa-download"></i><span class="hidden sm:inline">Unduh</span></a>
            </div>
          </div>
        </div>

        <div class="review-card rounded-3xl border border-[#ECEEEC] bg-white p-5 sm:p-6">
          <div class="flex items-center justify-between gap-3"><div class="flex items-center gap-3"><span class="section-icon bg-[#EEF7F5] text-[#236B61]"><i class="fa-regular fa-comments"></i></span><div><p class="section-kicker">Kolaborasi</p><h2 class="section-title">Diskusi</h2></div></div><span class="rounded-full bg-[#F2F5F3] px-2.5 py-1 text-[10px] font-bold text-[#6E7976]">{{ reviewData.comments?.length || 0 }} komentar</span></div>
          <div v-if="reviewData.comments?.length" class="mt-5 max-h-72 space-y-3 overflow-y-auto pr-1">
            <article v-for="comment in reviewData.comments" :key="comment.id" class="comment-card flex gap-3 rounded-2xl bg-[#F8FAF9] p-3.5">
              <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#FFE8D1] text-xs font-extrabold text-[#9A4700]">{{ initialOf(comment.author_name) }}</span>
              <div class="min-w-0 flex-1"><div class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:justify-between"><strong class="text-xs text-[#46504D]">{{ comment.author_name }}</strong><time class="text-[10px] text-[#98A19E]">{{ formatDateTime(comment.created_at) }} WIB</time></div><p class="mt-1.5 whitespace-pre-line text-sm leading-6 text-[#687370]">{{ comment.body }}</p></div>
            </article>
          </div>
          <div v-else class="mt-5 rounded-2xl bg-[#F8FAF9] px-4 py-5 text-center text-sm text-[#929B98]">Belum ada komentar pada laporan ini.</div>
          <form class="mt-5 space-y-4 border-t border-[#ECEEEC] pt-5" @submit.prevent="handleDeliveryComment">
            <label class="block text-xs font-bold text-[#596461]">Nama Anda<input v-model="reviewerIdentity.name" required maxlength="150" autocomplete="name" placeholder="Masukkan nama" class="mt-1.5 w-full rounded-xl border border-[#E1E4E2] px-3.5 py-2.5 text-sm" /></label>
            <label class="block text-xs font-bold text-[#596461]">Komentar<textarea v-model="newCommentBody" required maxlength="2000" rows="3" placeholder="Tulis pertanyaan atau tanggapan..." class="mt-1.5 w-full resize-y rounded-xl border border-[#E1E4E2] px-3.5 py-2.5 text-sm"></textarea></label>
            <button :disabled="loading" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#9A4700] px-5 py-2.5 text-sm font-bold text-white disabled:opacity-50 sm:w-auto"><i class="fa-solid fa-paper-plane"></i>Kirim Komentar</button>
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
        style="background: linear-gradient(135deg, #C93434 0%, #EB4545 46%, #F79B3B 100%)"
      >
        <div
          class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-[#FFA240]/15"
        ></div>

        <div
          class="absolute -right-5 -bottom-20 w-40 h-40 rounded-full bg-[#FFD41D]/10"
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
                class="text-[10px] uppercase font-bold tracking-[0.18em] text-[#FFA240]"
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
                class="text-xs text-[#FFD41D] mt-0.5"
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
        class="review-card review-card--featured relative overflow-hidden bg-white rounded-[24px] p-6 sm:p-8 border border-[#ECEEEC] shadow-[0_8px_30px_rgba(41,51,49,0.05)]"
      >
        <div
          class="absolute -right-20 -top-20 w-64 h-64 rounded-full bg-[#FFD41D]/20"
        ></div>

        <div
          class="relative flex flex-col md:flex-row md:items-center justify-between gap-5 border-b border-[#F0F1EF] pb-6 mb-6"
        >
          <div>
            <div class="flex flex-wrap items-center gap-2 mb-3">
              <span
                v-if="reviewData.code"
                class="px-3 py-1.5 rounded-lg bg-[#FFA240]/15 text-[#9A4700] border border-[#FFA240]/30 text-xs font-mono font-bold tracking-wider"
              >
                {{ reviewData.code }}
              </span>

              <div class="relative">
                <select
                  v-if="reviewData"
                  :value="reviewData.status"
                  @change="handleStatusChange"
                  :disabled="loading"
                  class="appearance-none min-w-[180px] pl-4 pr-10 py-2 rounded-full text-[11px] font-bold border cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#FFA240]/40 transition disabled:opacity-60 disabled:cursor-not-allowed"
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
              class="text-2xl sm:text-3xl font-bold tracking-[-0.025em] text-[#46504D] tracking-tight"
            >
              {{ reviewData.name }}
            </h1>

            <p
              v-if="reviewData.brand"
              class="text-sm text-[#858E8B] mt-2"
            >
              Brand:
              <span class="font-bold text-[#46504D]">
                {{ reviewData.brand.name }}
              </span>
            </p>
          </div>

          <div
            class="bg-[#FCFBF8] px-5 py-4 rounded-2xl border border-[#ECEEEC] min-w-fit"
          >
            <span
              class="block text-[10px] uppercase tracking-wider font-bold text-[#98A19E] mb-1"
            >
              Periode Promosi
            </span>

            <span class="font-bold text-sm text-[#46504D]">
              {{ formatDate(reviewData.start_date) }}
              <span class="text-[#D73535] mx-1">→</span>
              {{ formatDate(reviewData.end_date) }}
            </span>
          </div>
        </div>

        <div
          v-if="reviewData.description"
          class="text-[#77817E] text-sm leading-relaxed"
        >
          <p
            class="font-bold text-[#46504D] text-[10px] uppercase tracking-wider mb-2"
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
        class="rounded-[22px] p-5 border border-[#FFA240]/30 bg-[#FFA240]/10 shadow-sm"
      >
        <div
          class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
        >
          <div class="flex items-center gap-4">
            <div
              class="w-11 h-11 rounded-xl bg-[#FFA240] text-[#46504D] flex items-center justify-center font-bold text-lg shadow-sm"
            >
              🎯
            </div>

            <div>
              <span
                class="text-[10px] font-extrabold uppercase tracking-wider text-[#9A4700]"
              >
                Terhubung ke Campaign
              </span>

              <h3 class="text-lg font-bold tracking-[-0.01em] text-[#46504D] mt-0.5">
                {{ reviewData.campaign.name }}
              </h3>
            </div>
          </div>

          <div
            class="text-xs sm:text-sm text-[#687370] font-medium bg-white px-4 py-2.5 rounded-xl border border-[#FFA240]/30"
          >
            Periode:
            <span class="font-bold text-[#46504D]">
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
        class="review-card bg-white rounded-[24px] p-6 sm:p-8 border border-[#ECEEEC] shadow-[0_8px_30px_rgba(41,51,49,0.05)]"
      >
        <div
          class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6"
        >
          <div>
            <h2
              class="text-lg font-bold tracking-[-0.01em] text-[#46504D] flex items-center gap-2"
            >
            <span
              class="w-8 h-8 rounded-lg bg-[#FFA240]/15 flex items-center justify-center overflow-hidden"
            >
              <img
                src="/images/stat.webp"
                alt="Statistics"
                class="w-6 h-6 object-contain"
              />
            </span>
              <span>Ringkasan Persetujuan</span>
            </h2>

            <p class="text-xs text-[#98A19E] mt-1">
              Pantau status persetujuan seluruh variant produk
            </p>
          </div>

          <div
            class="flex items-center gap-4 text-xs text-[#77817E] bg-[#FCFBF8] px-4 py-2.5 rounded-xl border border-[#ECEEEC]"
          >
            <div>
              <span class="block text-[#98A19E] mb-0.5">
                Terakhir Diperbarui
              </span>

              <span class="font-bold text-[#46504D]">
                {{ formatDateTime(reviewData.approval_summary.last_updated) }}
              </span>
            </div>

            <div
              v-if="reviewData.approval_summary.last_reviewer"
              class="border-l border-[#E7E9E7] pl-4"
            >
              <span class="block text-[#98A19E] mb-0.5">
                Reviewer Terakhir
              </span>

              <span class="font-bold text-[#46504D]">
                {{ reviewData.approval_summary.last_reviewer.name }}
              </span>
            </div>
          </div>
        </div>

        <!-- Metric Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-7">
          <div
            class="rounded-2xl p-5 border border-[#ECEEEC] bg-[#FCFBF8] text-center"
          >
            <span
              class="block text-2xl sm:text-3xl font-bold tracking-[-0.025em] text-[#46504D]"
            >
              {{ reviewData.approval_summary.total_variants }}
            </span>

            <span
              class="text-[10px] font-bold text-[#98A19E] uppercase tracking-wider mt-1 block"
            >
              Total Variant
            </span>
          </div>

          <div
            class="rounded-2xl p-5 border border-[#FFD41D]/40 bg-[#FFD41D]/15 text-center"
          >
            <span
              class="block text-2xl sm:text-3xl font-extrabold text-[#654C00]"
            >
              {{ reviewData.approval_summary.approved }}
            </span>

            <span
              class="text-[10px] font-bold text-[#755700] uppercase tracking-wider mt-1 block"
            >
              Approved
            </span>
          </div>

          <div
            class="rounded-2xl p-5 border border-[#FFD41D]/70 bg-[#FFD41D]/30 text-center"
          >
            <span
              class="block text-2xl sm:text-3xl font-extrabold text-[#755700]"
            >
              {{ reviewData.approval_summary.pending }}
            </span>

            <span
              class="text-[10px] font-bold text-[#755700] uppercase tracking-wider mt-1 block"
            >
              Pending
            </span>
          </div>

          <div
            class="rounded-2xl p-5 border border-[#D73535]/20 bg-[#D73535]/10 text-center"
          >
            <span
              class="block text-2xl sm:text-3xl font-extrabold text-[#D73535]"
            >
              {{ reviewData.approval_summary.rejected }}
            </span>

            <span
              class="text-[10px] font-bold text-[#D73535] uppercase tracking-wider mt-1 block"
            >
              Rejected
            </span>
          </div>
        </div>

        <!-- Progress -->
        <div>
          <div
            class="flex items-center justify-between text-xs font-bold text-[#687370] mb-2"
          >
            <span>Tingkat Penyelesaian Review</span>

            <span class="text-[#D73535]">
              {{ reviewData.approval_summary.completion_percentage }}%
            </span>
          </div>

          <div
            class="w-full h-3 bg-[#F1F1EE] rounded-full overflow-hidden flex"
          >
            <div
              class="bg-[#FFD41D] h-full transition-all duration-500"
              :style="{
                width: `${(reviewData.approval_summary.approved / Math.max(reviewData.approval_summary.total_variants, 1)) * 100}%`
              }"
            ></div>

            <div
              class="bg-[#D73535] h-full transition-all duration-500"
              :style="{
                width: `${(reviewData.approval_summary.rejected / Math.max(reviewData.approval_summary.total_variants, 1)) * 100}%`
              }"
            ></div>
          </div>

          <div class="flex items-center gap-4 mt-3 text-[10px] font-semibold">
            <span class="flex items-center gap-1.5 text-[#755700]">
              <span class="w-2 h-2 rounded-full bg-[#FFD41D]"></span>
              Approved
            </span>

            <span class="flex items-center gap-1.5 text-[#D73535]">
              <span class="w-2 h-2 rounded-full bg-[#D73535]"></span>
              Rejected
            </span>
          </div>
        </div>
      </div>

      <!-- =======================================================
           VARIANTS & APPROVAL
      ======================================================== -->
      <div
        class="review-card bg-white rounded-[24px] p-6 sm:p-8 border border-[#ECEEEC] shadow-[0_8px_30px_rgba(41,51,49,0.05)]"
      >
        <div
          class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6"
        >
          <div>
            <h2
              class="text-lg font-bold tracking-[-0.01em] text-[#46504D] flex items-center gap-2"
            >
            <span
              class="w-8 h-8 rounded-lg bg-[#FFA240]/15 flex items-center justify-center overflow-hidden"
            >
              <img
                src="/images/product.webp"
                alt="Statistics"
                class="w-6 h-6 object-contain"
              />
            </span>

              <span>Daftar Produk & Variant</span>
            </h2>

            <p class="text-xs text-[#98A19E] mt-1">
              Tinjau harga dan berikan persetujuan per item maupun batch.
            </p>
          </div>

          <button
            @click="showHistoryModal = true"
            class="px-4 py-2.5 bg-[#FCFBF8] hover:bg-[#F1F1EE] text-[#687370] font-bold rounded-xl text-xs transition flex items-center gap-2 border border-[#ECEEEC]"
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
          class="flex flex-wrap items-center justify-between gap-4 p-4 bg-[#FFA240]/10 border border-[#FFA240]/25 rounded-2xl mb-5"
        >
          <div class="flex items-center gap-2 text-xs font-bold text-[#687370]">
            <span>
              {{ selectedVariantIds.length }} dari
              {{ reviewData.variants.length }} variant terpilih
            </span>

            <button
              v-if="selectedVariantIds.length > 0"
              @click="selectedVariantIds = []"
              class="text-[#D73535] hover:underline text-[10px]"
            >
              Reset
            </button>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <button
              @click="handleBatchAction('approve_selected')"
              :disabled="selectedVariantIds.length === 0 || batchLoading"
              class="px-3.5 py-2 bg-[#FFD41D] hover:bg-[#E3B900] disabled:bg-[#DDE4E1] text-[#5A4300] font-bold text-xs rounded-xl transition"
            >
              ✓ Setujui Terpilih ({{ selectedVariantIds.length }})
            </button>

            <button
              @click="handleBatchAction('reject_selected')"
              :disabled="selectedVariantIds.length === 0 || batchLoading"
              class="px-3.5 py-2 bg-[#D73535] hover:bg-[#B92D2D] disabled:bg-[#DDE4E1] text-white font-bold text-xs rounded-xl transition"
            >
              ✕ Tolak Terpilih ({{ selectedVariantIds.length }})
            </button>

            <span class="hidden sm:block text-[#C5CBC9]">|</span>

            <button
              @click="handleBatchAction('approve_all')"
              :disabled="batchLoading"
              class="px-3.5 py-2 bg-white hover:bg-[#F2F7EF] text-[#755700] border border-[#FFD41D] font-bold text-xs rounded-xl transition"
            >
              ✓ Setujui Semua
            </button>

            <button
              @click="handleBatchAction('reject_all')"
              :disabled="batchLoading"
              class="px-3.5 py-2 bg-white hover:bg-[#FCF1F1] text-[#D73535] border border-[#F3A0A0] font-bold text-xs rounded-xl transition"
            >
              ✕ Tolak Semua
            </button>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto rounded-2xl border border-[#ECEEEC]">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr
                class="border-b border-[#ECEEEC] text-[10px] font-extrabold text-[#98A19E] uppercase tracking-wider bg-[#FCFBF8]"
              >
                <th class="py-4 px-3 w-10 text-center">
                  <input
                    type="checkbox"
                    @change="toggleSelectAll"
                    :checked="isAllSelected"
                    class="rounded border-[#C7D0CD] text-[#D73535] focus:ring-[#D73535]"
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
                class="hover:bg-[#FCFBF8] transition"
                :class="{
                  'bg-[#FFA240]/10': selectedVariantIds.includes(variant.id)
                }"
              >
                <td class="py-4 px-3 text-center">
                  <input
                    type="checkbox"
                    :value="variant.id"
                    v-model="selectedVariantIds"
                    class="rounded border-[#C7D0CD] text-[#D73535] focus:ring-[#D73535]"
                  />
                </td>

                <td class="py-4 px-4">
                  <span class="block font-bold text-[#46504D]">
                    {{ variant.product_name }}
                  </span>

                  <span class="text-xs text-[#98A19E]">
                    {{ variant.name }}

                    <span
                      v-if="variant.sku"
                      class="font-mono text-[#ADB5B2]"
                    >
                      ({{ variant.sku }})
                    </span>
                  </span>

                  <p
                    v-if="variant.rejection_notes"
                    class="mt-2 text-xs text-[#D73535] bg-[#D73535]/10 px-2.5 py-1.5 rounded-lg border border-[#D73535]/20 inline-block"
                  >
                    ⚠️ Catatan: {{ variant.rejection_notes }}
                  </p>
                </td>

                <td class="py-4 px-4 font-mono text-[#77817E]">
                  {{ formatCurrency(variant.normal_price_snapshot) }}
                </td>

                <td class="py-4 px-4 font-mono font-bold text-[#755700]">
                  <span class="block">
                    {{ formatCurrency(variant.campaign_price) }}
                  </span>

                  <span
                    v-if="variant.discount_price < variant.normal_price_snapshot"
                    class="text-xs font-normal text-[#ADB5B2] line-through"
                  >
                    {{ formatCurrency(variant.discount_price) }}
                  </span>
                </td>

                <td class="py-4 px-4 text-[#77817E]">
                  <span class="block font-medium">
                    {{ variant.promotion_stock }} unit
                  </span>

                  <span
                    v-if="variant.purchase_limit > 0"
                    class="text-xs text-[#ADB5B2]"
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
                          ? 'bg-[#FFD41D] text-[#5A4300]'
                          : 'bg-[#FFD41D]/15 text-[#755700] hover:bg-[#FFD41D]/30 border border-[#FFD41D]/50'
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
                          ? 'bg-[#D73535] text-white'
                          : 'bg-[#D73535]/10 text-[#D73535] hover:bg-[#D73535]/15 border border-[#D73535]/25'
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
                  class="py-12 text-center text-[#98A19E] text-sm"
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
        class="review-card bg-white rounded-[24px] p-6 sm:p-8 border border-[#ECEEEC] shadow-[0_8px_30px_rgba(41,51,49,0.05)]"
      >
        <div class="mb-6">
          <h2
            class="text-lg font-bold tracking-[-0.01em] text-[#46504D] flex items-center gap-2"
          >
            <span
              class="w-8 h-8 rounded-lg bg-[#FFA240]/15 flex items-center justify-center overflow-hidden"
            >
              <img
                src="/images/task.webp"
                alt="Statistics"
                class="w-6 h-6 object-contain"
              />
            </span>
            <span>Daftar Task Campaign</span>
          </h2>

          <p class="text-xs text-[#98A19E] mt-1">
            Perbarui status pengerjaan dan kirim hasil visual jika dibutuhkan.
          </p>
        </div>

        <div class="space-y-4">
          <div
            v-for="task in reviewData.tasks"
            :key="task.id"
            class="rounded-2xl bg-[#FCFBF8] border border-[#ECEEEC] p-5"
          >
            <div
              class="flex flex-col sm:flex-row sm:items-start justify-between gap-4"
            >
              <div class="min-w-0 flex-1">
                <p
                  class="font-bold text-[#46504D] flex items-center gap-2 flex-wrap"
                >
                  <span>{{ task.name }}</span>

                  <span
                    v-if="task.requires_visual"
                    class="px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase tracking-wider bg-[#FFA240]/20 text-[#9A4700] border border-[#FFA240]/30"
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

                <p class="text-xs text-[#98A19E] mt-2">
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
                  class="text-xs text-[#77817E] mt-2 leading-relaxed"
                >
                  Brief:
                  {{
                    Array.isArray(task.creative_brief)
                      ? task.creative_brief.join(', ')
                      : task.creative_brief
                  }}
                </p>
              </div>

              <div class="shrink-0 rounded-xl border border-[#E1E4E2] bg-white px-3.5 py-2 text-xs font-medium text-[#77817E]">
                Status dikelola oleh Tim dan PIC SUNTRACK
              </div>
            </div>

            <!-- Visual Submission -->
            <div
              v-if="task.requires_visual"
              class="mt-5 pt-5 border-t border-[#ECEEEC]"
            >
              <div
                v-if="task.visual_link || task.visual_file_url"
                class="mb-4 p-4 rounded-xl bg-[#FFD41D]/15 border border-[#FFD41D]/40 text-xs text-[#654C00]"
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
                  class="text-[#755700] mt-2"
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
                  class="mt-3 px-3 py-1.5 text-[#D73535] bg-white border border-[#D73535]/25 rounded-lg text-xs font-bold hover:bg-[#D73535]/10"
                >
                  Hapus Visual
                </button>
              </div>

              <form @submit.prevent="handleSubmitVisual(task)">
                <label
                  class="block text-xs font-bold text-[#687370] mb-1.5"
                >
                  Link Google Drive / URL Visual
                </label>

                <input
                  type="url"
                  v-model="taskVisualLinks[task.id]"
                  placeholder="https://drive.google.com/..."
                  class="w-full rounded-xl border border-[#E1E4E2] bg-white px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#FFA240]/30 focus:border-[#FFA240] mb-3"
                />

                <label
                  class="block text-xs font-bold text-[#687370] mb-1.5"
                >
                  Atau unggah gambar
                  <span class="font-normal text-[#98A19E]">
                    (JPG/PNG/WEBP/GIF, maks 5MB)
                  </span>
                </label>

                <input
                  type="file"
                  accept="image/*"
                  @change="(e) => onTaskFileChange(e, task.id)"
                  class="block w-full text-sm text-[#77817E] file:mr-3 file:rounded-lg file:border-0 file:bg-[#FFD41D]/40 file:px-3 file:py-2 file:text-[#755700] file:font-bold mb-3"
                />

                <div
                  v-if="taskFileErrors[task.id]"
                  class="text-[#D73535] text-xs mb-2"
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
                    class="max-h-40 rounded-xl border border-[#E1E4E2]"
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
                  class="px-4 py-2.5 rounded-xl bg-[#D73535] hover:bg-[#B92D2D] disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-xs transition shadow-sm"
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
        class="review-card bg-white rounded-[24px] p-6 sm:p-8 border border-[#ECEEEC] shadow-[0_8px_30px_rgba(41,51,49,0.05)]"
      >
        <h2
          class="text-lg font-bold tracking-[-0.01em] text-[#46504D] flex items-center gap-2 mb-6"
        >
            <span
              class="w-8 h-8 rounded-lg bg-[#FFA240]/15 flex items-center justify-center overflow-hidden"
            >
              <img
                src="/images/chat.webp"
                alt="Statistics"
                class="w-6 h-6 object-contain"
              />
            </span>

          <span>Diskusi & Komentar</span>

          <span
            class="text-[10px] font-bold text-[#77817E] bg-[#F6F5F2] px-2 py-1 rounded-full"
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
                ? 'bg-[#FFA240]/10 border-[#FFA240]/25 ml-4 sm:ml-8'
                : 'bg-[#FCFBF8] border-[#ECEEEC] mr-4 sm:mr-8'
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
                      ? 'bg-[#FFA240] text-[#46504D]'
                      : 'bg-[#FFD41D] text-[#755700]'
                  "
                >
                  {{ comment.author_type }}
                </span>

                <span class="font-bold text-[#46504D] text-sm">
                  {{ comment.author_name }}
                </span>

                <span
                  v-if="comment.author_position"
                  class="text-xs text-[#98A19E]"
                >
                  ({{ comment.author_position }})
                </span>
              </div>

              <span class="text-xs text-[#AAB2AF]">
                {{ formatDateTime(comment.created_at) }}
              </span>
            </div>

            <p
              class="text-sm text-[#687370] whitespace-pre-line leading-relaxed"
            >
              {{ comment.body }}
            </p>
          </div>

          <div
            v-if="!reviewData.comments || reviewData.comments.length === 0"
            class="py-10 text-center bg-[#FCFBF8] rounded-2xl border border-dashed border-[#E1E4E2] text-[#98A19E] text-sm"
          >
            Belum ada komentar diskusi.
          </div>
        </div>

        <div
          class="bg-[#FCFBF8] rounded-2xl p-4 border border-[#ECEEEC]"
        >
          <label
            class="block text-[10px] font-extrabold uppercase tracking-wider text-[#77817E] mb-2"
          >
            Tulis Komentar atau Feedback
          </label>

          <textarea
            v-model="newCommentBody"
            rows="3"
            placeholder="Tulis pesan, pertanyaan, atau catatan kolaborasi untuk Admin..."
            class="w-full rounded-xl border border-[#E1E4E2] bg-white p-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#FFA240]/25 focus:border-[#FFA240] mb-3"
          ></textarea>

          <div
            class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
          >
            <span class="text-xs text-[#98A19E]">
              Posting sebagai:
              <strong class="text-[#46504D]">
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
              class="px-5 py-2.5 bg-[#D73535] hover:bg-[#B92D2D] disabled:opacity-50 text-white font-bold text-xs rounded-xl transition shadow-sm"
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
        class="review-card bg-white rounded-[24px] p-6 sm:p-8 border border-[#ECEEEC] shadow-[0_8px_30px_rgba(41,51,49,0.05)]"
      >
        <h2
          class="text-lg font-bold tracking-[-0.01em] text-[#46504D] flex items-center gap-2 mb-7"
        >
            <span
              class="w-8 h-8 rounded-lg bg-[#FFA240]/15 flex items-center justify-center overflow-hidden"
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
          class="relative pl-7 border-l-2 border-[#ECEEEC] space-y-7"
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
                  ? 'bg-[#FFA240]'
                  : 'bg-[#FFD41D]'
              "
            ></div>

            <div
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-1"
            >
              <span
                class="font-bold text-[#46504D] text-sm flex items-center gap-2"
              >
                <span>{{ log.action }}</span>

                <span
                  class="px-1.5 py-0.5 rounded text-[8px] font-extrabold uppercase"
                  :class="
                    log.actor_type === 'Admin'
                      ? 'bg-[#FFA240]/20 text-[#9A4700]'
                      : 'bg-[#FFD41D]/50 text-[#755700]'
                  "
                >
                  {{ log.actor_type }}
                </span>
              </span>

              <span class="text-xs text-[#AAB2AF]">
                {{ formatDateTime(log.created_at) }}
              </span>
            </div>

            <p
              class="text-xs sm:text-sm text-[#77817E] leading-relaxed"
            >
              {{ log.description }}
            </p>

            <p
              v-if="log.actor_name"
              class="text-xs text-[#AAB2AF] mt-1"
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

    <Teleport to="body">
      <Transition name="image-viewer">
        <div
          v-if="mediaPreview.open"
          class="image-viewer fixed inset-0 z-[80] flex flex-col bg-[#07100E]/95 p-3 backdrop-blur-md sm:p-5"
          role="dialog"
          aria-modal="true"
          :aria-label="`Pratinjau ${mediaPreview.alt}`"
          @click.self="closeMediaPreview"
        >
          <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-3 rounded-2xl border border-white/10 bg-white/10 px-3 py-2.5 text-white shadow-xl sm:px-4">
            <div class="min-w-0">
              <p class="truncate text-sm font-bold">{{ mediaPreview.alt }}</p>
              <p class="mt-0.5 text-[10px] text-white/60">Gunakan tombol untuk memperbesar atau memperkecil gambar</p>
            </div>
            <div class="flex shrink-0 items-center gap-1.5">
              <button type="button" class="image-viewer-control" :disabled="mediaPreview.zoom <= MIN_MEDIA_ZOOM" aria-label="Perkecil gambar" title="Perkecil" @click="zoomMediaOut">
                <i class="fa-solid fa-magnifying-glass-minus"></i>
              </button>
              <span class="hidden min-w-14 text-center text-xs font-bold sm:inline">{{ mediaZoomLabel }}</span>
              <button type="button" class="image-viewer-control" :disabled="mediaPreview.zoom >= MAX_MEDIA_ZOOM" aria-label="Perbesar gambar" title="Perbesar" @click="zoomMediaIn">
                <i class="fa-solid fa-magnifying-glass-plus"></i>
              </button>
              <button type="button" class="image-viewer-control image-viewer-reset" aria-label="Kembalikan ukuran gambar" title="Ukuran awal" @click="resetMediaZoom">
                <i class="fa-solid fa-arrows-rotate"></i>
              </button>
              <button type="button" class="image-viewer-control ml-1 bg-red-500/80 hover:bg-red-500" aria-label="Tutup pratinjau gambar" title="Tutup" @click="closeMediaPreview">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
          </div>

          <div class="image-viewer-scroll mx-auto mt-3 w-full max-w-7xl flex-1 overflow-auto rounded-2xl border border-white/10 bg-black/25">
            <div class="flex min-h-full min-w-full items-start justify-center p-3 sm:p-6">
              <img
                :src="mediaPreview.src"
                :alt="mediaPreview.alt"
                class="image-viewer-image h-auto object-contain"
                :style="{ width: `${mediaPreview.zoom * 100}%` }"
              />
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- =========================================================
         IDENTITY MODAL
    ========================================================== -->
    <div
      v-if="showIdentityModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#4A514F]/65 backdrop-blur-sm"
    >
      <div
        class="modal-card bg-white rounded-[26px] max-w-md w-full p-6 sm:p-7 shadow-2xl border border-[#ECEEEC]"
      >
        <div
          class="flex items-center justify-between pb-4 border-b border-[#F0F1EF] mb-5"
        >
          <div>
            <span
              class="text-[10px] font-extrabold uppercase tracking-wider text-[#D73535]"
            >
              Reviewer
            </span>

            <h3 class="text-lg font-bold tracking-[-0.01em] text-[#46504D] mt-1">
              {{
                isIdentified()
                  ? 'Ubah Identitas Reviewer'
                  : 'Identifikasi Reviewer'
              }}
            </h3>
          </div>

          <button
            @click="showIdentityModal = false"
            class="w-8 h-8 rounded-lg text-[#98A19E] hover:bg-[#F6F5F2] hover:text-[#46504D] transition"
          >
            ✕
          </button>
        </div>

        <p class="text-xs text-[#77817E] mb-5 leading-relaxed">
          Lengkapi identitas Anda untuk pencatatan audit trail pada setiap
          aktivitas review.
        </p>

        <form
          @submit.prevent="submitIdentityForm"
          class="space-y-4"
        >
          <div>
            <label
              class="block text-xs font-bold text-[#687370] mb-1.5"
            >
              Nama Lengkap
              <span class="text-[#D73535]">*</span>
            </label>

            <input
              v-model="identityForm.name"
              type="text"
              required
              placeholder="Contoh: Budi Santoso"
              class="w-full rounded-xl border border-[#E1E4E2] px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#FFA240]/25 focus:border-[#FFA240]"
            />
          </div>

          <div>
            <label
              class="block text-xs font-bold text-[#687370] mb-1.5"
            >
              Jabatan / Posisi
              <span class="text-[#98A19E] font-normal">
                (Opsional)
              </span>
            </label>

            <input
              v-model="identityForm.position"
              type="text"
              placeholder="Brand Manager / Marketing Director"
              class="w-full rounded-xl border border-[#E1E4E2] px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#FFA240]/25 focus:border-[#FFA240]"
            />
          </div>

          <div>
            <label
              class="block text-xs font-bold text-[#687370] mb-1.5"
            >
              Nama Perusahaan / Brand
            </label>

            <input
              v-model="identityForm.companyName"
              type="text"
              disabled
              class="w-full rounded-xl border border-[#ECEEEC] bg-[#F6F5F2] px-3.5 py-2.5 text-sm text-[#98A19E]"
            />
          </div>

          <div>
            <label
              class="block text-xs font-bold text-[#687370] mb-1.5"
            >
              Nomor WhatsApp
              <span class="text-[#98A19E] font-normal">
                (Opsional)
              </span>
            </label>

            <input
              v-model="identityForm.whatsappNumber"
              type="text"
              placeholder="081234567890"
              class="w-full rounded-xl border border-[#E1E4E2] px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#FFA240]/25 focus:border-[#FFA240]"
            />
          </div>

          <div
            class="flex items-center justify-end gap-3 pt-4 border-t border-[#F0F1EF]"
          >
            <button
              type="button"
              @click="showIdentityModal = false"
              class="px-4 py-2.5 rounded-xl border border-[#E1E4E2] text-xs font-bold text-[#77817E] hover:bg-[#FCFBF8] transition"
            >
              Batal
            </button>

            <button
              type="submit"
              :disabled="!identityForm.name.trim() || loading"
              class="px-5 py-2.5 rounded-xl bg-[#D73535] hover:bg-[#B92D2D] disabled:opacity-50 text-white text-xs font-bold transition shadow-sm"
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
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#4A514F]/65 backdrop-blur-sm"
    >
      <div
        class="modal-card bg-white rounded-[26px] max-w-md w-full p-6 sm:p-7 shadow-2xl border border-[#ECEEEC]"
      >
        <div
          class="flex items-center justify-between pb-4 border-b border-[#F0F1EF] mb-5"
        >
          <div>
            <span
              class="text-[10px] font-extrabold uppercase tracking-wider text-[#D73535]"
            >
              Approval Review
            </span>

            <h3 class="text-lg font-extrabold text-[#D73535] mt-1">
              Tolak Variant Produk
            </h3>
          </div>

          <button
            @click="showRejectModal = false"
            class="w-8 h-8 rounded-lg text-[#98A19E] hover:bg-[#F6F5F2]"
          >
            ✕
          </button>
        </div>

        <p class="text-xs text-[#77817E] mb-4">
          Anda akan menolak variant:
          <strong class="text-[#46504D]">
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
              class="block text-xs font-bold text-[#687370] mb-1.5"
            >
              Catatan Penolakan
              <span class="text-[#D73535]">*</span>
            </label>

            <textarea
              v-model="rejectionNoteInput"
              rows="4"
              required
              placeholder="Jelaskan alasan penolakan..."
              class="w-full rounded-xl border border-[#F3A0A0] p-3.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#D73535]/20 focus:border-[#D73535]"
            ></textarea>

            <p class="text-[10px] text-[#D73535] mt-1.5">
              Alasan penolakan wajib disertakan.
            </p>
          </div>

          <div
            class="flex items-center justify-end gap-3 pt-4 border-t border-[#F0F1EF]"
          >
            <button
              type="button"
              @click="showRejectModal = false"
              class="px-4 py-2.5 rounded-xl border border-[#E1E4E2] text-xs font-bold text-[#77817E]"
            >
              Batal
            </button>

            <button
              type="submit"
              :disabled="!rejectionNoteInput.trim() || loading"
              class="px-5 py-2.5 rounded-xl bg-[#D73535] hover:bg-[#B92D2D] disabled:opacity-50 text-white text-xs font-bold transition"
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
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#4A514F]/65 backdrop-blur-sm"
    >
      <div
        class="modal-card bg-white rounded-[26px] max-w-2xl w-full p-6 sm:p-7 shadow-2xl border border-[#ECEEEC] max-h-[85vh] flex flex-col"
      >
        <div
          class="flex items-center justify-between pb-4 border-b border-[#F0F1EF] mb-4"
        >
          <div>
            <span
              class="text-[10px] font-extrabold uppercase tracking-wider text-[#FFA240]"
            >
              Audit Trail
            </span>

            <h3 class="text-lg font-bold tracking-[-0.01em] text-[#46504D] mt-1">
              Riwayat Approval
            </h3>
          </div>

          <button
            @click="showHistoryModal = false"
            class="w-8 h-8 rounded-lg text-[#98A19E] hover:bg-[#F6F5F2]"
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
            class="p-4 rounded-2xl bg-[#FCFBF8] border border-[#ECEEEC]"
          >
            <div
              class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 font-bold text-[#46504D] mb-2"
            >
              <span>
                {{ hist.variant_name }}

                <span
                  v-if="hist.variant_sku"
                  class="font-mono font-normal text-[#AAB2AF]"
                >
                  ({{ hist.variant_sku }})
                </span>
              </span>

              <span class="text-xs text-[#AAB2AF] font-normal">
                {{ formatDateTime(hist.created_at) }}
              </span>
            </div>

            <div class="flex items-center gap-2 my-3 text-xs">
              <span
                class="px-2.5 py-1 rounded-lg bg-[#F0F1EF] font-semibold text-[#77817E]"
              >
                {{ hist.old_status }}
              </span>

              <span class="text-[#AAB2AF]">→</span>

              <span
                class="px-2.5 py-1 rounded-lg font-bold"
                :class="
                  hist.new_status === 'Approved'
                    ? 'bg-[#FFD41D]/25 text-[#654C00]'
                    : 'bg-[#D73535]/10 text-[#D73535]'
                "
              >
                {{ hist.new_status }}
              </span>
            </div>

            <p
              v-if="hist.notes"
              class="text-xs text-[#D73535] bg-[#D73535]/10 p-3 rounded-xl border border-[#D73535]/20"
            >
              📝 {{ hist.notes }}
            </p>

            <p
              class="text-[10px] text-[#98A19E] mt-3 border-t border-[#ECEEEC] pt-2"
            >
              Reviewer:
              <strong class="text-[#687370]">
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
            class="py-10 text-center text-[#98A19E] text-sm"
          >
            Belum ada riwayat perubahan status approval.
          </div>
        </div>

        <div
          class="pt-4 border-t border-[#F0F1EF] text-right mt-4"
        >
          <button
            @click="showHistoryModal = false"
            class="px-5 py-2.5 bg-[#4A514F] hover:bg-[#3F4644] text-white font-bold text-xs rounded-xl transition"
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
            'bg-[#FFD41D] text-[#5A4300] border-[#E3B900]':
              t.type === 'success',
            'bg-[#D73535] text-white border-[#B92D2D]':
              t.type === 'error',
            'bg-[#4A514F] text-white border-[#59615E]':
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
      class="fixed inset-0 z-[55] flex items-center justify-center p-4 bg-[#4A514F]/65 backdrop-blur-sm"
    >
      <div
        class="modal-card bg-white rounded-[24px] max-w-md w-full p-6 shadow-2xl border border-[#ECEEEC]"
      >
        <div
          class="w-10 h-10 rounded-xl bg-[#FFD41D]/40 flex items-center justify-center mb-4"
        >
          ?
        </div>

        <div class="text-lg font-bold tracking-[-0.01em] text-[#46504D] mb-2">
          Konfirmasi
        </div>

        <p class="text-sm text-[#77817E] mb-6 leading-relaxed">
          {{ confirmModal.message }}
        </p>

        <div class="flex justify-end gap-3">
          <button
            @click="confirmCancel"
            class="px-4 py-2.5 rounded-xl border border-[#E1E4E2] text-xs font-bold text-[#77817E] hover:bg-[#FCFBF8]"
          >
            Batal
          </button>

          <button
            @click="confirmOk"
            class="px-5 py-2.5 rounded-xl bg-[#D73535] hover:bg-[#B92D2D] text-white text-xs font-bold"
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
      class="fixed inset-0 z-[55] flex items-center justify-center p-4 bg-[#4A514F]/65 backdrop-blur-sm"
    >
      <div
        class="modal-card bg-white rounded-[24px] max-w-md w-full p-6 shadow-2xl border border-[#ECEEEC]"
      >
        <div
          class="w-10 h-10 rounded-xl bg-[#FFD41D]/40 flex items-center justify-center mb-4"
        >
          ✎
        </div>

        <div class="text-lg font-bold tracking-[-0.01em] text-[#46504D] mb-2">
          {{ promptModal.title || 'Masukkan' }}
        </div>

        <p
          v-if="promptModal.message"
          class="text-sm text-[#77817E] mb-4"
        >
          {{ promptModal.message }}
        </p>

        <input
          v-model="promptModal.value"
          type="text"
          class="w-full rounded-xl border border-[#E1E4E2] px-3.5 py-2.5 text-sm mb-5 focus:outline-none focus:ring-2 focus:ring-[#FFA240]/25 focus:border-[#FFA240]"
        />

        <div class="flex justify-end gap-3">
          <button
            @click="promptCancel"
            class="px-4 py-2.5 rounded-xl border border-[#E1E4E2] text-xs font-bold text-[#77817E]"
          >
            Batal
          </button>

          <button
            @click="promptOk"
            class="px-5 py-2.5 rounded-xl bg-[#D73535] hover:bg-[#B92D2D] text-white text-xs font-bold"
          >
            Kirim
          </button>
        </div>
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
const MIN_MEDIA_ZOOM = 0.5;
const MAX_MEDIA_ZOOM = 3;
const MEDIA_ZOOM_STEP = 0.25;
const mediaPreview = reactive({ open: false, src: '', alt: '', zoom: 1 });
const mediaZoomLabel = computed(() => `${Math.round(mediaPreview.zoom * 100)}%`);
let previousBodyOverflow = '';

const openMediaPreview = (media) => {
  mediaPreview.src = media?.url || '';
  mediaPreview.alt = media?.title || media?.original_name || 'Dokumentasi performa';
  mediaPreview.zoom = 1;
  mediaPreview.open = Boolean(mediaPreview.src);

  if (mediaPreview.open) {
    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
  }
};
const closeMediaPreview = () => {
  mediaPreview.open = false;
  mediaPreview.zoom = 1;
  document.body.style.overflow = previousBodyOverflow;
};
const zoomMediaIn = () => {
  mediaPreview.zoom = Math.min(MAX_MEDIA_ZOOM, mediaPreview.zoom + MEDIA_ZOOM_STEP);
};
const zoomMediaOut = () => {
  mediaPreview.zoom = Math.max(MIN_MEDIA_ZOOM, mediaPreview.zoom - MEDIA_ZOOM_STEP);
};
const resetMediaZoom = () => {
  mediaPreview.zoom = 1;
};
const handleMediaPreviewKeydown = (event) => {
  if (!mediaPreview.open) return;
  if (event.key === 'Escape') closeMediaPreview();
  if (event.key === '+' || event.key === '=') zoomMediaIn();
  if (event.key === '-') zoomMediaOut();
};
const isDelivery = computed(() => ['Task', 'PerformanceReport'].includes(reviewData.value?.type));
const reportTypeLabel = computed(() => ({
  daily: 'Harian',
  weekly: 'Mingguan',
  monthly: 'Bulanan',
}[reviewData.value?.report_type] || 'Laporan'));
const deliveryStatusLabel = computed(() => ({
  published: 'Dipublikasikan',
  draft: 'Draft',
  completed: 'Selesai',
  in_progress: 'Sedang Dikerjakan',
}[String(reviewData.value?.status || '').toLowerCase()] || String(reviewData.value?.status || '').replaceAll('_', ' ')));
const deliveryStatusClass = computed(() => {
  const status = String(reviewData.value?.status || '').toLowerCase();
  if (['published', 'completed'].includes(status)) return 'bg-emerald-400/20 text-emerald-100 ring-1 ring-inset ring-emerald-300/30';
  if (status === 'draft') return 'bg-amber-300/20 text-amber-100 ring-1 ring-inset ring-amber-200/30';
  return 'bg-white/10 text-white/85 ring-1 ring-inset ring-white/15';
});
const primaryReportMetrics = computed(() => {
  const report = reviewData.value || {};
  return [
    { label: 'Omzet Toko', value: formatCurrency(report.turnover), description: 'Total penjualan toko selama periode laporan.', icon: 'fa-solid fa-wallet', tone: 'metric-card--amber' },
    { label: 'Jumlah Pesanan', value: Number(report.order_count || 0).toLocaleString('id-ID'), description: 'Total pesanan yang diterima oleh toko.', icon: 'fa-solid fa-box', tone: 'metric-card--yellow' },
    { label: 'Biaya Iklan', value: formatCurrency(report.ad_spend), description: 'Total anggaran iklan yang telah digunakan.', icon: 'fa-solid fa-bullhorn', tone: 'metric-card--red' },
    { label: 'Penjualan dari Iklan', value: formatCurrency(report.ad_sales), description: 'Nilai penjualan yang dihasilkan oleh iklan.', icon: 'fa-solid fa-chart-line', tone: 'metric-card--green' },
  ];
});
const secondaryReportMetrics = computed(() => {
  const report = reviewData.value || {};
  return [
    { label: 'ROAS', value: report.roas === null ? '-' : `${Number(report.roas).toFixed(2)}x`, description: 'Pendapatan iklan untuk setiap Rp1 biaya.' },
    { label: 'ACOS', value: report.acos === null ? '-' : `${Number(report.acos).toFixed(2)}%`, description: 'Porsi biaya terhadap penjualan dari iklan.' },
    { label: 'Kontribusi Iklan', value: report.ad_contribution === null ? '-' : `${Number(report.ad_contribution).toFixed(2)}%`, description: 'Porsi penjualan iklan terhadap omzet toko.' },
    { label: 'Rata-rata Pesanan', value: formatCurrency(report.average_order_value), description: 'Nilai rata-rata dari setiap pesanan.' },
  ];
});
const reportContentSections = computed(() => {
  const report = reviewData.value || {};
  return [
    { title: 'Analisis Performa', description: 'Penjelasan hasil kinerja selama periode laporan.', html: report.content },
    { title: 'Temuan dan Kendala', description: 'Hal penting serta hambatan yang ditemukan.', html: report.findings },
    { title: 'Rencana Tindak Lanjut', description: 'Langkah yang disarankan setelah laporan ini.', html: report.action_plan },
  ].filter(section => section.html);
});
const initialOf = name => String(name || '?').trim().charAt(0).toUpperCase();
const formatAttachmentSize = bytes => {
  const size = Number(bytes);
  if (!Number.isFinite(size)) return '-';
  if (size < 1024) return `${size} B`;
  if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`;
  return `${(size / (1024 * 1024)).toFixed(1)} MB`;
};

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
  document.removeEventListener('keydown', handleMediaPreviewKeydown);
  document.body.style.overflow = previousBodyOverflow;
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
  document.addEventListener('keydown', handleMediaPreviewKeydown);
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
    timeZone: 'Asia/Jakarta',
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
      return 'bg-[#FFD41D]/25 text-[#6B5000] border-[#FFD41D]';

    case 'Rejected':
      return 'bg-[#D73535]/10 text-[#B52A2A] border-[#D73535]/40';

    case 'Partially Approved':
      return 'bg-[#FFD41D]/40 text-[#755700] border-[#FFD41D]';

    case 'Active':
      return 'bg-[#FFA240]/20 text-[#A04B00] border-[#FFA240]';

    case 'Draft':
      return 'bg-[#FFD41D]/40 text-[#755700] border-[#FFD41D]';

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
      return 'bg-[#FFD41D]/25 text-[#654C00] border border-[#FFD41D]/60';
    case 'in_progress':
      return 'bg-[#FFA240]/20 text-[#9A4700] border border-[#FFA240]/50';
    case 'revision':
      return 'bg-[#FFA240]/20 text-[#9A4700] border border-[#FFA240]/50';
    case 'on_hold':
      return 'bg-slate-100 text-slate-700 border border-slate-200';
    default:
      return 'bg-slate-100 text-slate-700 border border-slate-200';
  }
};

const getVariantStatusBadgeClass = (status) => {
  switch (status) {
    case 'Approved':
      return 'bg-[#FFD41D]/25 text-[#654C00]';
    case 'Rejected':
      return 'bg-[#FF4646]/12 text-[#D73535]';
    default:
      return 'bg-[#FFA240]/20 text-[#9A4700]';
  }
};

</script>


<style scoped>
/* ============================================================
   SUNTRACK PUBLIC REVIEW — MODERN BRAND THEME
   Palette:
   #FFD41D — Yellow
   #FFA240 — Orange
   #D73535 — Deep Red
   #FF4646 — Signal Red
============================================================ */

.review-shell {
  --st-yellow: #FFD41D;
  --st-orange: #FFA240;
  --st-red: #D73535;
  --st-signal: #FF4646;

  --st-ink: #252A2B;
  --st-text: #56605F;
  --st-muted: #8A9492;
  --st-line: #E7EAE8;
  --st-surface: #FFFFFF;
  --st-soft: #FFF9ED;

  position: relative;
  min-height: 100vh;
  padding-top: 1.25rem;
  background:
    radial-gradient(circle at 7% 3%, rgba(255, 212, 29, .20), transparent 22rem),
    radial-gradient(circle at 95% 8%, rgba(255, 70, 70, .10), transparent 25rem),
    linear-gradient(180deg, #FFFDF8 0%, #FFFFFF 34%, #FFF9F7 100%);
  isolation: isolate;
}

.review-shell::before,
.review-shell::after {
  content: "";
  position: fixed;
  z-index: -1;
  border-radius: 9999px;
  pointer-events: none;
  filter: blur(10px);
}

.review-shell::before {
  width: 18rem;
  height: 18rem;
  top: 9rem;
  left: -9rem;
  background: rgba(255, 162, 64, .08);
}

.review-shell::after {
  width: 24rem;
  height: 24rem;
  right: -12rem;
  bottom: 4rem;
  background: rgba(215, 53, 53, .06);
}

/* Main authenticated review content */
.review-shell > .space-y-7 {
  width: min(100% - 2rem, 1180px);
  margin-inline: auto;
}

.review-card {
  position: relative;
  border-color: rgba(37, 42, 43, .08) !important;
  box-shadow:
    0 1px 2px rgba(37, 42, 43, .02),
    0 14px 42px rgba(37, 42, 43, .06) !important;
  transition:
    transform .22s ease,
    box-shadow .22s ease,
    border-color .22s ease;
}

.review-card:hover {
  border-color: rgba(255, 162, 64, .30) !important;
  box-shadow:
    0 2px 4px rgba(37, 42, 43, .025),
    0 20px 52px rgba(37, 42, 43, .085) !important;
}

.review-card--featured::before {
  content: "";
  position: absolute;
  inset: 0 0 auto 0;
  height: 4px;
  background: linear-gradient(
    90deg,
    var(--st-yellow) 0%,
    var(--st-orange) 34%,
    var(--st-signal) 67%,
    var(--st-red) 100%
  );
}

.modal-card {
  position: relative;
  overflow: hidden;
  box-shadow:
    0 30px 90px rgba(37, 42, 43, .24),
    0 4px 18px rgba(37, 42, 43, .08) !important;
}

.modal-card::before {
  content: "";
  position: absolute;
  inset: 0 0 auto 0;
  height: 4px;
  background: linear-gradient(
    90deg,
    var(--st-yellow),
    var(--st-orange),
    var(--st-signal),
    var(--st-red)
  );
}

/* Inputs */
.review-shell :deep(input:not([type="checkbox"]):not([type="file"])),
.review-shell :deep(textarea),
.review-shell :deep(select) {
  transition:
    border-color .18s ease,
    box-shadow .18s ease,
    background-color .18s ease;
}

.review-shell :deep(input:not([type="checkbox"]):not([type="file"]):focus),
.review-shell :deep(textarea:focus),
.review-shell :deep(select:focus) {
  border-color: rgba(255, 162, 64, .85) !important;
  box-shadow: 0 0 0 4px rgba(255, 162, 64, .13) !important;
}

/* Checkbox brand treatment */
.review-shell :deep(input[type="checkbox"]) {
  accent-color: var(--st-red);
}

/* Buttons */
.review-shell :deep(button) {
  transition:
    transform .16s ease,
    box-shadow .16s ease,
    background-color .16s ease,
    border-color .16s ease,
    color .16s ease;
}

.review-shell :deep(button:not(:disabled):active) {
  transform: translateY(1px) scale(.99);
}

/* Table */
.review-shell :deep(table) {
  background: rgba(255, 255, 255, .94);
}

.review-shell :deep(thead) {
  backdrop-filter: blur(8px);
}

.review-shell :deep(tbody tr) {
  transition: background-color .18s ease;
}

/* Scrollbar */
.review-shell :deep(*::-webkit-scrollbar) {
  width: 8px;
  height: 8px;
}

.review-shell :deep(*::-webkit-scrollbar-track) {
  background: transparent;
}

.review-shell :deep(*::-webkit-scrollbar-thumb) {
  background: #D7DBD9;
  border-radius: 9999px;
}

.review-shell :deep(*::-webkit-scrollbar-thumb:hover) {
  background: #BCC2BF;
}

/* Rich text */
.review-shell :deep(.prose h1),
.review-shell :deep(.prose h2),
.review-shell :deep(.prose h3),
.review-shell :deep(.prose strong) {
  color: var(--st-ink);
}

.review-shell :deep(.prose a) {
  color: var(--st-red);
  text-decoration-color: rgba(215, 53, 53, .35);
}

/* Toast transitions */
.toast-enter-active,
.toast-leave-active {
  transition: all .22s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-8px) translateX(10px);
}

/* Mobile refinements */
@media (max-width: 640px) {
  .review-shell {
    padding-top: .75rem;
  }

  .review-shell > .space-y-7 {
    width: min(100% - 1rem, 1180px);
  }

  .review-card {
    border-radius: 20px !important;
  }

  .modal-card {
    border-radius: 22px !important;
  }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .review-shell *,
  .review-shell *::before,
  .review-shell *::after {
    scroll-behavior: auto !important;
    transition-duration: .01ms !important;
    animation-duration: .01ms !important;
    animation-iteration-count: 1 !important;
  }
}


/* ============================================================
   VISUAL REFINEMENT V2 — SOFTER TYPOGRAPHY & MODERN DEPTH
============================================================ */

.review-shell {
  --st-heading: #46504D;
  --st-body: #687370;
  --st-muted: #98A19E;
  --st-faint: #AAB2AF;

  background:
    radial-gradient(circle at 4% 4%, rgba(255, 212, 29, .16), transparent 25rem),
    radial-gradient(circle at 96% 7%, rgba(255, 162, 64, .10), transparent 25rem),
    radial-gradient(circle at 88% 85%, rgba(255, 70, 70, .055), transparent 28rem),
    linear-gradient(180deg, #FFFDF9 0%, #FFFFFF 36%, #FFFBF9 100%);
}

/* Give content slightly more breathing room */
.review-shell > .space-y-7 {
  gap: 1.65rem;
}

/* Modern card treatment: lighter and less boxed-in */
.review-card {
  background:
    linear-gradient(
      180deg,
      rgba(255, 255, 255, .97) 0%,
      rgba(255, 255, 255, .94) 100%
    ) !important;

  border-color: rgba(61, 70, 67, .075) !important;

  box-shadow:
    0 1px 2px rgba(35, 41, 39, .015),
    0 12px 34px rgba(35, 41, 39, .045) !important;

  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
}

.review-card:hover {
  transform: translateY(-1px);

  border-color: rgba(255, 162, 64, .22) !important;

  box-shadow:
    0 2px 4px rgba(35, 41, 39, .018),
    0 18px 44px rgba(35, 41, 39, .065) !important;
}

/* Softer text hierarchy */
.review-shell :deep(h1),
.review-shell :deep(h2),
.review-shell :deep(h3) {
  color: var(--st-heading);
  letter-spacing: -0.018em;
}

.review-shell :deep(p) {
  text-rendering: optimizeLegibility;
}

.review-shell :deep(.prose) {
  color: var(--st-body);
}

.review-shell :deep(.prose p),
.review-shell :deep(.prose li) {
  color: var(--st-body);
  line-height: 1.8;
}

.review-shell :deep(.prose h1),
.review-shell :deep(.prose h2),
.review-shell :deep(.prose h3),
.review-shell :deep(.prose strong) {
  color: var(--st-heading);
}

/* Metric cards feel more like dashboard widgets */
.review-shell :deep(.grid > div.rounded-2xl) {
  transition:
    transform .18s ease,
    box-shadow .18s ease,
    border-color .18s ease;
}

.review-shell :deep(.grid > div.rounded-2xl:hover) {
  transform: translateY(-1px);
  box-shadow: 0 10px 24px rgba(44, 50, 48, .045);
}

/* Softer table typography */
.review-shell :deep(table th) {
  color: #9AA29F;
  font-weight: 700;
  letter-spacing: .075em;
}

.review-shell :deep(table td) {
  color: #707A77;
}

.review-shell :deep(table tbody tr:hover) {
  background: rgba(255, 212, 29, .045) !important;
}

/* Inputs: cleaner neutral surface */
.review-shell :deep(input:not([type="checkbox"]):not([type="file"])),
.review-shell :deep(textarea),
.review-shell :deep(select) {
  color: #596360;
  background-color: rgba(255, 255, 255, .94);
}

.review-shell :deep(input::placeholder),
.review-shell :deep(textarea::placeholder) {
  color: #AEB5B2;
}

/* Buttons gain a more polished elevation */
.review-shell :deep(button:not(:disabled)) {
  box-shadow: 0 1px 2px rgba(35, 41, 39, .025);
}

.review-shell :deep(button:not(:disabled):hover) {
  box-shadow: 0 7px 18px rgba(35, 41, 39, .07);
}

/* Accent badges stay colorful without overpowering the page */
.review-shell :deep(.rounded-full),
.review-shell :deep(.rounded-lg) {
  -webkit-font-smoothing: antialiased;
}

/* Modal surface refinement */
.modal-card {
  border-color: rgba(61, 70, 67, .08) !important;
  background: rgba(255, 255, 255, .985) !important;

  box-shadow:
    0 34px 100px rgba(39, 44, 43, .18),
    0 5px 20px rgba(39, 44, 43, .06) !important;
}

/* Scrollable discussion/timeline areas */
.review-shell :deep(.overflow-y-auto) {
  scrollbar-gutter: stable;
}

/* Mobile spacing refinement */
@media (max-width: 640px) {
  .review-shell {
    background:
      radial-gradient(circle at 5% 2%, rgba(255, 212, 29, .12), transparent 18rem),
      linear-gradient(180deg, #FFFDF9 0%, #FFFFFF 40%, #FFFBF9 100%);
  }

  .review-card:hover {
    transform: none;
  }
}

/* Public delivery and performance report */
.delivery-hero {
  position: relative;
  background:
    radial-gradient(circle at 90% 15%, rgba(255, 212, 29, .22), transparent 18rem),
    radial-gradient(circle at 12% 115%, rgba(255, 162, 64, .2), transparent 20rem),
    linear-gradient(135deg, #25302e 0%, #313d3a 52%, #82372e 100%);
  box-shadow: 0 24px 60px rgba(37, 48, 46, .16);
}

.delivery-hero::after {
  content: "";
  position: absolute;
  inset: auto 0 0;
  height: 4px;
  background: linear-gradient(90deg, #FFD41D, #FFA240, #FF4646, #D73535);
}

.delivery-title {
  color: #ffffff !important;
  text-shadow: 0 2px 18px rgba(0, 0, 0, .18);
}

.section-icon {
  display: inline-flex;
  width: 42px;
  height: 42px;
  flex: 0 0 42px;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  font-size: 15px;
}

.section-kicker {
  color: #9A4700;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: .13em;
  line-height: 1.2;
  text-transform: uppercase;
}

.section-title {
  margin-top: 3px;
  color: #3c4643;
  font-size: 1.1rem;
  font-weight: 800;
  letter-spacing: -.02em;
}

.report-meta-grid > div {
  border: 1px solid #eceeec;
  border-radius: 16px;
  background: linear-gradient(180deg, #fff 0%, #fcfbf8 100%);
}

.metric-card {
  min-width: 0;
  padding: 1.15rem;
  overflow: hidden;
  border: 1px solid #e7ebe9;
  border-radius: 20px;
  background: #fff;
  box-shadow: 0 8px 22px rgba(47, 56, 53, .04);
}

.metric-icon {
  display: inline-flex;
  width: 38px;
  height: 38px;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
}

.metric-card--amber {
  border-top: 3px solid #ffa240;
}
.metric-card--amber .metric-icon {
  color: #a94d00;
  background: #fff0df;
}
.metric-card--yellow {
  border-top: 3px solid #ffd41d;
}
.metric-card--yellow .metric-icon {
  color: #745e00;
  background: #fff8ce;
}
.metric-card--red {
  border-top: 3px solid #d73535;
}
.metric-card--red .metric-icon {
  color: #b32323;
  background: #ffeded;
}
.metric-card--green {
  border-top: 3px solid #49a48b;
}
.metric-card--green .metric-icon {
  color: #26745f;
  background: #e9f7f2;
}

.ratio-metric + .ratio-metric {
  border-top: 1px solid #e8ecea;
}

.report-content-card {
  min-width: 0;
  max-width: 100%;
  overflow: hidden;
  padding: 1.25rem;
  border: 1px solid #e7ebe9;
  border-radius: 20px;
  background: linear-gradient(145deg, #fff 0%, #fafbf9 100%);
}

.content-number {
  display: inline-flex;
  width: 34px;
  height: 34px;
  flex: 0 0 34px;
  align-items: center;
  justify-content: center;
  border-radius: 11px;
  color: #9a4700;
  background: #fff0df;
  font-size: 10px;
  font-weight: 800;
}

.report-prose :deep(p:first-child) {
  margin-top: 0;
}

.report-prose :deep(p:last-child) {
  margin-bottom: 0;
}

.report-prose {
  min-width: 0;
  max-width: 100%;
  overflow-wrap: break-word;
  word-break: normal;
  hyphens: none;
  white-space: normal;
}

.report-prose :deep(*) {
  min-width: 0;
  max-width: 100%;
  overflow-wrap: break-word;
  word-break: normal;
  hyphens: none;
  white-space: normal !important;
}

.report-prose :deep(p) {
  margin-block: 0.75rem;
}

.report-prose :deep(ul),
.report-prose :deep(ol) {
  margin-block: 0.75rem;
  padding-inline-start: 1.35rem;
}

.report-prose :deep(li) {
  margin-block: 0.25rem;
  padding-inline-start: 0.15rem;
}

.report-prose :deep(a) {
  overflow-wrap: anywhere;
  word-break: break-word;
}

.report-prose :deep(pre) {
  max-width: 100%;
  overflow-x: auto;
  overflow-wrap: anywhere;
  word-break: break-word;
  white-space: pre-wrap !important;
}

.report-prose :deep(table) {
  display: block;
  max-width: 100%;
  overflow-x: auto;
}

.media-card,
.attachment-row,
.comment-card {
  transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}

.media-card {
  color: #46504d !important;
  background: #ffffff !important;
}

.media-visual:focus-visible {
  outline: 3px solid rgba(255, 162, 64, 0.75);
  outline-offset: -3px;
}

.media-zoom-indicator {
  pointer-events: none;
}

.image-viewer-control {
  display: inline-flex;
  width: 2.5rem;
  height: 2.5rem;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.16);
  border-radius: 0.75rem;
  background-color: rgba(255, 255, 255, 0.1);
  transition: background-color 0.18s ease, opacity 0.18s ease, transform 0.18s ease;
}

.image-viewer-control:hover:not(:disabled) {
  background-color: rgba(255, 255, 255, 0.2);
  transform: translateY(-1px);
}

.image-viewer-control:disabled {
  cursor: not-allowed;
  opacity: 0.35;
}

.image-viewer-reset {
  display: none;
}

.image-viewer-image {
  max-width: none;
  transition: width 0.2s ease;
}

.image-viewer-enter-active,
.image-viewer-leave-active {
  transition: opacity 0.2s ease;
}

.image-viewer-enter-from,
.image-viewer-leave-to {
  opacity: 0;
}

.media-caption {
  border-top: 1px solid #e7ebe9;
  background: linear-gradient(145deg, #ffffff 0%, #fffaf2 100%) !important;
}

.media-caption-title {
  color: #303a37 !important;
}

.media-caption-copy,
.media-caption-copy :deep(*) {
  color: #687370 !important;
}

.media-caption-copy :deep(strong),
.media-caption-copy :deep(h1),
.media-caption-copy :deep(h2),
.media-caption-copy :deep(h3) {
  color: #3c4643 !important;
}

.media-card:hover,
.attachment-row:hover {
  border-color: rgba(255, 162, 64, .4);
  box-shadow: 0 14px 30px rgba(45, 54, 51, .07);
}

.attachment-row:hover {
  transform: translateY(-1px);
}

.attachment-action {
  display: inline-flex;
  min-width: 2.5rem;
  height: 2.5rem;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding-inline: 0.7rem;
  border: 1px solid currentColor;
  border-radius: 0.75rem;
  font-size: 0.75rem;
  font-weight: 800;
  transition: background-color 0.18s ease, transform 0.18s ease;
}

.attachment-action:hover {
  background-color: rgba(255, 255, 255, 0.7);
  transform: translateY(-1px);
}

@media (min-width: 640px) {
  .image-viewer-reset {
    display: inline-flex;
  }

  .ratio-metric + .ratio-metric {
    border-top: 0;
  }

  .ratio-metric:nth-child(even) {
    border-left: 1px solid #e8ecea;
  }
}

@media (min-width: 1024px) {
  .ratio-metric + .ratio-metric {
    border-left: 1px solid #e8ecea;
  }
}

:global(:root[data-theme='dark']) .review-shell {
  --st-heading: #f1f5f3;
  --st-body: #b5c1bd;
  --st-muted: #899791;
  --st-line: #2d3a36;
  color: #dbe4e0;
  background:
    radial-gradient(circle at 5% 3%, rgba(255, 212, 29, .07), transparent 23rem),
    radial-gradient(circle at 95% 8%, rgba(215, 53, 53, .08), transparent 26rem),
    linear-gradient(180deg, #0b1211 0%, #101816 42%, #121311 100%);
}

:global(:root[data-theme='dark']) .delivery-page .review-card {
  border-color: #2d3935 !important;
  background: linear-gradient(180deg, #16201e 0%, #131c1a 100%) !important;
  box-shadow: 0 16px 38px rgba(0, 0, 0, .22) !important;
}

:global(:root[data-theme='dark']) .delivery-page .section-title,
:global(:root[data-theme='dark']) .delivery-page .review-card h2,
:global(:root[data-theme='dark']) .delivery-page .review-card h3,
:global(:root[data-theme='dark']) .delivery-page .review-card strong {
  color: #edf4f1 !important;
}

:global(:root[data-theme='dark']) .delivery-page .section-kicker {
  color: #ffb15f !important;
}

:global(:root[data-theme='dark']) .delivery-page .report-meta-grid > div,
:global(:root[data-theme='dark']) .delivery-page .metric-card,
:global(:root[data-theme='dark']) .delivery-page .report-content-card {
  border-color: #33423d !important;
  background: linear-gradient(145deg, #1b2724 0%, #17211f 100%) !important;
}

:global(:root[data-theme='dark']) .delivery-page .report-meta-grid p:first-child,
:global(:root[data-theme='dark']) .delivery-page .metric-card > p:first-of-type,
:global(:root[data-theme='dark']) .delivery-page .metric-card > p:last-of-type,
:global(:root[data-theme='dark']) .delivery-page .ratio-metric > p:last-child,
:global(:root[data-theme='dark']) .delivery-page .report-content-card > div > div > p {
  color: #91a19b !important;
}

:global(:root[data-theme='dark']) .delivery-page .report-meta-grid p:last-child,
:global(:root[data-theme='dark']) .delivery-page .metric-card > p:nth-of-type(2),
:global(:root[data-theme='dark']) .delivery-page .ratio-metric > p:nth-child(2) {
  color: #f2f7f5 !important;
}

:global(:root[data-theme='dark']) .delivery-page .ratio-grid,
:global(:root[data-theme='dark']) .delivery-page .summary-card {
  border-color: #33423d !important;
  background: #101a18 !important;
}

:global(:root[data-theme='dark']) .delivery-page .ratio-metric + .ratio-metric {
  border-color: #33423d !important;
}

:global(:root[data-theme='dark']) .delivery-page .report-prose,
:global(:root[data-theme='dark']) .delivery-page .report-prose :deep(*) {
  color: #b6c3be !important;
}

:global(:root[data-theme='dark']) .delivery-page .media-card {
  border-color: #33423d !important;
  background: #17211f !important;
}

:global(:root[data-theme='dark']) .delivery-page .media-visual {
  background: #0d1513 !important;
}

:global(:root[data-theme='dark']) .delivery-page .media-caption {
  border-color: #33423d;
  background: linear-gradient(145deg, #1b2724 0%, #17211f 100%) !important;
}

:global(:root[data-theme='dark']) .delivery-page .media-caption-title {
  color: #f2f7f5 !important;
}

:global(:root[data-theme='dark']) .delivery-page .attachment-row,
:global(:root[data-theme='dark']) .delivery-page .comment-card {
  border-color: #33423d !important;
  background: #1a2522 !important;
}

:global(:root[data-theme='dark']) .delivery-page input,
:global(:root[data-theme='dark']) .delivery-page textarea {
  border-color: #3b4a45 !important;
  color: #eef4f1 !important;
  background: #0f1816 !important;
}

:global(:root[data-theme='dark']) .delivery-page input::placeholder,
:global(:root[data-theme='dark']) .delivery-page textarea::placeholder {
  color: #72827c !important;
}

</style>
