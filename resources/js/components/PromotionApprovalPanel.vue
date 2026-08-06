<template>
  <div class="space-y-6">
    <!-- Secure Public Link Card (Admin Control - Refinement #1) -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-xs">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-100 mb-4">
        <div>
          <h3 class="text-lg font-bold text-gray-900 flex items-center space-x-2">
            <span>🔗</span>
            <span>Secure Public Link (Brand Review Workspace)</span>
          </h3>
          <p class="text-xs text-gray-500 mt-0.5">Bagikan tautan ini kepada tim Brand untuk melakukan peninjauan harga dan approval per variant tanpa perlu login.</p>
        </div>
        <div class="flex items-center space-x-2">
          <span v-if="secureLink" class="px-2.5 py-1 rounded-full text-xs font-bold"
                :class="secureLink.status === 'Active' ? 'bg-emerald-100 text-emerald-800' : (secureLink.status === 'Expired' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')">
            ● Status: {{ secureLink.status }}
          </span>
          <span v-else class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-bold">
            ● Belum Dibuat
          </span>
        </div>
      </div>

      <!-- Link Metadata & Actions -->
      <div v-if="secureLink" class="space-y-4">
        <!-- URL Copy Box -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
          <input type="text" readonly :value="secureLink.url"
                 class="flex-grow bg-gray-50 border border-gray-300 rounded-lg px-3.5 py-2 font-mono text-sm text-gray-700 select-all focus:outline-none focus:ring-2 focus:ring-blue-500/20" />
          <button @click="copyToClipboard" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-lg transition shadow-2xs whitespace-nowrap flex items-center justify-center space-x-1.5">
            <span>{{ copied ? '✓ Tersalin!' : '📋 Salin Tautan' }}</span>
          </button>
          <a :href="secureLink.url" target="_blank" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-lg transition whitespace-nowrap flex items-center justify-center space-x-1">
            <span>Buka ↗</span>
          </a>
        </div>

        <!-- Audit Metadata (Refinement #1) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-gray-50/80 p-4 rounded-xl border border-gray-100 text-xs text-gray-600">
          <div>
            <span class="block text-gray-400 font-medium">Tanggal Dibuat</span>
            <span class="font-bold text-gray-800">{{ formatDateTime(secureLink.created_at) }}</span>
          </div>
          <div>
            <span class="block text-gray-400 font-medium">Dibuat Oleh</span>
            <span class="font-bold text-gray-800">{{ secureLink.created_by_name || 'Admin' }}</span>
          </div>
          <div>
            <span class="block text-gray-400 font-medium">Terakhir Diakses</span>
            <span class="font-bold text-gray-800">{{ secureLink.last_accessed_at ? formatDateTime(secureLink.last_accessed_at) : 'Belum pernah diakses' }}</span>
          </div>
          <div>
            <span class="block text-gray-400 font-medium">Total Akses (View Count)</span>
            <span class="font-bold text-blue-600 text-sm">{{ secureLink.view_count }} kali</span>
          </div>
        </div>

        <!-- Expiry and Revoke Controls -->
        <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
          <div class="flex items-center space-x-2">
            <label class="text-xs font-bold text-gray-700">Masa Berlaku (Expires At):</label>
            <input v-model="expiryInput" type="datetime-local" class="border border-gray-300 rounded-lg px-2.5 py-1 text-xs text-gray-700 focus:ring-blue-500 focus:border-blue-500" />
            <button @click="updateExpiry" :disabled="loading" class="px-3 py-1 bg-gray-900 hover:bg-gray-800 text-white font-bold text-xs rounded-lg transition">
              Simpan Masa Berlaku
            </button>
          </div>
          <div class="flex items-center space-x-2">
            <button @click="handleRegenerate" :disabled="loading" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 font-bold text-xs rounded-lg transition">
              🔄 Regenerate Token
            </button>
            <button v-if="secureLink.status !== 'Revoked'" @click="handleRevoke" :disabled="loading" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs rounded-lg transition">
              🚫 Cabut Tautan (Revoke)
            </button>
          </div>
        </div>
      </div>

      <!-- No Link State -->
      <div v-else class="py-8 text-center bg-gray-50 rounded-xl border border-dashed border-gray-200">
        <p class="text-sm text-gray-500 mb-4">Promosi ini belum memiliki Secure Public Link untuk kolaborasi luar.</p>
        <button @click="handleGenerate" :disabled="loading" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition inline-flex items-center space-x-2">
          <span>✨</span>
          <span>Buat Secure Public Link Sekarang</span>
        </button>
      </div>
    </div>

    <!-- Variant Approval Progress Card & Batch Actions -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-xs">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-gray-100 mb-4">
        <div>
          <h3 class="text-lg font-bold text-gray-900 flex items-center space-x-2">
            <span>📋</span>
            <span>Status Persetujuan Variant &amp; Batch Action</span>
          </h3>
          <p class="text-xs text-gray-500 mt-0.5">Pantau dan kelola status approval per variant sekaligus dengan fitur Batch Approval.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button @click="showHistoryModal = true" class="px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-lg transition flex items-center space-x-1">
            <span>📜</span>
            <span>Riwayat Approval ({{ histories.length }})</span>
          </button>
        </div>
      </div>

      <!-- Batch Action Toolbar -->
      <div v-if="variants.length > 0" class="flex flex-wrap items-center justify-between gap-3 p-3.5 bg-blue-50/50 border border-blue-100 rounded-xl mb-4">
        <div class="flex items-center space-x-2 text-xs font-bold text-gray-700">
          <span>Pilih Variant: {{ selectedVariantIds.length }} dari {{ variants.length }} terpilih</span>
          <button v-if="selectedVariantIds.length > 0" @click="selectedVariantIds = []" class="text-blue-600 hover:underline text-2xs">(ResetPilihan)</button>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button @click="handleBatchApproval('approve_selected')" :disabled="selectedVariantIds.length === 0 || batchLoading"
                  class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 disabled:bg-gray-300 text-white font-bold text-xs rounded-lg shadow-xs transition flex items-center space-x-1">
            <span>✓ Setujui Terpilih ({{ selectedVariantIds.length }})</span>
          </button>
          <button @click="handleBatchApproval('reject_selected')" :disabled="selectedVariantIds.length === 0 || batchLoading"
                  class="px-3 py-1.5 bg-rose-600 hover:bg-rose-500 disabled:bg-gray-300 text-white font-bold text-xs rounded-lg shadow-xs transition flex items-center space-x-1">
            <span>✕ Tolak Terpilih ({{ selectedVariantIds.length }})</span>
          </button>
          <span class="text-gray-300">|</span>
          <button @click="handleBatchApproval('approve_all')" :disabled="batchLoading"
                  class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold text-xs rounded-lg transition flex items-center space-x-1">
            <span>✨ Setujui Semua ({{ variants.length }})</span>
          </button>
          <button @click="handleBatchApproval('reject_all')" :disabled="batchLoading"
                  class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-300 font-bold text-xs rounded-lg transition flex items-center space-x-1">
            <span>⚠️ Tolak Semua ({{ variants.length }})</span>
          </button>
        </div>
      </div>

      <!-- Table of Variants -->
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
          <thead>
            <tr class="border-b border-gray-200 text-xs font-bold text-gray-400 uppercase tracking-wider bg-gray-50">
              <th class="py-3 px-3 w-10 text-center">
                <input type="checkbox" @change="toggleSelectAll" :checked="isAllSelected" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
              </th>
              <th class="py-3 px-4">Variant SKU / Nama</th>
              <th class="py-3 px-4">Harga Promo</th>
              <th class="py-3 px-4 text-center">Status Approval</th>
              <th class="py-3 px-4">Catatan Brand</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="variant in variants" :key="variant.id" class="hover:bg-gray-50/50" :class="{'bg-blue-50/30': selectedVariantIds.includes(variant.id)}">
              <td class="py-3.5 px-3 text-center">
                <input type="checkbox" :value="variant.id" v-model="selectedVariantIds" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
              </td>
              <td class="py-3.5 px-4 font-medium text-gray-900">
                <span class="block">{{ variant.product_name || 'Product' }} — {{ variant.name }}</span>
                <span class="text-xs text-gray-400 font-mono">{{ variant.sku }}</span>
              </td>
              <td class="py-3.5 px-4 font-mono font-bold text-emerald-600">{{ formatCurrency(variant.campaign_price) }}</td>
              <td class="py-3.5 px-4 text-center">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                      :class="getVariantBadgeClass(variant.approval_status)">
                  {{ variant.approval_status || 'Pending' }}
                </span>
              </td>
              <td class="py-3.5 px-4 text-xs text-gray-600">
                <span v-if="variant.rejection_notes" class="text-rose-600 font-semibold bg-rose-50 px-2 py-1 rounded border border-rose-100 inline-block">
                  ⚠️ {{ variant.rejection_notes }}
                </span>
                <span v-else class="text-gray-400 italic">—</span>
              </td>
            </tr>
            <tr v-if="variants.length === 0">
              <td colspan="5" class="py-6 text-center text-gray-400">Belum ada variant produk pada promosi ini.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- MODAL: Immutable Approval History (Refinement #6) -->
    <div v-if="showHistoryModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs">
      <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-xl border border-gray-100 max-h-[80vh] flex flex-col">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-4">
          <h3 class="text-lg font-bold text-gray-900 flex items-center space-x-2">
            <span>📜</span>
            <span>Jejak Audit Approval (Immutable Audit Trail)</span>
          </h3>
          <button @click="showHistoryModal = false" class="text-gray-400 hover:text-gray-600 text-lg">✕</button>
        </div>
        <div class="flex-grow overflow-y-auto space-y-3 pr-2">
          <div v-for="h in histories" :key="h.id" class="p-3.5 rounded-xl bg-gray-50 border border-gray-200 text-xs sm:text-sm">
            <div class="flex items-center justify-between font-bold text-gray-800 mb-1">
              <span>{{ h.variant_name }} <span v-if="h.variant_sku" class="font-mono font-normal text-gray-400">({{ h.variant_sku }})</span></span>
              <span class="text-xs text-gray-400 font-normal">{{ formatDateTime(h.created_at) }}</span>
            </div>
            <div class="flex items-center space-x-2 my-1.5 text-xs">
              <span class="px-2 py-0.5 rounded bg-gray-200 font-semibold">{{ h.old_status }}</span>
              <span>➔</span>
              <span class="px-2 py-0.5 rounded font-bold"
                    :class="h.new_status === 'Approved' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'">
                {{ h.new_status }}
              </span>
            </div>
            <p v-if="h.notes" class="text-xs text-rose-700 bg-rose-50 p-2 rounded border border-rose-100 mt-2">
              📝 Catatan Penolakan: {{ h.notes }}
            </p>
            <p class="text-2xs text-gray-400 mt-2 pt-1 border-t border-gray-200/60">
              Reviewer: <strong>{{ h.reviewer_name }}</strong> <span v-if="h.reviewer_position">({{ h.reviewer_position }})</span> <span v-if="h.company_name">• {{ h.company_name }}</span>
            </p>
          </div>
          <div v-if="histories.length === 0" class="py-8 text-center text-gray-400 text-sm">
            Belum ada riwayat perubahan status approval.
          </div>
        </div>
        <div class="pt-4 border-t border-gray-100 text-right mt-4">
          <button @click="showHistoryModal = false" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white font-bold text-xs rounded-xl transition">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useSecureLink } from '../composables/useSecureLink';
import axios from 'axios';

const props = defineProps({
  promotionId: { type: [String, Number], required: true }
});

const emit = defineEmits(['updated']);

const {
  loading,
  secureLink,
  approvalHistories: histories,
  fetchPromotionLink,
  generatePromotionLink,
  regeneratePromotionLink,
  revokePromotionLink,
  fetchPromotionHistories,
} = useSecureLink();

const variants = ref([]);
const expiryInput = ref('');
const copied = ref(false);
const showHistoryModal = ref(false);
const selectedVariantIds = ref([]);
const batchLoading = ref(false);

const isAllSelected = computed(() => variants.value.length > 0 && selectedVariantIds.value.length === variants.value.length);

const toggleSelectAll = (e) => {
  selectedVariantIds.value = e.target.checked ? variants.value.map(v => v.id) : [];
};

const handleBatchApproval = async (action) => {
  let notes = undefined;
  if (action === 'reject_selected' || action === 'reject_all') {
    const input = prompt('Masukkan catatan penolakan untuk batch ini (wajib):');
    if (input === null) return; // User cancelled
    if (!input.trim()) {
      alert('Catatan penolakan tidak boleh kosong.');
      return;
    }
    notes = input.trim();
  } else {
    if (!confirm(`Apakah Anda yakin ingin melakukan proses [${action}]?`)) return;
  }

  batchLoading.value = true;
  try {
    const res = await axios.post(`/api/v1/admin/promotions/${props.promotionId}/batch-approval`, {
      action,
      variant_ids: selectedVariantIds.value,
      rejection_notes: notes
    });
    alert(res.data.message || 'Batch approval berhasil diproses.');
    selectedVariantIds.value = [];
    await initData();
    emit('updated');
  } catch (err) {
    alert('Gagal memproses batch approval: ' + (err.response?.data?.message || err.message));
  } finally {
    batchLoading.value = false;
  }
};

const loadVariants = async () => {
  try {
    const res = await axios.get(`/api/v1/admin/promotions/${props.promotionId}/variants`);
    variants.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to load variants:', err);
  }
};

const initData = async () => {
  await fetchPromotionLink(props.promotionId);
  await fetchPromotionHistories(props.promotionId);
  await loadVariants();
  if (secureLink.value?.expires_at) {
    // format for datetime-local input (YYYY-MM-DDTHH:MM)
    expiryInput.value = secureLink.value.expires_at.slice(0, 16);
  }
};

onMounted(() => initData());

const handleGenerate = async () => {
  await generatePromotionLink(props.promotionId);
  if (secureLink.value?.expires_at) expiryInput.value = secureLink.value.expires_at.slice(0, 16);
  emit('updated');
};

const handleRegenerate = async () => {
  if (!confirm('Apakah Anda yakin ingin meregenerasi token? Tautan lama tidak akan dapat diakses lagi oleh Brand.')) return;
  await regeneratePromotionLink(props.promotionId);
  emit('updated');
};

const handleRevoke = async () => {
  if (!confirm('Apakah Anda yakin ingin menonaktifkan tautan publik ini?')) return;
  await revokePromotionLink(props.promotionId);
  emit('updated');
};

const updateExpiry = async () => {
  if (!expiryInput.value) return;
  await generatePromotionLink(props.promotionId, expiryInput.value);
  alert('Masa berlaku tautan berhasil diperbarui.');
  emit('updated');
};

const copyToClipboard = async () => {
  if (!secureLink.value?.url) return;
  try {
    await navigator.clipboard.writeText(secureLink.value.url);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2500);
  } catch (err) {
    alert('Gagal menyalin tautan. Silakan salin secara manual dari kotak teks.');
  }
};

const formatDateTime = (dateStr) => {
  if (!dateStr) return '-';
  return new Date(dateStr).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const formatCurrency = (val) => {
  if (val === undefined || val === null) return 'Rp 0';
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
};

const getVariantBadgeClass = (status) => {
  switch (status) {
    case 'Approved': return 'bg-emerald-100 text-emerald-800';
    case 'Rejected': return 'bg-rose-100 text-rose-800';
    default: return 'bg-amber-100 text-amber-800';
  }
};
</script>
