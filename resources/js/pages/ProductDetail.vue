<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <router-link to="/products" class="text-gray-500 hover:text-gray-700 font-medium text-sm flex items-center">
          &larr; Back to Products
        </router-link>
        <span class="text-gray-300">/</span>
        <h1 class="text-2xl font-bold text-gray-900">{{ product ? `${product.code} - ${product.name}` : 'Loading...' }}</h1>
      </div>
      <div class="flex items-center space-x-3" v-if="product">
        <StatusBadge :status="product.status" />
        <button @click="openEditProduct" class="px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
          Edit Product
        </button>
      </div>
    </div>

    <!-- Loading / Error -->
    <div v-if="loading && !product" class="text-gray-500 text-sm">Loading product details...</div>
    <div v-else-if="error" class="bg-red-50 border border-red-200 p-4 rounded-md text-red-700 text-sm">{{ error }}</div>

    <template v-else-if="product">
      <!-- Overview Card -->
      <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
          <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</span>
          <span class="mt-1 block text-sm font-semibold text-gray-900">{{ product.sku || '—' }}</span>
        </div>
        <div>
          <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</span>
          <span class="mt-1 block text-sm font-semibold text-gray-900">{{ product.brand?.name || '—' }}</span>
        </div>
        <div>
          <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</span>
          <span class="mt-1 block text-sm text-gray-600">{{ product.created_at }}</span>
        </div>
        <div>
          <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Description</span>
          <span class="mt-1 block text-sm text-gray-600 truncate">{{ product.description || 'No description provided.' }}</span>
        </div>
      </div>

      <!-- Variants Workspace Section -->
      <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-6 bg-gray-50 border-b border-gray-200 flex flex-col sm:flex-row justify-between sm:items-center space-y-3 sm:space-y-0">
          <div>
            <h2 class="text-lg font-bold text-gray-900">Product Variants</h2>
            <p class="text-xs text-gray-500 mt-0.5">Manage master variants, reference retail prices (Normal), minimum floor prices (Bottom), and inventory stock.</p>
          </div>
          <div class="flex items-center space-x-3">
            <input
              type="text"
              v-model="variantSearch"
              @input="filterVariants"
              placeholder="Search variants..."
              class="rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"
            />
            <button @click="openCreateVariant" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-medium text-xs text-white uppercase tracking-widest hover:bg-blue-700 shadow-sm transition-colors">
              + New Variant
            </button>
          </div>
        </div>

        <div v-if="variantsLoading" class="p-6 text-sm text-gray-500">Loading variants...</div>
        
        <EmptyState v-else-if="!variants.length"
          title="No variants found"
          description="Create master variants for this product to set reference pricing and stock levels."
          button-text="+ Create First Variant"
          @action="openCreateVariant"
        />

        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
            <tr>
              <th class="px-6 py-3">Variant Code & Name</th>
              <th class="px-6 py-3">SKU</th>
              <th class="px-6 py-3">Master Normal Price</th>
              <th class="px-6 py-3">Master Bottom Price</th>
              <th class="px-6 py-3">Current Stock</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200 text-sm">
            <tr v-for="v in variants" :key="v.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-6 py-4">
                <div class="font-medium text-gray-900">{{ v.name }}</div>
                <div class="font-mono text-xs text-blue-600 bg-blue-50 inline-block px-1.5 py-0.5 rounded mt-0.5">{{ v.code }}</div>
              </td>
              <td class="px-6 py-4 text-gray-600">{{ v.sku || '—' }}</td>
              <td class="px-6 py-4 font-semibold text-gray-900">{{ formatCurrency(v.normal_price) }}</td>
              <td class="px-6 py-4 font-semibold text-amber-700">{{ formatCurrency(v.bottom_price) }}</td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" :class="v.current_stock > 10 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                  {{ v.current_stock }} units
                </span>
              </td>
              <td class="px-6 py-4">
                <StatusBadge :status="v.status" />
              </td>
              <td class="px-6 py-4 text-right space-x-3 font-medium">
                <button @click="openEditVariant(v)" class="text-blue-600 hover:text-blue-900">Edit</button>
                <button @click="handleDeleteVariant(v)" class="text-red-600 hover:text-red-900">Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- Product Edit Modal -->
    <ModalForm :is-open="isProductModalOpen" title="Edit Product" @close="isProductModalOpen = false">
      <form id="edit-product-form" @submit.prevent="submitProductEdit" class="space-y-4 mt-2">
        <div v-if="typeof error === 'string' && error" class="bg-red-50 border border-red-200 p-3 rounded-md text-sm text-red-700">{{ error }}</div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Product Code <span class="text-red-500">*</span></label>
            <input type="text" v-model="productForm.code" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">SKU</label>
            <input type="text" v-model="productForm.sku" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Product Name <span class="text-red-500">*</span></label>
          <input type="text" v-model="productForm.name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Description</label>
          <textarea v-model="productForm.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></textarea>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Brand</label>
          <select v-model="productForm.brand_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            <option value="">-- Select Brand --</option>
            <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Current Price</label>
          <input type="number" min="0" step="0.01" v-model="productForm.current_price" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm" placeholder="e.g. 150000">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Status</label>
          <select v-model="productForm.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </form>
      <template #footer>
        <div class="mt-5 sm:flex sm:flex-row-reverse w-full">
          <button type="submit" form="edit-product-form" :disabled="loading" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 sm:ml-3 sm:w-auto transition-colors">Save Changes</button>
          <button type="button" @click="isProductModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 sm:mt-0 sm:w-auto transition-colors">Cancel</button>
        </div>
      </template>
    </ModalForm>

    <!-- Variant Create / Edit Modal -->
    <ModalForm :is-open="isVariantModalOpen" :title="selectedVariant ? 'Edit Variant' : 'Create Variant'" @close="isVariantModalOpen = false">
      <form id="variant-form" @submit.prevent="submitVariantForm" class="space-y-4 mt-2">
        <div v-if="typeof error === 'object' && error" class="bg-red-50 border border-red-200 p-3 rounded-md text-sm text-red-700">
          <div v-for="(msgs, field) in error" :key="field">{{ msgs[0] }}</div>
        </div>
        <div v-if="typeof error === 'string' && error" class="bg-red-50 border border-red-200 p-3 rounded-md text-sm text-red-700">{{ error }}</div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Variant Code <span class="text-red-500">*</span></label>
            <input type="text" v-model="variantForm.code" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">SKU</label>
            <input type="text" v-model="variantForm.sku" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Variant Name <span class="text-red-500">*</span></label>
          <input type="text" v-model="variantForm.name" required placeholder="e.g. 50ml / SPF 50+" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Normal Price (Master) <span class="text-red-500">*</span></label>
            <input type="number" min="0" step="1" v-model="variantForm.normal_price" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm font-semibold text-gray-900">
            <span class="text-xs text-gray-400">Reference retail price</span>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Bottom Price (Master) <span class="text-red-500">*</span></label>
            <input type="number" min="0" step="1" v-model="variantForm.bottom_price" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm font-semibold text-amber-700">
            <span class="text-xs text-gray-400">Minimum floor price</span>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Current Stock <span class="text-red-500">*</span></label>
            <input type="number" min="0" v-model="variantForm.current_stock" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <select v-model="variantForm.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
      </form>
      <template #footer>
        <div class="mt-5 sm:flex sm:flex-row-reverse w-full">
          <button type="submit" form="variant-form" :disabled="loading" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 sm:ml-3 sm:w-auto transition-colors">{{ loading ? 'Saving...' : (selectedVariant ? 'Save Variant' : 'Create Variant') }}</button>
          <button type="button" @click="isVariantModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 sm:mt-0 sm:w-auto transition-colors">Cancel</button>
        </div>
      </template>
    </ModalForm>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import StatusBadge from '../components/StatusBadge.vue';
import EmptyState from '../components/EmptyState.vue';
import ModalForm from '../components/ModalForm.vue';
import { useProducts } from '../composables/useProducts';
import api from '../utils/api';

const route = useRoute();
const {
  product, variants, loading, error,
  fetchProduct, updateProduct,
  fetchVariants, createVariant, updateVariant, deleteVariant
} = useProducts();

const variantsLoading = ref(false);
const variantSearch = ref('');
const isProductModalOpen = ref(false);
const brands = ref([]);
const isVariantModalOpen = ref(false);
const selectedVariant = ref(null);

const productForm = ref({ code: '', sku: '', name: '', description: '', current_price: null, brand_id: '', status: 'Active' });
const variantForm = ref({ code: '', sku: '', name: '', normal_price: 0, bottom_price: 0, current_stock: 0, status: 'Active' });

const formatCurrency = (val) => {
  if (val == null) return '—';
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
};

onMounted(async () => {
  await loadData();
  fetchBrands();
});

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

const loadData = async () => {
  await fetchProduct(route.params.id);
  variantsLoading.value = true;
  await fetchVariants(route.params.id, { per_page: 100 });
  variantsLoading.value = false;
};

const filterVariants = async () => {
  variantsLoading.value = true;
  await fetchVariants(route.params.id, { search: variantSearch.value, per_page: 100 });
  variantsLoading.value = false;
};

// Product Edit
const openEditProduct = () => {
  productForm.value = {
    code: product.value.code,
    sku: product.value.sku || '',
    name: product.value.name,
    description: product.value.description || '',
    current_price: product.value.current_price ?? null,
    brand_id: product.value.brand?.id || '',
    status: product.value.status
  };
  isProductModalOpen.value = true;
};

const submitProductEdit = async () => {
  const res = await updateProduct(product.value.id, productForm.value);
  if (res) {
    isProductModalOpen.value = false;
    await fetchProduct(route.params.id);
  }
};

// Variant Management
const openCreateVariant = () => {
  selectedVariant.value = null;
  variantForm.value = { code: '', sku: '', name: '', normal_price: 0, bottom_price: 0, current_stock: 0, status: 'Active' };
  isVariantModalOpen.value = true;
};

const openEditVariant = (v) => {
  selectedVariant.value = v;
  variantForm.value = {
    code: v.code,
    sku: v.sku || '',
    name: v.name,
    normal_price: v.normal_price,
    bottom_price: v.bottom_price,
    current_stock: v.current_stock,
    status: v.status
  };
  isVariantModalOpen.value = true;
};

const submitVariantForm = async () => {
  const res = selectedVariant.value
    ? await updateVariant(product.value.id, selectedVariant.value.id, variantForm.value)
    : await createVariant(product.value.id, variantForm.value);
    
  if (res) {
    isVariantModalOpen.value = false;
    await loadData();
  }
};

const handleDeleteVariant = async (v) => {
  if (!confirm(`Are you sure you want to delete variant "${v.code} - ${v.name}"?`)) return;
  const res = await deleteVariant(product.value.id, v.id);
  if (res) {
    await loadData();
  }
};
</script>
