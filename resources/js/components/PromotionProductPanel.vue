<template>
  <div class="space-y-5">
    <!-- Panel Header -->
    <div
      class="flex flex-col gap-4 rounded-2xl border border-[#D0E7E6] bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="flex items-start gap-3">
        <div
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]"
        >
          <i class="fa-solid fa-box-open text-sm"></i>
        </div>

        <div>
          <h3 class="text-sm font-extrabold text-[#293681]">
            Promotion Products
          </h3>

          <p class="mt-1 text-xs text-gray-500">
            {{ variants.length }} variant(s) mapped to this promotion.
          </p>
        </div>
      </div>

      <button
        v-if="$can('promotion.update')"
        @click="isAddModalOpen = true"
        type="button"
        class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#4274D9] px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#293681] hover:shadow-md focus:outline-none focus:ring-4 focus:ring-[#95CCDD]/40"
      >
        <span
          class="flex h-5 w-5 items-center justify-center rounded-md bg-white/15"
        >
          <i class="fa-solid fa-plus text-[9px]"></i>
        </span>

        <span>Add Variant</span>
      </button>
    </div>

    <!-- Loading -->
    <div
      v-if="loading"
      class="flex items-center justify-center rounded-2xl border border-[#D0E7E6] bg-white px-6 py-12"
    >
      <div class="flex items-center gap-3">
        <span
          class="h-5 w-5 animate-spin rounded-full border-2 border-[#D0E7E6] border-t-[#4274D9]"
        ></span>

        <span class="text-sm font-medium text-gray-500">
          Loading variants...
        </span>
      </div>
    </div>

    <!-- Empty State -->
    <EmptyState
      v-else-if="!variants.length"
      title="No variants added yet"
      description="Add product variants to this promotion to manage pricing."
    />

    <!-- Product Table -->
    <div
      v-else
      class="overflow-hidden rounded-2xl border border-[#D0E7E6] bg-white shadow-sm"
    >
      <!-- Table Header -->
      <div
        class="border-b border-[#D0E7E6] bg-[#D0E7E6]/30 px-5 py-3.5"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-extrabold uppercase tracking-wider text-[#293681]">
              Product Variants
            </p>

            <p class="mt-0.5 text-[11px] text-gray-500">
              Manage pricing, stock, and approval status.
            </p>
          </div>

          <span
            class="rounded-full bg-white px-2.5 py-1 text-[10px] font-extrabold text-[#4274D9] shadow-sm ring-1 ring-[#95CCDD]/50"
          >
            {{ variants.length }} Variants
          </span>
        </div>
      </div>

      <!-- Responsive Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
          <thead class="bg-gray-50/70">
            <tr>
              <th
                class="whitespace-nowrap px-5 py-3.5 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
              >
                Variant
              </th>

              <th
                class="whitespace-nowrap px-5 py-3.5 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
              >
                Normal Price
              </th>

              <th
                class="whitespace-nowrap px-5 py-3.5 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
              >
                Campaign Price
              </th>

              <th
                class="whitespace-nowrap px-5 py-3.5 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
              >
                Stock
              </th>

              <th
                class="whitespace-nowrap px-5 py-3.5 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
              >
                Approval
              </th>

              <th
                class="whitespace-nowrap px-5 py-3.5 text-right text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
              >
                Actions
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100 bg-white">
            <tr
              v-for="v in variants"
              :key="v.id"
              class="group transition-colors duration-150 hover:bg-[#D0E7E6]/20"
            >
              <!-- Variant -->
              <td class="px-5 py-4">
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681] transition-colors group-hover:bg-[#95CCDD]"
                  >
                    <i class="fa-solid fa-box text-xs"></i>
                  </div>

                  <div class="min-w-0">
                    <div class="truncate text-sm font-bold text-gray-900">
                      {{ v.name }}
                    </div>

                    <div class="mt-0.5 flex items-center gap-1.5">
                      <span
                        class="rounded-md bg-gray-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-gray-500"
                      >
                        {{ v.code }}
                      </span>

                      <span
                        v-if="v.product?.name"
                        class="truncate text-[11px] text-gray-400"
                      >
                        {{ v.product?.name }}
                      </span>
                    </div>
                  </div>
                </div>
              </td>

              <!-- Normal Price -->
              <td class="whitespace-nowrap px-5 py-4">
                <span class="text-sm font-medium text-gray-600">
                  {{ formatCurrency(v.promotion_pricing?.normal_price_snapshot) }}
                </span>
              </td>

              <!-- Campaign Price -->
              <td class="whitespace-nowrap px-5 py-4">
                <div
                  class="inline-flex rounded-lg bg-[#D0E7E6]/60 px-2.5 py-1.5"
                >
                  <span class="text-sm font-extrabold text-[#293681]">
                    {{ formatCurrency(v.promotion_pricing?.campaign_price) }}
                  </span>
                </div>
              </td>

              <!-- Stock -->
              <td class="whitespace-nowrap px-5 py-4">
                <div class="flex items-center gap-2">
                  <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 text-gray-500"
                  >
                    <i class="fa-solid fa-layer-group text-[10px]"></i>
                  </div>

                  <div>
                    <div class="text-sm font-bold text-gray-700">
                      {{ v.promotion_pricing?.promotion_stock ?? 0 }}
                    </div>

                    <div class="text-[10px] text-gray-400">
                      Limit:
                      {{ v.promotion_pricing?.purchase_limit ?? 0 }}
                    </div>
                  </div>
                </div>
              </td>

              <!-- Approval -->
              <td class="px-5 py-4">
                <StatusBadge
                  :status="
                    v.promotion_pricing?.approval_status || 'Pending'
                  "
                />
              </td>

              <!-- Actions -->
              <td class="px-5 py-4">
                <div class="flex items-center justify-end gap-2">
                  <button
                    v-if="$can('promotion.update')"
                    @click="openEditPricing(v)"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-[#95CCDD]/60 bg-white px-3 py-2 text-xs font-bold text-[#4274D9] transition-all duration-200 hover:border-[#4274D9] hover:bg-[#D0E7E6]/40 hover:text-[#293681]"
                  >
                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                    Edit
                  </button>

                  <button
                    v-if="$can('promotion.update')"
                    @click="removeVariant(v.id)"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-rose-100 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-600 transition-all duration-200 hover:border-rose-200 hover:bg-rose-100 hover:text-rose-700"
                  >
                    <i class="fa-solid fa-trash-can text-[10px]"></i>
                    Remove
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add / Edit Variant Modal -->
    <ModalForm
      :is-open="isAddModalOpen"
      title="Add Variant to Promotion"
      @close="isAddModalOpen = false"
    >
      <form
        id="promo-product-form"
        @submit.prevent="submitAdd"
        class="space-y-5"
      >
        <!-- Error -->
        <div
          v-if="typeof addError === 'object' && addError"
          class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
        >
          <div
            v-for="(msgs, field) in addError"
            :key="field"
            class="flex items-start gap-2"
          >
            <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs"></i>
            <span>{{ msgs[0] }}</span>
          </div>
        </div>

        <div
          v-if="typeof addError === 'string' && addError"
          class="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
        >
          <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs"></i>
          <span>{{ addError }}</span>
        </div>

        <!-- Product -->
        <div>
          <label
            class="mb-1.5 block text-xs font-extrabold text-[#293681]"
          >
            Select Product
          </label>

          <div class="relative">
            <select
              v-model="selectedProductId"
              @change="loadVariantsForProduct"
              class="block w-full appearance-none rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 pr-10 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30"
            >
              <option :value="null">— Select product —</option>

              <option
                v-for="p in allProducts"
                :key="p.id"
                :value="p.id"
              >
                {{ p.code }} - {{ p.name }}
              </option>
            </select>

            <i
              class="fa-solid fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-gray-400"
            ></i>
          </div>
        </div>

        <!-- Variant -->
        <div v-if="availableVariants.length">
          <label
            class="mb-1.5 block text-xs font-extrabold text-[#293681]"
          >
            Select Variant
          </label>

          <div class="relative">
            <select
              v-model="pricingForm.variant_id"
              class="block w-full appearance-none rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 pr-10 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30"
            >
              <option :value="null">— Select variant —</option>

              <option
                v-for="v in availableVariants"
                :key="v.id"
                :value="v.id"
              >
                {{ v.code }} - {{ v.name }} (Normal:
                {{ formatCurrency(v.normal_price) }}, Stock:
                {{ v.current_stock }})
              </option>
            </select>

            <i
              class="fa-solid fa-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-gray-400"
            ></i>
          </div>
        </div>

        <!-- Pricing -->
        <div>
          <div class="mb-3 flex items-center gap-2">
            <span
              class="h-5 w-1 rounded-full bg-[#4274D9]"
            ></span>

            <h4 class="text-xs font-extrabold uppercase tracking-wider text-[#293681]">
              Pricing & Stock
            </h4>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <!-- Campaign Price -->
          <div>
            <label
              class="mb-1.5 block text-xs font-bold text-gray-600"
            >
              Campaign Price
              <span class="text-rose-500">*</span>
            </label>

            <div class="relative">
              <span
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-bold text-[#4274D9]"
              >
                Rp.
              </span>

              <input
                type="text"
                inputmode="numeric"
                :value="formatPriceInput(pricingForm.campaign_price)"
                @input="updatePriceField('campaign_price', $event)"
                required
                class="block w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-12 pr-3.5 text-sm font-bold text-gray-700 shadow-sm outline-none transition-all placeholder:text-gray-400 hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30"
                placeholder="0"
              />
            </div>
          </div>

          <!-- Discount Price -->
          <div>
            <label
              class="mb-1.5 block text-xs font-bold text-gray-600"
            >
              Discount Price
              <span class="text-rose-500">*</span>
            </label>

            <div class="relative">
              <span
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-bold text-[#4274D9]"
              >
                Rp.
              </span>

              <input
                type="text"
                inputmode="numeric"
                :value="formatPriceInput(pricingForm.discount_price)"
                @input="updatePriceField('discount_price', $event)"
                required
                class="block w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-12 pr-3.5 text-sm font-bold text-gray-700 shadow-sm outline-none transition-all placeholder:text-gray-400 hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30"
                placeholder="0"
              />
            </div>
          </div>

          <!-- Bottom Price -->
          <div>
            <label
              class="mb-1.5 block text-xs font-bold text-gray-600"
            >
              Bottom Price
            </label>

            <div class="relative">
              <span
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm font-bold text-[#4274D9]"
              >
                Rp.
              </span>

              <input
                type="text"
                inputmode="numeric"
                :value="formatPriceInput(pricingForm.bottom_price)"
                @input="updatePriceField('bottom_price', $event)"
                class="block w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-12 pr-3.5 text-sm font-bold text-gray-700 shadow-sm outline-none transition-all placeholder:text-gray-400 hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30"
                placeholder="0"
              />
            </div>
          </div>

            <!-- Promotion Stock -->
            <div>
              <label
                class="mb-1.5 block text-xs font-bold text-gray-600"
              >
                Promotion Stock
                <span class="text-rose-500">*</span>
              </label>

              <input
                type="number"
                min="0"
                v-model="pricingForm.promotion_stock"
                required
                class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all placeholder:text-gray-400 hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30"
              />
            </div>

            <!-- Purchase Limit -->
            <div class="sm:col-span-2">
              <label
                class="mb-1.5 block text-xs font-bold text-gray-600"
              >
                Purchase Limit
              </label>

              <input
                type="number"
                min="0"
                v-model="pricingForm.purchase_limit"
                class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all placeholder:text-gray-400 hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30"
              />
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label
            class="mb-1.5 block text-xs font-extrabold text-[#293681]"
          >
            Notes
          </label>

          <textarea
            v-model="pricingForm.notes"
            rows="3"
            placeholder="Add additional notes..."
            class="block w-full resize-none rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all placeholder:text-gray-400 hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30"
          ></textarea>
        </div>
      </form>

      <!-- Modal Footer -->
      <template #footer>
        <div
          class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end"
        >
          <button
            type="button"
            @click="isAddModalOpen = false"
            class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-extrabold text-gray-600 shadow-sm transition-all duration-200 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
          >
            Cancel
          </button>

          <button
            v-if="$can('promotion.update')"
            type="submit"
            form="promo-product-form"
            :disabled="addLoading"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#4274D9] px-5 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#293681] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
          >
            <i
              v-if="addLoading"
              class="fa-solid fa-spinner animate-spin text-[10px]"
            ></i>

            <i
              v-else
              class="fa-solid fa-plus text-[10px]"
            ></i>

            {{ addLoading ? 'Saving...' : 'Add Variant' }}
          </button>
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
  if (val == null || val === '') return '—';

  const number = Number(val);

  if (Number.isNaN(number)) return '—';

  return `Rp. ${new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(number)}`;
};

const formatPriceInput = (value) => {
  if (value === null || value === undefined || value === '') {
    return '';
  }

  const number = Number(
    String(value).replace(/\D/g, '')
  );

  if (!number) {
    return '';
  }

  return new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 0,
  }).format(number);
};

const parsePriceInput = (value) => {
  if (value === null || value === undefined || value === '') {
    return 0;
  }

  const numericValue = String(value).replace(/\D/g, '');

  return Number(numericValue) || 0;
};

const updatePriceField = (field, event) => {
  const numericValue = parsePriceInput(event.target.value);

  pricingForm.value[field] = numericValue;

  event.target.value = formatPriceInput(numericValue);
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
