<template>
  <div class="h-screen flex overflow-hidden bg-gray-50">

    <!-- Floating Toggle -->
    <button
      @click="sidebarOpen = !sidebarOpen"
      class="hidden md:flex fixed top-5 z-50 h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg transition-all duration-300 hover:bg-blue-700"
      :style="{ left: sidebarOpen ? '240px' : '60px' }"
    >
      <i
        :class="sidebarOpen ? 'fa-solid fa-chevron-left' : 'fa-solid fa-chevron-right'"
      ></i>
    </button>

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-40 flex flex-col border-r border-gray-200 bg-white transition-all duration-300"
      :class="sidebarOpen ? 'w-64' : 'w-20'"
    >
      <!-- Logo -->
      <div
        class="flex h-16 items-center border-b border-gray-200 px-6 flex-shrink-0"
      >
        <span
          v-if="sidebarOpen"
          class="text-xl font-bold tracking-tight text-gray-900 whitespace-nowrap"
        >
          SunTrack
        </span>

        <i
          v-else
          class="fa-solid fa-sun text-2xl text-amber-500 mx-auto"
        ></i>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto px-3 py-5 space-y-1">

        <router-link
          to="/dashboard"
          class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition"
          :class="$route.path === '/dashboard'
              ? 'bg-blue-50 text-blue-700'
              : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
        >
          <i class="fa-solid fa-chart-line w-5 text-center"></i>
          <span v-if="sidebarOpen" class="ml-3">Dashboard</span>
        </router-link>

        <router-link
          to="/users"
          class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition"
          :class="$route.path.startsWith('/users')
              ? 'bg-blue-50 text-blue-700'
              : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
        >
          <i class="fa-solid fa-users w-5 text-center"></i>
          <span v-if="sidebarOpen" class="ml-3">Users</span>
        </router-link>

        <router-link
          to="/companies"
          class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition"
          :class="$route.path.startsWith('/companies')
              ? 'bg-blue-50 text-blue-700'
              : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
        >
          <i class="fa-solid fa-building w-5 text-center"></i>
          <span v-if="sidebarOpen" class="ml-3">Companies</span>
        </router-link>

        <router-link
          to="/brands"
          class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition"
          :class="$route.path.startsWith('/brands')
              ? 'bg-blue-50 text-blue-700'
              : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
        >
          <i class="fa-solid fa-tag w-5 text-center"></i>
          <span v-if="sidebarOpen" class="ml-3">Brands</span>
        </router-link>

        <router-link
          to="/campaigns"
          class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition"
          :class="$route.path.startsWith('/campaigns')
              ? 'bg-blue-50 text-blue-700'
              : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
        >
          <i class="fa-solid fa-bullhorn w-5 text-center"></i>
          <span v-if="sidebarOpen" class="ml-3">Campaigns</span>
        </router-link>

        <router-link
          to="/promotions"
          class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition"
          :class="$route.path.startsWith('/promotions')
              ? 'bg-blue-50 text-blue-700'
              : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
        >
          <i class="fa-solid fa-tags w-5 text-center"></i>
          <span v-if="sidebarOpen" class="ml-3">Promotions</span>
        </router-link>

        <router-link
          to="/tasks"
          class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition"
          :class="$route.path.startsWith('/tasks')
              ? 'bg-blue-50 text-blue-700'
              : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
        >
          <i class="fa-solid fa-list-check w-5 text-center"></i>
          <span v-if="sidebarOpen" class="ml-3">Tasks</span>
        </router-link>
        
        <router-link
          to="/products"
          class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition"
          :class="$route.path.startsWith('/products')
              ? 'bg-blue-50 text-blue-700'
              : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
        >
          <i class="fa-solid fa-box w-5 text-center"></i>
          <span v-if="sidebarOpen" class="ml-3">Products</span>
        </router-link>

        <div class="my-4 border-t border-gray-200"></div>

        <router-link
          to="/activity"
          class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
        >
          <i class="fa-solid fa-file-lines w-5 text-center"></i>
          <span v-if="sidebarOpen" class="ml-3">Activity Logs</span>
        </router-link>

        <router-link
          to="/export"
          class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
        >
          <i class="fa-solid fa-download w-5 text-center"></i>
          <span v-if="sidebarOpen" class="ml-3">Export</span>
        </router-link>

        <router-link
          to="/settings"
          class="flex items-center rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900"
        >
          <i class="fa-solid fa-gear w-5 text-center"></i>
          <span v-if="sidebarOpen" class="ml-3">Settings</span>
        </router-link>

      </nav>

      <!-- User -->
      <div
        v-if="sidebarOpen"
        class="border-t border-gray-200 p-4 flex-shrink-0"
      >
        <div class="flex items-center">

          <div
            class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100"
          >
            <span class="font-semibold text-blue-700">
              {{ (authStore.user?.name || 'A').charAt(0) }}
            </span>
          </div>

          <div class="ml-3 flex-1 overflow-hidden">
            <p class="truncate text-sm font-semibold text-gray-800">
              {{ authStore.user?.name || 'Administrator' }}
            </p>

            <button
              @click="handleLogout"
              class="text-xs text-red-500 hover:text-red-600"
            >
              Logout
            </button>
          </div>

        </div>
      </div>

    </aside>

    <!-- Main -->
    <div
      class="flex flex-1 flex-col overflow-hidden transition-all duration-300"
      :class="sidebarOpen ? 'md:ml-64' : 'md:ml-20'"
    >

      <!-- Mobile Header -->
      <header
        class="flex h-16 items-center justify-between border-b border-gray-200 bg-white px-5 md:hidden"
      >
        <h1 class="text-lg font-bold">
          SunTrack
        </h1>

        <button
          @click="sidebarOpen = true"
          class="rounded-lg p-2 hover:bg-gray-100"
        >
          <i class="fa-solid fa-bars"></i>
        </button>
      </header>

      <!-- Content -->
      <main class="flex-1 overflow-y-auto bg-gray-50 p-6 lg:p-8">
        <router-view />
      </main>

    </div>

  </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";

const authStore = useAuthStore();
const router = useRouter();

const sidebarOpen = ref(true);

const handleLogout = async () => {
    await authStore.logout();
    router.push("/");
};
</script>