<template>
  <div class="space-y-6">
    <!-- Top Metrics Summary Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <!-- Total Variants -->
      <div
        class="rounded-2xl border border-[#D0E7E6] bg-white p-5 shadow-sm transition-all duration-200 hover:shadow-md"
      >
        <div class="flex items-start justify-between">
          <div>
            <span
              class="block text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
            >
              Total Variants
            </span>

            <span
              class="mt-2 block text-2xl font-extrabold text-[#293681]"
            >
              {{ variants.length }}

              <span class="text-xs font-medium text-gray-400">
                items
              </span>
            </span>
          </div>

          <div
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]"
          >
            <i class="fa-solid fa-boxes-stacked text-sm"></i>
          </div>
        </div>
      </div>

      <!-- Total Promo Stock -->
      <div
        class="rounded-2xl border border-[#D0E7E6] bg-white p-5 shadow-sm transition-all duration-200 hover:shadow-md"
      >
        <div class="flex items-start justify-between">
          <div>
            <span
              class="block text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
            >
              Total Promo Stock
            </span>

            <span
              class="mt-2 block text-2xl font-extrabold text-[#4274D9]"
            >
              {{ totalStock }}

              <span class="text-xs font-medium text-gray-400">
                units
              </span>
            </span>
          </div>

          <div
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#4274D9]"
          >
            <i class="fa-solid fa-layer-group text-sm"></i>
          </div>
        </div>
      </div>

      <!-- Estimated Promotion Value -->
      <div
        class="rounded-2xl border border-[#D0E7E6] bg-white p-5 shadow-sm transition-all duration-200 hover:shadow-md"
      >
        <div class="flex items-start justify-between">
          <div class="min-w-0">
            <span
              class="block text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
            >
              Est. Promotion Value
            </span>

            <span
              class="mt-2 block truncate text-xl font-extrabold text-[#293681]"
              :title="formatCurrency(totalEstimatedValue)"
            >
              {{ formatCurrency(totalEstimatedValue) }}
            </span>
          </div>

          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]"
          >
            <i class="fa-solid fa-money-bill-wave text-sm"></i>
          </div>
        </div>
      </div>

      <!-- Total Customer Savings -->
      <div
        class="rounded-2xl border border-[#D0E7E6] bg-white p-5 shadow-sm transition-all duration-200 hover:shadow-md"
      >
        <div class="flex items-start justify-between">
          <div class="min-w-0">
            <span
              class="block text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
            >
              Total Customer Savings
            </span>

            <span
              class="mt-2 block truncate text-xl font-extrabold text-[#4274D9]"
              :title="formatCurrency(totalSavings)"
            >
              {{ formatCurrency(totalSavings) }}
            </span>
          </div>

          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#4274D9]"
          >
            <i class="fa-solid fa-piggy-bank text-sm"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Pricing Analysis & Rules Table -->
    <div
      class="overflow-hidden rounded-2xl border border-[#D0E7E6] bg-white shadow-sm"
    >
      <!-- Table Header -->
      <div
        class="flex flex-col gap-3 border-b border-[#D0E7E6] bg-[#D0E7E6]/30 p-5 sm:flex-row sm:items-center sm:justify-between"
      >
        <div>
          <div class="flex items-center gap-2">
            <span
              class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#4274D9] text-white"
            >
              <i class="fa-solid fa-tags text-xs"></i>
            </span>

            <h3
              class="text-sm font-extrabold uppercase tracking-wider text-[#293681]"
            >
              Promotion Pricing Snapshot & Rules
            </h3>
          </div>

          <p class="mt-2 text-xs leading-relaxed text-gray-500">
            All campaign prices are historical snapshots and do not alter
            master variant records.
          </p>
        </div>

        <span
          class="inline-flex w-fit items-center gap-1.5 rounded-full bg-[#D0E7E6] px-3 py-1.5 text-[10px] font-extrabold text-[#293681]"
        >
          <span
            class="h-1.5 w-1.5 rounded-full bg-[#4274D9]"
          ></span>

          Strict Floor Enforcement Active
        </span>
      </div>

      <!-- Loading -->
      <div
        v-if="loading"
        class="flex items-center justify-center px-6 py-12"
      >
        <div class="flex items-center gap-3">
          <span
            class="h-5 w-5 animate-spin rounded-full border-2 border-[#D0E7E6] border-t-[#4274D9]"
          ></span>

          <span class="text-sm font-medium text-gray-500">
            Loading pricing data...
          </span>
        </div>
      </div>

      <!-- Empty -->
      <EmptyState
        v-else-if="!variants.length"
        title="No pricing data available"
        description="Switch to the Products tab to map product variants to this promotion first."
      />

      <!-- Table -->
      <div
        v-else
        class="overflow-x-auto"
      >
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
                Master Normal
              </th>

              <th
                class="whitespace-nowrap px-5 py-3.5 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
              >
                Master Bottom Floor
              </th>

              <th
                class="whitespace-nowrap px-5 py-3.5 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
              >
                Campaign Price
              </th>

              <th
                class="whitespace-nowrap px-5 py-3.5 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
              >
                Discount Price
              </th>

              <th
                class="whitespace-nowrap px-5 py-3.5 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
              >
                Margin Protection
              </th>

              <th
                class="whitespace-nowrap px-5 py-3.5 text-right text-[10px] font-extrabold uppercase tracking-wider text-gray-500"
              >
                Action
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
                    <div
                      class="truncate text-sm font-bold text-gray-900"
                    >
                      {{ v.name }}
                    </div>

                    <div class="mt-0.5">
                      <span
                        class="rounded-md bg-gray-100 px-1.5 py-0.5 font-mono text-[10px] font-semibold text-gray-500"
                      >
                        {{ v.code }}
                      </span>
                    </div>
                  </div>
                </div>
              </td>

              <!-- Master Normal -->
              <td class="whitespace-nowrap px-5 py-4">
                <span class="text-sm font-semibold text-gray-600">
                  {{ formatCurrency(v.promotion_pricing?.normal_price_snapshot) }}
                </span>
              </td>

              <!-- Master Bottom Floor -->
              <td class="whitespace-nowrap px-5 py-4">
                <span
                  class="inline-flex rounded-lg bg-amber-50 px-2.5 py-1.5 text-sm font-bold text-amber-700"
                >
                  {{ formatCurrency(v.promotion_pricing?.bottom_price) }}
                </span>
              </td>

              <!-- Campaign Price -->
              <td class="whitespace-nowrap px-5 py-4">
                <span
                  class="inline-flex rounded-lg bg-[#D0E7E6]/60 px-2.5 py-1.5 text-sm font-extrabold text-[#293681]"
                >
                  {{ formatCurrency(v.promotion_pricing?.campaign_price) }}
                </span>
              </td>

              <!-- Discount Price -->
              <td class="whitespace-nowrap px-5 py-4">
                <span
                  class="inline-flex rounded-lg bg-[#95CCDD]/30 px-2.5 py-1.5 text-sm font-extrabold text-[#4274D9]"
                >
                  {{ formatCurrency(v.promotion_pricing?.discount_price) }}
                </span>
              </td>

              <!-- Margin Protection -->
              <td class="px-5 py-4">
                <span
                  v-if="
                    v.promotion_pricing?.campaign_price >=
                    v.promotion_pricing?.bottom_price
                  "
                  class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1.5 text-[10px] font-extrabold text-emerald-700"
                >
                  <span
                    class="h-1.5 w-1.5 rounded-full bg-emerald-500"
                  ></span>

                  Above Floor
                </span>

                <span
                  v-else
                  class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1.5 text-[10px] font-extrabold text-rose-700"
                >
                  <span
                    class="h-1.5 w-1.5 rounded-full bg-rose-500"
                  ></span>

                  Below Floor
                </span>
              </td>

              <!-- Action -->
              <td class="px-5 py-4 text-right">
                <button
                  v-if="$can('promotion.update')"
                  @click="openEditPricing(v)"
                  type="button"
                  class="inline-flex items-center gap-1.5 rounded-lg border border-[#95CCDD]/60 bg-white px-3 py-2 text-xs font-bold text-[#4274D9] transition-all duration-200 hover:border-[#4274D9] hover:bg-[#D0E7E6]/40 hover:text-[#293681]"
                >
                  <i class="fa-solid fa-sliders text-[10px]"></i>
                  Adjust Pricing
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Edit Pricing Modal -->
    <ModalForm
      :is-open="isModalOpen"
      title="Adjust Promotion Pricing"
      @close="isModalOpen = false"
    >
      <form
        id="promo-pricing-form"
        @submit.prevent="submitPricing"
        class="space-y-5"
      >
        <!-- Validation Error -->
        <div
          v-if="typeof error === 'object' && error"
          class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
        >
          <div
            v-for="(msgs, field) in error"
            :key="field"
            class="flex items-start gap-2"
          >
            <i
              class="fa-solid fa-circle-exclamation mt-0.5 text-xs"
            ></i>

            <span>{{ msgs[0] }}</span>
          </div>
        </div>

        <!-- String Error -->
        <div
          v-if="typeof error === 'string' && error"
          class="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
        >
          <i
            class="fa-solid fa-circle-exclamation mt-0.5 text-xs"
          ></i>

          <span>{{ error }}</span>
        </div>

        <!-- Selected Variant Info -->
        <div
          class="rounded-2xl border border-[#D0E7E6] bg-[#D0E7E6]/30 p-4"
        >
          <div class="mb-3 flex items-center gap-2">
            <div
              class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#4274D9] text-white"
            >
              <i class="fa-solid fa-box text-xs"></i>
            </div>

            <div>
              <p
                class="text-xs font-extrabold uppercase tracking-wider text-[#293681]"
              >
                Selected Variant
              </p>

              <p class="text-[11px] text-gray-500">
                Current master pricing information
              </p>
            </div>
          </div>

          <div class="space-y-2 text-xs">
            <div class="flex items-center justify-between gap-4">
              <span class="font-medium text-gray-500">
                Variant
              </span>

              <span class="text-right font-bold text-gray-800">
                {{ selectedVariant?.code }} -
                {{ selectedVariant?.name }}
              </span>
            </div>

            <div class="flex items-center justify-between gap-4">
              <span class="font-medium text-gray-500">
                Master Normal Price
              </span>

              <span class="font-bold text-[#293681]">
                {{
                  formatCurrency(
                    selectedVariant?.promotion_pricing
                      ?.normal_price_snapshot
                  )
                }}
              </span>
            </div>

            <div class="flex items-center justify-between gap-4">
              <span class="font-medium text-gray-500">
                Master Bottom Floor
              </span>

              <span class="font-bold text-amber-700">
                {{
                  formatCurrency(
                    selectedVariant?.promotion_pricing
                      ?.bottom_price
                  )
                }}
              </span>
            </div>

            <div class="flex items-center justify-between gap-4">
              <span class="font-medium text-gray-500">
                Available Current Stock
              </span>

              <span class="font-bold text-gray-800">
                {{ selectedVariant?.current_stock ?? 0 }} units
              </span>
            </div>
          </div>
        </div>

        <!-- Pricing -->
        <div>
          <div class="mb-3 flex items-center gap-2">
            <span
              class="h-5 w-1 rounded-full bg-[#4274D9]"
            ></span>

            <h4
              class="text-xs font-extrabold uppercase tracking-wider text-[#293681]"
            >
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
                  :value="formatPriceInput(form.campaign_price)"
                  @input="
                    updatePriceField(
                      'campaign_price',
                      $event
                    )
                  "
                  required
                  class="block w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-12 pr-3.5 text-sm font-bold text-[#4274D9] shadow-sm outline-none transition-all placeholder:text-gray-400 hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30"
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
                  :value="formatPriceInput(form.discount_price)"
                  @input="
                    updatePriceField(
                      'discount_price',
                      $event
                    )
                  "
                  required
                  class="block w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-12 pr-3.5 text-sm font-bold text-[#4274D9] shadow-sm outline-none transition-all placeholder:text-gray-400 hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30"
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
                v-model="form.promotion_stock"
                required
                class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30"
              />
            </div>

            <!-- Purchase Limit -->
            <div>
              <label
                class="mb-1.5 block text-xs font-bold text-gray-600"
              >
                Purchase Limit
              </label>

              <input
                type="number"
                min="0"
                v-model="form.purchase_limit"
                class="block w-full rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-sm font-medium text-gray-700 shadow-sm outline-none transition-all hover:border-[#95CCDD] focus:border-[#4274D9] focus:ring-4 focus:ring-[#95CCDD]/30"
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
            v-model="form.notes"
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
            @click="isModalOpen = false"
            class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-extrabold text-gray-600 shadow-sm transition-all duration-200 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
          >
            Cancel
          </button>

          <button
            v-if="$can('promotion.update')"
            type="submit"
            form="promo-pricing-form"
            :disabled="saving"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#4274D9] px-5 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#293681] hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
          >
            <i
              v-if="saving"
              class="fa-solid fa-spinner animate-spin text-[10px]"
            ></i>

            <i
              v-else
              class="fa-solid fa-floppy-disk text-[10px]"
            ></i>

            {{ saving ? 'Saving...' : 'Update Pricing' }}
          </button>
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
  promotionId: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['updated']);

const {
  variants,
  loading,
  fetchPromotionVariants,
  addVariantToPromotion,
} = useProducts();

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

/*
|--------------------------------------------------------------------------
| Currency Helpers
|--------------------------------------------------------------------------
*/

/**
 * Format number for display.
 *
 * Example:
 * 100000 -> Rp. 100.000
 * 1500000 -> Rp. 1.500.000
 */
const formatCurrency = (val) => {
  if (val === null || val === undefined || val === '') {
    return '—';
  }

  const number = Number(val);

  if (Number.isNaN(number)) {
    return '—';
  }

  return `Rp. ${new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(number)}`;
};

/**
 * Format price input without currency prefix.
 *
 * Example:
 * 100000 -> 100.000
 * 1500000 -> 1.500.000
 */
const formatPriceInput = (value) => {
  if (
    value === null ||
    value === undefined ||
    value === ''
  ) {
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

/**
 * Convert formatted input back to integer.
 *
 * Example:
 * "100.000" -> 100000
 * "1.500.000" -> 1500000
 */
const parsePriceInput = (value) => {
  if (
    value === null ||
    value === undefined ||
    value === ''
  ) {
    return 0;
  }

  const numericValue = String(value).replace(/\D/g, '');

  return Number(numericValue) || 0;
};

/**
 * Update price field while keeping
 * the Vue state as a numeric value.
 */
const updatePriceField = (field, event) => {
  const numericValue = parsePriceInput(
    event.target.value
  );

  form.value[field] = numericValue;

  event.target.value =
    numericValue > 0
      ? formatPriceInput(numericValue)
      : '';
};

/*
|--------------------------------------------------------------------------
| Metrics
|--------------------------------------------------------------------------
*/

const totalStock = computed(() => {
  return variants.value.reduce(
    (acc, v) =>
      acc +
      Number(
        v.promotion_pricing?.promotion_stock || 0
      ),
    0
  );
});

const totalEstimatedValue = computed(() => {
  return variants.value.reduce((acc, v) => {
    const campaignPrice = Number(
      v.promotion_pricing?.campaign_price || 0
    );

    const stock = Number(
      v.promotion_pricing?.promotion_stock || 0
    );

    return acc + campaignPrice * stock;
  }, 0);
});

const totalSavings = computed(() => {
  return variants.value.reduce((acc, v) => {
    const normal = Number(
      v.promotion_pricing?.normal_price_snapshot || 0
    );

    const campaign = Number(
      v.promotion_pricing?.campaign_price || 0
    );

    const stock = Number(
      v.promotion_pricing?.promotion_stock || 0
    );

    const diff = Math.max(
      0,
      normal - campaign
    );

    return acc + diff * stock;
  }, 0);
});

/*
|--------------------------------------------------------------------------
| Load Data
|--------------------------------------------------------------------------
*/

onMounted(() => {
  fetchPromotionVariants(props.promotionId);
});

/*
|--------------------------------------------------------------------------
| Edit Pricing
|--------------------------------------------------------------------------
*/

const openEditPricing = (v) => {
  selectedVariant.value = v;

  form.value = {
    variant_id: v.id,

    campaign_price:
      Number(
        v.promotion_pricing?.campaign_price || 0
      ),

    bottom_price:
      Number(
        v.promotion_pricing?.bottom_price || 0
      ),

    discount_price:
      Number(
        v.promotion_pricing?.discount_price || 0
      ),

    promotion_stock:
      Number(
        v.promotion_pricing?.promotion_stock || 0
      ),

    purchase_limit:
      Number(
        v.promotion_pricing?.purchase_limit || 0
      ),

    notes:
      v.promotion_pricing?.notes || '',
  };

  error.value = null;
  isModalOpen.value = true;
};

/*
|--------------------------------------------------------------------------
| Submit Pricing
|--------------------------------------------------------------------------
*/

const submitPricing = async () => {
  saving.value = true;
  error.value = null;

  try {
    const payload = {
      ...form.value,

      campaign_price: Number(
        form.value.campaign_price || 0
      ),

      bottom_price: Number(
        form.value.bottom_price || 0
      ),

      discount_price: Number(
        form.value.discount_price || 0
      ),

      promotion_stock: Number(
        form.value.promotion_stock || 0
      ),

      purchase_limit: Number(
        form.value.purchase_limit || 0
      ),
    };

    const res =
      await addVariantToPromotion(
        props.promotionId,
        payload
      );

    if (res) {
      isModalOpen.value = false;

      await fetchPromotionVariants(
        props.promotionId
      );

      emit('updated');
    } else {
      const { error: err } =
        useProducts();

      error.value =
        err.value ||
        'Failed to update pricing.';
    }
  } catch (e) {
    console.error(
      'Failed to update pricing:',
      e
    );

    error.value =
      e?.response?.data?.message ||
      e?.message ||
      'Failed to update pricing.';
  } finally {
    saving.value = false;
  }
};
</script>
