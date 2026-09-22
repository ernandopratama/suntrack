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
      <nav
        class="flex-1 space-y-1 overflow-y-auto px-3 py-5"
        @mouseover="showSidebarTooltip"
        @mouseout="hideSidebarTooltipOnLeave"
        @focusin="showSidebarTooltip"
        @focusout="hideSidebarTooltip"
        @scroll="hideSidebarTooltip"
      >

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
          data-sidebar-label="Dashboard"
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
          data-sidebar-label="Users"
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
          data-sidebar-label="Roles"
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
          data-sidebar-label="Companies"
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
          data-sidebar-label="Brands"
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
          data-sidebar-label="Campaigns"
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
          data-sidebar-label="Promotions"
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
          data-sidebar-label="Tasks"
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
          data-sidebar-label="Performance Reports"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="$route.path.startsWith('/performance-reports') ? 'shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'"
          :style="$route.path.startsWith('/performance-reports') ? { background: '#D0E7E6', color: '#293681' } : {}"
        >
          <i class="fa-solid fa-file-lines w-5 text-center" :style="$route.path.startsWith('/performance-reports') ? { color: '#4274D9' } : {}"></i>
          <span v-if="sidebarOpen" class="ml-3">Performance Reports</span>
          <span v-if="$route.path.startsWith('/performance-reports') && sidebarOpen" class="ml-auto h-2 w-2 rounded-full" style="background: #4274d9"></span>
        </router-link>

        <!-- PMS Secure Links -->
        <router-link
          v-if="$hasRole('Super Admin')"
          to="/performance-report-links"
          data-sidebar-label="Secure Link PMS"
          @click="closeOnMobile"
          class="group flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
          :class="$route.path.startsWith('/performance-report-links') ? 'shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-[#293681]'"
          :style="$route.path.startsWith('/performance-report-links') ? { background: '#D0E7E6', color: '#293681' } : {}"
        >
          <i class="fa-solid fa-link w-5 text-center" :style="$route.path.startsWith('/performance-report-links') ? { color: '#4274D9' } : {}"></i>
          <span v-if="sidebarOpen" class="ml-3">Secure Link PMS</span>
        </router-link>

        <!-- Products -->
        <router-link
          v-if="$can('product.view')"
          to="/products"
          data-sidebar-label="Products"
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
          v-if="$can('activity.view') || $can('report.export') || $hasRole('Super Admin') || $hasRole('Admin') || $hasRole('Tim')"
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
          data-sidebar-label="Activity Logs"
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
          data-sidebar-label="Export"
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
          v-if="$hasRole('Super Admin') || $hasRole('Admin') || $hasRole('Tim')"
          to="/settings"
          data-sidebar-label="Settings"
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
            </div>

            <ThemeToggle />
          </div>

          <div class="mt-3 flex justify-center">
            <button
              type="button"
              aria-label="Logout"
              title="Logout"
              class="suntrack-logout-btn"
              @click="handleLogout"
            >
              <span class="suntrack-logout-sign" aria-hidden="true">
                <svg viewBox="0 0 512 512">
                  <path
                    d="M377.9 105.9 500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9-18.7 0-33.9-15.2-33.9-33.9V320H192c-17.7 0-32-14.3-32-32v-64c0-17.7 14.3-32 32-32h128v-62.1c0-18.7 15.2-33.9 33.9-33.9 9 0 17.6 3.6 24 9.9ZM160 96H96c-17.7 0-32 14.3-32 32v256c0 17.7 14.3 32 32 32h64c17.7 0 32 14.3 32 32s-14.3 32-32 32H96c-53 0-96-43-96-96V128c0-53 43-96 96-96h64c17.7 0 32 14.3 32 32s-14.3 32-32 32Z"
                  />
                </svg>
              </span>

              <span class="suntrack-logout-text">Logout</span>
            </button>
          </div>
        </div>

        <button
          v-else
          type="button"
          aria-label="Logout"
          title="Logout"
          class="suntrack-logout-compact mx-auto"
          @click="handleLogout"
        >
          <svg viewBox="0 0 512 512" aria-hidden="true">
            <path
              d="M377.9 105.9 500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9-18.7 0-33.9-15.2-33.9-33.9V320H192c-17.7 0-32-14.3-32-32v-64c0-17.7 14.3-32 32-32h128v-62.1c0-18.7 15.2-33.9 33.9-33.9 9 0 17.6 3.6 24 9.9ZM160 96H96c-17.7 0-32 14.3-32 32v256c0 17.7 14.3 32 32 32h64c17.7 0 32 14.3 32 32s-14.3 32-32 32H96c-53 0-96-43-96-96V128c0-53 43-96 96-96h64c17.7 0 32 14.3 32 32s-14.3 32-32 32Z"
            />
          </svg>
        </button>
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

    <Teleport to="body">
      <Transition name="suntrack-sidebar-tooltip">
        <div
          v-if="!sidebarOpen && sidebarTooltip.visible"
          role="tooltip"
          class="suntrack-sidebar-tooltip"
          :style="{
            left: '88px',
            top: `${sidebarTooltip.top}px`,
            '--tooltip-gradient': sidebarTooltip.theme.gradient,
            '--tooltip-border': sidebarTooltip.theme.border,
            '--tooltip-glow': sidebarTooltip.theme.glow,
          }"
        >
          <span class="suntrack-sidebar-tooltip-label">{{ sidebarTooltip.label }}</span>
        </div>
      </Transition>
    </Teleport>

    <Teleport to="body">
      <div
        v-if="showTaskNotificationModal"
        class="fixed inset-0 z-[10020] flex items-center justify-center overflow-y-auto bg-slate-950/55 px-4 py-8 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="task-notification-title"
      >
        <button class="absolute inset-0 h-full w-full cursor-default" aria-label="Tutup pemberitahuan" @click="closeTaskNotificationModal"></button>

        <section class="relative z-10 w-full max-w-2xl overflow-hidden rounded-3xl border border-violet-100 bg-white shadow-2xl">
          <header class="relative overflow-hidden border-b border-violet-100 bg-gradient-to-br from-violet-50 via-white to-blue-50 px-5 py-5 sm:px-7">
            <div class="absolute -right-10 -top-12 h-36 w-36 rounded-full bg-violet-200/35 blur-2xl"></div>
            <div class="relative flex items-start gap-4 pr-12">
              <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-600 text-white shadow-lg shadow-violet-200">
                <i class="fa-solid fa-bell"></i>
              </span>
              <div>
                <p class="text-[11px] font-extrabold uppercase tracking-[0.18em] text-violet-600">Pemberitahuan Task</p>
                <h2 id="task-notification-title" class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">
                  Anda memiliki {{ taskLoginNotifications.length }} tugas yang perlu diperhatikan
                </h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">Periksa tenggat dan detail task berikut sebelum melanjutkan pekerjaan.</p>
              </div>
            </div>
            <button type="button" class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-800" aria-label="Tutup" @click="closeTaskNotificationModal">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </header>

          <div class="max-h-[55vh] space-y-3 overflow-y-auto p-5 sm:p-7">
            <article v-for="notification in taskLoginNotifications" :key="notification.id" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 transition hover:border-violet-200 hover:bg-violet-50/40">
              <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600">
                  <i class="fa-solid fa-clock"></i>
                </span>
                <div class="min-w-0 flex-1">
                  <h3 class="font-extrabold text-slate-900">{{ notification.subject || 'Pengingat Task' }}</h3>
                  <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-600">{{ notification.body }}</p>
                  <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <time class="text-[11px] font-semibold text-slate-400">{{ formatTaskNotificationDate(notification.created_at) }}</time>
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-violet-700" @click="openNotificationTask(notification)">
                      <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                      Lihat Task
                    </button>
                  </div>
                </div>
              </div>
            </article>
          </div>

          <footer class="flex justify-end border-t border-slate-100 bg-slate-50 px-5 py-4 sm:px-7">
            <button type="button" :disabled="closingTaskNotifications" class="rounded-xl bg-[#293681] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#202b68] disabled:cursor-wait disabled:opacity-60" @click="closeTaskNotificationModal">
              {{ closingTaskNotifications ? 'Menyimpan...' : 'Saya Mengerti' }}
            </button>
          </footer>
        </section>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { reactive, ref, watch, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import ThemeToggle from "../components/ThemeToggle.vue";
import api from "../utils/api";

const authStore = useAuthStore();
const router = useRouter();
const showTaskNotificationModal = ref(false);
const taskLoginNotifications = ref([]);
const closingTaskNotifications = ref(false);

const formatTaskNotificationDate = (value) => value
    ? new Date(value).toLocaleString("id-ID", { dateStyle: "medium", timeStyle: "short" })
    : "";

const loadLoginTaskNotifications = async () => {
    if (!authStore.consumeTaskLoginNotifications() || !authStore.can("task.view")) return;

    try {
        const response = await api.get("/admin/tasks/notifications");
        taskLoginNotifications.value = response.data.data?.notifications || [];
        showTaskNotificationModal.value = taskLoginNotifications.value.length > 0;
    } catch {
        taskLoginNotifications.value = [];
        showTaskNotificationModal.value = false;
    }
};

const closeTaskNotificationModal = async () => {
    if (closingTaskNotifications.value) return;

    closingTaskNotifications.value = true;
    try {
        if (taskLoginNotifications.value.length) {
            await api.post("/admin/tasks/notifications/read");
        }
    } finally {
        showTaskNotificationModal.value = false;
        taskLoginNotifications.value = [];
        closingTaskNotifications.value = false;
    }
};

const openNotificationTask = async (notification) => {
    const taskId = notification.notifiable_id;
    await closeTaskNotificationModal();
    await router.push({ path: "/tasks", query: taskId ? { task: taskId } : {} });
};

const SIDEBAR_AUTO_CLOSE_DELAY_MS = 5000;
const SIDEBAR_TOOLTIP_THEMES = {
    Dashboard: {
        gradient: "linear-gradient(135deg, #102a56, #1d4ed8, #3b82f6, #0b1835)",
        border: "rgba(96, 165, 250, 0.7)",
        glow: "rgba(59, 130, 246, 0.65)",
    },
    Users: {
        gradient: "linear-gradient(135deg, #12352b, #047857, #10b981, #09251d)",
        border: "rgba(52, 211, 153, 0.7)",
        glow: "rgba(16, 185, 129, 0.65)",
    },
    Roles: {
        gradient: "linear-gradient(135deg, #351352, #7e22ce, #a855f7, #230c38)",
        border: "rgba(192, 132, 252, 0.7)",
        glow: "rgba(168, 85, 247, 0.65)",
    },
    Companies: {
        gradient: "linear-gradient(135deg, #3d2508, #b45309, #f59e0b, #2b1905)",
        border: "rgba(251, 191, 36, 0.7)",
        glow: "rgba(245, 158, 11, 0.65)",
    },
    Brands: {
        gradient: "linear-gradient(135deg, #3e1025, #be185d, #ec4899, #2a0a19)",
        border: "rgba(244, 114, 182, 0.7)",
        glow: "rgba(236, 72, 153, 0.65)",
    },
    Campaigns: {
        gradient: "linear-gradient(135deg, #3d160c, #c2410c, #f97316, #2b0f08)",
        border: "rgba(251, 146, 60, 0.7)",
        glow: "rgba(249, 115, 22, 0.65)",
    },
    Promotions: {
        gradient: "linear-gradient(135deg, #37100e, #b91c1c, #ef4444, #260908)",
        border: "rgba(248, 113, 113, 0.7)",
        glow: "rgba(239, 68, 68, 0.65)",
    },
    Tasks: {
        gradient: "linear-gradient(135deg, #0e3440, #0e7490, #06b6d4, #08242c)",
        border: "rgba(34, 211, 238, 0.7)",
        glow: "rgba(6, 182, 212, 0.65)",
    },
    "Performance Reports": {
        gradient: "linear-gradient(135deg, #18350d, #4d7c0f, #84cc16, #102507)",
        border: "rgba(163, 230, 53, 0.7)",
        glow: "rgba(132, 204, 22, 0.65)",
    },
    "Secure Link PMS": {
        gradient: "linear-gradient(135deg, #0d3540, #0f766e, #14b8a6, #082622)",
        border: "rgba(45, 212, 191, 0.7)",
        glow: "rgba(20, 184, 166, 0.65)",
    },
    Products: {
        gradient: "linear-gradient(135deg, #33280b, #a16207, #eab308, #251c06)",
        border: "rgba(250, 204, 21, 0.7)",
        glow: "rgba(234, 179, 8, 0.65)",
    },
    "Activity Logs": {
        gradient: "linear-gradient(135deg, #20253a, #475569, #64748b, #141827)",
        border: "rgba(148, 163, 184, 0.7)",
        glow: "rgba(100, 116, 139, 0.65)",
    },
    Export: {
        gradient: "linear-gradient(135deg, #152c45, #0369a1, #0ea5e9, #0b1e31)",
        border: "rgba(56, 189, 248, 0.7)",
        glow: "rgba(14, 165, 233, 0.65)",
    },
    Settings: {
        gradient: "linear-gradient(135deg, #29203f, #4f46e5, #818cf8, #191329)",
        border: "rgba(165, 180, 252, 0.7)",
        glow: "rgba(99, 102, 241, 0.65)",
    },
};
const DEFAULT_SIDEBAR_TOOLTIP_THEME = SIDEBAR_TOOLTIP_THEMES.Dashboard;
const sidebarOpen = ref(window.innerWidth >= 768);
const sidebarTooltip = reactive({
    visible: false,
    label: "",
    top: 0,
    theme: DEFAULT_SIDEBAR_TOOLTIP_THEME,
});
let sidebarAutoCloseTimer = null;

const hideSidebarTooltip = () => {
    sidebarTooltip.visible = false;
};

const showSidebarTooltip = (event) => {
    if (sidebarOpen.value || !(event.target instanceof Element)) {
        hideSidebarTooltip();
        return;
    }

    const item = event.target.closest("[data-sidebar-label]");
    if (!item) return;

    const bounds = item.getBoundingClientRect();
    sidebarTooltip.label = item.dataset.sidebarLabel || "";
    sidebarTooltip.theme = SIDEBAR_TOOLTIP_THEMES[sidebarTooltip.label] || DEFAULT_SIDEBAR_TOOLTIP_THEME;
    sidebarTooltip.top = Math.max(20, Math.min(window.innerHeight - 20, bounds.top + bounds.height / 2));
    sidebarTooltip.visible = sidebarTooltip.label !== "";
};

const hideSidebarTooltipOnLeave = (event) => {
    if (event.target instanceof Element && event.relatedTarget instanceof Element) {
        const currentItem = event.target.closest("[data-sidebar-label]");
        const nextItem = event.relatedTarget.closest("[data-sidebar-label]");
        if (currentItem && currentItem === nextItem) return;
    }
    hideSidebarTooltip();
};

const clearSidebarAutoCloseTimer = () => {
    if (sidebarAutoCloseTimer !== null) {
        window.clearTimeout(sidebarAutoCloseTimer);
        sidebarAutoCloseTimer = null;
    }
};

const scheduleSidebarAutoClose = () => {
    clearSidebarAutoCloseTimer();

    sidebarAutoCloseTimer = window.setTimeout(() => {
        sidebarOpen.value = false;
    }, SIDEBAR_AUTO_CLOSE_DELAY_MS);
};

watch(sidebarOpen, (isOpen) => {
    hideSidebarTooltip();
    if (isOpen) {
        scheduleSidebarAutoClose();
        return;
    }

    clearSidebarAutoCloseTimer();
}, { immediate: true });

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
    loadLoginTaskNotifications();
});

onUnmounted(() => {
    window.removeEventListener("resize", handleResize);
    clearSidebarAutoCloseTimer();
});

const closeOnMobile = () => {
    hideSidebarTooltip();
    if (window.innerWidth < 768) {
        sidebarOpen.value = false;
    }
};

const handleLogout = async () => {
    await authStore.logout();
    router.push("/");
};
</script>

<style scoped>
.suntrack-sidebar-tooltip {
  --tooltip-gradient: linear-gradient(135deg, #102a56, #1d4ed8, #3b82f6, #0b1835);
  --tooltip-border: rgba(96, 165, 250, 0.7);
  --tooltip-glow: rgba(59, 130, 246, 0.65);
  pointer-events: none;
  position: fixed;
  z-index: 10000;
  isolation: isolate;
  overflow: hidden;
  transform: translateY(-50%);
  min-width: 92px;
  padding: 8px 13px;
  border: 1px solid var(--tooltip-border);
  border-radius: 15px;
  background: var(--tooltip-gradient);
  background-size: 400% 400%;
  color: #ffffff;
  font-size: 0.8125rem;
  font-weight: 700;
  line-height: 1.25;
  text-align: center;
  white-space: nowrap;
  backdrop-filter: blur(14px) saturate(180%);
  -webkit-backdrop-filter: blur(14px) saturate(180%);
  box-shadow:
    inset 0 1px 2px rgba(255, 255, 255, 0.35),
    inset 0 0 10px var(--tooltip-glow),
    0 0 18px var(--tooltip-glow),
    0 8px 18px rgba(0, 0, 0, 0.28);
  animation: suntrack-tooltip-gradient 6s ease infinite;
}

.suntrack-sidebar-tooltip::before {
  content: "";
  position: absolute;
  z-index: 0;
  top: -75%;
  left: -65%;
  width: 60%;
  height: 250%;
  transform: rotate(25deg) translateX(-220%);
  background: linear-gradient(
    120deg,
    rgba(255, 255, 255, 0) 20%,
    rgba(255, 255, 255, 0.42) 50%,
    rgba(255, 255, 255, 0) 80%
  );
  animation: suntrack-tooltip-shine 900ms ease-out;
}

.suntrack-sidebar-tooltip-label {
  position: relative;
  z-index: 1;
}

.suntrack-sidebar-tooltip-enter-active,
.suntrack-sidebar-tooltip-leave-active {
  transition: opacity 160ms ease, transform 160ms ease;
}

.suntrack-sidebar-tooltip-enter-from,
.suntrack-sidebar-tooltip-leave-to {
  opacity: 0;
  transform: translate(-8px, -50%);
}

@keyframes suntrack-tooltip-gradient {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

@keyframes suntrack-tooltip-shine {
  from {
    transform: rotate(25deg) translateX(-220%);
  }
  to {
    transform: rotate(25deg) translateX(500%);
  }
}

.suntrack-logout-btn {
  position: relative;
  display: flex;
  width: 38px;
  height: 38px;
  cursor: pointer;
  align-items: center;
  justify-content: flex-start;
  overflow: hidden;
  border: 0;
  border-radius: 9999px;
  background-color: #dc2626;
  box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
  color: #ffffff;
  transition: width 300ms ease, background-color 300ms ease, transform 150ms ease;
}

.suntrack-logout-sign {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: center;
  transition: width 300ms ease, padding-left 300ms ease;
}

.suntrack-logout-sign svg {
  width: 15px;
  flex-shrink: 0;
}

.suntrack-logout-sign path {
  fill: currentColor;
}

.suntrack-logout-text {
  position: absolute;
  right: 0;
  width: 0;
  overflow: hidden;
  opacity: 0;
  color: #ffffff;
  font-size: 0.6875rem;
  font-weight: 700;
  white-space: nowrap;
  transition: width 300ms ease, padding-right 300ms ease, opacity 300ms ease;
}

.suntrack-logout-btn:hover,
.suntrack-logout-btn:focus-visible {
  width: 106px;
  background-color: #b91c1c;
  outline: none;
}

.suntrack-logout-btn:hover .suntrack-logout-sign,
.suntrack-logout-btn:focus-visible .suntrack-logout-sign {
  width: 30%;
  padding-left: 11px;
}

.suntrack-logout-btn:hover .suntrack-logout-text,
.suntrack-logout-btn:focus-visible .suntrack-logout-text {
  width: 70%;
  padding-right: 8px;
  opacity: 1;
}

.suntrack-logout-btn:focus-visible {
  box-shadow: 0 0 0 3px rgba(248, 113, 113, 0.45);
}

.suntrack-logout-btn:active {
  transform: translate(2px, 2px);
}

.suntrack-logout-compact {
  display: flex;
  width: 40px;
  height: 40px;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 9999px;
  background-color: #dc2626;
  box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
  color: #ffffff;
}

.suntrack-logout-compact svg {
  width: 16px;
}

.suntrack-logout-compact path {
  fill: currentColor;
}

.suntrack-logout-compact:focus-visible {
  outline: 3px solid rgba(248, 113, 113, 0.55);
  outline-offset: 2px;
}

@media (hover: none) {
  .suntrack-logout-btn {
    width: 106px;
  }

  .suntrack-logout-sign {
    width: 30%;
    padding-left: 11px;
  }

  .suntrack-logout-text {
    width: 70%;
    padding-right: 8px;
    opacity: 1;
  }
}
</style>
