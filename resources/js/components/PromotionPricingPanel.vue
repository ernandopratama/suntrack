<template>
  <div class="space-y-6">
    <!-- Top Metrics Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Total Variants</span>
        <span class="mt-2 block text-2xl font-bold text-gray-900">{{ variants.length }} <span class="text-xs font-normal text-gray-400">items</span></span>
      </div>
      <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Total Promo Stock</span>
        <span class="mt-2 block text-2xl font-bold text-blue-600">{{ totalStock }} <span class="text-xs font-normal text-gray-400">units</span></span>
      </div>
      <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Est. Promotion Value</span>
        <span class="mt-2 block text-2xl font-bold text-emerald-600">{{ formatCurrency(totalEstimatedValue) }}</span>
      </div>
      <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
        <span class="block text-xs font-medium text-gray-500 uppercase tracking-wider">Total Customer Savings</span>
        <span class="mt-2 block text-2xl font-bold text-purple-600">{{ formatCurrency(totalSavings) }}</span>
      </div>
    </div>

    <!-- Pricing Analysis & Rules Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
      <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
        <div>
          <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Promotion Pricing Snapshot & Rules</h3>
          <p class="text-xs text-gray-500 mt-0.5">All campaign prices are historical snapshots and do not alter master variant records.</p>
        </div>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
          Strict Floor Enforcement Active
        </span>
      </div>

      <div v-if="loading" class="p-6 text-sm text-gray-400 text-center">Loading pricing data...</div>

      <EmptyState v-else-if="!variants.length"
        title="No pricing data available"
        description="Switch to the Products tab to map product variants to this promotion first."
      />

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
          <tr>
            <th class="px-4 py-3">Variant</th>
            <th class="px-4 py-3">Master Normal (Snapshot)</th>
            <th class="px-4 py-3">Master Bottom Floor</th>
            <th class="px-4 py-3">Campaign Price</th>
            <th class="px-4 py-3">Discount Price</th>
            <th class="px-4 py-3">Margin Protection</th>
            <th class="px-4 py-3 text-right">Action</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200 text-sm">
          <tr v-for="v in variants" :key="v.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-4 py-3">
              <div class="font-medium text-gray-900">{{ v.name }}</div>
              <div class="text-xs text-gray-400 font-mono">{{ v.code }}</div>
            </td>
            <td class="px-4 py-3 text-gray-600 font-medium">
              {{ formatCurrency(v.promotion_pricing?.normal_price_snapshot) }}
            </td>
            <td class="px-4 py-3 text-amber-700 font-medium">
              {{ formatCurrency(v.promotion_pricing?.bottom_price) }}
            </td>
            <td class="px-4 py-3 font-bold text-blue-600">
              {{ formatCurrency(v.promotion_pricing?.campaign_price) }}
            </td>
            <td class="px-4 py-3 font-semibold text-purple-600">
              {{ formatCurrency(v.promotion_pricing?.discount_price) }}
            </td>
            <td class="px-4 py-3">
              <span v-if="v.promotion_pricing?.campaign_price >= v.promotion_pricing?.bottom_price" class="inline-flex items-center text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-1 rounded">
                ✓ Above Floor
              </span>
              <span v-else class="inline-flex items-center text-xs font-semibold text-red-700 bg-red-50 px-2 py-1 rounded">
                ⚠ Below Floor
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button @click="openEditPricing(v)" class="text-blue-600 hover:text-blue-900 font-medium text-xs bg-blue-50 hover:bg-blue-100 px-2.5 py-1.5 rounded transition-colors">
                Adjust Pricing
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Edit Pricing Modal -->
    <ModalForm :is-open="isModalOpen" title="Adjust Promotion Pricing" @close="isModalOpen = false">
      <form id="promo-pricing-form" @submit.prevent="submitPricing" class="space-y-4 mt-2">
        <div v-if="typeof error === 'object' && error" class="bg-red-50 border border-red-200 p-3 rounded-md text-sm text-red-700">
          <div v-for="(msgs, field) in error" :key="field">{{ msgs[0] }}</div>
        </div>
        <div v-if="typeof error === 'string' && error" class="bg-red-50 border border-red-200 p-3 rounded-md text-sm text-red-700">{{ error }}</div>

        <div class="bg-gray-50 p-3 rounded-md border border-gray-200 text-xs text-gray-600 space-y-1">
          <div><strong class="text-gray-800">Variant:</strong> {{ selectedVariant?.code }} - {{ selectedVariant?.name }}</div>
          <div><strong class="text-gray-800">Master Normal Price:</strong> {{ formatCurrency(selectedVariant?.promotion_pricing?.normal_price_snapshot) }}</div>
          <div><strong class="text-gray-800">Master Bottom Floor:</strong> {{ formatCurrency(selectedVariant?.promotion_pricing?.bottom_price) }}</div>
          <div><strong class="text-gray-800">Available Current Stock:</strong> {{ selectedVariant?.current_stock }} units</div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Campaign Price <span class="text-red-500">*</span></label>
            <input type="number" min="0" step="1" v-model="form.campaign_price" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm font-semibold text-blue-600">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Discount Price <span class="text-red-500">*</span></label>
            <input type="number" min="0" step="1" v-model="form.discount_price" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm font-semibold text-purple-600">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Promotion Stock <span class="text-red-500">*</span></label>
            <input type="number" min="0" v-model="form.promotion_stock" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Purchase Limit</label>
            <input type="number" min="0" v-model="form.purchase_limit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Notes</label>
          <textarea v-model="form.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></textarea>
        </div>
      </form>
      <template #footer>
        <div class="mt-5 sm:flex sm:flex-row-reverse w-full">
          <button type="submit" form="promo-pricing-form" :disabled="saving" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 sm:ml-3 sm:w-auto transition-colors">{{ saving ? 'Saving...' : 'Update Pricing' }}</button>
          <button type="button" @click="isModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 sm:mt-0 sm:w-auto transition-colors">Cancel</button>
        </div>
      </template>
    </ModalForm>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import EmptyState from './EmptyState.vue';
import ModalForm from './ModalForm.vue';
import { useProducts } from '../composables/useProducts';

const props = defineProps({
  promotionId: { type: String, required: true },
});
const emit = defineEmits(['updated']);

const { variants, loading, fetchPromotionVariants, addVariantToPromotion } = useProducts();

const isModalOpen = ref(false);
const saving = ref(false);
const error = ref(null);
const selectedVariant = ref(null);

const form = ref({
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

const totalStock = computed(() => variants.value.reduce((acc, v) => acc + (v.promotion_pricing?.promotion_stock || 0), 0));
const totalEstimatedValue = computed(() => variants.value.reduce((acc, v) => acc + ((v.promotion_pricing?.campaign_price || 0) * (v.promotion_pricing?.promotion_stock || 0)), 0));
const totalSavings = computed(() => variants.value.reduce((acc, v) => {
  const normal = v.promotion_pricing?.normal_price_snapshot || 0;
  const camp = v.promotion_pricing?.campaign_price || 0;
  const diff = Math.max(0, normal - camp);
  return acc + (diff * (v.promotion_pricing?.promotion_stock || 0));
}, 0));

onMounted(() => fetchPromotionVariants(props.promotionId));

const openEditPricing = (v) => {
  selectedVariant.value = v;
  form.value = {
    variant_id: v.id,
    campaign_price: v.promotion_pricing?.campaign_price || 0,
    bottom_price: v.promotion_pricing?.bottom_price || 0,
    discount_price: v.promotion_pricing?.discount_price || 0,
    promotion_stock: v.promotion_pricing?.promotion_stock || 0,
    purchase_limit: v.promotion_pricing?.purchase_limit || 0,
    notes: v.promotion_pricing?.notes || '',
  };
  error.value = null;
  isModalOpen.value = true;
};

const submitPricing = async () => {
  saving.value = true;
  error.value = null;
  const res = await addVariantToPromotion(props.promotionId, form.value);
  if (res) {
    isModalOpen.value = false;
    await fetchPromotionVariants(props.promotionId);
    emit('updated');
  } else {
    const { error: err } = useProducts();
    error.value = err.value || 'Failed to update pricing.';
  }
  saving.value = false;
};
</script>
