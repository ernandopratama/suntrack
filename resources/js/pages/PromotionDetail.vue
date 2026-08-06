<template>
  <div v-if="loading" class="space-y-4">
    <div class="h-32 bg-gray-200 rounded-xl animate-pulse"></div>
    <div class="h-64 bg-gray-200 rounded-xl animate-pulse"></div>
  </div>

  <div v-else-if="error">
    <div class="bg-red-50 border border-red-200 p-4 rounded-md text-red-700">{{ error }}</div>
  </div>

  <div v-else-if="promotion">
    <!-- Promotion Summary Card -->
    <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div class="flex-1">
          <div class="flex items-center space-x-3 mb-2">
            <span class="font-mono text-sm font-bold text-gray-500 bg-gray-100 px-3 py-1 rounded-md tracking-wider">
              {{ promotion.code }}
            </span>
            <StatusBadge :status="promotion.status" />
          </div>
          <h1 class="text-2xl font-bold text-gray-900">{{ promotion.name }}</h1>
          <p v-if="promotion.campaign" class="mt-1 text-sm text-gray-500">
            Campaign:
            <router-link :to="`/campaigns/${promotion.campaign.id}`" class="text-blue-600 hover:text-blue-900 font-medium">
              {{ promotion.campaign.name }}
            </router-link>
          </p>
          <p v-else class="mt-1 text-sm text-gray-400 italic">Standalone promotion (not linked to a campaign)</p>
        </div>
        <div class="flex-shrink-0">
          <button @click="isModalOpen = true"
            class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
            Edit Promotion
          </button>
        </div>
      </div>

      <!-- Metrics Row -->
      <dl class="mt-6 grid grid-cols-2 sm:grid-cols-6 gap-4 border-t border-gray-100 pt-5">
        <div>
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Start Date</dt>
          <dd class="mt-1 text-sm font-semibold text-gray-900">{{ promotion.start_date || '—' }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">End Date</dt>
          <dd class="mt-1 text-sm font-semibold text-gray-900">{{ promotion.end_date || '—' }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Products</dt>
          <dd class="mt-1 text-sm font-semibold text-gray-900">{{ promotion.total_products }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Variants</dt>
          <dd class="mt-1 text-sm font-semibold text-gray-900">{{ promotion.total_variants }}</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Promo Stock</dt>
          <dd class="mt-1 text-sm font-semibold text-blue-600">{{ promotion.total_promotion_stock }} units</dd>
        </div>
        <div>
          <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Est. Promo Value</dt>
          <dd class="mt-1 text-sm font-bold text-emerald-600">{{ formatCurrency(promotion.total_estimated_promotion_value) }}</dd>
        </div>
      </dl>
    </div>

    <!-- Tabbed Workspace -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
          <button v-for="tab in tabs" :key="tab.id" @click="currentTab = tab.id"
            :class="[
              currentTab === tab.id
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
              'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors'
            ]">
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <div class="p-6 bg-gray-50 min-h-[380px]">
        <!-- Overview tab -->
        <div v-if="currentTab === 'overview'" class="space-y-4">
          <div class="bg-white p-6 rounded-lg border border-gray-200">
            <h3 class="font-medium text-gray-900 mb-2">Description</h3>
            <p class="text-sm text-gray-600">{{ promotion.description || 'No description provided.' }}</p>
          </div>
        </div>

        <!-- Products tab (live) -->
        <div v-else-if="currentTab === 'products'">
          <PromotionProductPanel :promotion-id="promotion.id" @updated="onProductsUpdated" />
        </div>

        <!-- Pricing tab (live) -->
        <div v-else-if="currentTab === 'pricing'">
          <PromotionPricingPanel :promotion-id="promotion.id" @updated="onProductsUpdated" />
        </div>

        <!-- Approval tab (live - Sprint 6) -->
        <div v-else-if="currentTab === 'approval'">
          <PromotionApprovalPanel :promotion-id="promotion.id" @updated="onProductsUpdated" />
        </div>

        <!-- Comments tab (live - Sprint 6) -->
        <div v-else-if="currentTab === 'comments'">
          <PromotionCommentsPanel :promotion-id="promotion.id" @updated="onProductsUpdated" />
        </div>

        <!-- All other tabs = EmptyState placeholder -->
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
import StatusBadge from '../components/StatusBadge.vue';
import EmptyState from '../components/EmptyState.vue';
import PromotionForm from '../components/PromotionForm.vue';
import PromotionProductPanel from '../components/PromotionProductPanel.vue';
import PromotionPricingPanel from '../components/PromotionPricingPanel.vue';
import PromotionApprovalPanel from '../components/PromotionApprovalPanel.vue';
import PromotionCommentsPanel from '../components/PromotionCommentsPanel.vue';
import { usePromotions } from '../composables/usePromotions';

const route = useRoute();
const { promotion, loading, error, fetchPromotion } = usePromotions();

const currentTab = ref('overview');
const isModalOpen = ref(false);

const tabs = [
  { id: 'overview',    name: 'Overview' },
  { id: 'pricing',     name: 'Pricing Information' },
  { id: 'campaign',    name: 'Campaign Info' },
  { id: 'products',    name: 'Products' },
  { id: 'approval',    name: 'Approval' },
  { id: 'attachments', name: 'Attachments' },
  { id: 'comments',    name: 'Comments' },
  { id: 'timeline',    name: 'Activity Timeline' },
];

const getTabName = (id) => tabs.find(t => t.id === id)?.name || id;

const formatCurrency = (val) => {
  if (val == null) return 'IDR 0';
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
};

onMounted(() => fetchPromotion(route.params.id));

const onSaved = () => {
  isModalOpen.value = false;
  fetchPromotion(route.params.id);
};

const onProductsUpdated = () => {
  // Re-fetch promotion to update metrics (total_products, etc.)
  fetchPromotion(route.params.id);
};
</script>
