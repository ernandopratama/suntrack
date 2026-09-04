<template>
  <div class="mx-auto max-w-6xl space-y-6">

    <!-- Page Header -->
    <div
      class="relative overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100"
    >
      <!-- Decorative Background -->
      <div
        class="absolute -right-20 -top-24 h-64 w-64 rounded-full opacity-50"
        style="background: #d0e7e6"
      ></div>

      <div
        class="absolute right-28 bottom-[-90px] h-52 w-52 rounded-full opacity-30"
        style="background: #95ccdd"
      ></div>

      <div
        class="relative flex flex-col gap-6 p-7 sm:p-8 lg:flex-row lg:items-center lg:justify-between"
      >
        <!-- Title -->
        <div class="flex items-start gap-5">
          <div
            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl"
            style="background: #d0e7e6"
          >
            <svg
              class="h-7 w-7"
              style="color: #293681"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="1.8"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.827 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.827 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.827-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.827-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065Z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
              />
            </svg>
          </div>

          <div>
            <div class="flex flex-wrap items-center gap-3">
              <h1
                class="text-2xl font-bold tracking-tight sm:text-3xl"
                style="color: #293681"
              >
                System Settings
              </h1>

              <span
                class="rounded-full px-3 py-1 text-xs font-semibold"
                style="background: #d0e7e6; color: #293681"
              >
                Administration
              </span>
            </div>

            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
              Configure operational parameters, storage drivers, and gateway
              credentials dynamically.
            </p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-3">
          <button
            @click="fetchSettings"
            :disabled="loading"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
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
                d="M4 4v5h5M20 20v-5h-5M5.5 9A7 7 0 0 1 18.5 6.5L20 9M18.5 15A7 7 0 0 1 5.5 17.5L4 15"
              />
            </svg>
            Refresh
          </button>

          <button
            @click="saveSettings"
            :disabled="saving || !canUpdate"
            class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-50"
            style="
              background: #4274d9;
              box-shadow: 0 8px 20px rgba(66, 116, 217, 0.22);
            "
          >
            <span
              v-if="saving"
              class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
            ></span>

            <svg
              v-else
              class="h-4 w-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="2"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M5 12h14M12 5l7 7-7 7"
              />
            </svg>

            <span>{{ saving ? 'Saving...' : 'Save Settings' }}</span>
          </button>
        </div>
      </div>

      <!-- Accent -->
      <div
        class="h-1 w-full"
        style="
          background: linear-gradient(
            90deg,
            #293681 0%,
            #4274d9 45%,
            #95ccdd 75%,
            #d0e7e6 100%
          );
        "
      ></div>
    </div>

    <!-- Permission Warning -->
    <div
      v-if="!canUpdate"
      class="flex items-start gap-4 rounded-2xl border p-4"
      style="
        background: #fffaf0;
        border-color: #f4dfad;
        color: #80621b;
      "
    >
      <div
        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
        style="background: #fff0c7"
      >
        <svg
          class="h-5 w-5"
          style="color: #a47b1d"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
      </div>

      <div class="text-sm leading-6">
        <p class="font-semibold">Read-only access</p>

        <p class="mt-0.5 opacity-90">
          You have read-only access to System Settings. Only users with the
          <strong>Super Admin</strong> role or
          <code class="rounded bg-white/70 px-1.5 py-0.5 text-xs">
            settings.update
          </code>
          permission can save modifications.
        </p>
      </div>
    </div>

    <!-- Tabs -->
    <div
      class="overflow-x-auto rounded-2xl bg-white p-2 shadow-sm ring-1 ring-slate-100"
    >
      <div class="flex min-w-max gap-1">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          class="rounded-xl px-4 py-2.5 text-sm font-medium transition"
          :class="
            activeTab === tab.id
              ? 'font-semibold'
              : 'text-slate-500 hover:bg-slate-50 hover:text-slate-700'
          "
          :style="
            activeTab === tab.id
              ? {
                  background: '#D0E7E6',
                  color: '#293681',
                }
              : {}
          "
        >
          {{ tab.label }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div
      v-if="loading"
      class="rounded-3xl bg-white p-14 text-center shadow-sm ring-1 ring-slate-100"
    >
      <div
        class="mx-auto mb-4 h-9 w-9 animate-spin rounded-full border-4 border-slate-100"
        style="border-top-color: #4274d9"
      ></div>

      <p
        class="text-sm font-semibold"
        style="color: #293681"
      >
        Loading system configurations...
      </p>

      <p class="mt-1 text-xs text-slate-400">
        Please wait while the latest settings are loaded.
      </p>
    </div>

    <!-- Content -->
    <div
      v-else
      class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100"
    >
      <div class="p-6 sm:p-8">

        <!-- General -->
        <div
          v-if="activeTab === 'general'"
          class="space-y-6"
        >
          <div
            class="flex items-start gap-4 border-b border-slate-100 pb-5"
          >
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
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
                  d="M12 8v4l3 2"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                />
              </svg>
            </div>

            <div>
              <h3
                class="text-lg font-bold"
                style="color: #293681"
              >
                General Environment
              </h3>

              <p class="mt-1 text-sm text-slate-500">
                Configure the basic application environment and operational state.
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
              <label class="settings-label">
                Application Name
              </label>

              <input
                v-model="settingsForm['app.name']"
                :disabled="!canUpdate"
                type="text"
                class="settings-input"
              />

              <p class="settings-help">
                Displayed in title bar and public review branding.
              </p>
            </div>

            <div>
              <label class="settings-label">
                Support Email
              </label>

              <input
                v-model="settingsForm['app.support_email']"
                :disabled="!canUpdate"
                type="email"
                class="settings-input"
              />
            </div>

            <div>
              <label class="settings-label">
                Default Currency Symbol
              </label>

              <input
                v-model="settingsForm['app.currency']"
                :disabled="!canUpdate"
                type="text"
                class="settings-input"
              />
            </div>

            <div>
              <label class="settings-label">
                Maintenance Mode
              </label>

              <select
                v-model="settingsForm['app.maintenance']"
                :disabled="!canUpdate"
                class="settings-input"
              >
                <option :value="false">
                  Disabled (Active Operational Mode)
                </option>

                <option :value="true">
                  Enabled (System Under Maintenance)
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Branding -->
        <div
          v-if="activeTab === 'branding'"
          class="space-y-6"
        >
          <div
            class="flex items-start gap-4 border-b border-slate-100 pb-5"
          >
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
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
                  d="M4 16l4.586-4.586a2 2 0 0 1 2.828 0L16 16m-2-2 1.586-1.586a2 2 0 0 1 2.828 0L20 14"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M4 19h16V5H4v14Z"
                />
                <circle cx="8.5" cy="8.5" r="1.5" />
              </svg>
            </div>

            <div>
              <h3
                class="text-lg font-bold"
                style="color: #293681"
              >
                Public Portal Branding
              </h3>

              <p class="mt-1 text-sm text-slate-500">
                Customize the visual identity displayed across the public portal.
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
              <label class="settings-label">
                Brand Logo URL
              </label>

              <input
                v-model="settingsForm['branding.logo_url']"
                :disabled="!canUpdate"
                type="text"
                placeholder="/images/logo.png"
                class="settings-input"
              />
            </div>

            <div>
              <label class="settings-label">
                Primary Color Hex
              </label>

              <div class="mt-1 flex gap-3">
                <div
                  class="flex h-11 w-12 items-center justify-center rounded-xl border border-slate-200 bg-white p-1"
                >
                  <input
                    v-model="settingsForm['branding.primary_color']"
                    :disabled="!canUpdate"
                    type="color"
                    class="h-full w-full cursor-pointer rounded-lg border-0 bg-transparent disabled:cursor-not-allowed disabled:opacity-50"
                  />
                </div>

                <input
                  v-model="settingsForm['branding.primary_color']"
                  :disabled="!canUpdate"
                  type="text"
                  class="settings-input mt-0"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Notification -->
        <div
          v-if="activeTab === 'notification'"
          class="space-y-6"
        >
          <div
            class="flex items-start gap-4 border-b border-slate-100 pb-5"
          >
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
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
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 1 0-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M10 21h4"
                />
              </svg>
            </div>

            <div>
              <h3
                class="text-lg font-bold"
                style="color: #293681"
              >
                Notification Gateway Configuration
              </h3>

              <p class="mt-1 text-sm text-slate-500">
                Configure the active notification channel and gateway credentials.
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
              <label class="settings-label">
                Active Gateway Driver
              </label>

              <select
                v-model="settingsForm['notification.default_driver']"
                :disabled="!canUpdate"
                class="settings-input"
              >
                <option value="log">
                  Log Mode (Fallback Test Gateway)
                </option>

                <option value="smtp">
                  SMTP Email Gateway
                </option>

                <option value="whatsapp">
                  WhatsApp API Gateway
                </option>
              </select>
            </div>

            <div>
              <label class="settings-label">
                WhatsApp API Key / Token
              </label>

              <div class="relative mt-1.5">
                <input
                  v-model="settingsForm['notification.wa_token']"
                  :disabled="!canUpdate"
                  :type="showWaToken ? 'text' : 'password'"
                  placeholder="••••••••••••••••"
                  class="settings-input settings-password-input !mt-0"
                />
                <button
                  type="button"
                  class="absolute inset-y-0 right-0 flex w-11 items-center justify-center text-content-muted transition hover:text-brand disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="!canUpdate"
                  :aria-label="showWaToken ? 'Sembunyikan token WhatsApp' : 'Tampilkan token WhatsApp'"
                  :aria-pressed="showWaToken"
                  @click="showWaToken = !showWaToken"
                >
                  <i :class="showWaToken ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye'"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Storage -->
        <div
          v-if="activeTab === 'storage'"
          class="space-y-6"
        >
          <div
            class="flex items-start gap-4 border-b border-slate-100 pb-5"
          >
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
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
                  d="M3 7h5l2 2h11v10H3V7Z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M7 13h10"
                />
              </svg>
            </div>

            <div>
              <h3
                class="text-lg font-bold"
                style="color: #293681"
              >
                Media Storage Driver
              </h3>

              <p class="mt-1 text-sm text-slate-500">
                Define where product attachments and automated backups are stored.
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
              <label class="settings-label">
                Default Storage Disk
              </label>

              <select
                v-model="settingsForm['storage.default_disk']"
                :disabled="!canUpdate"
                class="settings-input"
              >
                <option value="local">
                  Local Public Storage (Default)
                </option>

                <option value="s3">
                  AWS S3 Cloud Storage
                </option>

                <option value="google_drive">
                  Google Drive Cloud Storage
                </option>
              </select>

              <p class="settings-help">
                Controls where product attachments and automated backups are saved.
              </p>
            </div>

            <div>
              <label class="settings-label">
                S3 Bucket Name
              </label>

              <input
                v-model="settingsForm['storage.s3_bucket']"
                :disabled="!canUpdate"
                type="text"
                placeholder="suntrack-prod-media"
                class="settings-input"
              />
            </div>
          </div>
        </div>

        <!-- Security -->
        <div
          v-if="activeTab === 'security'"
          class="space-y-6"
        >
          <div
            class="flex items-start gap-4 border-b border-slate-100 pb-5"
          >
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
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
                  d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="m9 12 2 2 4-4"
                />
              </svg>
            </div>

            <div>
              <h3
                class="text-lg font-bold"
                style="color: #293681"
              >
                Security & Rate Limiting Thresholds
              </h3>

              <p class="mt-1 text-sm text-slate-500">
                Configure API request thresholds and public review token expiration.
              </p>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <div>
              <label class="settings-label">
                API Rate Limit (Req / Min)
              </label>

              <input
                v-model="settingsForm['security.rate_limit_api']"
                :disabled="!canUpdate"
                type="number"
                class="settings-input"
              />
            </div>

            <div>
              <label class="settings-label">
                Public Review Token Expiry (Days)
              </label>

              <input
                v-model="settingsForm['security.link_expiry_days']"
                :disabled="!canUpdate"
                type="number"
                class="settings-input"
              />
            </div>
          </div>
        </div>

      </div>

      <!-- Bottom Accent -->
      <div
        class="h-1 w-full"
        style="
          background: linear-gradient(
            90deg,
            #293681 0%,
            #4274d9 45%,
            #95ccdd 75%,
            #d0e7e6 100%
          );
        "
      ></div>
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
      <div
        v-if="notification.show"
        class="fixed bottom-6 right-6 z-50 flex max-w-sm items-center gap-3 rounded-2xl border px-5 py-4 text-sm font-medium shadow-xl"
        :style="
          notification.type === 'success'
            ? {
                background: '#D0E7E6',
                borderColor: '#95CCDD',
                color: '#293681',
              }
            : {
                background: '#fff1f2',
                borderColor: '#fecdd3',
                color: '#9f1239',
              }
        "
      >
        <div
          class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl"
          :style="
            notification.type === 'success'
              ? { background: '#95CCDD' }
              : { background: '#ffe4e6' }
          "
        >
          <svg
            v-if="notification.type === 'success'"
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
              d="m5 12 4 4L19 6"
            />
          </svg>

          <svg
            v-else
            class="h-4 w-4 text-rose-700"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            stroke-width="2"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M6 18 18 6M6 6l12 12"
            />
          </svg>
        </div>

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
const showWaToken = ref(false);

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

const notification = reactive({
  show: false,
  message: '',
  type: 'success'
});

const canUpdate = computed(() => {
  return authStore.can('settings.update');
});

const showNotification = (message, type = 'success') => {
  notification.message = message;
  notification.type = type;
  notification.show = true;

  setTimeout(() => {
    notification.show = false;
  }, 4000);
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
      {
        key: 'app.name',
        value: settingsForm['app.name'],
        type: 'string',
        group: 'general',
        is_public: true
      },
      {
        key: 'app.support_email',
        value: settingsForm['app.support_email'],
        type: 'string',
        group: 'general',
        is_public: false
      },
      {
        key: 'app.currency',
        value: settingsForm['app.currency'],
        type: 'string',
        group: 'general',
        is_public: true
      },
      {
        key: 'app.maintenance',
        value: settingsForm['app.maintenance'],
        type: 'boolean',
        group: 'general',
        is_public: true
      },
      {
        key: 'branding.logo_url',
        value: settingsForm['branding.logo_url'],
        type: 'string',
        group: 'branding',
        is_public: true
      },
      {
        key: 'branding.primary_color',
        value: settingsForm['branding.primary_color'],
        type: 'string',
        group: 'branding',
        is_public: true
      },
      {
        key: 'notification.default_driver',
        value: settingsForm['notification.default_driver'],
        type: 'string',
        group: 'notification',
        is_public: false
      },
      {
        key: 'notification.wa_token',
        value: settingsForm['notification.wa_token'],
        type: 'string',
        group: 'notification',
        is_public: false
      },
      {
        key: 'storage.default_disk',
        value: settingsForm['storage.default_disk'],
        type: 'string',
        group: 'storage',
        is_public: false
      },
      {
        key: 'storage.s3_bucket',
        value: settingsForm['storage.s3_bucket'],
        type: 'string',
        group: 'storage',
        is_public: false
      },
      {
        key: 'security.rate_limit_api',
        value: parseInt(settingsForm['security.rate_limit_api']),
        type: 'integer',
        group: 'security',
        is_public: false
      },
      {
        key: 'security.link_expiry_days',
        value: parseInt(settingsForm['security.link_expiry_days']),
        type: 'integer',
        group: 'security',
        is_public: false
      }
    ];

    await axios.put('/api/v1/admin/settings', {
      settings: payload
    });

    showNotification(
      'System settings updated and cached successfully!'
    );
  } catch (err) {
    showNotification(
      err.response?.data?.message ||
        'Failed to update system settings.',
      'error'
    );
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  fetchSettings();
});
</script>

<style scoped>
.settings-label {
  display: block;
  font-size: 0.875rem;
  line-height: 1.25rem;
  font-weight: 600;
  color: var(--ui-content-soft);
}

.settings-input {
  display: block;
  width: 100%;
  margin-top: 0.375rem;
  border-radius: 0.75rem;
  border: 1px solid var(--ui-border);
  background-color: var(--ui-surface);
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

.settings-input:hover:not(:disabled) {
  border-color: #95ccdd;
}

.settings-input:focus {
  border-color: #4274d9;
  box-shadow: 0 0 0 3px rgba(66, 116, 217, 0.12);
}

.settings-input:disabled {
  cursor: not-allowed;
  background-color: var(--ui-surface-muted);
  color: var(--ui-content-muted);
}

.settings-password-input {
  padding-right: 3rem;
}

.settings-help {
  margin-top: 0.375rem;
  font-size: 0.75rem;
  line-height: 1.25rem;
  color: var(--ui-content-muted);
}
</style>
