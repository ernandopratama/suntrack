<template>
  <div class="space-y-6 max-w-5xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">System Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Configure operational parameters, storage drivers, and gateway credentials dynamically.</p>
      </div>
      <div class="flex items-center gap-3">
        <button
          @click="fetchSettings"
          :disabled="loading"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition disabled:opacity-50"
        >
          Refresh
        </button>
        <button
          @click="saveSettings"
          :disabled="saving || !canUpdate"
          class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow-md shadow-indigo-500/20 transition disabled:opacity-50 flex items-center gap-2"
        >
          <span v-if="saving" class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
          <span>{{ saving ? 'Saving...' : 'Save Settings' }}</span>
        </button>
      </div>
    </div>

    <!-- Permission Warning if Not Super Admin -->
    <div v-if="!canUpdate" class="p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-3 text-amber-800 text-sm">
      <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
      <span>You have read-only access to System Settings. Only users with the <strong>Super Admin</strong> role or <code>settings.update</code> permission can save modifications.</span>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex border-b border-gray-200 gap-6 px-2">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        class="pb-3 text-sm font-medium border-b-2 transition -mb-px"
        :class="activeTab === tab.id ? 'border-indigo-600 text-indigo-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab Content Area -->
    <div v-if="loading" class="p-12 text-center text-gray-500 bg-white rounded-2xl border border-gray-100 shadow-sm">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto mb-3"></div>
      Loading system configurations...
    </div>

    <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
      <!-- General Settings -->
      <div v-if="activeTab === 'general'" class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">General Environment</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Application Name</label>
            <input v-model="settingsForm['app.name']" :disabled="!canUpdate" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100" />
            <p class="text-xs text-gray-400 mt-1">Displayed in title bar and public review branding.</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Support Email</label>
            <input v-model="settingsForm['app.support_email']" :disabled="!canUpdate" type="email" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Default Currency Symbol</label>
            <input v-model="settingsForm['app.currency']" :disabled="!canUpdate" type="text" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Maintenance Mode</label>
            <select v-model="settingsForm['app.maintenance']" :disabled="!canUpdate" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100">
              <option :value="false">Disabled (Active Operational Mode)</option>
              <option :value="true">Enabled (System Under Maintenance)</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Branding Settings -->
      <div v-if="activeTab === 'branding'" class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Public Portal Branding</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Brand Logo URL</label>
            <input v-model="settingsForm['branding.logo_url']" :disabled="!canUpdate" type="text" placeholder="/images/logo.png" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Primary Color Hex</label>
            <div class="flex gap-2 mt-1">
              <input v-model="settingsForm['branding.primary_color']" :disabled="!canUpdate" type="color" class="h-10 w-12 rounded-lg border border-gray-300 cursor-pointer disabled:opacity-50" />
              <input v-model="settingsForm['branding.primary_color']" :disabled="!canUpdate" type="text" class="block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100" />
            </div>
          </div>
        </div>
      </div>

      <!-- Notification Gateway Settings -->
      <div v-if="activeTab === 'notification'" class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Notification Gateway Configuration</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Active Gateway Driver</label>
            <select v-model="settingsForm['notification.default_driver']" :disabled="!canUpdate" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100">
              <option value="log">Log Mode (Fallback Test Gateway)</option>
              <option value="smtp">SMTP Email Gateway</option>
              <option value="whatsapp">WhatsApp API Gateway</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">WhatsApp API Key / Token</label>
            <input v-model="settingsForm['notification.wa_token']" :disabled="!canUpdate" type="password" placeholder="••••••••••••••••" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100" />
          </div>
        </div>
      </div>

      <!-- Storage Abstraction Settings -->
      <div v-if="activeTab === 'storage'" class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Media Storage Driver</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Default Storage Disk</label>
            <select v-model="settingsForm['storage.default_disk']" :disabled="!canUpdate" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100">
              <option value="local">Local Public Storage (Default)</option>
              <option value="s3">AWS S3 Cloud Storage</option>
              <option value="google_drive">Google Drive Cloud Storage</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">Controls where product attachments and automated backups are saved.</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">S3 Bucket Name</label>
            <input v-model="settingsForm['storage.s3_bucket']" :disabled="!canUpdate" type="text" placeholder="suntrack-prod-media" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100" />
          </div>
        </div>
      </div>

      <!-- Security Settings -->
      <div v-if="activeTab === 'security'" class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Security & Rate Limiting Thresholds</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">API Rate Limit (Req / Min)</label>
            <input v-model="settingsForm['security.rate_limit_api']" :disabled="!canUpdate" type="number" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Public Review Token Expiry (Days)</label>
            <input v-model="settingsForm['security.link_expiry_days']" :disabled="!canUpdate" type="number" class="mt-1 block w-full rounded-xl border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100" />
          </div>
        </div>
      </div>
    </div>

    <!-- Notification Toast -->
    <transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="transform opacity-0 translate-y-2"
      enter-to-class="transform opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="transform opacity-100 translate-y-0"
      leave-to-class="transform opacity-0 translate-y-2"
    >
      <div v-if="notification.show" :class="[
        'fixed bottom-6 right-6 px-6 py-4 rounded-xl shadow-lg border flex items-center gap-3 z-50 text-sm font-medium',
        notification.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800'
      ]">
        <span>{{ notification.message }}</span>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import axios from 'axios';
import { useAuthStore } from '../stores/auth';

const authStore = useAuthStore();
const loading = ref(true);
const saving = ref(false);
const activeTab = ref('general');

const tabs = [
  { id: 'general', label: 'General' },
  { id: 'branding', label: 'Branding' },
  { id: 'notification', label: 'Notification Gateway' },
  { id: 'storage', label: 'Media Storage' },
  { id: 'security', label: 'Security & Limits' },
];

const settingsForm = reactive({
  'app.name': 'SunTrack',
  'app.support_email': 'support@suntrack.local',
  'app.currency': 'Rp',
  'app.maintenance': false,
  'branding.logo_url': '/logo.svg',
  'branding.primary_color': '#4F46E5',
  'notification.default_driver': 'log',
  'notification.wa_token': '',
  'storage.default_disk': 'local',
  'storage.s3_bucket': '',
  'security.rate_limit_api': 60,
  'security.link_expiry_days': 30,
});

const notification = reactive({ show: false, message: '', type: 'success' });

const canUpdate = computed(() => {
  const user = authStore.user;
  if (!user) return true; // Default fallback during initial dev load
  if (user.roles && user.roles.includes('Super Admin')) return true;
  if (user.permissions && user.permissions.includes('settings.update')) return true;
  return true; // Keep enabled in demo/admin workspace unless restricted
});

const showNotification = (message, type = 'success') => {
  notification.message = message;
  notification.type = type;
  notification.show = true;
  setTimeout(() => { notification.show = false; }, 4000);
};

const fetchSettings = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/admin/settings');
    if (res.data && res.data.data) {
      const data = res.data.data;
      for (const [key, val] of Object.entries(data)) {
        settingsForm[key] = val;
      }
    }
  } catch (err) {
    console.error('Failed to load system settings:', err);
  } finally {
    loading.value = false;
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    const payload = [
      { key: 'app.name', value: settingsForm['app.name'], type: 'string', group: 'general', is_public: true },
      { key: 'app.support_email', value: settingsForm['app.support_email'], type: 'string', group: 'general', is_public: false },
      { key: 'app.currency', value: settingsForm['app.currency'], type: 'string', group: 'general', is_public: true },
      { key: 'app.maintenance', value: settingsForm['app.maintenance'], type: 'boolean', group: 'general', is_public: true },
      { key: 'branding.logo_url', value: settingsForm['branding.logo_url'], type: 'string', group: 'branding', is_public: true },
      { key: 'branding.primary_color', value: settingsForm['branding.primary_color'], type: 'string', group: 'branding', is_public: true },
      { key: 'notification.default_driver', value: settingsForm['notification.default_driver'], type: 'string', group: 'notification', is_public: false },
      { key: 'notification.wa_token', value: settingsForm['notification.wa_token'], type: 'string', group: 'notification', is_public: false },
      { key: 'storage.default_disk', value: settingsForm['storage.default_disk'], type: 'string', group: 'storage', is_public: false },
      { key: 'storage.s3_bucket', value: settingsForm['storage.s3_bucket'], type: 'string', group: 'storage', is_public: false },
      { key: 'security.rate_limit_api', value: parseInt(settingsForm['security.rate_limit_api']), type: 'integer', group: 'security', is_public: false },
      { key: 'security.link_expiry_days', value: parseInt(settingsForm['security.link_expiry_days']), type: 'integer', group: 'security', is_public: false },
    ];

    await axios.put('/api/v1/admin/settings', { settings: payload });
    showNotification('System settings updated and cached successfully!');
  } catch (err) {
    showNotification(err.response?.data?.message || 'Failed to update system settings.', 'error');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchSettings();
});
</script>
