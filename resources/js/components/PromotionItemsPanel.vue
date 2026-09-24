<template>
  <div class="space-y-5">
    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
      <div class="flex flex-col gap-4 border-b border-gray-100 px-5 py-5 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-start gap-3">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]">
            <i class="fa-solid fa-file-excel"></i>
          </div>
          <div>
            <h3 class="text-base font-extrabold text-gray-900">Data Produk Promosi</h3>
            <p class="mt-1 text-xs leading-5 text-gray-500">
              Gunakan template kosong. Upload baru akan mengganti seluruh data produk promosi ini.
            </p>
          </div>
        </div>

        <div v-if="$can('promotion.update')" class="flex flex-wrap gap-2">
          <button
            type="button"
            :disabled="loading"
            @click="downloadTemplate(promotionId)"
            class="inline-flex items-center gap-2 rounded-xl border border-[#4274D9]/30 bg-white px-4 py-2.5 text-xs font-extrabold text-[#293681] transition hover:bg-[#D0E7E6]/40 disabled:opacity-50"
          >
            <i class="fa-solid fa-download"></i>
            Download Template
          </button>
          <button
            type="button"
            :disabled="loading"
            @click="fileInput?.click()"
            class="inline-flex items-center gap-2 rounded-xl bg-[#293681] px-4 py-2.5 text-xs font-extrabold text-white transition hover:bg-[#4274D9] disabled:opacity-50"
          >
            <i class="fa-solid fa-upload"></i>
            Upload Excel
          </button>
          <input ref="fileInput" type="file" accept=".xlsx" class="hidden" @change="handleFile" />
        </div>
      </div>

      <div v-if="error" class="mx-5 mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 sm:mx-6">
        <template v-if="typeof error === 'object'">
          <p v-for="(messages, field) in error" :key="field">{{ Array.isArray(messages) ? messages[0] : messages }}</p>
        </template>
        <p v-else>{{ error }}</p>
      </div>

      <div v-if="preview" class="m-5 overflow-hidden rounded-2xl border border-[#95CCDD] bg-[#D0E7E6]/20 sm:m-6">
        <div class="flex flex-col gap-3 border-b border-[#95CCDD]/60 p-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm font-extrabold text-[#293681]">Preview {{ preview.total_rows }} produk</p>
            <p class="mt-1 text-xs text-gray-500">Periksa data sebelum mengganti daftar produk saat ini.</p>
          </div>
          <div class="flex gap-2">
            <button type="button" @click="cancelPreview" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600">Batal</button>
            <button
              type="button"
              :disabled="!preview.valid || loading"
              @click="confirmImport"
              class="rounded-lg bg-[#293681] px-4 py-2 text-xs font-extrabold text-white disabled:cursor-not-allowed disabled:bg-gray-300"
            >
              Konfirmasi Import
            </button>
          </div>
        </div>

        <div v-if="preview.errors?.length" class="border-b border-rose-200 bg-rose-50 p-4 text-xs text-rose-700">
          <p v-for="message in preview.errors" :key="message">{{ message }}</p>
        </div>

        <div class="max-h-72 overflow-auto">
          <table class="w-full min-w-[900px] text-left text-xs">
            <thead class="sticky top-0 bg-white text-[10px] uppercase tracking-wider text-gray-500">
              <tr>
                <th class="px-4 py-3">Baris</th><th class="px-4 py-3">Nama Produk</th><th class="px-4 py-3">Variasi</th>
                <th class="px-4 py-3 text-right">Harga Normal</th><th class="px-4 py-3 text-right">Harga Diskon</th>
                <th class="px-4 py-3 text-right">Diskon</th><th class="px-4 py-3 text-right">Stok</th><th class="px-4 py-3 text-right">Batas</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
              <tr v-for="row in preview.rows" :key="row.row_number" :class="row.valid ? '' : 'bg-rose-50'">
                <td class="px-4 py-3">{{ row.row_number }}</td>
                <td class="px-4 py-3 font-bold text-gray-800">{{ row.product_name || '—' }}</td>
                <td class="px-4 py-3">{{ row.variant_name || '—' }}</td>
                <td class="px-4 py-3 text-right">{{ formatCurrency(row.normal_price) }}</td>
                <td class="px-4 py-3 text-right">{{ formatCurrency(row.discount_price) }}</td>
                <td class="px-4 py-3 text-right">{{ formatPercentage(row.discount_percentage) }}</td>
                <td class="px-4 py-3 text-right">{{ row.promotion_stock ?? '—' }}</td>
                <td class="px-4 py-3 text-right">{{ row.purchase_limit ?? '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-if="loading && !items.length" class="p-10 text-center text-sm text-gray-500">
        <i class="fa-solid fa-spinner mr-2 animate-spin"></i>Memuat data...
      </div>

      <div v-else-if="!items.length && !preview" class="p-8">
        <EmptyState title="Belum ada produk promosi" description="Download template, isi data, lalu upload file XLSX." />
      </div>

      <div v-else-if="items.length">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[1100px] text-left text-sm">
          <thead class="border-b border-gray-200 bg-gray-50 text-[10px] font-extrabold uppercase tracking-wider text-gray-500">
            <tr>
              <th class="px-5 py-3.5">No.</th><th class="px-5 py-3.5">Nama Produk</th><th class="px-5 py-3.5">Variasi</th>
              <th class="px-5 py-3.5 text-right">Harga Normal</th><th class="px-5 py-3.5 text-right">Harga Diskon</th>
              <th class="px-5 py-3.5 text-right">Potongan</th><th class="px-5 py-3.5 text-right">Stok</th>
              <th class="px-5 py-3.5 text-right">Batas</th><th class="px-5 py-3.5 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(item, index) in paginatedItems" :key="item.id" class="hover:bg-[#D0E7E6]/15">
              <td class="px-5 py-4 text-gray-400">{{ pageStart + index }}</td>
              <td class="px-5 py-4 font-bold text-gray-900">{{ item.product_name }}</td>
              <td class="px-5 py-4 text-gray-600">{{ item.variant_name || '—' }}</td>
              <td class="px-5 py-4 text-right text-gray-600">{{ formatCurrency(item.normal_price) }}</td>
              <td class="px-5 py-4 text-right font-extrabold text-[#293681]">{{ formatCurrency(item.discount_price) }}</td>
              <td class="px-5 py-4 text-right">
                <div class="font-bold text-emerald-700">{{ formatPercentage(item.discount_percentage) }}</div>
                <div class="text-[10px] text-gray-400">{{ formatCurrency(item.discount_amount) }}</div>
              </td>
              <td class="px-5 py-4 text-right">{{ item.promotion_stock ?? '—' }}</td>
              <td class="px-5 py-4 text-right">{{ item.purchase_limit ?? '—' }}</td>
              <td class="px-5 py-4">
                <div v-if="$can('promotion.update')" class="flex justify-end gap-2">
                  <button type="button" title="Edit produk" @click="openEdit(item)" class="flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 text-blue-600 hover:bg-blue-50">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>
                  <button type="button" title="Hapus produk" @click="removeItem(item)" class="flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
          </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-200 bg-gray-50/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-xs font-medium text-gray-500">
            Menampilkan {{ pageStart }}–{{ pageEnd }} dari {{ items.length }} produk
          </p>

          <div class="flex flex-wrap items-center gap-3">
            <label class="flex items-center gap-2 text-xs font-semibold text-gray-600">
              Tampilkan
              <select
                v-model.number="pageSize"
                class="rounded-lg border border-gray-200 bg-white px-2.5 py-2 text-xs font-bold text-gray-700 outline-none focus:border-[#4274D9] focus:ring-4 focus:ring-[#4274D9]/10"
              >
                <option v-for="size in pageSizeOptions" :key="size" :value="size">
                  {{ size }}
                </option>
              </select>
              data
            </label>

            <div class="flex items-center gap-2">
              <button
                type="button"
                :disabled="currentPage === 1"
                aria-label="Halaman sebelumnya"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-[#4274D9]/40 hover:text-[#293681] disabled:cursor-not-allowed disabled:opacity-40"
                @click="currentPage -= 1"
              >
                <i class="fa-solid fa-chevron-left text-[10px]"></i>
              </button>

              <span class="min-w-24 text-center text-xs font-bold text-gray-600">
                Halaman {{ currentPage }} dari {{ totalPages }}
              </span>

              <button
                type="button"
                :disabled="currentPage === totalPages"
                aria-label="Halaman berikutnya"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-[#4274D9]/40 hover:text-[#293681] disabled:cursor-not-allowed disabled:opacity-40"
                @click="currentPage += 1"
              >
                <i class="fa-solid fa-chevron-right text-[10px]"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <ModalForm :is-open="editOpen" title="Edit Produk Promosi" @close="editOpen = false">
      <form id="promotion-item-edit-form" class="space-y-4" @submit.prevent="submitEdit">
        <div>
          <label class="mb-1.5 block text-xs font-bold text-gray-700">Nama Produk</label>
          <input v-model.trim="form.product_name" required maxlength="255" class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm outline-none focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30" />
        </div>
        <div>
          <label class="mb-1.5 block text-xs font-bold text-gray-700">Variasi</label>
          <input v-model.trim="form.variant_name" maxlength="255" class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm outline-none focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-700">Harga Normal</label>
            <input type="number" min="0" step="0.01" v-model.number="form.normal_price" required class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm outline-none focus:border-[#4274D9]" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-700">Harga Diskon</label>
            <input type="number" min="0" step="0.01" v-model.number="form.discount_price" required class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm outline-none focus:border-[#4274D9]" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-700">Stok Promosi</label>
            <input type="number" min="0" v-model.number="form.promotion_stock" class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm outline-none focus:border-[#4274D9]" />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-700">Batas Pembelian</label>
            <input type="number" min="0" v-model.number="form.purchase_limit" class="w-full rounded-xl border border-gray-200 px-3.5 py-2.5 text-sm outline-none focus:border-[#4274D9]" />
          </div>
        </div>
      </form>
      <template #footer>
        <button type="button" @click="editOpen = false" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-bold text-gray-600">Batal</button>
        <button type="submit" form="promotion-item-edit-form" :disabled="loading" class="rounded-xl bg-[#293681] px-5 py-2.5 text-sm font-extrabold text-white disabled:opacity-50">Simpan</button>
      </template>
    </ModalForm>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import EmptyState from './EmptyState.vue';
import ModalForm from './ModalForm.vue';
import { usePromotionItems } from '../composables/usePromotionItems';

const props = defineProps({ promotionId: { type: String, required: true } });
const emit = defineEmits(['updated']);
const fileInput = ref(null);
const selectedFile = ref(null);
const editOpen = ref(false);
const editingId = ref(null);
const pageSizeOptions = [10, 25, 50, 100];
const pageSize = ref(10);
const currentPage = ref(1);
const form = ref({ product_name: '', variant_name: '', normal_price: 0, discount_price: 0, promotion_stock: null, purchase_limit: null });

const { items, preview, loading, error, fetchItems, downloadTemplate, previewImport, importItems, updateItem, deleteItem } = usePromotionItems();

const totalPages = computed(() => Math.max(1, Math.ceil(items.value.length / pageSize.value)));
const pageStart = computed(() => items.value.length ? ((currentPage.value - 1) * pageSize.value) + 1 : 0);
const pageEnd = computed(() => Math.min(currentPage.value * pageSize.value, items.value.length));
const paginatedItems = computed(() => {
  const offset = (currentPage.value - 1) * pageSize.value;
  return items.value.slice(offset, offset + pageSize.value);
});

watch(pageSize, () => {
  currentPage.value = 1;
});

watch(() => items.value.length, () => {
  if (currentPage.value > totalPages.value) currentPage.value = totalPages.value;
});

onMounted(() => fetchItems(props.promotionId));

const handleFile = async (event) => {
  const [file] = event.target.files || [];
  if (!file) return;
  selectedFile.value = file;
  await previewImport(props.promotionId, file);
  event.target.value = '';
};

const cancelPreview = () => {
  preview.value = null;
  selectedFile.value = null;
};

const confirmImport = async () => {
  if (!selectedFile.value || !preview.value?.valid) return;
  if (items.value.length && !confirm('Upload ini akan mengganti seluruh data produk promosi. Lanjutkan?')) return;
  const result = await importItems(props.promotionId, selectedFile.value);
  if (result) {
    selectedFile.value = null;
    currentPage.value = 1;
    emit('updated');
  }
};

const openEdit = (item) => {
  editingId.value = item.id;
  form.value = {
    product_name: item.product_name,
    variant_name: item.variant_name || '',
    normal_price: Number(item.normal_price),
    discount_price: Number(item.discount_price),
    promotion_stock: item.promotion_stock,
    purchase_limit: item.purchase_limit,
  };
  editOpen.value = true;
};

const submitEdit = async () => {
  const result = await updateItem(props.promotionId, editingId.value, form.value);
  if (result) {
    editOpen.value = false;
    emit('updated');
  }
};

const removeItem = async (item) => {
  if (!confirm(`Hapus produk "${item.product_name}" dari promosi?`)) return;
  if (await deleteItem(props.promotionId, item.id)) emit('updated');
};

const formatCurrency = (value) => value === null || value === undefined
  ? '—'
  : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));

const formatPercentage = (value) => `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(value || 0))}%`;
</script>
