<template>
  <div class="space-y-5">
    <!-- Secure Public Link Card -->
    <div
      class="overflow-hidden rounded-2xl border border-[#D0E7E6] bg-white shadow-sm"
    >
      <!-- Header -->
      <div
        class="border-b border-[#D0E7E6] bg-gradient-to-r from-[#D0E7E6]/40 via-white to-[#95CCDD]/10 px-5 py-5 sm:px-6"
      >
        <div
          class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
        >
          <div class="flex items-start gap-3">
            <!-- Icon -->
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#293681] text-white shadow-sm"
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
                  d="M13.828 10.172a4 4 0 015.656 5.656l-2.121 2.121a4 4 0 01-5.657-5.657m-1.414 1.414a4 4 0 01-5.657-5.657l2.121-2.121a4 4 0 015.657 5.657"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.8"
                  d="M8.464 15.536l7.072-7.072"
                />
              </svg>
            </div>

            <div>
              <div class="flex flex-wrap items-center gap-2">
                <h3
                  class="text-base font-extrabold tracking-tight text-[#293681]"
                >
                  Secure Public Link
                </h3>

                <span
                  class="rounded-full bg-[#95CCDD]/30 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-[#293681]"
                >
                  Brand Review
                </span>
              </div>

              <p
                class="mt-1 max-w-2xl text-xs leading-5 text-gray-500"
              >
                Bagikan tautan kepada tim Brand untuk melihat informasi
                campaign dan melakukan peninjauan tanpa perlu login.
              </p>
            </div>
          </div>

          <!-- Status -->
          <div class="shrink-0">
            <span
              v-if="secureLink"
              class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold"
              :class="{
                'bg-emerald-100 text-emerald-700':
                  secureLink.status === 'Active',
                'bg-amber-100 text-amber-700':
                  secureLink.status === 'Expired',
                'bg-rose-100 text-rose-700':
                  secureLink.status === 'Revoked'
              }"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
              {{ secureLink.status }}
            </span>

            <span
              v-else
              class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-500"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
              Belum Dibuat
            </span>
          </div>
        </div>
      </div>

      <!-- Existing Link -->
      <div v-if="secureLink" class="p-5 sm:p-6">
        <div class="space-y-5">
          <!-- URL Section -->
          <div>
            <div class="mb-2 flex items-center justify-between gap-3">
              <div>
                <h4 class="text-sm font-bold text-[#293681]">
                  Public Access Link
                </h4>

                <p class="mt-0.5 text-[11px] text-gray-400">
                  Gunakan tautan berikut untuk memberikan akses review kepada
                  tim Brand.
                </p>
              </div>
            </div>

            <div
              class="flex flex-col gap-2 rounded-xl border border-[#D0E7E6] bg-[#D0E7E6]/20 p-2 sm:flex-row"
            >
              <div class="relative min-w-0 flex-1">
                <div
                  class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-[#4274D9]"
                >
                  <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.8"
                      d="M13.828 10.172a4 4 0 015.656 5.656l-2.121 2.121a4 4 0 01-5.657-5.657m-1.414 1.414a4 4 0 01-5.657-5.657l2.121-2.121a4 4 0 015.657 5.657"
                    />
                  </svg>
                </div>

                <input
                  type="text"
                  readonly
                  :value="secureLink.url"
                  class="block w-full rounded-lg border border-[#D0E7E6] bg-white py-2.5 pl-9 pr-3 font-mono text-xs text-gray-700 shadow-sm outline-none transition-all focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
                />
              </div>

              <button
                type="button"
                @click="copyToClipboard"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-[#4274D9] px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#293681] hover:shadow-md focus:outline-none focus:ring-4 focus:ring-[#4274D9]/20"
              >
                <svg
                  v-if="!copied"
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <rect
                    x="9"
                    y="9"
                    width="11"
                    height="11"
                    rx="2"
                    stroke-width="1.8"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-width="1.8"
                    d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"
                  />
                </svg>

                <svg
                  v-else
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7"
                  />
                </svg>

                <span>
                  {{ copied ? 'Tersalin' : 'Salin Tautan' }}
                </span>
              </button>

              <a
                :href="secureLink.url"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-[#D0E7E6] bg-white px-4 py-2.5 text-xs font-extrabold text-[#293681] shadow-sm transition-all duration-200 hover:border-[#95CCDD] hover:bg-[#D0E7E6]/30 focus:outline-none focus:ring-4 focus:ring-[#4274D9]/10"
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M14 3h7v7m0-7L10 14"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"
                  />
                </svg>

                <span>Buka</span>
              </a>
            </div>
          </div>

          <!-- Audit Metadata -->
          <div>
            <div class="mb-3">
              <h4 class="text-sm font-bold text-[#293681]">
                Link Information
              </h4>
            </div>

            <div
              class="grid grid-cols-1 overflow-hidden rounded-xl border border-[#D0E7E6] sm:grid-cols-2 lg:grid-cols-4"
            >
              <!-- Created -->
              <div
                class="border-b border-[#D0E7E6] bg-[#D0E7E6]/15 p-4 sm:border-r lg:border-b-0"
              >
                <div class="mb-2 flex items-center gap-2">
                  <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#95CCDD]/40 text-[#293681]"
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
                        d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"
                      />
                    </svg>
                  </div>

                  <span
                    class="text-[10px] font-bold uppercase tracking-wider text-gray-400"
                  >
                    Dibuat
                  </span>
                </div>

                <p class="text-xs font-bold text-gray-800">
                  {{ formatDateTime(secureLink.created_at) }}
                </p>
              </div>

              <!-- Created By -->
              <div
                class="border-b border-[#D0E7E6] bg-white p-4 lg:border-b-0 lg:border-r"
              >
                <div class="mb-2 flex items-center gap-2">
                  <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#95CCDD]/40 text-[#293681]"
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
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM4 21a8 8 0 0116 0"
                      />
                    </svg>
                  </div>

                  <span
                    class="text-[10px] font-bold uppercase tracking-wider text-gray-400"
                  >
                    Dibuat Oleh
                  </span>
                </div>

                <p class="text-xs font-bold text-gray-800">
                  {{ secureLink.created_by_name || 'Admin' }}
                </p>
              </div>

              <!-- Last Access -->
              <div
                class="border-b border-[#D0E7E6] bg-white p-4 sm:border-r lg:border-b-0"
              >
                <div class="mb-2 flex items-center gap-2">
                  <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#95CCDD]/40 text-[#293681]"
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
                        d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"
                      />
                    </svg>
                  </div>

                  <span
                    class="text-[10px] font-bold uppercase tracking-wider text-gray-400"
                  >
                    Akses Terakhir
                  </span>
                </div>

                <p class="text-xs font-bold text-gray-800">
                  {{
                    secureLink.last_accessed_at
                      ? formatDateTime(secureLink.last_accessed_at)
                      : 'Belum pernah diakses'
                  }}
                </p>
              </div>

              <!-- View Count -->
              <div class="bg-white p-4">
                <div class="mb-2 flex items-center gap-2">
                  <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#4274D9]/10 text-[#4274D9]"
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
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                      />
                      <circle
                        cx="12"
                        cy="12"
                        r="3"
                        stroke-width="1.8"
                      />
                    </svg>
                  </div>

                  <span
                    class="text-[10px] font-bold uppercase tracking-wider text-gray-400"
                  >
                    Total Akses
                  </span>
                </div>

                <p class="text-sm font-extrabold text-[#4274D9]">
                  {{ secureLink.view_count }} kali
                </p>
              </div>
            </div>
          </div>

          <!-- Controls -->
          <div
            v-if="$can('campaign.update')"
            class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-gray-50/70 p-4 lg:flex-row lg:items-end lg:justify-between"
          >
            <!-- Expiry -->
            <div class="w-full lg:max-w-xl">
              <label
                class="mb-2 block text-xs font-bold text-[#293681]"
              >
                Masa Berlaku
              </label>

              <div class="flex flex-col gap-2 sm:flex-row">
                <input
                  v-model="expiryInput"
                  type="datetime-local"
                  class="block w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-xs font-medium text-gray-700 shadow-sm outline-none transition-all focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10 sm:flex-1"
                />

                <button
                  type="button"
                  @click="updateExpiry"
                  :disabled="loading"
                  class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#293681] px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#4274D9] disabled:cursor-not-allowed disabled:opacity-50"
                >
                  <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.8"
                      d="M5 13l4 4L19 7"
                    />
                  </svg>

                  <span>
                    {{ loading ? 'Menyimpan...' : 'Simpan' }}
                  </span>
                </button>
              </div>
            </div>

            <!-- Revoke -->
            <div>
              <button
                v-if="secureLink.status !== 'Revoked'"
                type="button"
                @click="handleRevoke"
                :disabled="loading"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-rose-200 bg-white px-4 py-2.5 text-xs font-extrabold text-rose-600 transition-all duration-200 hover:border-rose-300 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50 lg:w-auto"
              >
                <svg
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M18.364 5.636L5.636 18.364M5.636 5.636l12.728 12.728"
                  />
                </svg>

                <span>Cabut Tautan</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- No Link State -->
      <div v-else class="p-5 sm:p-6">
        <div
          class="flex flex-col items-center justify-center rounded-xl border border-dashed border-[#95CCDD] bg-[#D0E7E6]/20 px-6 py-12 text-center"
        >
          <div
            class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#95CCDD]/30 text-[#293681]"
          >
            <svg
              class="h-7 w-7"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M13.828 10.172a4 4 0 015.656 5.656l-2.121 2.121a4 4 0 01-5.657-5.657m-1.414 1.414a4 4 0 01-5.657-5.657l2.121-2.121a4 4 0 015.657 5.657"
              />
            </svg>
          </div>

          <h4 class="text-sm font-extrabold text-[#293681]">
            Secure Public Link Belum Tersedia
          </h4>

          <p
            class="mt-1 max-w-md text-xs leading-5 text-gray-500"
          >
            Campaign ini belum memiliki secure public link. Buat tautan untuk
            memberikan akses review kepada tim Brand.
          </p>

          <button
            v-if="$can('campaign.update')"
            type="button"
            @click="handleGenerate"
            :disabled="loading"
            class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-[#4274D9] px-5 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#293681] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
          >
            <svg
              class="h-4 w-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M12 3v18m9-9H3"
              />
            </svg>

            <span>
              {{ loading ? 'Membuat...' : 'Buat Secure Public Link' }}
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

  if (secureLink.value?.expires_at) {
    expiryInput.value = secureLink.value.expires_at.slice(0, 16);
  }
};

const handleRevoke = async () => {
  if (!confirm('Apakah Anda yakin ingin menonaktifkan tautan publik ini?')) {
    return;
  }

  await revokeCampaignLink(props.campaignId);
};

const updateExpiry = async () => {
  if (!expiryInput.value) return;

  await generateCampaignLink(
    props.campaignId,
    expiryInput.value
  );

  alert('Masa berlaku tautan berhasil diperbarui.');
};

const copyToClipboard = async () => {
  if (!secureLink.value?.url) return;

  try {
    await navigator.clipboard.writeText(secureLink.value.url);

    copied.value = true;

    setTimeout(() => {
      copied.value = false;
    }, 2500);
  } catch (err) {
    alert(
      'Gagal menyalin tautan. Silakan salin secara manual dari kotak teks.'
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
</script>
