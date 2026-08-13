<template>
  <!-- Loading -->
  <div v-if="loading" class="space-y-6">
    <div
      class="h-40 rounded-2xl border border-gray-100 bg-white shadow-sm animate-pulse"
    ></div>

    <div
      class="h-96 rounded-2xl border border-gray-100 bg-white shadow-sm animate-pulse"
    ></div>
  </div>

  <!-- Error -->
  <div v-else-if="error">
    <div
      class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700"
    >
      {{ error }}
    </div>
  </div>

  <!-- Content -->
  <div v-else-if="promotion" class="space-y-6">

    <!-- Promotion Header -->
    <div
      class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
      <!-- Top Accent -->
      <div class="h-1.5 bg-[#293681]"></div>

      <div class="p-5 sm:p-6">

        <!-- Header Top -->
        <div
          class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
        >
          <!-- Information -->
          <div class="min-w-0 flex-1">

            <!-- Back + Code + Status -->
            <div class="flex flex-wrap items-center gap-2.5">

              <!-- Back Button -->
              <router-link
                to="/promotions"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-600 shadow-sm transition-all duration-200 hover:border-[#95CCDD] hover:bg-[#D0E7E6]/30 hover:text-[#293681]"
              >
                <svg
                  class="h-3.5 w-3.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 19l-7-7 7-7"
                  />
                </svg>

                <span>Back to Promotions</span>
              </router-link>

              <!-- Promotion Code -->
              <span
                class="inline-flex items-center rounded-lg bg-[#D0E7E6] px-3 py-2 font-mono text-[11px] font-extrabold tracking-wider text-[#293681]"
              >
                {{ promotion.code }}
              </span>

              <!-- Status -->
              <span
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold"
                :class="{
                  'bg-gray-100 text-gray-600':
                    promotion.status === 'Draft',

                  'bg-amber-100 text-amber-700':
                    promotion.status === 'Waiting Approval',

                  'bg-blue-100 text-blue-700':
                    promotion.status === 'Approved',

                  'bg-emerald-100 text-emerald-700':
                    promotion.status === 'Running',

                  'bg-indigo-100 text-indigo-700':
                    promotion.status === 'Finished',

                  'bg-rose-100 text-rose-700':
                    promotion.status === 'Cancelled'
                }"
              >
                <span
                  class="h-1.5 w-1.5 rounded-full bg-current"
                ></span>

                {{ promotion.status }}
              </span>
            </div>

            <!-- Title -->
            <h1
              class="mt-4 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl"
            >
              {{ promotion.name }}
            </h1>

            <!-- Campaign -->
            <div v-if="promotion.campaign" class="mt-2 flex items-center gap-2">
              <span class="text-xs font-semibold text-gray-400">
                Campaign
              </span>

              <router-link
                :to="`/campaigns/${promotion.campaign.id}`"
                class="inline-flex items-center gap-1.5 text-sm font-bold text-[#4274D9] transition-colors hover:text-[#293681]"
              >
                {{ promotion.campaign.name }}

                <svg
                  class="h-3.5 w-3.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M13 5h6m0 0v6m0-6L10 14"
                  />
                </svg>
              </router-link>
            </div>

            <p
              v-else
              class="mt-2 text-xs italic text-gray-400"
            >
              Standalone promotion · Not linked to a campaign
            </p>
          </div>

          <!-- Edit Button -->
          <div class="shrink-0">
            <button
              type="button"
              @click="isModalOpen = true"
              class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#293681] px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#4274D9] hover:shadow-md sm:w-auto"
            >
              <span
                class="flex h-5 w-5 items-center justify-center rounded-md bg-white/15"
              >
                <svg
                  class="h-3 w-3"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"
                  />
                </svg>
              </span>

              <span>Edit Promotion</span>
            </button>
          </div>
        </div>

        <!-- Metrics -->
        <div
          class="mt-6 grid grid-cols-2 gap-3 border-t border-gray-100 pt-5 sm:grid-cols-3 lg:grid-cols-6"
        >
          <!-- Start Date -->
          <div
            class="rounded-xl border border-gray-100 bg-gray-50/70 p-3.5"
          >
            <dt
              class="text-[10px] font-bold uppercase tracking-wider text-gray-400"
            >
              Start Date
            </dt>

            <dd
              class="mt-1.5 text-sm font-bold text-gray-800"
            >
              {{ promotion.start_date || '—' }}
            </dd>
          </div>

          <!-- End Date -->
          <div
            class="rounded-xl border border-gray-100 bg-gray-50/70 p-3.5"
          >
            <dt
              class="text-[10px] font-bold uppercase tracking-wider text-gray-400"
            >
              End Date
            </dt>

            <dd
              class="mt-1.5 text-sm font-bold text-gray-800"
            >
              {{ promotion.end_date || '—' }}
            </dd>
          </div>

          <!-- Products -->
          <div
            class="rounded-xl border border-gray-100 bg-gray-50/70 p-3.5"
          >
            <dt
              class="text-[10px] font-bold uppercase tracking-wider text-gray-400"
            >
              Total Products
            </dt>

            <dd
              class="mt-1.5 text-sm font-bold text-[#293681]"
            >
              {{ promotion.total_products }}
            </dd>
          </div>

          <!-- Variants -->
          <div
            class="rounded-xl border border-gray-100 bg-gray-50/70 p-3.5"
          >
            <dt
              class="text-[10px] font-bold uppercase tracking-wider text-gray-400"
            >
              Total Variants
            </dt>

            <dd
              class="mt-1.5 text-sm font-bold text-[#293681]"
            >
              {{ promotion.total_variants }}
            </dd>
          </div>

          <!-- Stock -->
          <div
            class="rounded-xl border border-[#95CCDD]/40 bg-[#D0E7E6]/25 p-3.5"
          >
            <dt
              class="text-[10px] font-bold uppercase tracking-wider text-[#4274D9]"
            >
              Promo Stock
            </dt>

            <dd
              class="mt-1.5 text-sm font-extrabold text-[#293681]"
            >
              {{ promotion.total_promotion_stock }}
              <span class="text-xs font-semibold text-[#4274D9]">
                units
              </span>
            </dd>
          </div>

          <!-- Estimated Value -->
          <div
            class="rounded-xl border border-[#95CCDD]/40 bg-[#D0E7E6]/25 p-3.5"
          >
            <dt
              class="text-[10px] font-bold uppercase tracking-wider text-[#4274D9]"
            >
              Est. Promo Value
            </dt>

            <dd
              class="mt-1.5 truncate text-sm font-extrabold text-[#293681]"
              :title="formatCurrency(promotion.total_estimated_promotion_value)"
            >
              {{ formatCurrency(promotion.total_estimated_promotion_value) }}
            </dd>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabbed Workspace -->
    <div
      class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >

      <!-- Tabs -->
      <div class="border-b border-gray-200 bg-white">
        <nav
          class="flex gap-1 overflow-x-auto px-4 sm:px-6"
          aria-label="Promotion Tabs"
        >
          <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            @click="currentTab = tab.id"
            :class="[
              currentTab === tab.id
                ? 'border-[#4274D9] text-[#293681]'
                : 'border-transparent text-gray-400 hover:border-[#95CCDD] hover:text-[#4274D9]',
              'relative whitespace-nowrap border-b-2 px-2 py-4 text-xs font-bold transition-all duration-200'
            ]"
          >
            {{ tab.name }}

            <span
              v-if="currentTab === tab.id"
              class="absolute bottom-[-2px] left-1/2 h-0.5 w-8 -translate-x-1/2 rounded-full bg-[#4274D9]"
            ></span>
          </button>
        </nav>
      </div>

      <!-- Workspace -->
      <div class="min-h-[380px] bg-[#D0E7E6]/20 p-4 sm:p-6">

        <!-- Overview -->
        <div
          v-if="currentTab === 'overview'"
          class="space-y-4"
        >
          <div
            class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"
          >
            <div class="mb-5 flex items-center gap-3">
              <div
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]"
              >
                <svg
                  class="h-4.5 w-4.5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M4 6h16M4 12h16M4 18h10"
                  />
                </svg>
              </div>

              <div>
                <h3
                  class="text-sm font-extrabold text-gray-900"
                >
                  Promotion Description
                </h3>

                <p class="text-xs text-gray-400">
                  Additional information about this promotion
                </p>
              </div>
            </div>

            <div
              class="rounded-xl border border-gray-100 bg-gray-50/70 p-4"
            >
              <p
                class="whitespace-pre-wrap text-sm leading-6 text-gray-600"
              >
                {{ promotion.description || 'No description provided.' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Products -->
        <div v-else-if="currentTab === 'products'">
          <PromotionProductPanel
            :promotion-id="promotion.id"
            @updated="onProductsUpdated"
          />
        </div>

        <!-- Pricing -->
        <div v-else-if="currentTab === 'pricing'">
          <PromotionPricingPanel
            :promotion-id="promotion.id"
            @updated="onProductsUpdated"
          />
        </div>

        <!-- Approval -->
        <div v-else-if="currentTab === 'approval'">
          <PromotionApprovalPanel
            :promotion-id="promotion.id"
            @updated="onProductsUpdated"
          />
        </div>

        <!-- Comments -->
        <div v-else-if="currentTab === 'comments'">
          <PromotionCommentsPanel
            :promotion-id="promotion.id"
            @updated="onProductsUpdated"
          />
        </div>

        <!-- Placeholder -->
        <div v-else>
          <EmptyState
            :title="getTabName(currentTab) + ' — Coming Soon'"
            :description="`The ${getTabName(currentTab)} module will be available in a future sprint.`"
          />
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <PromotionForm
      :is-open="isModalOpen"
      :promotion="promotion"
      @close="isModalOpen = false"
      @saved="onSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import EmptyState from '../components/EmptyState.vue';
import PromotionForm from '../components/PromotionForm.vue';
import PromotionProductPanel from '../components/PromotionProductPanel.vue';
import PromotionPricingPanel from '../components/PromotionPricingPanel.vue';
import PromotionApprovalPanel from '../components/PromotionApprovalPanel.vue';
import PromotionCommentsPanel from '../components/PromotionCommentsPanel.vue';
import { usePromotions } from '../composables/usePromotions';

const route = useRoute();

const {
  promotion,
  loading,
  error,
  fetchPromotion
} = usePromotions();

const currentTab = ref('overview');
const isModalOpen = ref(false);

const tabs = [
  { id: 'overview', name: 'Overview' },
  { id: 'pricing', name: 'Pricing Information' },
  { id: 'campaign', name: 'Campaign Info' },
  { id: 'products', name: 'Products' },
  { id: 'approval', name: 'Approval' },
  { id: 'attachments', name: 'Attachments' },
  { id: 'comments', name: 'Comments' },
  { id: 'timeline', name: 'Activity Timeline' },
];

const getTabName = (id) =>
  tabs.find(t => t.id === id)?.name || id;

const formatCurrency = (val) => {
  if (val == null) return 'IDR 0';

  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val);
};

onMounted(() => {
  fetchPromotion(route.params.id);
});

const onSaved = () => {
  isModalOpen.value = false;
  fetchPromotion(route.params.id);
};

const onProductsUpdated = () => {
  fetchPromotion(route.params.id);
};
</script>