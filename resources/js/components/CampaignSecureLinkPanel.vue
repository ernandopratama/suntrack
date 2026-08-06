<template>
  <div class="space-y-6">
    <!-- Secure Public Link Card -->
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-xs">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-gray-100 mb-4">
        <div>
          <h3 class="text-lg font-bold text-gray-900 flex items-center space-x-2">
            <span>&#x1F517;</span>
            <span>Secure Public Link (Brand Review)</span>
          </h3>
          <p class="text-xs text-gray-500 mt-0.5">Bagikan tautan ini kepada tim Brand untuk melihat informasi Campaign dan melakukan peninjauan tanpa perlu login.</p>
        </div>
        <div class="flex items-center space-x-2">
          <span v-if="secureLink" class="px-2.5 py-1 rounded-full text-xs font-bold"
                :class="secureLink.status === 'Active' ? 'bg-emerald-100 text-emerald-800' : (secureLink.status === 'Expired' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')">
            &#x25CF; Status: {{ secureLink.status }}
          </span>
          <span v-else class="px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-bold">
            &#x25CF; Belum Dibuat
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
            <span>{{ copied ? '&#x2713; Tersalin!' : '&#x1F4CB; Salin Tautan' }}</span>
          </button>
          <a :href="secureLink.url" target="_blank" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-lg transition whitespace-nowrap flex items-center justify-center space-x-1">
            <span>Buka &#x2197;</span>
          </a>
        </div>

        <!-- Audit Metadata -->
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
            <button v-if="secureLink.status !== 'Revoked'" @click="handleRevoke" :disabled="loading" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs rounded-lg transition">
              &#x1F6AB; Cabut Tautan (Revoke)
            </button>
          </div>
        </div>
      </div>

      <!-- No Link State -->
      <div v-else class="py-8 text-center bg-gray-50 rounded-xl border border-dashed border-gray-200">
        <p class="text-sm text-gray-500 mb-4">Campaign ini belum memiliki Secure Public Link untuk kolaborasi dengan Brand.</p>
        <button @click="handleGenerate" :disabled="loading" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs transition inline-flex items-center space-x-2">
          <span>&#x2728;</span>
          <span>Buat Secure Public Link Sekarang</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useSecureLink } from '../composables/useSecureLink';

const props = defineProps({
  campaignId: { type: [String, Number], required: true }
});

const {
  loading,
  secureLink,
  fetchCampaignLink,
  generateCampaignLink,
  revokeCampaignLink,
} = useSecureLink();

const expiryInput = ref('');
const copied = ref(false);

const initData = async () => {
  await fetchCampaignLink(props.campaignId);
  if (secureLink.value?.expires_at) {
    expiryInput.value = secureLink.value.expires_at.slice(0, 16);
  }
};

onMounted(() => initData());

const handleGenerate = async () => {
  await generateCampaignLink(props.campaignId);
  if (secureLink.value?.expires_at) expiryInput.value = secureLink.value.expires_at.slice(0, 16);
};

const handleRevoke = async () => {
  if (!confirm('Apakah Anda yakin ingin menonaktifkan tautan publik ini?')) return;
  await revokeCampaignLink(props.campaignId);
};

const updateExpiry = async () => {
  if (!expiryInput.value) return;
  await generateCampaignLink(props.campaignId, expiryInput.value);
  alert('Masa berlaku tautan berhasil diperbarui.');
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
</script>