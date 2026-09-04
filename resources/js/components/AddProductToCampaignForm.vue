<template>
  <div class="p-1">
    <form
      id="add-product-form"
      @submit.prevent="submitForm"
      class="space-y-5"
    >
      <!-- Error -->
      <div
        v-if="error"
        class="flex items-start gap-3 rounded-2xl border p-4"
        style="background: #fff5f5; border-color: #fecaca; color: #b91c1c"
      >
        <div
          class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl"
          style="background: #fee2e2"
        >
          <svg
            class="h-4 w-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M12 9v3.5m0 3h.01M10.29 4.86l-7.1 12.3A2 2 0 0 0 4.92 20h14.16a2 2 0 0 0 1.73-2.84l-7.1-12.3a2 2 0 0 0-3.42 0Z"
            />
          </svg>
        </div>

        <div>
          <p class="text-sm font-semibold">Unable to continue</p>
          <p class="mt-0.5 text-xs opacity-90">{{ error }}</p>
        </div>
      </div>

      <!-- Basic Information -->
      <div
        class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm"
      >
        <div
          class="flex items-center gap-4 border-b border-slate-100 px-5 py-4"
        >
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
            style="background: #d0e7e6"
          >
            <svg
              class="h-5 w-5"
              style="color: #293681"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="1.8"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h6l5 5v11a2 2 0 0 1-2 2Z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M13 3v5h5"
              />
            </svg>
          </div>

          <div>
            <h3
              class="text-sm font-bold"
              style="color: #293681"
            >
              Campaign Product
            </h3>

            <p class="mt-0.5 text-xs text-slate-500">
              Select the promotion and product you want to add.
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-5 p-5 md:grid-cols-2">
          <!-- Promotion -->
          <div>
            <label class="form-label">
              Promotion
              <span class="text-red-500">*</span>
            </label>

            <div class="relative">
              <select
                v-model="form.promotion_id"
                required
                class="form-input appearance-none pr-10"
              >
                <option value="">-- Select Promotion --</option>

                <option
                  v-for="p in promotions"
                  :key="p.id"
                  :value="p.id"
                >
                  {{ p.code }} - {{ p.name }}
                </option>
              </select>

              <svg
                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="m6 9 6 6 6-6"
                />
              </svg>
            </div>
          </div>

          <!-- Product -->
          <div>
            <label class="form-label">
              Product
              <span class="text-red-500">*</span>
            </label>

            <div class="relative">
              <select
                v-model="selectedProductId"
                @change="loadVariants"
                required
                class="form-input appearance-none pr-10"
              >
                <option value="">-- Select Product --</option>

                <option
                  v-for="p in allProducts"
                  :key="p.id"
                  :value="p.id"
                >
                  {{ p.code }} - {{ p.name }}
                </option>
              </select>

              <svg
                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="m6 9 6 6 6-6"
                />
              </svg>
            </div>
          </div>

          <!-- Variant -->
          <div
            v-if="availableVariants.length"
            class="md:col-span-2"
          >
            <label class="form-label">
              Variant
              <span class="text-red-500">*</span>
            </label>

            <div class="relative">
              <select
                v-model="form.variant_id"
                required
                class="form-input appearance-none pr-10"
              >
                <option value="">-- Select Variant --</option>

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

              <svg
                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="m6 9 6 6 6-6"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Pricing -->
      <div
        v-if="form.variant_id"
        class="overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-sm"
      >
        <div
          class="flex items-center gap-4 border-b border-slate-100 px-5 py-4"
        >
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
            style="background: #d0e7e6"
          >
            <svg
              class="h-5 w-5"
              style="color: #293681"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="1.8"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"
              />
            </svg>
          </div>

          <div>
            <h3
              class="text-sm font-bold"
              style="color: #293681"
            >
              Pricing & Campaign
            </h3>

            <p class="mt-0.5 text-xs text-slate-500">
              Configure pricing, discount, stock, and purchase limits.
            </p>
          </div>
        </div>

        <div class="p-5">
          <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <!-- Normal Price -->
            <div>
              <label class="form-label">
                Normal Price
              </label>

              <div
                class="flex h-11 items-center rounded-xl border px-3 text-sm font-semibold"
                style="
                  background: #f1f8f8;
                  border-color: #d0e7e6;
                  color: #293681;
                "
              >
                {{ formatCurrency(selectedVariantNormalPrice) }}
              </div>
            </div>

            <!-- Campaign Price -->
            <div>
              <label class="form-label">
                Campaign Price
                <span class="text-red-500">*</span>
              </label>

              <input
                type="number"
                min="0"
                step="1"
                v-model="form.campaign_price"
                required
                class="form-input"
              />
            </div>

            <!-- Discount Price -->
            <div>
              <label class="form-label">
                Discount Price
                <span class="text-red-500">*</span>
              </label>

              <input
                type="number"
                min="0"
                step="1"
                v-model="form.discount_price"
                required
                class="form-input"
              />
            </div>

            <!-- Discount -->
            <div>
              <label class="form-label">
                Discount %
              </label>

              <div
                class="mt-1 flex h-11 items-center"
              >
                <span
                  v-if="discountPercent > 0"
                  class="inline-flex items-center rounded-xl px-3 py-2 text-sm font-bold"
                  style="
                    background: #d0e7e6;
                    color: #293681;
                  "
                >
                  {{ discountPercent }}% OFF
                </span>

                <span
                  v-else
                  class="text-sm font-medium text-slate-400"
                >
                  No discount
                </span>
              </div>
            </div>

            <!-- Bottom Price -->
            <div>
              <label class="form-label">
                Bottom Price
              </label>

              <input
                type="number"
                min="0"
                step="1"
                v-model="form.bottom_price"
                class="form-input"
              />
            </div>

            <!-- Promotion Stock -->
            <div>
              <label class="form-label">
                Promotion Stock
                <span class="text-red-500">*</span>
              </label>

              <input
                type="number"
                min="0"
                v-model="form.promotion_stock"
                required
                class="form-input"
              />
            </div>

            <!-- Purchase Limit -->
            <div>
              <label class="form-label">
                Purchase Limit
              </label>

              <input
                type="number"
                min="0"
                v-model="form.purchase_limit"
                class="form-input"
              />
            </div>

            <!-- Notes -->
            <div class="sm:col-span-2">
              <label class="form-label">
                Notes
              </label>

              <textarea
                v-model="form.notes"
                rows="3"
                placeholder="Add notes for this campaign product..."
                class="form-input resize-none"
              ></textarea>
            </div>
          </div>

          <!-- Summary -->
          <div
            class="mt-6 rounded-2xl border p-4"
            style="
              background: #f1f8f8;
              border-color: #d0e7e6;
            "
          >
            <div class="flex items-start gap-3">
              <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                style="background: #95ccdd"
              >
                <svg
                  class="h-4 w-4"
                  style="color: #293681"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M13 16h-1v-4h-1m1-4h.01M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"
                  />
                </svg>
              </div>

              <div>
                <p
                  class="text-xs font-bold uppercase tracking-wide"
                  style="color: #293681"
                >
                  Campaign Pricing Summary
                </p>

                <div
                  class="mt-2 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm"
                >
                  <div>
                    <span class="text-slate-400">Normal</span>
                    <span class="ml-1 font-semibold text-slate-700">
                      {{ formatCurrency(selectedVariantNormalPrice) }}
                    </span>
                  </div>

                  <div>
                    <span class="text-slate-400">Discount</span>
                    <span
                      class="ml-1 font-bold"
                      style="color: #4274d9"
                    >
                      {{ discountPercent }}%
                    </span>
                  </div>

                  <div>
                    <span class="text-slate-400">Stock</span>
                    <span class="ml-1 font-semibold text-slate-700">
                      {{ form.promotion_stock || 0 }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import api from "../utils/api";

const props = defineProps({
  campaignId: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(["close", "saved"]);

const promotions = ref([]);
const allProducts = ref([]);
const availableVariants = ref([]);
const selectedProductId = ref("");
const submitting = ref(false);
const error = ref(null);

const form = ref({
  promotion_id: "",
  variant_id: "",
  campaign_price: 0,
  bottom_price: 0,
  discount_price: 0,
  promotion_stock: 0,
  purchase_limit: 0,
  notes: "",
});

const formatCurrency = (val) => {
  if (val == null) return "\u2014";

  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(val);
};

const selectedVariantNormalPrice = computed(() => {
  const v = availableVariants.value.find(
    (v) => v.id === form.value.variant_id
  );

  return v?.normal_price || 0;
});

const discountPercent = computed(() => {
  const normal = selectedVariantNormalPrice.value;
  const discount = Number(form.value.discount_price) || 0;

  if (normal <= 0) return 0;

  return Math.round(((normal - discount) / normal) * 100);
});

onMounted(async () => {
  try {
    const r1 = await api.get("/admin/promotions", {
      params: {
        campaign_id: props.campaignId,
        per_page: 100,
      },
    });

    promotions.value = r1.data.data?.promotions?.data || [];

    const r2 = await api.get("/admin/products", {
      params: {
        per_page: 200,
      },
    });

    allProducts.value = r2.data.data?.products?.data || [];
  } catch (e) {
    error.value = "Failed to load data.";
  }
});

const loadVariants = async () => {
  form.value.variant_id = "";
  availableVariants.value = [];

  if (!selectedProductId.value) return;

  try {
    const r = await api.get(
      "/admin/products/" + selectedProductId.value + "/variants",
      {
        params: {
          per_page: 200,
        },
      }
    );

    availableVariants.value =
      r.data.data?.variants?.data || [];
  } catch (e) {
    error.value = "Failed to load variants.";
  }
};

const submitForm = async () => {
  submitting.value = true;
  error.value = null;

  try {
    await api.post(
      "/admin/promotions/" + form.value.promotion_id + "/variants",
      {
        variant_id: form.value.variant_id,
        campaign_price: Number(form.value.campaign_price) || 0,
        bottom_price: Number(form.value.bottom_price) || 0,
        discount_price: Number(form.value.discount_price) || 0,
        promotion_stock: Number(form.value.promotion_stock) || 0,
        purchase_limit: Number(form.value.purchase_limit) || 0,
        notes: form.value.notes,
      }
    );

    emit("saved");
    emit("close");
  } catch (e) {
    error.value =
      e.response?.data?.message || "Failed to save.";
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
.form-label {
  display: block;
  font-size: 0.8125rem;
  line-height: 1.25rem;
  font-weight: 600;
  color: var(--ui-content-soft);
}

.form-input {
  display: block;
  width: 100%;
  margin-top: 0.375rem;
  min-height: 2.75rem;
  border-radius: 0.75rem;
  border: 1px solid var(--ui-border);
  background: var(--ui-surface);
  padding: 0.625rem 0.875rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: var(--ui-content);
  outline: none;
  transition:
    border-color 150ms ease,
    box-shadow 150ms ease,
    background-color 150ms ease;
}

.form-input:hover {
  border-color: #95ccdd;
}

.form-input:focus {
  border-color: #4274d9;
  box-shadow: 0 0 0 3px rgba(66, 116, 217, 0.12);
}

.form-input::placeholder {
  color: #94a3b8;
}
</style>
