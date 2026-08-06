<template>  <PublicLayout>    <!-- Loading State -->    <div v-if="loading && !reviewData" class="flex flex-col items-center justify-center py-20">      <div class="w-12 h-12 border-4 border-amber-500 border-t-transparent rounded-full animate-spin">
</div>
      <p class="mt-4 text-slate-500 font-medium">
Memuat data peninjauan dan persetujuan...</p>
    </div>
    <!-- Expired / Revoked / Not Found Fallback (Refinement #2) -->    <div v-else-if="linkStatus !== 'Active'" class="max-w-2xl mx-auto py-12">      <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-lg text-center">        <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl mb-4"             :class="linkStatus === 'Expired' ? 'bg-amber-100 text-amber-600' : 'bg-rose-100 text-rose-600'">
          {{ linkStatus === 'Expired' ? '⏰' : '🔒' }}        </div>
        <h2 class="text-2xl font-bold text-slate-900 mb-2">
          {{ linkStatus === 'Expired' ? 'Tautan Sudah Kedaluwarsa' : (linkStatus === 'Revoked' ? 'Tautan Telah Dinonaktifkan' : 'Tautan Tidak Ditemukan') }}        </h2>
        <p class="text-slate-600 mb-6 leading-relaxed">
          {{ errorMessage || 'Tautan publik ini tidak lagi dapat diakses untuk peninjauan. Hal ini dapat terjadi karena masa berlaku tautan telah habis atau tautan telah ditarik kembali oleh Admin sistem.' }}        </p>
        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/80 text-sm text-slate-500 mb-6">          <p class="font-medium text-slate-700 mb-1">
Bantuan & Tindak Lanjut:</p>
          <p>
Silakan hubungi kontak Admin SunTrack atau Brand Manager Anda untuk meminta pembuatan tautan peninjauan (Secure Public Link) yang baru.</p>
        </div>
        <a href="mailto:admin@suntrack.app" class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl bg-slate-900 text-white font-medium hover:bg-slate-800 transition shadow-sm">
          Hubungi Admin System        </a>
      </div>
    </div>
    <!-- Main Public Review Content (Refinement #9 Order) -->    <div v-else-if="reviewData" class="space-y-8">            <!-- Top Bar: Reviewer Identity Status Badge -->      <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white rounded-2xl p-4 sm:p-6 shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">        <div class="flex items-center space-x-3">          <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center text-xl">👤</div>
          <div>            <p class="text-xs text-slate-400 uppercase font-semibold tracking-wider">
Reviewer Identity</p>
            <p class="text-sm sm:text-base font-bold">
              {{ isIdentified() ? `${reviewerIdentity.name} (${reviewerIdentity.position || 'Reviewer'})` : 'Belum Teridentifikasi' }}              <span v-if="reviewerIdentity.companyName" class="text-xs font-normal text-amber-300 ml-1.5">
• {{ reviewerIdentity.companyName }}</span>
            </p>
          </div>
        </div>
        <button @click="openIdentityModal(true)" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-xs sm:text-sm font-semibold rounded-xl border border-white/15 transition flex items-center space-x-1.5">          <span>
{{ isIdentified() ? 'Ubah Identitas' : 'Identifikasi Diri' }}</span>
          <span>✏️</span>
        </button>
      </div>
      <!-- 1. Informasi Promotion -->      <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm relative overflow-hidden">        <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-amber-400/10 to-transparent rounded-bl-full pointer-events-none">
</div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-6 mb-6">          <div>            <div class="flex items-center space-x-3 mb-2">              <span v-if="reviewData.code" class="px-3 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 text-xs font-mono font-bold tracking-wider">
                {{ reviewData.code }}              </span>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold"                    :class="getStatusBadgeClass(reviewData.status)">
                {{ reviewData.status }}              </span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
{{ reviewData.name }}</h1>
            <p v-if="reviewData.brand" class="text-sm font-semibold text-slate-500 mt-1">
              Brand: <span class="text-slate-700">
{{ reviewData.brand.name }}</span>
            </p>
          </div>
          <div class="flex flex-wrap gap-4 text-sm text-slate-600 bg-slate-50 p-4 rounded-xl border border-slate-100">            <div>              <span class="block text-xs text-slate-400 font-medium">
Periode Promosi</span>
              <span class="font-semibold text-slate-800">
{{ formatDate(reviewData.start_date) }}  -  {{ formatDate(reviewData.end_date) }}</span>
            </div>
          </div>
        </div>
        <div v-if="reviewData.description" class="text-slate-600 text-sm leading-relaxed">          <p class="font-semibold text-slate-800 text-xs uppercase tracking-wider mb-1">
Deskripsi Promosi</p>
          <p>
{{ reviewData.description }}</p>
        </div>
      </div>
      <!-- 2. Informasi Campaign -->      <div v-if="reviewData.campaign" class="bg-gradient-to-r from-blue-50/50 to-indigo-50/50 rounded-2xl p-6 border border-blue-100/80 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">        <div class="flex items-center space-x-3">          <div class="w-10 h-10 rounded-xl bg-blue-500 text-white flex items-center justify-center font-bold text-lg shadow-sm">[Target]</div>
          <div>            <span class="text-xs font-bold uppercase tracking-wider text-blue-600">
Terhubung ke Campaign</span>
            <h3 class="text-lg font-bold text-slate-900">
{{ reviewData.campaign.name }}</h3>
          </div>
        </div>
        <div class="text-xs sm:text-sm text-slate-600 font-medium bg-white px-3.5 py-2 rounded-xl border border-blue-100 shadow-2xs">
          Periode Campaign: <span class="font-bold text-slate-800">
{{ formatDate(reviewData.campaign?.start_date) }} - {{ formatDate(reviewData.campaign?.end_date) }}</span>
        </div>
      </div>
      <!-- 3. Ringkasan Approval (Summary Card - Refinement #5) -->      <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm">        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">          <div>            <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">              
        <span>📊</span>
        <span>Ringkasan Persetujuan (Approval Summary)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
Pantau status persetujuan untuk seluruh wujud produk dalam promosi ini</p>
          </div>
          <div class="flex items-center space-x-4 text-xs text-slate-500 bg-slate-50 px-3.5 py-2 rounded-xl border border-slate-100">            <div>              <span class="block text-slate-400">
Terakhir Diperbarui:</span>
              <span class="font-semibold text-slate-700">
{{ formatDateTime(reviewData.approval_summary.last_updated) }}</span>
            </div>
            <div v-if="reviewData.approval_summary.last_reviewer" class="border-l border-slate-200 pl-4">              <span class="block text-slate-400">
Reviewer Terakhir:</span>
              <span class="font-semibold text-slate-700">
{{ reviewData.approval_summary.last_reviewer.name }}</span>
            </div>
          </div>
        </div>
        <!-- Metric Grid -->        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">          <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 text-center">            <span class="block text-2xl sm:text-3xl font-extrabold text-slate-800">
{{ reviewData.approval_summary.total_variants }}</span>
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider mt-1 block">
Total Variant</span>
          </div>
          <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100 text-center">            <span class="block text-2xl sm:text-3xl font-extrabold text-emerald-600">
{{ reviewData.approval_summary.approved }}</span>
            <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mt-1 block">
Approved</span>
          </div>
          <div class="bg-amber-50 rounded-xl p-4 border border-amber-100 text-center">            <span class="block text-2xl sm:text-3xl font-extrabold text-amber-600">
{{ reviewData.approval_summary.pending }}</span>
            <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider mt-1 block">
Pending</span>
          </div>
          <div class="bg-rose-50 rounded-xl p-4 border border-rose-100 text-center">            <span class="block text-2xl sm:text-3xl font-extrabold text-rose-600">
{{ reviewData.approval_summary.rejected }}</span>
            <span class="text-xs font-semibold text-rose-600 uppercase tracking-wider mt-1 block">
Rejected</span>
          </div>
        </div>
        <!-- Progress Bar -->        <div>          <div class="flex items-center justify-between text-xs font-bold text-slate-700 mb-1.5">            <span>
Tingkat Penyelesaian Review (Completion Rate)</span>
            <span>
{{ reviewData.approval_summary.completion_percentage }}%</span>
          </div>
          <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden flex">            <div class="bg-emerald-500 h-full transition-all duration-500"                 :style="{ width: `${(reviewData.approval_summary.approved / Math.max(reviewData.approval_summary.total_variants, 1)) * 100}%` }"                 title="Approved">
</div>
            <div class="bg-rose-500 h-full transition-all duration-500"                 :style="{ width: `${(reviewData.approval_summary.rejected / Math.max(reviewData.approval_summary.total_variants, 1)) * 100}%` }"                 title="Rejected">
</div>
          </div>
        </div>
      </div>
      <!-- 4. Daftar Variant & Approval Workflow -->      <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm">        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">          <div>            <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">              <span>🛍️</span>
              <span>
Daftar Produk & Wujud Variant (Approval per Variant &amp; Batch)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">
Tinjau harga dan berikan persetujuan atau penolakan per item maupun secara batch.</p>
          </div>
          <button @click="showHistoryModal = true" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition flex items-center space-x-1.5">            <span>📜</span>
            <span>
Riwayat Approval ({{ reviewData.approval_histories?.length || 0 }})</span>
          </button>
        </div>
        <!-- Batch Action Toolbar -->          <div>        <div v-if="reviewData.variants && reviewData.variants.length >
 0" class="flex flex-wrap items-center justify-between gap-3 p-3.5 bg-blue-50/50 border border-blue-100 rounded-xl mb-4">          <div class="flex items-center space-x-2 text-xs font-bold text-slate-700">            <span>
Pilih Variant: {{ selectedVariantIds.length }} dari {{ reviewData.variants.length }} terpilih</span>
            <button v-if="selectedVariantIds.length >
 0" @click="selectedVariantIds = []" class="text-blue-600 hover:underline text-2xs">
(ResetPilihan)</button>
          </div>
          <div class="flex flex-wrap items-center gap-2">            <button @click="handleBatchAction('approve_selected')" :disabled="selectedVariantIds.length === 0 || batchLoading"                    class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 disabled:bg-slate-300 text-white font-bold text-xs rounded-lg shadow-xs transition flex items-center space-x-1">              <span>
✓ Setujui Terpilih ({{ selectedVariantIds.length }})</span>
            </button>
            <button @click="handleBatchAction('reject_selected')" :disabled="selectedVariantIds.length === 0 || batchLoading"                    class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 disabled:bg-slate-300 text-white font-bold text-xs rounded-lg shadow-xs transition flex items-center space-x-1">              <span>
✕ Tolak Terpilih ({{ selectedVariantIds.length }})</span>
            </button>
            <span class="text-slate-300">|</span>
            <button @click="handleBatchAction('approve_all')" :disabled="batchLoading"                    class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold text-xs rounded-lg transition flex items-center space-x-1">              <span>
✨ Setujui Semua ({{ reviewData.variants.length }})</span>
            </button>
            <button @click="handleBatchAction('reject_all')" :disabled="batchLoading"                    class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-300 font-bold text-xs rounded-lg transition flex items-center space-x-1">              <span>
⚠️ Tolak Semua ({{ reviewData.variants.length }})</span>
            </button>
          </div>
        </div>
        </div>
        <!-- Table / List -->        <div class="overflow-x-auto">          <table class="w-full text-left border-collapse">            <thead>              <tr class="border-b border-slate-200 text-xs font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">                <th class="py-3.5 px-3 w-10 text-center rounded-l-xl">                  <input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />                </th>
                <th class="py-3.5 px-4">
Produk & Variant</th>
                <th class="py-3.5 px-4">
Harga Normal</th>
                <th class="py-3.5 px-4">
Harga Promo</th>
                <th class="py-3.5 px-4">
Stok / Limit</th>
                <th class="py-3.5 px-4 text-center">
Status</th>
                <th class="py-3.5 px-4 text-right rounded-r-xl">
Aksi Review</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">              <tr v-for="variant in reviewData.variants" :key="variant.id" class="hover:bg-slate-50/60 transition" :class="{'bg-blue-50/30': selectedVariantIds.includes(variant.id)}">                <td class="py-4 px-3 text-center">                  <input type="checkbox" :value="variant.id" v-model="selectedVariantIds" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />                </td>
                <td class="py-4 px-4 font-medium text-slate-900">                  <span class="block font-bold">
{{ variant.product_name }}</span>
                  <span class="text-xs text-slate-500">
{{ variant.name }} <span v-if="variant.sku" class="font-mono text-slate-400">
({{ variant.sku }})</span>

</span>
                  <p v-if="variant.rejection_notes" class="mt-1 text-xs text-rose-600 bg-rose-50 px-2 py-1 rounded border border-rose-100 inline-block font-normal">
                    ⚠️ Catatan: {{ variant.rejection_notes }}                  </p>
                </td>
                <td class="py-4 px-4 font-mono text-slate-600">
{{ formatCurrency(variant.normal_price_snapshot) }}</td>
                <td class="py-4 px-4 font-mono font-bold text-emerald-600">                  <span class="block">
{{ formatCurrency(variant.campaign_price) }}</span>
                  <span v-if="variant.discount_price < variant.normal_price_snapshot" class="text-xs font-normal text-slate-400 line-through">
                    {{ formatCurrency(variant.discount_price) }}                  </span>
                </td>
                <td class="py-4 px-4 text-slate-600">                  <span class="block">
{{ variant.promotion_stock }} unit</span>
                  <span v-if="variant.purchase_limit >
 0" class="text-xs text-slate-400">
Max {{ variant.purchase_limit }}/user</span>
                </td>
                <td class="py-4 px-4 text-center">                  <span class="px-2.5 py-1 rounded-full text-xs font-semibold inline-block"                        :class="getVariantStatusBadgeClass(variant.approval_status)">
                    {{ variant.approval_status }}                  </span>
                </td>
                <td class="py-4 px-4 text-right">                  <div class="inline-flex flex-wrap justify-end items-center gap-2">
                    <button @click="handleAction('Approved', variant)"                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-2xs whitespace-nowrap"                            :class="variant.approval_status === 'Approved' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200'">
                      ✓ {{ variant.approval_status === 'Approved' ? 'Disetujui' : 'Approve' }}                    </button>
                    <button @click="handleAction('Rejected', variant)"                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-2xs whitespace-nowrap"                            :class="variant.approval_status === 'Rejected' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200'">
                      ✕ {{ variant.approval_status === 'Rejected' ? 'Ditolak' : 'Reject' }}                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="reviewData.variants.length === 0">                <td colspan="7" class="py-8 text-center text-slate-400">
Belum ada wujud variant yang dipetakan ke promosi ini.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <!-- 4b. Daftar Task Campaign (Refinement #10) -->      <div v-if="reviewData.type === 'Campaign' && reviewData.tasks && reviewData.tasks.length" class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm">        <div class="flex items-center justify-between gap-4 mb-6">          <div>            <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">              <span>📋</span>
              <span>Daftar Task Campaign</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Perbarui status pengerjaan dan kirim hasil visual jika dibutuhkan.</p>
          </div>
        </div>
        <div class="space-y-4">          <div v-for="task in reviewData.tasks" :key="task.id" class="rounded-xl bg-slate-50 border border-slate-200/80 p-4">            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">              <div class="min-w-0 flex-1">                <p class="font-bold text-slate-800 flex items-center space-x-2 flex-wrap gap-y-1">                  <span>{{ task.name }}</span>
                  <span v-if="task.requires_visual" class="px-2 py-0.5 rounded text-2xs font-extrabold uppercase tracking-wider bg-purple-100 text-purple-700 border border-purple-200">Butuh Visual</span>
                  <span class="px-2.5 py-0.5 rounded-full text-2xs font-semibold inline-block" :class="getTaskStatusBadgeClass(task.progress_status)">
                    {{ getTaskStatusLabel(task.progress_status) }}
                  </span>
                </p>
                <p class="text-xs text-slate-500 mt-1">                  <span v-if="task.visual_type" class="font-medium">Jenis Visual: {{ task.visual_type }}</span>
                  <span v-if="task.deadline" class="ml-2">⏰ Deadline: {{ formatDate(task.deadline) }}</span>
                </p>
                <p v-if="task.creative_brief" class="text-xs text-slate-500 mt-1">Brief: {{ Array.isArray(task.creative_brief) ? task.creative_brief.join(', ') : task.creative_brief }}</p>
              </div>
              <div class="shrink-0 flex flex-wrap items-center gap-2">
                <button type="button" @click="handleTaskStatus(task, 'Completed')"
                    :disabled="taskBusy(task.id) || taskIsLocked(task) || (task.requires_visual && !hasVisualForTask(task))"
                    :class="task.progress_status === 'Completed' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition border"
                    :title="taskIsLocked(task) ? 'Task sudah selesai dan dikunci' : (task.requires_visual ? (hasVisualForTask(task) ? 'Siap dikirim' : 'Butuh visual sebelum menandai selesai') : '')">
                  ✓ Sudah Dikerjakan
                </button>
                <button type="button" @click="handleTaskStatus(task, 'NotStarted')"
                    :disabled="taskBusy(task.id) || taskIsLocked(task)"
                    :class="task.progress_status === 'NotStarted' ? 'bg-slate-600 text-white border-slate-600' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-100'"
                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition border"
                    :title="taskIsLocked(task) ? 'Task sudah selesai dan dikunci' : ''">
                  ✕ Belum Dikerjakan
                </button>
              </div>
            </div>
            <!-- Visual submission area (only when requires_visual) -->            <div v-if="task.requires_visual" class="mt-3 pt-3 border-t border-slate-200/70">              <!-- Already submitted summary -->              <div v-if="task.visual_link || task.visual_file_url" class="mb-3 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-xs text-emerald-800">                <p class="font-bold mb-1">Visual sudah dikirim:  {{ task.visual_file_name || 'via Link' }}</p>
                <p v-if="task.visual_link" class="mb-1">🔗 Link: <a :href="task.visual_link" target="_blank" rel="noopener" class="underline">{{ task.visual_link }}</a></p>
                <p v-if="task.visual_file_url" class="mb-1">🖼️ File: <a :href="task.visual_file_url" target="_blank" rel="noopener" class="underline">{{ task.visual_file_name || 'Lihat gambar' }}</a></p>
                <p v-if="task.submitted_by" class="text-emerald-600">Dikirim oleh: {{ task.submitted_by }} {{ task.submitted_at ? ('pada ' + formatDateTime(task.submitted_at)) : '' }}</p>
                <div v-if="task.visual_file_url || task.visual_link" class="mt-2">
                  <button @click="handleDeleteVisual(task)" class="px-3 py-1 text-rose-700 bg-rose-50 border border-rose-200 rounded-lg text-xs hover:bg-rose-100">Hapus Visual</button>
                </div>
              </div>
              <form @submit.prevent="handleSubmitVisual(task)">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Link Google Drive / URL Visual:</label>
                <input type="url" v-model="taskVisualLinks[task.id]" placeholder="https://drive.google.com/..." class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-400 mb-2" />
                <label class="block text-xs font-semibold text-slate-600 mb-1">Atau unggah gambar (JPG/PNG/WEBP/GIF, maks 5MB):</label>
                <input type="file" accept="image/*" @change="(e) => onTaskFileChange(e, task.id)" ref="" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-amber-50 file:px-3 file:py-1.5 file:text-amber-700 file:font-semibold mb-2" />
                <div v-if="taskFileErrors[task.id]" class="text-rose-600 text-xs mb-2">{{ taskFileErrors[task.id] }}</div>
                <div v-if="taskVisualPreviews[task.id]" class="mb-2">
                  <img :src="taskVisualPreviews[task.id]" alt="Preview" class="max-h-40 rounded-md border border-slate-200" />
                </div>
                <button type="button" @click="handleSubmitVisual(task)" :disabled="taskBusy(task.id) || (!taskVisualFiles[task.id] && !taskVisualLinks[task.id]) || taskFileErrors[task.id]" class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-400 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-xs transition">
                  Kirim Visual
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
      <!-- 5. Komentar (Discussion Thread - Refinement #7) -->      <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm">        <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2 mb-6">          <span>💬</span>
          <span>
Diskusi & Komentar (Comments)</span>
          <span class="text-xs font-normal text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full">
{{ reviewData.comments?.length || 0 }}</span>
        </h2>
        <!-- Comment List -->        <div class="space-y-4 mb-6 max-h-96 overflow-y-auto pr-2">          <div v-for="comment in reviewData.comments" :key="comment.id" class="p-4 rounded-xl border transition"               :class="comment.author_type === 'Admin' ? 'bg-blue-50/40 border-blue-100 ml-4 sm:ml-8' : 'bg-slate-50 border-slate-200/80 mr-4 sm:mr-8'">            <div class="flex items-center justify-between mb-2">              <div class="flex items-center space-x-2">                <!-- Author Type Badge (Refinement #7) -->                <span class="px-2 py-0.5 rounded text-2xs font-extrabold uppercase tracking-wider"                      :class="comment.author_type === 'Admin' ? 'bg-blue-600 text-white' : 'bg-amber-500 text-white'">
                  {{ comment.author_type }}                </span>
                <span class="font-bold text-slate-800 text-sm">
{{ comment.author_name }}</span>
                <span v-if="comment.author_position" class="text-xs text-slate-400">
({{ comment.author_position }})</span>
              </div>
              <span class="text-xs text-slate-400 font-medium">
{{ formatDateTime(comment.created_at) }}</span>
            </div>
            <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
{{ comment.body }}</p>
          </div>
          <div v-if="!reviewData.comments || reviewData.comments.length === 0" class="py-8 text-center bg-slate-50/50 rounded-xl border border-dashed border-slate-200 text-slate-400 text-sm">
            Belum ada komentar diskusi. Mulai percakapan atau tinggalkan catatan untuk tim Admin di bawah ini.          </div>
        </div>
        <!-- Post Comment Form -->        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200/80">          <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
Tulis Komentar atau Feedback</label>
          <textarea v-model="newCommentBody" rows="3"                    placeholder="Tulis pesan, pertanyaan, atau catatan kolaborasi untuk Admin..."                    class="w-full rounded-xl border border-slate-200 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 mb-3">
</textarea>
          <div class="flex items-center justify-between">            <span class="text-xs text-slate-400">
              Posting sebagai: <strong class="text-slate-600">
{{ isIdentified() ? reviewerIdentity.name : 'Belum Teridentifikasi' }}</strong>
            </span>
            <button @click="handlePostComment" :disabled="!newCommentBody.trim() || loading"                    class="px-5 py-2 bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white font-bold text-xs rounded-xl transition shadow-sm">
              Kirim Komentar 🚀            </button>
          </div>
        </div>
      </div>
      <!-- 6. Timeline (Unified Activity Timeline - Refinement #8) -->      <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm">        <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2 mb-6">          <span>⏱️</span>
          <span>
Kronologi Aktivitas (Activity Timeline)</span>
        </h2>
        <div class="relative pl-6 border-l-2 border-slate-200 space-y-6">          <div v-for="log in latestTimeline" :key="log.id" class="relative group">            <div class="absolute -left-[31px] top-0 w-3.5 h-3.5 rounded-full border-2 border-white shadow-xs"                 :class="log.actor_type === 'Admin' ? 'bg-blue-500' : 'bg-amber-500'">
</div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-1">              <span class="font-bold text-slate-800 text-sm flex items-center space-x-2">                <span>
{{ log.action }}</span>
                <span class="px-1.5 py-0.5 rounded text-3xs font-extrabold uppercase"                      :class="log.actor_type === 'Admin' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'">
                  {{ log.actor_type }}                </span>
              </span>
              <span class="text-xs text-slate-400">
{{ formatDateTime(log.created_at) }}</span>
            </div>
            <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
{{ log.description }}</p>
            <p v-if="log.actor_name" class="text-xs text-slate-400 mt-0.5">
Oleh: {{ log.actor_name }} <span v-if="log.actor_position">
({{ log.actor_position }})</span>

</p>
          </div>
        </div>
      </div>
    </div>
    <!-- MODAL: Reviewer Identification (Refinement #3) -->    <div v-if="showIdentityModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">          <h3 class="text-lg font-bold text-slate-900">            {{ isIdentified() ? 'Ubah Identitas Reviewer Brand' : 'Identifikasi Reviewer Brand' }}
          </h3>
          <button @click="showIdentityModal = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>
        <p class="text-xs text-slate-500 mb-4 leading-relaxed">
          Sebelum melakukan aksi review (Approve/Reject) atau komentar, mohon lengkapi identitas Anda untuk pencatatan jejak audit (Audit Trail) SunTrack.        </p>
        <form @submit.prevent="submitIdentityForm" class="space-y-4">          <div>            <label class="block text-xs font-bold text-slate-700 mb-1">
Nama Lengkap <span class="text-rose-500">*</span>

</label>
            <input v-model="identityForm.name" type="text" required placeholder="Contoh: Budi Santoso"                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500" />          </div>
          <div>            <label class="block text-xs font-bold text-slate-700 mb-1">
Jabatan / Posisi <span class="text-slate-400 font-normal">
(Opsional)</span>

</label>
            <input v-model="identityForm.position" type="text" placeholder="Contoh: Brand Manager / Marketing Director"                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500" />          </div>
          <div>            <label class="block text-xs font-bold text-slate-700 mb-1">
Nama Perusahaan / Brand
</label>
            <input v-model="identityForm.companyName" type="text" placeholder="Nama brand otomatis" disabled class="w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 text-sm text-slate-500 focus:outline-none" />          </div>
          <div>            <label class="block text-xs font-bold text-slate-700 mb-1">
Nomor WhatsApp <span class="text-slate-400 font-normal">
(Opsional)</span>

</label>
            <input v-model="identityForm.whatsappNumber" type="text" placeholder="Contoh: 081234567890"                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500" />          </div>
          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">            <button type="button" @click="showIdentityModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
              Batal            </button>
            <button type="submit" :disabled="!identityForm.name.trim() || loading" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white text-xs font-bold transition shadow-sm">
              Simpan Identitas & Lanjutkan            </button>
          </div>
        </form>
      </div>
    </div>
    <!-- MODAL: Rejection Notes (Mandatory - Refinement #4) -->    <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">          <h3 class="text-lg font-bold text-rose-600 flex items-center space-x-2">            <span>⚠️</span>
            <span>
Tolak Variant Produk</span>
          </h3>
          <button @click="showRejectModal = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>
        <p class="text-xs text-slate-600 mb-3">
          Anda akan menolak variant: <strong class="text-slate-900">
{{ selectedVariant?.product_name }} - {{ selectedVariant?.name }}</strong>
.        </p>
        <form @submit.prevent="confirmRejectVariant" class="space-y-4">          <div>            <label class="block text-xs font-bold text-slate-700 mb-1">
Catatan Penolakan (Wajib Diisi) <span class="text-rose-500">*</span>

</label>
            <textarea v-model="rejectionNoteInput" rows="3" required placeholder="Jelaskan alasan penolakan, misalnya harga promo terlalu rendah atau stok tidak mencukupi..."                      class="w-full rounded-xl border border-rose-200 p-3 text-sm focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500">
</textarea>
            <p class="text-2xs text-rose-500 mt-1">
Alasan penolakan wajib disertakan agar Admin dapat menindaklanjuti.</p>
          </div>
          <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">            <button type="button" @click="showRejectModal = false" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
              Batal            </button>
            <button type="submit" :disabled="!rejectionNoteInput.trim() || loading" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white text-xs font-bold transition shadow-sm">
              Konfirmasi Penolakan            </button>
          </div>
        </form>
      </div>
    </div>
    <!-- MODAL: Approval History (Refinement #6) -->    <div v-if="showHistoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">      <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 max-h-[85vh] flex flex-col">        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">          <h3 class="text-lg font-bold text-slate-900 flex items-center space-x-2">            <span>📜</span>
            <span>
Riwayat Approval (Immutable History)</span>
          </h3>
          <button @click="showHistoryModal = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>
        <div class="flex-grow overflow-y-auto space-y-3 pr-2">          <div v-for="hist in reviewData.approval_histories" :key="hist.id" class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 text-xs sm:text-sm">            <div class="flex items-center justify-between font-bold text-slate-800 mb-1">              <span>
{{ hist.variant_name }} <span v-if="hist.variant_sku" class="font-mono font-normal text-slate-400">
({{ hist.variant_sku }})</span>

</span>
              <span class="text-xs text-slate-400 font-normal">
{{ formatDateTime(hist.created_at) }}</span>
            </div>
            <div class="flex items-center space-x-2 my-2 text-xs">              <span class="px-2 py-0.5 rounded bg-slate-200 font-semibold text-slate-700">
{{ hist.old_status }}</span>
              <span>➝</span>
              <span class="px-2 py-0.5 rounded font-bold"                    :class="hist.new_status === 'Approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'">
                {{ hist.new_status }}              </span>
            </div>
            <p v-if="hist.notes" class="text-xs text-rose-700 bg-rose-50 p-2 rounded border border-rose-100 mt-2">
              📝 Catatan: {{ hist.notes }}            </p>
            <p class="text-2xs text-slate-400 mt-2 border-t border-slate-200/60 pt-1.5">
              Reviewer: <strong>
{{ hist.reviewer_name }}</strong>
 <span v-if="hist.reviewer_position">
({{ hist.reviewer_position }})</span>
 <span v-if="hist.company_name">
• {{ hist.company_name }}</span>
            </p>
          </div>
          <div v-if="!reviewData.approval_histories || reviewData.approval_histories.length === 0" class="py-8 text-center text-slate-400 text-sm">
            Belum ada riwayat perubahan status approval.          </div>
        </div>
        <div class="pt-4 border-t border-slate-100 text-right mt-4">          <button @click="showHistoryModal = false" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition">
              Tutup Riwayat          </button>
          </div>
        </div>
      </div>

      <!-- Toasts -->
      <div class="fixed top-4 right-4 z-50 flex flex-col items-end space-y-2">
        <transition-group name="toast" tag="div">
          <div v-for="t in toasts" :key="t.id" class="max-w-sm w-full px-4 py-2 rounded-lg shadow-md flex items-start gap-3" :class="{
            'bg-emerald-500 text-white': t.type === 'success',
            'bg-rose-500 text-white': t.type === 'error',
            'bg-slate-800 text-white': t.type === 'info'
          }">
            <div class="flex-1 text-sm" v-html="t.message"></div>
            <button @click="removeToast(t.id)" class="text-white opacity-90 hover:opacity-100">✕</button>
          </div>
        </transition-group>
      </div>

      <!-- Confirm Modal -->
      <div v-if="confirmModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
          <div class="mb-4 text-lg font-bold">Konfirmasi</div>
          <p class="text-sm text-slate-600 mb-4">{{ confirmModal.message }}</p>
          <div class="flex justify-end space-x-3">
            <button @click="confirmCancel" class="px-4 py-2 rounded-lg border">Batal</button>
            <button @click="confirmOk" class="px-4 py-2 rounded-lg bg-amber-500 text-white">Ya</button>
          </div>
        </div>
      </div>

      <!-- Prompt Modal -->
      <div v-if="promptModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
          <div class="mb-4 text-lg font-bold">{{ promptModal.title || 'Masukkan' }}</div>
          <p v-if="promptModal.message" class="text-sm text-slate-600 mb-3">{{ promptModal.message }}</p>
          <input v-model="promptModal.value" type="text" class="w-full rounded-lg border px-3 py-2 mb-4" />
          <div class="flex justify-end space-x-3">
            <button @click="promptCancel" class="px-4 py-2 rounded-lg border">Batal</button>
            <button @click="promptOk" class="px-4 py-2 rounded-lg bg-amber-500 text-white">Kirim</button>
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

const taskBusyIds = ref(new Set());
const taskVisualFiles = ref({});
const taskVisualLinks = ref({});
const taskVisualPreviews = ref({});
const taskFileErrors = ref({});

const taskBusy = (taskId) => taskBusyIds.value.has(taskId);

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

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'Approved':
      return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
    case 'Partially Approved':
      return 'bg-blue-100 text-blue-800 border border-blue-200';
    case 'Rejected':
      return 'bg-rose-100 text-rose-800 border border-rose-200';
    default:
      return 'bg-amber-100 text-amber-800 border border-amber-200';
  }
};

const getTaskStatusLabel = (status) => {
  const map = {
    NotStarted: 'Belum Dikerjakan',
    InProgress: 'Sedang Dikerjakan',
    Revision: 'Revisi',
    Completed: 'Selesai',
    OnHold: 'Ditunda',
  };
  return map[status] || status || 'Belum Dikerjakan';
};

const getTaskStatusBadgeClass = (status) => {
  switch (status) {
    case 'Completed':
      return 'bg-emerald-100 text-emerald-800 border border-emerald-200';
    case 'InProgress':
      return 'bg-blue-100 text-blue-800 border border-blue-200';
    case 'Revision':
      return 'bg-amber-100 text-amber-800 border border-amber-200';
    case 'OnHold':
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

