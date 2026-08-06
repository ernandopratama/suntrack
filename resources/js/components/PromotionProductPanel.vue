<template>
  <div>
    <!-- Panel Header -->
    <div class="flex justify-between items-center mb-4">
      <p class="text-sm text-gray-500">{{ variants.length }} variant(s) mapped to this promotion.</p>
      <button @click="isAddModalOpen = true"
        class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
        + Add Variant
      </button>
    </div>

    <div v-if="loading" class="text-sm text-gray-400">Loading...</div>
    
    <EmptyState v-else-if="!variants.length"
      title="No variants added yet"
      description="Add product variants to this promotion to manage pricing." />

    <div v-else class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variant</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Normal Price</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campaign Price</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approval</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="v in variants" :key="v.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3">
              <div class="text-sm font-medium text-gray-900">{{ v.name }}</div>
              <div class="text-xs text-gray-400 font-mono">{{ v.code }}</div>
              <div class="text-xs text-gray-400">{{ v.product?.name }}</div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
              {{ formatCurrency(v.promotion_pricing?.normal_price_snapshot) }}
            </td>
            <td class="px-4 py-3 text-sm font-semibold text-blue-700">
              {{ formatCurrency(v.promotion_pricing?.campaign_price) }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">
              {{ v.promotion_pricing?.promotion_stock ?? 0 }}
              <span class="text-xs text-gray-400">/ limit {{ v.promotion_pricing?.purchase_limit ?? 0 }}</span>
            </td>
            <td class="px-4 py-3">
              <StatusBadge :status="v.promotion_pricing?.approval_status || 'Pending'" />
            </td>
            <td class="px-4 py-3 text-sm">
              <button @click="openEditPricing(v)" class="text-gray-600 hover:text-gray-900 font-medium mr-3">Edit</button>
              <button @click="removeVariant(v.id)" class="text-red-500 hover:text-red-700 font-medium">Remove</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add Variant Modal -->
    <ModalForm :is-open="isAddModalOpen" title="Add Variant to Promotion" @close="isAddModalOpen = false">
      <form id="promo-product-form" @submit.prevent="submitAdd" class="space-y-4 mt-2">
        <div v-if="typeof addError === 'object' && addError" class="bg-red-50 border border-red-200 p-3 rounded-md text-sm text-red-700">
          <div v-for="(msgs, field) in addError" :key="field">{{ msgs[0] }}</div>
        </div>
        <div v-if="typeof addError === 'string' && addError" class="bg-red-50 border border-red-200 p-3 rounded-md text-sm text-red-700">{{ addError }}</div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700">Select Product</label>
          <select v-model="selectedProductId" @change="loadVariantsForProduct" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            <option :value="null">— Select product —</option>
            <option v-for="p in allProducts" :key="p.id" :value="p.id">{{ p.code }} - {{ p.name }}</option>
          </select>
        </div>
        <div v-if="availableVariants.length">
          <label class="block text-sm font-medium text-gray-700">Select Variant</label>
          <select v-model="pricingForm.variant_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
            <option :value="null">— Select variant —</option>
            <option v-for="v in availableVariants" :key="v.id" :value="v.id">
              {{ v.code }} - {{ v.name }} (Normal: {{ formatCurrency(v.normal_price) }}, Stock: {{ v.current_stock }})
            </option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div><label class="block text-sm font-medium text-gray-700">Campaign Price <span class="text-red-500">*</span></label>
            <input type="number" min="0" step="1" v-model="pricingForm.campaign_price" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
          <div><label class="block text-sm font-medium text-gray-700">Discount Price <span class="text-red-500">*</span></label>
            <input type="number" min="0" step="1" v-model="pricingForm.discount_price" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
          <div><label class="block text-sm font-medium text-gray-700">Bottom Price</label>
            <input type="number" min="0" step="1" v-model="pricingForm.bottom_price" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
          <div><label class="block text-sm font-medium text-gray-700">Promotion Stock <span class="text-red-500">*</span></label>
            <input type="number" min="0" v-model="pricingForm.promotion_stock" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
          <div><label class="block text-sm font-medium text-gray-700">Purchase Limit</label>
            <input type="number" min="0" v-model="pricingForm.purchase_limit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Notes</label>
          <textarea v-model="pricingForm.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></textarea>
        </div>
      </form>
      <template #footer>
        <div class="mt-5 sm:flex sm:flex-row-reverse w-full">
          <button type="submit" form="promo-product-form" :disabled="addLoading" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 sm:ml-3 sm:w-auto transition-colors">
            {{ addLoading ? 'Saving...' : 'Add Variant' }}
          </button>
          <button type="button" @click="isAddModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 sm:mt-0 sm:w-auto transition-colors">Cancel</button>
        </div>
      </template>
    </ModalForm>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import EmptyState from './EmptyState.vue';
import ModalForm from './ModalForm.vue';
import StatusBadge from './StatusBadge.vue';
import { useProducts } from '../composables/useProducts';

const props = defineProps({
  promotionId: { type: String, required: true },
});
const emit = defineEmits(['updated']);

const {
  variants, loading, fetchPromotionVariants,
  addVariantToPromotion, removeVariantFromPromotion,
  products: allProducts, fetchProducts,
} = useProducts();

const isAddModalOpen = ref(false);
const addLoading = ref(false);
const addError = ref(null);
const selectedProductId = ref(null);
const availableVariants = ref([]);

const pricingForm = ref({
  variant_id: null,
  campaign_price: 0,
  bottom_price: 0,
  discount_price: 0,
  promotion_stock: 0,
  purchase_limit: 0,
  notes: '',
});

const formatCurrency = (val) => {
  if (val == null) return '—';
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
};

onMounted(async () => {
  await fetchPromotionVariants(props.promotionId);
  await fetchProducts({ per_page: 200 });
});

const loadVariantsForProduct = async () => {
  if (!selectedProductId.value) { availableVariants.value = []; return; }
  const { useProducts: up } = await import('../composables/useProducts.js');
  const { variants: pv, fetchVariants } = up();
  await fetchVariants(selectedProductId.value, { per_page: 200 });
  availableVariants.value = pv.value;
};

const openEditPricing = (v) => {
  pricingForm.value = {
    variant_id: v.id,
    campaign_price: v.promotion_pricing?.campaign_price || 0,
    bottom_price: v.promotion_pricing?.bottom_price || 0,
    discount_price: v.promotion_pricing?.discount_price || 0,
    promotion_stock: v.promotion_pricing?.promotion_stock || 0,
    purchase_limit: v.promotion_pricing?.purchase_limit || 0,
    notes: v.promotion_pricing?.notes || '',
  };
  isAddModalOpen.value = true;
};

const submitAdd = async () => {
  addLoading.value = true;
  addError.value = null;
  const result = await addVariantToPromotion(props.promotionId, pricingForm.value);
  if (result) {
    isAddModalOpen.value = false;
    await fetchPromotionVariants(props.promotionId);
    emit('updated');
  } else {
    addError.value = result === false ? 'Validation error' : result;
  }
  addLoading.value = false;
};

const removeVariant = async (variantId) => {
  if (!confirm('Remove this variant from the promotion?')) return;
  await removeVariantFromPromotion(props.promotionId, variantId);
  await fetchPromotionVariants(props.promotionId);
  emit('updated');
};
</script>
