<template>
  <div class="space-y-6">

    <!-- =========================================================
         SECURE PUBLIC LINK
    ========================================================== -->
    <section
      class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
      <!-- Header -->
      <div
        class="flex flex-col gap-4 border-b border-gray-100 px-5 py-5 sm:px-6 md:flex-row md:items-center md:justify-between"
      >
        <div class="flex min-w-0 items-start gap-3">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]"
          >
            <i class="fa-solid fa-link text-sm"></i>
          </div>

          <div class="min-w-0">
            <h3 class="text-base font-extrabold tracking-tight text-gray-900">
              Secure Public Link
            </h3>

            <p class="mt-1 max-w-3xl text-xs leading-5 text-gray-500">
              Bagikan tautan ini kepada tim Brand untuk melakukan peninjauan
              harga dan approval per variant tanpa perlu login.
            </p>
          </div>
        </div>

        <!-- Status -->
        <div class="shrink-0">
          <span
            v-if="secureLink"
            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold"
            :class="{
              'bg-emerald-50 text-emerald-700':
                secureLink.status === 'Active',

              'bg-amber-50 text-amber-700':
                secureLink.status === 'Expired',

              'bg-rose-50 text-rose-700':
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

      <!-- Existing Link -->
      <div v-if="secureLink" class="space-y-5 p-5 sm:p-6">

        <!-- URL -->
        <div>
          <label
            class="mb-2 block text-xs font-bold uppercase tracking-wide text-gray-500"
          >
            Public Access URL
          </label>

          <div class="flex flex-col gap-2 sm:flex-row">
            <div class="relative min-w-0 flex-1">
              <div
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#4274D9]"
              >
                <i class="fa-solid fa-globe text-xs"></i>
              </div>

              <input
                type="text"
                readonly
                :value="secureLink.url"
                class="block w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-9 pr-3.5 font-mono text-xs text-gray-700 outline-none transition focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
              />
            </div>

            <button
              @click="copyToClipboard"
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#4274D9] px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all hover:bg-[#293681] hover:shadow-md"
            >
              <i
                :class="copied ? 'fa-solid fa-check' : 'fa-regular fa-copy'"
                class="text-[11px]"
              ></i>

              <span>
                {{ copied ? 'Tersalin!' : 'Salin Tautan' }}
              </span>
            </button>

            <a
              :href="secureLink.url"
              target="_blank"
              class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 transition-all hover:border-[#95CCDD] hover:bg-[#D0E7E6]/30 hover:text-[#293681]"
            >
              <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
              <span>Buka</span>
            </a>
          </div>
        </div>

        <!-- Metadata -->
        <div
          class="grid grid-cols-1 overflow-hidden rounded-xl border border-[#D0E7E6] bg-[#D0E7E6]/30 sm:grid-cols-2 lg:grid-cols-4"
        >
          <div class="border-b border-[#D0E7E6] p-4 sm:border-r lg:border-b-0">
            <span
              class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-gray-400"
            >
              Tanggal Dibuat
            </span>

            <span class="text-sm font-bold text-[#293681]">
              {{ formatDateTime(secureLink.created_at) }}
            </span>
          </div>

          <div
            class="border-b border-[#D0E7E6] p-4 lg:border-b-0 lg:border-r"
          >
            <span
              class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-gray-400"
            >
              Dibuat Oleh
            </span>

            <span class="text-sm font-bold text-gray-800">
              {{ secureLink.created_by_name || 'Admin' }}
            </span>
          </div>

          <div
            class="border-b border-[#D0E7E6] p-4 sm:border-r sm:border-b-0"
          >
            <span
              class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-gray-400"
            >
              Terakhir Diakses
            </span>

            <span class="text-sm font-bold text-gray-800">
              {{
                secureLink.last_accessed_at
                  ? formatDateTime(secureLink.last_accessed_at)
                  : 'Belum pernah diakses'
              }}
            </span>
          </div>

          <div class="p-4">
            <span
              class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-gray-400"
            >
              Total Akses
            </span>

            <span class="text-sm font-extrabold text-[#4274D9]">
              {{ secureLink.view_count }} kali
            </span>
          </div>
        </div>

        <!-- Controls -->
        <div
          class="flex flex-col gap-4 rounded-xl border border-gray-100 bg-gray-50/70 p-4 lg:flex-row lg:items-end lg:justify-between"
        >
          <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
            <div>
              <label
                class="mb-1.5 block text-[10px] font-bold uppercase tracking-wide text-gray-400"
              >
                Masa Berlaku
              </label>

              <input
                v-model="expiryInput"
                type="datetime-local"
                class="rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs font-semibold text-gray-700 outline-none transition focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
              />
            </div>

            <button
              @click="updateExpiry"
              :disabled="loading"
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#293681] px-4 py-2.5 text-xs font-bold text-white transition-all hover:bg-[#4274D9] disabled:cursor-not-allowed disabled:opacity-50"
            >
              <i class="fa-regular fa-clock text-[11px]"></i>
              Simpan
            </button>
          </div>

          <div class="flex flex-wrap gap-2">
            <button
              @click="handleRegenerate"
              :disabled="loading"
              type="button"
              class="inline-flex items-center gap-2 rounded-xl border border-[#95CCDD] bg-white px-3.5 py-2.5 text-xs font-bold text-[#293681] transition-all hover:bg-[#D0E7E6]/50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <i class="fa-solid fa-rotate text-[10px]"></i>
              Regenerate Token
            </button>

            <button
              v-if="secureLink.status !== 'Revoked'"
              @click="handleRevoke"
              :disabled="loading"
              type="button"
              class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-white px-3.5 py-2.5 text-xs font-bold text-rose-600 transition-all hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <i class="fa-solid fa-ban text-[10px]"></i>
              Cabut Tautan
            </button>
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div v-else class="p-5 sm:p-6">
        <div
          class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-[#95CCDD] bg-[#D0E7E6]/20 px-6 py-10 text-center"
        >
          <div
            class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#D0E7E6] text-[#293681]"
          >
            <i class="fa-solid fa-link text-base"></i>
          </div>

          <h4 class="text-sm font-extrabold text-gray-900">
            Secure Public Link belum tersedia
          </h4>

          <p class="mt-1.5 max-w-md text-xs leading-5 text-gray-500">
            Buat tautan publik untuk memberikan akses review kepada tim Brand.
          </p>

          <button
            @click="handleGenerate"
            :disabled="loading"
            type="button"
            class="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#4274D9] px-5 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all hover:bg-[#293681] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
          >
            <i class="fa-solid fa-plus text-[10px]"></i>
            Buat Secure Public Link
          </button>
        </div>
      </div>
    </section>


    <!-- =========================================================
         VARIANT APPROVAL
    ========================================================== -->
    <section
      class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
      <!-- Header -->
      <div
        class="flex flex-col gap-4 border-b border-gray-100 px-5 py-5 sm:px-6 md:flex-row md:items-center md:justify-between"
      >
        <div class="flex items-start gap-3">
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#95CCDD]/40 text-[#293681]"
          >
            <i class="fa-solid fa-list-check text-sm"></i>
          </div>

          <div>
            <h3 class="text-base font-extrabold tracking-tight text-gray-900">
              Variant Approval
            </h3>

            <p class="mt-1 text-xs leading-5 text-gray-500">
              Pantau dan kelola status approval setiap variant melalui batch
              action.
            </p>
          </div>
        </div>

        <button
          @click="showHistoryModal = true"
          type="button"
          class="inline-flex items-center justify-center gap-2 self-start rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-xs font-bold text-gray-700 transition-all hover:border-[#95CCDD] hover:bg-[#D0E7E6]/30 hover:text-[#293681] md:self-auto"
        >
          <i class="fa-solid fa-clock-rotate-left text-[10px]"></i>
          <span>Riwayat Approval</span>

          <span
            class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-extrabold text-gray-500"
          >
            {{ histories.length }}
          </span>
        </button>
      </div>

      <div class="p-5 sm:p-6">

        <!-- Batch Toolbar -->
        <div
          v-if="variants.length > 0"
          class="mb-5 rounded-2xl border border-[#95CCDD]/60 bg-[#D0E7E6]/30 p-4"
        >
          <div
            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
          >
            <div>
              <div class="flex items-center gap-2">
                <span
                  class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-[#4274D9] shadow-sm"
                >
                  <i class="fa-solid fa-check-double text-xs"></i>
                </span>

                <div>
                  <p class="text-xs font-extrabold text-[#293681]">
                    Batch Selection
                  </p>

                  <p class="mt-0.5 text-[11px] text-gray-500">
                    {{ selectedVariantIds.length }} dari
                    {{ variants.length }} variant terpilih
                  </p>
                </div>
              </div>

              <button
                v-if="selectedVariantIds.length > 0"
                @click="selectedVariantIds = []"
                type="button"
                class="mt-2 text-[11px] font-bold text-[#4274D9] hover:text-[#293681] hover:underline"
              >
                Reset pilihan
              </button>
            </div>

            <div class="flex flex-wrap gap-2">
              <button
                @click="handleBatchApproval('approve_selected')"
                :disabled="
                  selectedVariantIds.length === 0 || batchLoading
                "
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2.5 text-xs font-extrabold text-white transition-all hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-gray-300"
              >
                <i class="fa-solid fa-check text-[10px]"></i>
                Setujui Terpilih
                <span
                  v-if="selectedVariantIds.length > 0"
                  class="rounded-md bg-white/15 px-1.5 py-0.5"
                >
                  {{ selectedVariantIds.length }}
                </span>
              </button>

              <button
                @click="handleBatchApproval('reject_selected')"
                :disabled="
                  selectedVariantIds.length === 0 || batchLoading
                "
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-3.5 py-2.5 text-xs font-extrabold text-white transition-all hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-gray-300"
              >
                <i class="fa-solid fa-xmark text-[10px]"></i>
                Tolak Terpilih
                <span
                  v-if="selectedVariantIds.length > 0"
                  class="rounded-md bg-white/15 px-1.5 py-0.5"
                >
                  {{ selectedVariantIds.length }}
                </span>
              </button>

              <div class="hidden h-8 w-px bg-[#95CCDD] sm:block"></div>

              <button
                @click="handleBatchApproval('approve_all')"
                :disabled="batchLoading"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-white px-3.5 py-2.5 text-xs font-extrabold text-emerald-700 transition-all hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <i class="fa-solid fa-check-double text-[10px]"></i>
                Setujui Semua
              </button>

              <button
                @click="handleBatchApproval('reject_all')"
                :disabled="batchLoading"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-white px-3.5 py-2.5 text-xs font-extrabold text-rose-700 transition-all hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
              >
                <i class="fa-solid fa-ban text-[10px]"></i>
                Tolak Semua
              </button>
            </div>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-2xl border border-gray-200">
          <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-left text-sm">
              <thead>
                <tr
                  class="border-b border-gray-200 bg-gray-50 text-[10px] font-extrabold uppercase tracking-wider text-gray-400"
                >
                  <th class="w-12 px-3 py-3.5 text-center">
                    <input
                      type="checkbox"
                      @change="toggleSelectAll"
                      :checked="isAllSelected"
                      class="h-4 w-4 rounded border-gray-300 text-[#4274D9] focus:ring-[#4274D9]"
                    />
                  </th>

                  <th class="px-4 py-3.5">
                    Variant SKU / Nama
                  </th>

                  <th class="px-4 py-3.5">
                    Harga Promo
                  </th>

                  <th class="px-4 py-3.5 text-center">
                    Status Approval
                  </th>

                  <th class="px-4 py-3.5">
                    Catatan Brand
                  </th>
                </tr>
              </thead>

              <tbody class="divide-y divide-gray-100 bg-white">
                <tr
                  v-for="variant in variants"
                  :key="variant.id"
                  class="transition-colors hover:bg-[#D0E7E6]/20"
                  :class="{
                    'bg-[#95CCDD]/10':
                      selectedVariantIds.includes(variant.id)
                  }"
                >
                  <td class="px-3 py-4 text-center">
                    <input
                      type="checkbox"
                      :value="variant.id"
                      v-model="selectedVariantIds"
                      class="h-4 w-4 rounded border-gray-300 text-[#4274D9] focus:ring-[#4274D9]"
                    />
                  </td>

                  <td class="px-4 py-4">
                    <span
                      class="block font-bold text-gray-900"
                    >
                      {{ variant.product_name || 'Product' }}
                      <span class="font-normal text-gray-400">—</span>
                      {{ variant.name }}
                    </span>

                    <span
                      class="mt-1 block font-mono text-[11px] text-gray-400"
                    >
                      {{ variant.sku }}
                    </span>
                  </td>

                  <td class="px-4 py-4">
                    <span
                      class="font-mono text-sm font-extrabold text-[#4274D9]"
                    >
                      {{ formatCurrency(variant.campaign_price) }}
                    </span>
                  </td>

                  <td class="px-4 py-4 text-center">
                    <span
                      class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                      :class="{
                        'bg-emerald-50 text-emerald-700':
                          variant.approval_status === 'Approved',

                        'bg-rose-50 text-rose-700':
                          variant.approval_status === 'Rejected',

                        'bg-amber-50 text-amber-700':
                          !variant.approval_status ||
                          variant.approval_status === 'Pending'
                      }"
                    >
                      <span
                        class="h-1.5 w-1.5 rounded-full bg-current"
                      ></span>

                      {{ variant.approval_status || 'Pending' }}
                    </span>
                  </td>

                  <td class="px-4 py-4">
                    <span
                      v-if="variant.rejection_notes"
                      class="inline-flex max-w-sm items-start gap-2 rounded-lg border border-rose-100 bg-rose-50 px-2.5 py-2 text-xs font-semibold text-rose-600"
                    >
                      <i class="fa-solid fa-triangle-exclamation mt-0.5 text-[10px]"></i>
                      <span>{{ variant.rejection_notes }}</span>
                    </span>

                    <span
                      v-else
                      class="text-xs italic text-gray-400"
                    >
                      —
                    </span>
                  </td>
                </tr>

                <tr v-if="variants.length === 0">
                  <td
                    colspan="5"
                    class="px-6 py-12 text-center"
                  >
                    <div
                      class="mx-auto flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 text-gray-400"
                    >
                      <i class="fa-solid fa-box-open text-sm"></i>
                    </div>

                    <p class="mt-3 text-sm font-bold text-gray-700">
                      Belum ada variant
                    </p>

                    <p class="mt-1 text-xs text-gray-400">
                      Belum ada variant produk pada promosi ini.
                    </p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>


    <!-- =========================================================
         APPROVAL HISTORY MODAL
    ========================================================== -->
    <div
      v-if="showHistoryModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-[#293681]/40 p-4 backdrop-blur-sm"
    >
      <div
        class="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl"
      >

        <!-- Modal Header -->
        <div
          class="flex items-center justify-between border-b border-gray-100 px-5 py-4 sm:px-6"
        >
          <div class="flex items-center gap-3">
            <div
              class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]"
            >
              <i class="fa-solid fa-clock-rotate-left text-xs"></i>
            </div>

            <div>
              <h3 class="text-sm font-extrabold text-gray-900">
                Jejak Audit Approval
              </h3>

              <p class="mt-0.5 text-[11px] text-gray-400">
                Immutable Audit Trail
              </p>
            </div>
          </div>

          <button
            @click="showHistoryModal = false"
            type="button"
            class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
          >
            <i class="fa-solid fa-xmark text-sm"></i>
          </button>
        </div>

        <!-- History -->
        <div class="flex-grow overflow-y-auto p-5 sm:p-6">
          <div class="space-y-3">
            <div
              v-for="h in histories"
              :key="h.id"
              class="rounded-xl border border-gray-200 bg-gray-50/70 p-4 transition-colors hover:border-[#95CCDD] hover:bg-[#D0E7E6]/20"
            >
              <div
                class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
              >
                <div class="min-w-0">
                  <p class="text-sm font-extrabold text-gray-800">
                    {{ h.variant_name }}

                    <span
                      v-if="h.variant_sku"
                      class="ml-1 font-mono text-[10px] font-normal text-gray-400"
                    >
                      ({{ h.variant_sku }})
                    </span>
                  </p>
                </div>

                <span class="shrink-0 text-[10px] text-gray-400">
                  {{ formatDateTime(h.created_at) }}
                </span>
              </div>

              <!-- Status Transition -->
              <div class="mt-3 flex items-center gap-2">
                <span
                  class="rounded-lg bg-gray-100 px-2.5 py-1 text-[11px] font-bold text-gray-600"
                >
                  {{ h.old_status }}
                </span>

                <i class="fa-solid fa-arrow-right text-[9px] text-gray-300"></i>

                <span
                  class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-bold"
                  :class="
                    h.new_status === 'Approved'
                      ? 'bg-emerald-50 text-emerald-700'
                      : 'bg-rose-50 text-rose-700'
                  "
                >
                  <span
                    class="h-1.5 w-1.5 rounded-full bg-current"
                  ></span>

                  {{ h.new_status }}
                </span>
              </div>

              <!-- Notes -->
              <p
                v-if="h.notes"
                class="mt-3 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs leading-5 text-rose-700"
              >
                <span class="font-bold">Catatan Penolakan:</span>
                {{ h.notes }}
              </p>

              <!-- Reviewer -->
              <div
                class="mt-3 border-t border-gray-200/70 pt-2.5 text-[10px] text-gray-400"
              >
                Reviewer:
                <strong class="text-gray-600">
                  {{ h.reviewer_name }}
                </strong>

                <span v-if="h.reviewer_position">
                  ({{ h.reviewer_position }})
                </span>

                <span v-if="h.company_name">
                  • {{ h.company_name }}
                </span>
              </div>
            </div>

            <!-- Empty History -->
            <div
              v-if="histories.length === 0"
              class="py-12 text-center"
            >
              <div
                class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 text-gray-400"
              >
                <i class="fa-solid fa-clock-rotate-left text-sm"></i>
              </div>

              <p class="mt-3 text-sm font-bold text-gray-700">
                Belum ada riwayat
              </p>

              <p class="mt-1 text-xs text-gray-400">
                Belum ada riwayat perubahan status approval.
              </p>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div
          class="border-t border-gray-100 bg-gray-50/70 px-5 py-4 text-right sm:px-6"
        >
          <button
            @click="showHistoryModal = false"
            type="button"
            class="inline-flex items-center justify-center rounded-xl bg-[#293681] px-5 py-2.5 text-xs font-extrabold text-white transition-all hover:bg-[#4274D9]"
          >
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
