<template>
  <div class="fixed inset-0 flex overflow-hidden bg-page text-content">

    <!-- Mobile Backdrop -->
    <div
      v-if="sidebarOpen"
      @click="sidebarOpen = false"
      class="fixed inset-0 z-30 bg-[#293681]/20 backdrop-blur-[3px] md:hidden"
    ></div>

    <!-- Floating Toggle -->
    <button
      @click="sidebarOpen = !sidebarOpen"
      class="fixed top-5 z-50 hidden h-10 w-10 items-center justify-center rounded-full text-white shadow-lg transition-all duration-300 hover:scale-105 md:flex"
      :style="{
        left: sidebarOpen ? '240px' : '60px',
        background: '#4274D9',
        boxShadow: '0 8px 20px rgba(66, 116, 217, 0.25)'
      }"
    >
      <i
        :class="
          sidebarOpen
            ? 'fa-solid fa-chevron-left'
            : 'fa-solid fa-chevron-right'
        "
      ></i>
    </button>

    <!-- Sidebar -->
    <aside
      class="fixed inset-y-0 left-0 z-40 flex flex-col border-r border-default bg-surface transition-all duration-300"
      :class="
        sidebarOpen
          ? 'w-64 translate-x-0'
          : 'w-20 -translate-x-full md:translate-x-0'
      "
    >

      <!-- Logo -->
      <div
        class="relative flex h-16 flex-shrink-0 items-center border-b border-slate-100 px-5"
      >
        <!-- Logo Accent -->
        <div
          v-if="sidebarOpen"
          class="mr-3 flex h-9 w-9 items-center justify-center rounded-xl"
          style="background: #d0e7e6"
        >
          <img
            src="/favicon.png"
            alt="SunTrack"
            class="h-6 w-6 object-contain"
          />
        </div>

        <span
          v-if="sidebarOpen"
          class="whitespace-nowrap text-xl font-bold tracking-tight text-brand-strong"
        >
          SunTrack
        </span>

        <img
          v-else
          src="/favicon.png"
          alt="SunTrack"
          class="mx-auto h-8 w-8 object-contain"
        />
      </div>

      <!-- Navigation -->
      <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">

        <!-- Main Navigation -->
        <p
          v-if="sidebarOpen"
          class="mb-3 px-3 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400"
        >
          Main Menu
        </p>

        <!-- Dashboard -->
        <router-link
          v-if="$can('campaign.view')"
          to="/dashboard"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="
            $route.path === '/dashboard'
              ? 'shadow-sm'
              : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'
          "
          :style="
            $route.path === '/dashboard'
              ? {
                  background: '#D0E7E6',
                  color: '#293681'
                }
              : {}
          "
        >
          <i
            class="fa-solid fa-chart-line w-5 text-center transition"
            :style="
              $route.path === '/dashboard'
                ? { color: '#4274D9' }
                : {}
            "
          ></i>

          <span v-if="sidebarOpen" class="ml-3">
            Dashboard
          </span>

          <span
            v-if="$route.path === '/dashboard' && sidebarOpen"
            class="ml-auto h-2 w-2 rounded-full"
            style="background: #4274d9"
          ></span>
        </router-link>

        <!-- Users -->
        <router-link
          v-if="$can('user.view')"
          to="/users"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/users')
              ? 'shadow-sm'
              : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'
          "
          :style="
            $route.path.startsWith('/users')
              ? { background: '#D0E7E6', color: '#293681' }
              : {}
          "
        >
          <i
            class="fa-solid fa-users w-5 text-center"
            :style="
              $route.path.startsWith('/users')
                ? { color: '#4274D9' }
                : {}
            "
          ></i>

          <span v-if="sidebarOpen" class="ml-3">Users</span>

          <span
            v-if="$route.path.startsWith('/users') && sidebarOpen"
            class="ml-auto h-2 w-2 rounded-full"
            style="background: #4274d9"
          ></span>
        </router-link>

        <!-- Roles -->
        <router-link
          v-if="$hasRole('Super Admin')"
          to="/roles"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/roles')
              ? 'shadow-sm'
              : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'
          "
          :style="
            $route.path.startsWith('/roles')
              ? { background: '#D0E7E6', color: '#293681' }
              : {}
          "
        >
          <i
            class="fa-solid fa-user-shield w-5 text-center"
            :style="
              $route.path.startsWith('/roles')
                ? { color: '#4274D9' }
                : {}
            "
          ></i>

          <span v-if="sidebarOpen" class="ml-3">Roles</span>

          <span
            v-if="$route.path.startsWith('/roles') && sidebarOpen"
            class="ml-auto h-2 w-2 rounded-full"
            style="background: #4274d9"
          ></span>
        </router-link>

        <!-- Companies -->
        <router-link
          v-if="$can('company.view')"
          to="/companies"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/companies')
              ? 'shadow-sm'
              : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'
          "
          :style="
            $route.path.startsWith('/companies')
              ? { background: '#D0E7E6', color: '#293681' }
              : {}
          "
        >
          <i
            class="fa-solid fa-building w-5 text-center"
            :style="
              $route.path.startsWith('/companies')
                ? { color: '#4274D9' }
                : {}
            "
          ></i>

          <span v-if="sidebarOpen" class="ml-3">Companies</span>

          <span
            v-if="$route.path.startsWith('/companies') && sidebarOpen"
            class="ml-auto h-2 w-2 rounded-full"
            style="background: #4274d9"
          ></span>
        </router-link>

        <!-- Brands -->
        <router-link
          v-if="$can('brand.view')"
          to="/brands"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/brands')
              ? 'shadow-sm'
              : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'
          "
          :style="
            $route.path.startsWith('/brands')
              ? { background: '#D0E7E6', color: '#293681' }
              : {}
          "
        >
          <i
            class="fa-solid fa-tag w-5 text-center"
            :style="
              $route.path.startsWith('/brands')
                ? { color: '#4274D9' }
                : {}
            "
          ></i>

          <span v-if="sidebarOpen" class="ml-3">Brands</span>

          <span
            v-if="$route.path.startsWith('/brands') && sidebarOpen"
            class="ml-auto h-2 w-2 rounded-full"
            style="background: #4274d9"
          ></span>
        </router-link>

        <!-- Campaigns -->
        <router-link
          v-if="$can('campaign.view')"
          to="/campaigns"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/campaigns')
              ? 'shadow-sm'
              : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'
          "
          :style="
            $route.path.startsWith('/campaigns')
              ? { background: '#D0E7E6', color: '#293681' }
              : {}
          "
        >
          <i
            class="fa-solid fa-bullhorn w-5 text-center"
            :style="
              $route.path.startsWith('/campaigns')
                ? { color: '#4274D9' }
                : {}
            "
          ></i>

          <span v-if="sidebarOpen" class="ml-3">Campaigns</span>

          <span
            v-if="$route.path.startsWith('/campaigns') && sidebarOpen"
            class="ml-auto h-2 w-2 rounded-full"
            style="background: #4274d9"
          ></span>
        </router-link>

        <!-- Promotions -->
        <router-link
          v-if="$can('promotion.view')"
          to="/promotions"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/promotions')
              ? 'shadow-sm'
              : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'
          "
          :style="
            $route.path.startsWith('/promotions')
              ? { background: '#D0E7E6', color: '#293681' }
              : {}
          "
        >
          <i
            class="fa-solid fa-tags w-5 text-center"
            :style="
              $route.path.startsWith('/promotions')
                ? { color: '#4274D9' }
                : {}
            "
          ></i>

          <span v-if="sidebarOpen" class="ml-3">Promotions</span>

          <span
            v-if="$route.path.startsWith('/promotions') && sidebarOpen"
            class="ml-auto h-2 w-2 rounded-full"
            style="background: #4274d9"
          ></span>
        </router-link>

        <!-- Tasks -->
        <router-link
          v-if="$can('task.view')"
          to="/tasks"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/tasks')
              ? 'shadow-sm'
              : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'
          "
          :style="
            $route.path.startsWith('/tasks')
              ? { background: '#D0E7E6', color: '#293681' }
              : {}
          "
        >
          <i
            class="fa-solid fa-list-check w-5 text-center"
            :style="
              $route.path.startsWith('/tasks')
                ? { color: '#4274D9' }
                : {}
            "
          ></i>

          <span v-if="sidebarOpen" class="ml-3">Tasks</span>

          <span
            v-if="$route.path.startsWith('/tasks') && sidebarOpen"
            class="ml-auto h-2 w-2 rounded-full"
            style="background: #4274d9"
          ></span>
        </router-link>

        <!-- Performance Reports -->
        <router-link
          v-if="$can('performance-report.view')"
          to="/performance-reports"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="$route.path.startsWith('/performance-reports') ? 'shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'"
          :style="$route.path.startsWith('/performance-reports') ? { background: '#D0E7E6', color: '#293681' } : {}"
        >
          <i class="fa-solid fa-file-chart-column w-5 text-center" :style="$route.path.startsWith('/performance-reports') ? { color: '#4274D9' } : {}"></i>
          <span v-if="sidebarOpen" class="ml-3">Performance Reports</span>
          <span v-if="$route.path.startsWith('/performance-reports') && sidebarOpen" class="ml-auto h-2 w-2 rounded-full" style="background: #4274d9"></span>
        </router-link>

        <!-- Products -->
        <router-link
          v-if="$can('product.view')"
          to="/products"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/products')
              ? 'shadow-sm'
              : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'
          "
          :style="
            $route.path.startsWith('/products')
              ? { background: '#D0E7E6', color: '#293681' }
              : {}
          "
        >
          <i
            class="fa-solid fa-box w-5 text-center"
            :style="
              $route.path.startsWith('/products')
                ? { color: '#4274D9' }
                : {}
            "
          ></i>

          <span v-if="sidebarOpen" class="ml-3">Products</span>

          <span
            v-if="$route.path.startsWith('/products') && sidebarOpen"
            class="ml-auto h-2 w-2 rounded-full"
            style="background: #4274d9"
          ></span>
        </router-link>

        <!-- Divider -->
        <div
          v-if="$can('activity.view') || $can('report.export') || $can('settings.view')"
          class="my-5 flex items-center gap-3 px-3"
        >
          <div class="h-px flex-1 bg-slate-100"></div>

          <span
            v-if="sidebarOpen"
            class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400"
          >
            System
          </span>

          <div
            v-else
            class="h-px flex-1 bg-slate-100"
          ></div>
        </div>

        <!-- Activity -->
        <router-link
          v-if="$can('activity.view')"
          to="/activity"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/activity')
              ? 'shadow-sm'
              : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'
          "
          :style="
            $route.path.startsWith('/activity')
              ? { background: '#D0E7E6', color: '#293681' }
              : {}
          "
        >
          <i
            class="fa-solid fa-file-lines w-5 text-center"
            :style="
              $route.path.startsWith('/activity')
                ? { color: '#4274D9' }
                : {}
            "
          ></i>

          <span v-if="sidebarOpen" class="ml-3">
            Activity Logs
          </span>
        </router-link>

        <!-- Export -->
        <router-link
          v-if="$can('report.export')"
          to="/export"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/export')
              ? 'shadow-sm'
              : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'
          "
          :style="
            $route.path.startsWith('/export')
              ? { background: '#D0E7E6', color: '#293681' }
              : {}
          "
        >
          <i
            class="fa-solid fa-download w-5 text-center"
            :style="
              $route.path.startsWith('/export')
                ? { color: '#4274D9' }
                : {}
            "
          ></i>

          <span v-if="sidebarOpen" class="ml-3">Export</span>
        </router-link>

        <!-- Settings -->
        <router-link
          v-if="$can('settings.view')"
          to="/settings"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="
            $route.path.startsWith('/settings')
              ? 'shadow-sm'
              : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'
          "
          :style="
            $route.path.startsWith('/settings')
              ? { background: '#D0E7E6', color: '#293681' }
              : {}
          "
        >
          <i
            class="fa-solid fa-gear w-5 text-center transition-transform duration-300 group-hover:rotate-45"
            :style="
              $route.path.startsWith('/settings')
                ? { color: '#4274D9' }
                : {}
            "
          ></i>

          <span v-if="sidebarOpen" class="ml-3">Settings</span>
        </router-link>

      </nav>

      <!-- User -->
      <div class="flex-shrink-0 border-t border-default p-4">
        <div
          v-if="sidebarOpen"
          class="rounded-2xl p-3"
          style="background: var(--ui-surface-muted)"
        >
          <div class="flex items-center">

            <!-- Avatar -->
            <div
              class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
              style="background: #d0e7e6"
            >
              <span
                class="font-bold"
                style="color: #293681"
              >
                {{ (authStore.user?.name || 'A').charAt(0) }}
              </span>
            </div>

            <!-- User Info -->
            <div class="ml-3 min-w-0 flex-1 overflow-hidden">
              <p
                class="truncate text-sm font-semibold text-content"
              >
                {{ authStore.user?.name || 'Administrator' }}
              </p>

              <button
                @click="handleLogout"
                class="mt-0.5 text-xs font-medium text-content-muted transition hover:text-red-500"
              >
                Logout
              </button>
            </div>

            <ThemeToggle />
          </div>
        </div>

        <ThemeToggle v-else class="mx-auto" />
      </div>

    </aside>

    <!-- Main -->
    <div
      class="flex min-w-0 flex-1 flex-col overflow-hidden transition-all duration-300"
      :class="sidebarOpen ? 'md:ml-64' : 'md:ml-20'"
    >

      <!-- Mobile Header -->
      <header
        class="flex h-16 flex-shrink-0 items-center justify-between border-b border-default bg-surface px-5 md:hidden"
      >
        <div class="flex items-center gap-3">
          <div
            class="flex h-9 w-9 items-center justify-center rounded-xl"
            style="background: #d0e7e6"
          >
            <img
              src="/favicon.png"
              alt="SunTrack"
              class="h-6 w-6 object-contain"
            />
          </div>

          <span
            class="font-bold"
            style="color: #293681"
          >
            SunTrack
          </span>
        </div>

        <div class="flex items-center gap-2">
          <ThemeToggle />
          <button
            @click="sidebarOpen = !sidebarOpen"
            class="flex h-10 w-10 items-center justify-center rounded-xl text-content-soft transition hover:bg-surface-muted"
          >
            <i class="fa-solid fa-bars"></i>
          </button>
        </div>
      </header>

      <!-- Content -->
      <main class="min-w-0 flex-1 overflow-x-hidden overflow-y-auto bg-page p-5 lg:p-7">
        <router-view />
      </main>

    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import ThemeToggle from "../components/ThemeToggle.vue";

const authStore = useAuthStore();
const router = useRouter();

const sidebarOpen = ref(window.innerWidth >= 768);

const handleResize = () => {
    if (window.innerWidth >= 768) {
        if (!sidebarOpen.value) sidebarOpen.value = true;
    } else {
        sidebarOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener("resize", handleResize);
    handleResize();
});

onUnmounted(() => {
    window.removeEventListener("resize", handleResize);
});

const closeOnMobile = () => {
    if (window.innerWidth < 768) {
        sidebarOpen.value = false;
    }
};

const handleLogout = async () => {
    await authStore.logout();
    router.push("/");
};
</script>
