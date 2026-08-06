<template>
  <div class="p-2">
    <form id="add-product-form" @submit.prevent="submitForm" class="space-y-4 mt-2">
      <div v-if="error" class="bg-red-50 border border-red-200 p-3 rounded-md text-sm text-red-700">{{ error }}</div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Promotion <span class="text-red-500">*</span></label>
        <select v-model="form.promotion_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          <option value="">-- Select Promotion --</option>
          <option v-for="p in promotions" :key="p.id" :value="p.id">{{ p.code }} - {{ p.name }}</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Product <span class="text-red-500">*</span></label>
        <select v-model="selectedProductId" @change="loadVariants" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          <option value="">-- Select Product --</option>
          <option v-for="p in allProducts" :key="p.id" :value="p.id">{{ p.code }} - {{ p.name }}</option>
        </select>
      </div>
      <div v-if="availableVariants.length">
        <label class="block text-sm font-medium text-gray-700">Variant <span class="text-red-500">*</span></label>
        <select v-model="form.variant_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          <option value="">-- Select Variant --</option>
          <option v-for="v in availableVariants" :key="v.id" :value="v.id">{{ v.code }} - {{ v.name }} (Normal: {{ formatCurrency(v.normal_price) }}, Stock: {{ v.current_stock }})</option>
        </select>
      </div>
      <div v-if="form.variant_id" class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <div class="col-span-2"><h4 class="text-sm font-semibold text-gray-700 mb-2">Pricing</h4></div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Normal Price</label>
          <div class="mt-1 text-sm font-semibold text-gray-900 bg-white px-3 py-2 rounded border border-gray-300">{{ formatCurrency(selectedVariantNormalPrice) }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Campaign Price <span class="text-red-500">*</span></label>
          <input type="number" min="0" step="1" v-model="form.campaign_price" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Discount Price <span class="text-red-500">*</span></label>
          <input type="number" min="0" step="1" v-model="form.discount_price" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Discount %</label>
          <div class="mt-1 text-sm font-bold" :class="discountPercent > 0 ? 'text-green-600' : 'text-gray-400'">{{ discountPercent }}%</div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Bottom Price</label>
          <input type="number" min="0" step="1" v-model="form.bottom_price" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Promotion Stock <span class="text-red-500">*</span></label>
          <input type="number" min="0" v-model="form.promotion_stock" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Purchase Limit</label>
          <input type="number" min="0" v-model="form.purchase_limit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700">Notes</label>
          <textarea v-model="form.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm"></textarea>
        </div>
      </div>
    </form>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from "vue";
import api from "../utils/api";
const props = defineProps({ campaignId: { type: String, required: true } });
const emit = defineEmits(["close", "saved"]);
const promotions = ref([]);
const allProducts = ref([]);
const availableVariants = ref([]);
const selectedProductId = ref("");
const submitting = ref(false);
const error = ref(null);
const form = ref({ promotion_id: "", variant_id: "", campaign_price: 0, bottom_price: 0, discount_price: 0, promotion_stock: 0, purchase_limit: 0, notes: "" });
const formatCurrency = (val) => { if (val == null) return "\u2014"; return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(val); };
const selectedVariantNormalPrice = computed(() => { const v = availableVariants.value.find(v => v.id === form.value.variant_id); return v?.normal_price || 0; });
const discountPercent = computed(() => { const normal = selectedVariantNormalPrice.value; const discount = Number(form.value.discount_price) || 0; if (normal <= 0) return 0; return Math.round(((normal - discount) / normal) * 100); });
onMounted(async () => {
  try {
    const r1 = await api.get("/admin/promotions", { params: { campaign_id: props.campaignId, per_page: 100 } });
    promotions.value = r1.data.data?.promotions?.data || [];
    const r2 = await api.get("/admin/products", { params: { per_page: 200 } });
    allProducts.value = r2.data.data?.products?.data || [];
  } catch (e) { error.value = "Failed to load data."; }
});
const loadVariants = async () => {
  form.value.variant_id = ""; availableVariants.value = [];
  if (!selectedProductId.value) return;
  try {
    const r = await api.get("/admin/products/" + selectedProductId.value + "/variants", { params: { per_page: 200 } });
    availableVariants.value = r.data.data?.variants?.data || [];
  } catch (e) { error.value = "Failed to load variants."; }
};
const submitForm = async () => {
  submitting.value = true; error.value = null;
  try {
    await api.post("/admin/promotions/" + form.value.promotion_id + "/variants", {
      variant_id: form.value.variant_id,
      campaign_price: Number(form.value.campaign_price) || 0,
      bottom_price: Number(form.value.bottom_price) || 0,
      discount_price: Number(form.value.discount_price) || 0,
      promotion_stock: Number(form.value.promotion_stock) || 0,
      purchase_limit: Number(form.value.purchase_limit) || 0,
      notes: form.value.notes,
    });
    emit("saved");
    emit("close");
  } catch (e) { error.value = e.response?.data?.message || "Failed to save."; }
  finally { submitting.value = false; }
};
</script>