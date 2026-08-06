<template>
  <div v-if="loading" class="animate-pulse space-y-4">
    <div class="h-8 bg-gray-200 rounded w-1/4"></div>
    <div class="h-64 bg-gray-200 rounded w-full"></div>
  </div>
  
  <div v-else-if="error">
    <div class="bg-red-50 p-4 rounded-md text-red-700">{{ error }}</div>
  </div>
  
  <div v-else-if="campaign">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-start">
      <div>
        <div class="flex items-center space-x-3">
          <h1 class="text-2xl font-bold text-gray-900">{{ campaign.name }}</h1>
          <router-link to="/campaigns" class="ml-3 text-sm text-blue-600 hover:text-blue-800 font-medium">&larr; Back to Campaigns</router-link>
          <StatusBadge :status="campaign.status" />
        </div>
        <p class="mt-1 text-sm text-gray-500">
          Duration: {{ campaign.start_date || 'TBD' }} to {{ campaign.end_date || 'TBD' }}
        </p>
      </div>
      <div>
        <button @click="openEditModal" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition-colors">
          Edit Campaign
        </button>
      </div>
    </div>

    <!-- Tabs Architecture -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-lg overflow-hidden">
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
          <button v-for="tab in tabs" :key="tab.id" @click="currentTab = tab.id"
            :class="[currentTab === tab.id ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors']"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>
      
      <div class="p-6 bg-gray-50 min-h-[400px]">
        
        <!-- Overview Tab -->
        <div v-if="currentTab === 'overview'" class="space-y-6">
          <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Campaign Details</h3>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
              <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500">Description</dt>
                <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ campaign.description || 'No description provided.' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Deadline</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ campaign.deadline || 'None' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Person in Charge (PIC)</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ campaign.pic?.name || 'Unassigned' }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Tasks Tab -->
        <div v-if="currentTab === 'tasks'">
          <div class="mb-4 flex justify-between items-center">
            <p class="text-sm text-gray-500">Tasks for this campaign.</p>
            <button @click="openCreateTaskModal"
              class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
              + Add Task
            </button>
          </div>
          <div v-if="tasksLoading" class="text-sm text-gray-500">Loading...</div>
          <div v-else-if="!tasks.length">
            <EmptyState title="No tasks yet" description="Add a task to this campaign to get started." />
          </div>
          <ul v-else class="divide-y divide-gray-200 bg-white rounded-lg border border-gray-200">
            <li v-for="t in tasks" :key="t.id" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 transition-colors">
              <div class="flex items-center space-x-3">
                <span class="text-sm font-medium text-gray-900">{{ t.name }}</span>
                <span v-if="t.requires_visual" class="text-xs text-gray-400">(visual)</span>
              </div>
              <div class="flex items-center space-x-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="taskStatusClass(t.progress_status)"
                >{{ taskStatusLabel(t.progress_status) }}</span>
                <span v-if="t.deadline" class="text-xs text-gray-500">{{ t.deadline.slice(0, 10) }}</span>
                <button @click="openEditTaskModal(t)" class="text-sm text-blue-600 hover:text-blue-900 font-medium">Edit</button>
                <button @click="confirmDeleteTask(t)" class="text-sm text-red-600 hover:text-red-900 font-medium">Delete</button>
              </div>
            </li>
          </ul>
        </div>

        <!-- Promotions Tab -->
        <div v-if="currentTab === 'promotions'">
          <div class="mb-4 flex justify-between items-center">
            <p class="text-sm text-gray-500">Promotions linked to this campaign.</p>
            <button @click="isPromotionModalOpen = true"
              class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
              + Add Promotion
            </button>
          </div>
          <div v-if="promotionsLoading" class="text-sm text-gray-500">Loading...</div>
          <div v-else-if="!linkedPromotions.length">
            <EmptyState title="No promotions yet" description="Add a promotion to this campaign to get started." />
          </div>
          <ul v-else class="divide-y divide-gray-200 bg-white rounded-lg border border-gray-200">
            <li v-for="p in linkedPromotions" :key="p.id" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 transition-colors">
              <div class="flex items-center space-x-3">
                <span class="font-mono text-xs font-bold text-gray-500 bg-gray-100 px-2 py-1 rounded">{{ p.code }}</span>
                <span class="text-sm font-medium text-gray-900">{{ p.name }}</span>
              </div>
              <div class="flex items-center space-x-3">
                <StatusBadge :status="p.status" />
                <router-link :to="`/promotions/${p.id}`" class="text-sm text-blue-600 hover:text-blue-900 font-medium">View</router-link>
                <button @click="deletePromotionAction(p)" class="text-sm text-red-600 hover:text-red-900 font-medium">Delete</button>
              </div>
            </li>
          </ul>
        </div>


        <!-- Products Tab -->
        <div v-if="currentTab === 'products'">
          <div class="mb-4 flex justify-between items-center">
            <p class="text-sm text-gray-500">Products assigned via promotions.</p>
            <button @click="openAddProductModal" class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">+ Add Product</button>
          </div>
          <div v-if="campaignProductsLoading" class="text-sm text-gray-400 py-8 text-center">Loading...</div>
          <EmptyState v-else-if="!campaignProducts.length" title="No products" description="Add products through promotions to set pricing." />
          <div v-else class="space-y-4">
            <div v-for="promo in campaignProducts" :key="promo.id" class="bg-white rounded-lg border border-gray-200 overflow-hidden">
              <div class="px-4 py-2 bg-gray-50 border-b flex justify-between items-center">
                <span class="text-sm font-semibold text-gray-900">{{ promo.name }} <span class="text-xs text-gray-500 font-mono">{{ promo.code }}</span></span>
                <StatusBadge :status="promo.status" />
              </div>
              <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50"><tr>
                  <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Variant</th>
                  <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Normal</th>
                  <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                  <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                  <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">%</th>
                  <th class="px-3 py-2"></th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="v in promo.variants" :key="v.id" class="hover:bg-gray-50">
                    <td class="px-3 py-2"><div class="font-medium text-gray-900">{{ v.name }}</div><div class="text-xs text-gray-400">{{ v.product?.name }} &middot; {{ v.code }}</div></td>
                    <td class="px-3 py-2 text-gray-700">{{ formatCurrency(v.promotion_pricing?.normal_price_snapshot) }}</td>
                    <td class="px-3 py-2 font-semibold text-blue-700">{{ formatCurrency(v.promotion_pricing?.campaign_price) }}</td>
                    <td class="px-3 py-2 text-green-600 font-semibold">{{ formatCurrency(v.promotion_pricing?.discount_price) }}</td>
                    <td class="px-3 py-2 font-semibold" :class="discountPercent(v) > 0 ? 'text-green-600' : 'text-gray-400'">{{ discountPercent(v) }}%</td>
                    <td class="px-3 py-2"><button @click="openEditVariantPricing(promo.id, v)" class="text-blue-600 hover:text-blue-900 mr-2 text-xs">Edit</button><button @click="removeVariantFromPromo(promo.id, v.id)" class="text-red-500 hover:text-red-700 text-xs">Remove</button></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Secure Link Tab -->
        <div v-else-if="currentTab === 'secure-link'">
          <CampaignSecureLinkPanel :campaign-id="campaign.id" />
        </div>

        <!-- Comments Tab -->
        <div v-else-if="currentTab === 'comments'">
          <CampaignCommentsPanel :campaign-id="campaign.id" />
        </div>

        <!-- All other placeholder tabs -->
        <div v-else-if="currentTab !== 'overview' && currentTab !== 'tasks' && currentTab !== 'promotions' && currentTab !== 'products' && currentTab !== 'comments'">
          <EmptyState
            :title="getTabName(currentTab)"
            :description="`The ${getTabName(currentTab)} module is planned for a future sprint.`"
          />
        </div>

      </div>
    </div>

    <!-- Edit Modal -->
    <CampaignForm :is-open="isModalOpen" :campaign="campaign" @close="closeModal" @saved="handleSaved" />

    <!-- Add Promotion Modal -->
    <PromotionForm
      :is-open="isPromotionModalOpen"
      :default-campaign-id="campaign.id"
      @close="isPromotionModalOpen = false"
      @saved="loadLinkedPromotions"
    />

    <!-- Task Modal -->
    <TaskForm
      :is-open="isTaskModalOpen"
      :task="selectedTask"
      :campaign-id="campaign.id"
      @close="closeTaskModal"
      @saved="fetchCampaignTasks"
    />

    
    <!-- Product Pricing Edit Modal -->
    <ModalForm :is-open="isProductPricingModalOpen" title="Edit Product Pricing" @close="isProductPricingModalOpen = false">
      <form id="pricing-form" @submit.prevent="submitPricingForm" class="space-y-4 mt-2">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Campaign Price</label>
            <input type="number" min="0" step="1" v-model="pricingForm.campaign_price" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Discount Price</label>
            <input type="number" min="0" step="1" v-model="pricingForm.discount_price" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Bottom Price</label>
            <input type="number" min="0" step="1" v-model="pricingForm.bottom_price" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Promotion Stock</label>
            <input type="number" min="0" v-model="pricingForm.promotion_stock" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Purchase Limit</label>
            <input type="number" min="0" v-model="pricingForm.purchase_limit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm sm:text-sm">
          </div>
        </div>
      </form>
      <template #footer>
        <div class="flex gap-2">
          <button type="submit" form="pricing-form" :disabled="pricingSubmitting" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
            {{ pricingSubmitting ? "Saving..." : "Save Pricing" }}
          </button>
          <button type="button" @click="isProductPricingModalOpen = false" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">Cancel</button>
        </div>
      </template>
    </ModalForm>


    <!-- Add Product to Campaign Modal -->
    <ModalForm :is-open="isAddProductModalOpen" title="Add Product to Campaign" @close="isAddProductModalOpen = false">
      <AddProductToCampaignForm
        :campaign-id="campaign.id"
        @close="isAddProductModalOpen = false"
        @saved="loadCampaignProducts"
      />
      <template #footer>
        <div class="flex gap-2">
          <button type="submit" form="add-product-form" class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Add to Promotion</button>
          <button type="button" @click="isAddProductModalOpen = false" class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50">Cancel</button>
        </div>
      </template>
    </ModalForm>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useCampaigns } from '../composables/useCampaigns';
import CampaignSecureLinkPanel from '../components/CampaignSecureLinkPanel.vue';
import CampaignCommentsPanel from '../components/CampaignCommentsPanel.vue';
import { usePromotions } from '../composables/usePromotions';
import { useTasks } from '../composables/useTasks';
import StatusBadge from '../components/StatusBadge.vue';
import EmptyState from '../components/EmptyState.vue';
import CampaignForm from '../components/CampaignForm.vue';
import ModalForm from '../components/ModalForm.vue';
import PromotionForm from '../components/PromotionForm.vue';
import AddProductToCampaignForm from '../components/AddProductToCampaignForm.vue';
import TaskForm from '../components/TaskForm.vue';

const route = useRoute();
const { campaign, loading, error, fetchCampaign } = useCampaigns();
const { promotions: linkedPromotions, loading: promotionsLoading, fetchPromotions, deletePromotion } = usePromotions();
const { tasks, loading: tasksLoading, fetchTasks, deleteTask } = useTasks();

const currentTab = ref('overview');
const tabs = [
  { id: 'overview', name: 'Overview' },
  { id: 'tasks', name: 'Tasks' },
  { id: 'promotions', name: 'Promotions' },
  { id: 'products', name: 'Products' },
  { id: 'attachments', name: 'Attachments' },
  { id: 'comments', name: 'Comments' },
  { id: 'secure-link', name: 'Secure Link' },
  { id: 'timeline', name: 'Activity Timeline' },
];

const getTabName = (id) => tabs.find(t => t.id === id)?.name || 'Module';

const isModalOpen = ref(false);
const isPromotionModalOpen = ref(false);
const isTaskModalOpen = ref(false);
const selectedTask = ref(null);
const campaignProducts = ref([]);
const campaignProductsLoading = ref(false);
const editingPromoId = ref(null);
const editingVariant = ref(null);
const pricingForm = ref({ variant_id: null, campaign_price: 0, bottom_price: 0, discount_price: 0, promotion_stock: 0, purchase_limit: 0, notes: "" });
const isProductPricingModalOpen = ref(false);
const isAddProductModalOpen = ref(false);
const pricingSubmitting = ref(false);

// Load tasks for this campaign
const fetchCampaignTasks = () => {
  if (campaign.value?.id) {
    fetchTasks({ campaign_id: campaign.value.id, per_page: 100 });
  }
};

// Load promotions for this campaign
const loadLinkedPromotions = () => {
  if (campaign.value) {
    fetchPromotions({ campaign_id: campaign.value.id, per_page: 100 });
  }
};

// Watch tab changes
watch(currentTab, (tab) => {
  if (tab === 'promotions') loadLinkedPromotions();
  if (tab === 'tasks') fetchCampaignTasks();
  if (tab === 'products') loadCampaignProducts();
});

onMounted(() => {
  fetchCampaign(route.params.id);
});

// Watch for campaign loaded
watch(campaign, (val) => {
  if (val?.id) {
    fetchCampaignTasks();
    loadCampaignProducts();
  }
});

// Campaign edit modal
const openEditModal = () => { isModalOpen.value = true; };
const closeModal = () => { isModalOpen.value = false; };
const handleSaved = () => {
  fetchCampaign(route.params.id);
};

// Task modal
const openCreateTaskModal = () => {
  selectedTask.value = null;
  isTaskModalOpen.value = true;
};

const openEditTaskModal = (task) => {
  selectedTask.value = task;
  isTaskModalOpen.value = true;
};

const closeTaskModal = () => {
  isTaskModalOpen.value = false;
};

const confirmDeleteTask = (task) => {
  if (confirm(`Delete task "${task.name}"?`)) {
    deleteTask(task.id);
    fetchCampaignTasks();
  }
};

// Task status helpers
const taskStatusClass = (status) => {
  const map = {
    NotStarted: 'bg-gray-100 text-gray-700',
    InProgress: 'bg-blue-100 text-blue-700',
    Completed: 'bg-green-100 text-green-700',
    OnHold: 'bg-yellow-100 text-yellow-700'
  };
  return map[status] || 'bg-gray-100 text-gray-700';
};

const taskStatusLabel = (status) => {
  const map = {
    NotStarted: 'Not Started',
    InProgress: 'In Progress',
    Completed: 'Completed',
    OnHold: 'On Hold'
  };
  return map[status] || status;
};


const formatCurrency = (val) => {
  if (val == null) return "\u2014";
  return new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(val);
};

// === Campaign Products Functions ===
const loadCampaignProducts = async () => {
  if (!campaign.value?.id) return;
  campaignProductsLoading.value = true;
  try {
    const api = (await import("../utils/api")).default;
    const promoRes = await api.get("/admin/promotions", { params: { campaign_id: campaign.value.id, per_page: 100 } });
    const promotions = promoRes.data.data?.promotions?.data || [];
    const results = [];
    for (const promo of promotions) {
      try {
        const varRes = await api.get("/admin/promotions/".concat(promo.id, "/variants"), { params: { per_page: 200 } });
        const variants = varRes.data.data?.variants?.data || [];
        results.push({ ...promo, variants });
      } catch (e) {}
    }
    campaignProducts.value = results;
  } catch (e) {
    console.error("Failed to load campaign products", e);
  } finally {
    campaignProductsLoading.value = false;
  }
};

const discountPercent = (variant) => {
  const normal = variant.promotion_pricing?.normal_price_snapshot || 0;
  const discount = variant.promotion_pricing?.discount_price || 0;
  if (normal <= 0) return 0;
  return Math.round(((normal - discount) / normal) * 100);
};

const openEditVariantPricing = (promoId, variant) => {
  editingPromoId.value = promoId;
  editingVariant.value = variant;
  pricingForm.value = {
    variant_id: variant.id,
    campaign_price: variant.promotion_pricing?.campaign_price || 0,
    bottom_price: variant.promotion_pricing?.bottom_price || 0,
    discount_price: variant.promotion_pricing?.discount_price || 0,
    promotion_stock: variant.promotion_pricing?.promotion_stock || 0,
    purchase_limit: variant.promotion_pricing?.purchase_limit || 0,
    notes: variant.promotion_pricing?.notes || "",
  };
  isProductPricingModalOpen.value = true;
};

const removeVariantFromPromo = async (promoId, variantId) => {
  if (!confirm("Remove this variant from the promotion?")) return;
  try {
    const api = (await import("../utils/api")).default;
    await api.delete("/admin/promotions/".concat(promoId, "/variants/", variantId));
    await loadCampaignProducts();
  } catch (e) {}
};

const submitPricingForm = async () => {
  if (!editingPromoId.value) return;
  pricingSubmitting.value = true;
  try {
    const api = (await import("../utils/api")).default;
    await api.post("/admin/promotions/".concat(editingPromoId.value, "/variants"), pricingForm.value);
    isProductPricingModalOpen.value = false;
    await loadCampaignProducts();
  } catch (e) {
    console.error("Failed to save pricing", e);
  } finally {
    pricingSubmitting.value = false;
  }
};

const openAddProductModal = () => {
  isAddProductModalOpen.value = true;
};

const deletePromotionAction = async (promotion) => {
  if (confirm(`Delete promotion "${promotion.name}"?`)) {
    await deletePromotion(promotion.id);
    loadLinkedPromotions();
  }
};

</script>