<template>
  <div>
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Products</h1>
        <p class="mt-1 text-sm text-gray-500">Manage your product catalog and variants.</p>
      </div>
      <div class="flex items-center space-x-3">
        <button @click="openImportModal" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
          <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
          Import Excel
        </button>
        <button @click="openCreate" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
          <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
          New Product
        </button>
      </div>
    </div>

    <!-- Errors -->
    <div v-if="pageError" class="mb-4 bg-red-50 border border-red-200 p-4 rounded-md">
      <div class="flex">
        <div class="flex-shrink-0"><svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
        <div class="ml-3"><p class="text-sm text-red-700">{{ pageError }}</p></div>
      </div>
    </div>

    <DataTable :columns="columns" :data="products" :loading="loading" @search="handleSearch">
      <template #actions>
        <select v-model="filters.brand_id" @change="handleBrandFilter" class="block pl-3 pr-10 py-2 text-base border-gray-300 sm:text-sm rounded-md">
            <option value="">All Brands</option>
            <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
          <select v-model="filters.status" @change="fetchData" class="block pl-3 pr-10 py-2 text-base border-gray-300 sm:text-sm rounded-md">
            <option value="">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
          <button v-if="selectedIds.length > 0" @click="confirmBulkDelete" class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 transition-colors">
            Delete Selected ({{ selectedIds.length }})
          </button>
      </template>
      
      <template #head-checkbox>
        <input type="checkbox" :checked="selectedIds.length === products.length && products.length > 0" @change="toggleSelectAll" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
      </template>
      <template #cell-checkbox="{ row }">
        <input type="checkbox" :checked="selectedIds.includes(row.id)" @change="toggleSelect(row.id)" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
      </template>
      <template #cell-no="{ idx }">
        <span class="text-sm text-gray-500">{{ pagination.current_page > 1 ? (pagination.current_page - 1) * pagination.per_page + idx + 1 : idx + 1 }}</span>
      </template>
      <template #cell-code="{ row }">
        <span class="font-mono text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-1 rounded">{{ row.code }}</span>
      </template>
      <template #cell-name="{ row }">
        <div class="font-medium text-gray-900">{{ row.name }}</div>
        <div class="text-xs text-gray-400">{{ row.sku || 'No SKU' }}</div>
      </template>
      <template #cell-brand_name="{ row }">
        <span class="text-sm text-gray-700">{{ row.brand?.name || "\u2014" }}</span>
      </template>
      <template #cell-current_price="{ row }">
        <span class="text-sm font-medium text-gray-900">{{ row.current_price ? formatCurrency(row.current_price) : '—' }}</span>
      </template>
      <template #cell-variants_count="{ row }">
        <span class="text-sm text-gray-700">{{ row.variants_count }} variant{{ row.variants_count !== 1 ? 's' : '' }}</span>
      </template>
      <template #cell-status="{ row }">
        <StatusBadge :status="row.status" />
      </template>
      <template #cell-actions="{ row }">
        <div class="flex space-x-3 text-sm">
          <router-link :to="`/products/${row.id}`" class="text-blue-600 hover:text-blue-900 font-medium transition-colors">View</router-link>
          <button @click="openEdit(row)" class="text-gray-600 hover:text-gray-900 font-medium transition-colors">Edit</button>
          <button @click="confirmDelete(row)" class="text-red-600 hover:text-red-900 font-medium transition-colors">Delete</button>
        </div>
      </template>
      <template #pagination>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
          <p class="text-sm text-gray-700">Page <span class="font-medium">{{ pagination.current_page }}</span> of <span class="font-medium">{{ pagination.last_page }}</span> ({{ pagination.total }} results)</p>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
            <button @click="changePage(-1)" :disabled="pagination.current_page === 1" class="px-3 py-2 rounded-l-md border border-gray-300 bg-white text-sm text-gray-500 hover:bg-gray-50 disabled:opacity-40">&larr; Prev</button>
            <button @click="changePage(1)" :disabled="pagination.current_page === pagination.last_page" class="px-3 py-2 rounded-r-md border border-gray-300 bg-white text-sm text-gray-500 hover:bg-gray-50 disabled:opacity-40">Next &rarr;</button>
          </nav>
        </div>
      </template>
    </DataTable>

    <!-- Create/Edit Modal -->
    <ModalForm :is-open="isModalOpen" :title="selectedProduct ? 'Edit Product' : 'Create Product'" @close="isModalOpen = false">
      <form id="product-form" @submit.prevent="submitForm" class="space-y-4 mt-2">
        <div v-if="formError" class="bg-red-50 border border-red-200 p-3 rounded-md">
          <p class="text-sm text-red-700">{{ formError }}</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Product Code <span class="text-red-500">*</span></label>
            <input type="text" v-model="form.code" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">SKU</label>
            <input type="text" v-model="form.sku" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Product Name <span class="text-red-500">*</span></label>
          <input type="text" v-model="form.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Description</label>
          <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Current Price (Selling Price)</label>
          <input type="number" min="0" step="0.01" v-model="form.current_price" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" placeholder="e.g. 150000">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Brand <span class="text-red-500">*</span></label>
          <select v-model="form.brand_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            <option value="">-- Select Brand --</option>
            <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </form>
      <template #footer>
        <div class="mt-5 sm:flex sm:flex-row-reverse w-full">
          <button type="submit" form="product-form" :disabled="submitting" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 sm:ml-3 sm:w-auto transition-colors">{{ submitting ? 'Saving...' : (selectedProduct ? 'Update Product' : 'Save Product') }}</button>
          <button type="button" @click="isModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 sm:mt-0 sm:w-auto transition-colors">Cancel</button>
        </div>
      </template>
    </ModalForm>

    <!-- Import Modal -->
    <ModalForm :is-open="isImportModalOpen" title="Import Products from Excel" @close="closeImportModal">
      <form id="import-form" @submit.prevent="submitImport" class="space-y-5 mt-2">
        <div v-if="importError" class="bg-red-50 border border-red-200 p-3 rounded-md">
          <p class="text-sm text-red-700">{{ importError }}</p>
        </div>
        <div v-if="importResult" class="bg-green-50 border border-green-200 p-4 rounded-md space-y-1">
          <p class="text-sm font-medium text-green-800">Import Completed!</p>
          <p class="text-sm text-green-700">{{ importResult.imported }} product(s) imported</p>
          <p v-if="importResult.updated > 0" class="text-sm text-blue-700">{{ importResult.updated }} product(s) updated</p>
          <p v-if="importResult.skipped > 0" class="text-sm text-amber-700">{{ importResult.skipped }} row(s) skipped</p>
          <div v-if="importResult.errors && importResult.errors.length" class="mt-2 max-h-32 overflow-y-auto">
            <p v-for="(err, idx) in importResult.errors" :key="idx" class="text-xs text-red-600">⚠ {{ err }}</p>
          </div>
        </div>

        <div v-if="!importResult">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Select Brand <span class="text-red-500">*</span></label>
            <select v-model="importForm.brand_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
              <option value="">-- Choose Brand --</option>
              <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
          </div>
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Excel File <span class="text-red-500">*</span></label>
            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-blue-400 transition-colors cursor-pointer" @click="triggerFileInput">
              <div class="space-y-1 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                <div class="flex text-sm text-gray-600">
                  <label class="relative cursor-pointer rounded-md font-medium text-blue-600 hover:text-blue-500">
                    <span>{{ importForm.file ? importForm.file.name : 'Upload an Excel file' }}</span>
                    <input ref="fileInputRef" type="file" accept=".xlsx,.xls" class="sr-only" @change="onFileChange">
                  </label>
                </div>
                <p class="text-xs text-gray-500">.xlsx or .xls up to 10MB</p>
              </div>
            </div>
          </div>
          <div class="mt-4 bg-blue-50 border border-blue-200 p-3 rounded-md">
            <h4 class="text-xs font-semibold text-blue-800 uppercase mb-1">Expected Excel Columns</h4>
            <p class="text-xs text-blue-700">Nama Produk, Kode Produk, Nama Variasi, Kode Variasi, Harga Awal, Harga Saat Ini, Stok Saat Ini</p>
          </div>
        </div>
      </form>
      <template #footer>
        <div class="flex w-full gap-2">
          <button v-if="!importResult" type="submit" form="import-form" :disabled="importing" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">{{ importing ? 'Importing...' : 'Import' }}</button>
          <button v-if="importResult" type="button" @click="closeImportModal" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">Done</button>
          <button type="button" @click="closeImportModal" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition-colors">Cancel</button>
        </div>
      </template>
    </ModalForm>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import StatusBadge from '../components/StatusBadge.vue';
import ModalForm from '../components/ModalForm.vue';
import { useProducts } from '../composables/useProducts';
import api from '../utils/api';

const {
  products, loading, pagination,
  fetchProducts, createProduct, updateProduct, deleteProduct, bulkDeleteProducts
} = useProducts();

const columns = [
  { key: 'checkbox', label: '', sortable: false },
  { key: 'no', label: 'No', sortable: false },
  { key: 'code', label: 'Code', sortable: false },
  { key: 'name', label: 'Name / SKU', sortable: true },
  { key: 'brand_name', label: 'Brand', sortable: false },
  { key: 'current_price', label: 'Price', sortable: false },
  { key: 'variants_count', label: 'Variants', sortable: false },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'actions', label: '', sortable: false },
];

const filters = ref({ search: '', status: '' });
const selectedIds = ref([]);
const pageError = ref(null);
const isModalOpen = ref(false);
const selectedProduct = ref(null);
const submitting = ref(false);
const formError = ref(null);
const form = ref({ code: '', sku: '', name: '', description: '', current_price: null, status: 'Active' });

// Import state
const isImportModalOpen = ref(false);
const importing = ref(false);
const importError = ref(null);
const importResult = ref(null);
const brands = ref([]);
const importForm = ref({ brand_id: '', file: null });
const fileInputRef = ref(null);


const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id);
  if (idx > -1) {
    selectedIds.value.splice(idx, 1);
  } else {
    selectedIds.value.push(id);
  }
};

const toggleSelectAll = () => {
  if (selectedIds.value.length === products.value.length) {
    selectedIds.value = [];
  } else {
    selectedIds.value = products.value.map(p => p.id);
  }
};

const confirmBulkDelete = async () => {
  if (selectedIds.value.length === 0) return;
  if (!confirm('Delete ' + selectedIds.value.length + ' selected product(s)?')) return;
  const success = await bulkDeleteProducts(selectedIds.value);
  if (success) {
    selectedIds.value = [];
    fetchData();
  }
};

onMounted(() => {
  fetchData();
  fetchBrands();
});

const fetchData = () => {
  pageError.value = null;
  const params = { page: pagination.value.current_page };
  if (filters.value.search) params.search = filters.value.search;
  if (filters.value.status) params.status = filters.value.status;
  if (filters.value.brand_id) params.brand_id = filters.value.brand_id;
  fetchProducts(params);
};

const handleBrandFilter = () => { pagination.value.current_page = 1; fetchData(); };

const handleSearch = (q) => {
  filters.value.search = q;
  pagination.value.current_page = 1;
  fetchData();
};

const changePage = (dir) => {
  pagination.value.current_page += dir;
  fetchData();
};

const openCreate = () => {
  selectedProduct.value = null;
  form.value = { code: '', sku: '', name: '', description: '', current_price: null, status: 'Active', brand_id: '' };
  formError.value = null;
  isModalOpen.value = true;
};

const openEdit = (p) => {
  selectedProduct.value = p;
  form.value = {
    code: p.code,
    sku: p.sku || '',
    name: p.name,
    description: p.description || '',
    current_price: p.current_price ?? null,
    status: p.status,
    brand_id: p.brand?.id || ''
  };
  formError.value = null;
  isModalOpen.value = true;
};

const submitForm = async () => {
  submitting.value = true;
  formError.value = null;
  try {
    const data = { ...form.value };
    if (data.current_price === '' || data.current_price === null) {
      data.current_price = null;
    } else {
      data.current_price = parseFloat(data.current_price);
    }

    const result = selectedProduct.value
      ? await updateProduct(selectedProduct.value.id, data)
      : await createProduct(data);

    if (result) {
      isModalOpen.value = false;
      fetchData();
    } else {
      formError.value = 'Failed to save product. Please check your input.';
    }
  } catch (e) {
    formError.value = e.response?.data?.message || 'An unexpected error occurred.';
  } finally {
    submitting.value = false;
  }
};

const confirmDelete = async (row) => {
  if (!confirm(`Delete product "${row.code} - ${row.name}"?`)) return;
  await deleteProduct(row.id);
  fetchData();
};

const fetchBrands = async () => {
  try {
    const res = await api.get('/admin/brands', { params: { per_page: 100 } });
    if (res.data.success) {
      brands.value = res.data.data.brands.data || res.data.data.brands || [];
    }
  } catch (e) {
    console.error('Failed to load brands', e);
  }
};

const formatCurrency = (val) => {
  if (val == null) return '—';
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
};

// Import handlers
const openImportModal = async () => {
  importError.value = null;
  importResult.value = null;
  importForm.value = { brand_id: '', file: null };
  isImportModalOpen.value = true;
  // Load brands
  try {
    const res = await api.get('/admin/brands');
    if (res.data.success) {
      brands.value = res.data.data.brands.data || res.data.data.brands || [];
    }
  } catch (e) {
    importError.value = 'Failed to load brands.';
  }
};

const triggerFileInput = () => {
  fileInputRef.value?.click();
};

const closeImportModal = () => {
  isImportModalOpen.value = false;
  importResult.value = null;
  importError.value = null;
  // Refresh data if import happened
  fetchData();
};

const onFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    importForm.value.file = file;
  }
};

const submitImport = async () => {
  if (!importForm.value.brand_id) {
    importError.value = 'Please select a brand.';
    return;
  }
  if (!importForm.value.file) {
    importError.value = 'Please select an Excel file.';
    return;
  }

  importing.value = true;
  importError.value = null;
  importResult.value = null;

  try {
    const formData = new FormData();
    formData.append('file', importForm.value.file);
    formData.append('brand_id', importForm.value.brand_id);

    const res = await api.post('/admin/products/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (res.data.success) {
      importResult.value = res.data.data;
    } else {
      importError.value = res.data.message || 'Import failed.';
    }
  } catch (e) {
    importError.value = e.response?.data?.message || 'Import failed. Please check your file.';
  } finally {
    importing.value = false;
  }
};
</script>



