<template>
  <!-- Loading -->
  <div v-if="loading" class="space-y-6 animate-pulse">
    <div class="rounded-2xl border border-gray-200 bg-white p-6">
      <div class="flex items-center justify-between gap-6">
        <div class="space-y-3">
          <div class="h-7 w-64 rounded-lg bg-gray-200"></div>
          <div class="h-4 w-96 rounded bg-gray-100"></div>
        </div>

        <div class="h-10 w-32 rounded-xl bg-gray-200"></div>
      </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
      <div class="flex gap-6 border-b border-gray-100 px-6 py-4">
        <div
          v-for="i in 5"
          :key="i"
          class="h-5 w-20 rounded bg-gray-200"
        ></div>
      </div>

      <div class="space-y-5 p-6">
        <div class="h-24 rounded-xl bg-gray-100"></div>
        <div class="h-24 rounded-xl bg-gray-100"></div>
      </div>
    </div>
  </div>

  <!-- Error -->
  <div v-else-if="error" class="space-y-4">
    <div
      class="rounded-2xl border border-red-100 bg-red-50 p-5 text-sm text-red-700"
    >
      <div class="flex items-start gap-3">
        <div
          class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600"
        >
          <i class="fa-solid fa-triangle-exclamation text-sm"></i>
        </div>

        <div>
          <p class="font-bold">Unable to load campaign</p>
          <p class="mt-1 text-xs text-red-600">
            {{ error }}
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- Campaign -->
  <div v-else-if="campaign" class="space-y-6">

    <!-- =========================
         HEADER
    ========================== -->
    <div
      class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >
      <div class="p-5 sm:p-6">

        <!-- Breadcrumb -->
        <div class="mb-5 flex items-center gap-2 text-xs">
          <router-link
            to="/campaigns"
            class="inline-flex items-center gap-1.5 font-semibold text-gray-400 transition-colors hover:text-[#4274D9]"
          >
            <i class="fa-solid fa-arrow-left text-[10px]"></i>
            Campaigns
          </router-link>

          <i class="fa-solid fa-chevron-right text-[8px] text-gray-300"></i>

          <span class="font-semibold text-gray-500">
            Campaign Detail
          </span>
        </div>

        <div
          class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
        >
          <!-- Campaign Information -->
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2.5">
              <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]"
              >
                <i class="fa-solid fa-bullhorn text-base"></i>
              </div>

              <div class="min-w-0">
                <h1
                  class="truncate text-2xl font-extrabold tracking-tight text-[#293681] sm:text-3xl"
                >
                  {{ campaign.name }}
                </h1>

                <div class="mt-1 flex flex-wrap items-center gap-2">
                  <span class="text-xs font-medium text-gray-400">
                    Promotional Campaign
                  </span>

                  <span class="text-gray-300">•</span>

                  <span
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                    :class="{
                      'bg-gray-100 text-gray-600': campaign.status === 'draft',
                      'bg-amber-100 text-amber-700':
                        campaign.status === 'waiting_review' || campaign.status === 'revision',
                      'bg-blue-100 text-blue-700':
                        campaign.status === 'assigned' || campaign.status === 'approved',
                      'bg-emerald-100 text-emerald-700':
                        campaign.status === 'in_progress',
                      'bg-indigo-100 text-indigo-700':
                        campaign.status === 'completed',
                      'bg-rose-100 text-rose-700':
                        campaign.status === 'cancelled'
                    }"
                  >
                    <span
                      class="h-1.5 w-1.5 rounded-full bg-current"
                    ></span>

                    {{ campaign.status_label || campaign.status }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Duration -->
            <div
              class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-gray-500"
            >
              <div class="inline-flex items-center gap-2">
                <span
                  class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#95CCDD]/25 text-[#293681]"
                >
                  <i class="fa-regular fa-calendar text-[11px]"></i>
                </span>

                <div>
                  <span class="font-semibold text-gray-400">
                    Campaign Period
                  </span>

                  <p class="font-bold text-gray-700">
                    {{ campaign.start_date || 'TBD' }}
                    <span class="mx-1 text-gray-300">→</span>
                    {{ campaign.end_date || 'TBD' }}
                  </p>
                </div>
              </div>

              <div
                v-if="campaign.deadline"
                class="inline-flex items-center gap-2"
              >
                <span
                  class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-500"
                >
                  <i class="fa-regular fa-clock text-[11px]"></i>
                </span>

                <div>
                  <span class="font-semibold text-gray-400">
                    Deadline
                  </span>

                  <p class="font-bold text-gray-700">
                    {{ campaign.deadline }}
                  </p>
                </div>
              </div>

              <div class="inline-flex items-center gap-2">
                <span
                  class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#D0E7E6] text-[#293681]"
                >
                  <i class="fa-solid fa-user text-[10px]"></i>
                </span>

                <div>
                  <span class="font-semibold text-gray-400">
                    PIC
                  </span>

                  <p class="font-bold text-gray-700">
                    {{ campaign.pic?.name || 'Unassigned' }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Edit -->
          <button
            v-if="$can('campaign.update')"
            @click="openEditModal"
            type="button"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-[#D0E7E6] bg-[#D0E7E6]/40 px-4 py-2.5 text-xs font-extrabold text-[#293681] shadow-sm transition-all duration-200 hover:border-[#95CCDD] hover:bg-[#D0E7E6] hover:shadow-md"
          >
            <span
              class="flex h-5 w-5 items-center justify-center rounded-md bg-[#293681]/10"
            >
              <i class="fa-solid fa-pen text-[9px]"></i>
            </span>

            <span>Edit Campaign</span>
          </button>
        </div>
      </div>
    </div>

    <!-- =========================
         MAIN CONTENT
    ========================== -->
    <div
      class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
    >

      <!-- Tabs -->
      <div class="border-b border-gray-100 bg-white">
        <nav
          class="flex gap-1 overflow-x-auto px-3 sm:px-5"
          aria-label="Campaign Tabs"
        >
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="currentTab = tab.id"
            type="button"
            :class="[
              currentTab === tab.id
                ? 'border-[#4274D9] bg-[#4274D9]/5 text-[#293681]'
                : 'border-transparent text-gray-500 hover:border-[#95CCDD] hover:bg-[#D0E7E6]/30 hover:text-[#293681]',
              'group relative inline-flex shrink-0 items-center gap-2 border-b-2 px-3 py-4 text-xs font-bold transition-all duration-200'
            ]"
          >
            <i
              v-if="tab.id === 'overview'"
              class="fa-solid fa-chart-pie text-[11px]"
            ></i>

            <i
              v-else-if="tab.id === 'tasks'"
              class="fa-solid fa-list-check text-[11px]"
            ></i>

            <i
              v-else-if="tab.id === 'promotions'"
              class="fa-solid fa-tags text-[11px]"
            ></i>

            <i
              v-else-if="tab.id === 'products'"
              class="fa-solid fa-box text-[11px]"
            ></i>

            <i
              v-else-if="tab.id === 'attachments'"
              class="fa-solid fa-paperclip text-[11px]"
            ></i>

            <i
              v-else-if="tab.id === 'comments'"
              class="fa-regular fa-comments text-[11px]"
            ></i>

            <i
              v-else-if="tab.id === 'secure-link'"
              class="fa-solid fa-link text-[11px]"
            ></i>

            <i
              v-else
              class="fa-solid fa-clock-rotate-left text-[11px]"
            ></i>

            <span>{{ tab.name }}</span>
          </button>
        </nav>
      </div>

      <!-- Tab Content -->
      <div class="min-h-[420px] bg-gray-50/70 p-4 sm:p-6">

        <!-- =========================
             OVERVIEW
        ========================== -->
        <div
          v-if="currentTab === 'overview'"
          class="space-y-5"
        >
          <div
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
          >
            <!-- Section Header -->
            <div
              class="flex items-center gap-3 border-b border-gray-100 px-5 py-4"
            >
              <div
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]"
              >
                <i class="fa-solid fa-circle-info text-sm"></i>
              </div>

              <div>
                <h3 class="text-sm font-extrabold text-[#293681]">
                  Campaign Details
                </h3>

                <p class="mt-0.5 text-[11px] text-gray-400">
                  Basic information about this campaign.
                </p>
              </div>
            </div>

            <div class="p-5">
              <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                <!-- Description -->
                <div
                  class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 sm:col-span-2"
                >
                  <dt
                    class="mb-2 flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-wider text-gray-400"
                  >
                    <i class="fa-regular fa-file-lines text-[#4274D9]"></i>
                    Description
                  </dt>

                  <dd
                    class="whitespace-pre-wrap text-sm font-medium leading-6 text-gray-700"
                  >
                    {{ campaign.description || 'No description provided.' }}
                  </dd>
                </div>

                <!-- Deadline -->
                <div
                  class="rounded-xl border border-gray-100 bg-white p-4"
                >
                  <dt
                    class="flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-wider text-gray-400"
                  >
                    <i class="fa-regular fa-clock text-[#4274D9]"></i>
                    Deadline
                  </dt>

                  <dd class="mt-2 text-sm font-bold text-gray-800">
                    {{ campaign.deadline || 'None' }}
                  </dd>
                </div>

                <!-- PIC -->
                <div
                  class="rounded-xl border border-gray-100 bg-white p-4"
                >
                  <dt
                    class="flex items-center gap-2 text-[11px] font-extrabold uppercase tracking-wider text-gray-400"
                  >
                    <i class="fa-solid fa-user text-[#4274D9]"></i>
                    Person in Charge
                  </dt>

                  <dd class="mt-2 text-sm font-bold text-gray-800">
                    {{ campaign.pic?.name || 'Unassigned' }}
                  </dd>
                </div>

              </dl>
            </div>
          </div>
        </div>

        <!-- =========================
             TASKS
        ========================== -->
        <div v-if="currentTab === 'tasks'" class="space-y-4">

          <div
            class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              <div class="flex items-center gap-2">
                <div
                  class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]"
                >
                  <i class="fa-solid fa-list-check text-sm"></i>
                </div>

                <div>
                  <h3 class="text-sm font-extrabold text-[#293681]">
                    Campaign Tasks
                  </h3>

                  <p class="text-[11px] text-gray-400">
                    Manage tasks associated with this campaign.
                  </p>
                </div>
              </div>
            </div>

            <button
              v-if="$can('task.create')"
              @click="openCreateTaskModal"
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#4274D9] px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#293681] hover:shadow-md"
            >
              <span
                class="flex h-5 w-5 items-center justify-center rounded-md bg-white/15"
              >
                <i class="fa-solid fa-plus text-[9px]"></i>
              </span>

              <span>Add Task</span>
            </button>
          </div>

          <div
            v-if="tasksLoading"
            class="rounded-2xl border border-gray-200 bg-white p-10 text-center"
          >
            <i class="fa-solid fa-spinner fa-spin text-[#4274D9]"></i>
            <p class="mt-2 text-xs font-semibold text-gray-400">
              Loading tasks...
            </p>
          </div>

          <div v-else-if="!tasks.length">
            <EmptyState
              title="No tasks yet"
              description="Add a task to this campaign to get started."
            />
          </div>

          <div
            v-else
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
          >
            <ul class="divide-y divide-gray-100">
              <li
                v-for="t in tasks"
                :key="t.id"
                class="flex flex-col gap-4 px-5 py-4 transition-colors hover:bg-[#D0E7E6]/20 sm:flex-row sm:items-center sm:justify-between"
              >
                <div class="flex min-w-0 items-center gap-3">
                  <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#95CCDD]/20 text-[#293681]"
                  >
                    <i class="fa-solid fa-check-double text-xs"></i>
                  </div>

                  <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-gray-800">
                      {{ t.name }}
                    </p>

                    <div class="mt-1 flex items-center gap-2">
                      <span
                        v-if="t.requires_visual"
                        class="rounded-md bg-[#D0E7E6] px-2 py-0.5 text-[10px] font-bold text-[#293681]"
                      >
                        Visual Required
                      </span>

                      <span
                        v-if="t.deadline"
                        class="text-[11px] text-gray-400"
                      >
                        <i class="fa-regular fa-clock mr-1"></i>
                        {{ t.deadline.slice(0, 10) }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                  <span
                    class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-extrabold"
                    :class="taskStatusClass(t.progress_status)"
                  >
                    {{ taskStatusLabel(t.progress_status) }}
                  </span>

                  <button
                    v-if="$can('task.update')"
                    @click="openEditTaskModal(t)"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-[11px] font-bold text-gray-600 transition-all hover:border-[#95CCDD] hover:bg-[#D0E7E6]/30 hover:text-[#293681]"
                  >
                    <i class="fa-solid fa-pen text-[9px]"></i>
                    Edit
                  </button>

                  <button
                    v-if="$can('task.delete')"
                    @click="confirmDeleteTask(t)"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-[11px] font-bold text-red-600 transition-all hover:bg-red-100"
                  >
                    <i class="fa-solid fa-trash text-[9px]"></i>
                    Delete
                  </button>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- =========================
             PROMOTIONS
        ========================== -->
        <div v-if="currentTab === 'promotions'" class="space-y-4">

          <div
            class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              <div class="flex items-center gap-3">
                <div
                  class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]"
                >
                  <i class="fa-solid fa-tags text-sm"></i>
                </div>

                <div>
                  <h3 class="text-sm font-extrabold text-[#293681]">
                    Linked Promotions
                  </h3>

                  <p class="text-[11px] text-gray-400">
                    Promotions linked to this campaign.
                  </p>
                </div>
              </div>
            </div>

            <button
              @click="isPromotionModalOpen = true"
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#4274D9] px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#293681] hover:shadow-md"
            >
              <span
                class="flex h-5 w-5 items-center justify-center rounded-md bg-white/15"
              >
                <i class="fa-solid fa-plus text-[9px]"></i>
              </span>

              <span>Add Promotion</span>
            </button>
          </div>

          <div
            v-if="promotionsLoading"
            class="rounded-2xl border border-gray-200 bg-white p-10 text-center"
          >
            <i class="fa-solid fa-spinner fa-spin text-[#4274D9]"></i>
            <p class="mt-2 text-xs font-semibold text-gray-400">
              Loading promotions...
            </p>
          </div>

          <div v-else-if="!linkedPromotions.length">
            <EmptyState
              title="No promotions yet"
              description="Add a promotion to this campaign to get started."
            />
          </div>

          <div
            v-else
            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
          >
            <ul class="divide-y divide-gray-100">
              <li
                v-for="p in linkedPromotions"
                :key="p.id"
                class="flex flex-col gap-4 px-5 py-4 transition-colors hover:bg-[#D0E7E6]/20 sm:flex-row sm:items-center sm:justify-between"
              >
                <div class="flex min-w-0 items-center gap-3">
                  <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#95CCDD]/20 text-[#293681]"
                  >
                    <i class="fa-solid fa-tag text-xs"></i>
                  </div>

                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <span
                        class="rounded-md bg-[#D0E7E6] px-2 py-1 font-mono text-[10px] font-extrabold text-[#293681]"
                      >
                        {{ p.code }}
                      </span>

                      <span class="text-sm font-bold text-gray-800">
                        {{ p.name }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                  <StatusBadge :status="p.status" />

                  <router-link
                    :to="`/promotions/${p.id}`"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-[#95CCDD] bg-[#D0E7E6]/30 px-3 py-2 text-[11px] font-bold text-[#293681] transition-all hover:bg-[#D0E7E6]"
                  >
                    <i class="fa-regular fa-eye text-[9px]"></i>
                    View
                  </router-link>

                  <button
                    v-if="$can('promotion.delete')"
                    @click="deletePromotionAction(p)"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-[11px] font-bold text-red-600 transition-all hover:bg-red-100"
                  >
                    <i class="fa-solid fa-trash text-[9px]"></i>
                    Delete
                  </button>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- =========================
             PRODUCTS
        ========================== -->
        <div v-if="currentTab === 'products'" class="space-y-4">

          <div
            class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between"
          >
            <div>
              <div class="flex items-center gap-3">
                <div
                  class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#D0E7E6] text-[#293681]"
                >
                  <i class="fa-solid fa-box text-sm"></i>
                </div>

                <div>
                  <h3 class="text-sm font-extrabold text-[#293681]">
                    Campaign Products
                  </h3>

                  <p class="text-[11px] text-gray-400">
                    Products assigned via promotions.
                  </p>
                </div>
              </div>
            </div>

            <button
              v-if="$can('promotion.update')"
              @click="openAddProductModal"
              type="button"
              class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#4274D9] px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#293681] hover:shadow-md"
            >
              <span
                class="flex h-5 w-5 items-center justify-center rounded-md bg-white/15"
              >
                <i class="fa-solid fa-plus text-[9px]"></i>
              </span>

              <span>Add Product</span>
            </button>
          </div>

          <div
            v-if="campaignProductsLoading"
            class="rounded-2xl border border-gray-200 bg-white p-10 text-center"
          >
            <i class="fa-solid fa-spinner fa-spin text-[#4274D9]"></i>
            <p class="mt-2 text-xs font-semibold text-gray-400">
              Loading products...
            </p>
          </div>

          <EmptyState
            v-else-if="!campaignProducts.length"
            title="No products"
            description="Add products through promotions to set pricing."
          />

          <div v-else class="space-y-4">
            <div
              v-for="promo in campaignProducts"
              :key="promo.id"
              class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
            >
              <!-- Promotion Header -->
              <div
                class="flex flex-col gap-3 border-b border-gray-100 bg-gradient-to-r from-[#D0E7E6]/40 to-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
              >
                <div class="flex items-center gap-3">
                  <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-[#293681] shadow-sm ring-1 ring-[#D0E7E6]"
                  >
                    <i class="fa-solid fa-tag text-xs"></i>
                  </div>

                  <div>
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="text-sm font-extrabold text-[#293681]">
                        {{ promo.name }}
                      </span>

                      <span
                        class="rounded-md bg-white px-2 py-1 font-mono text-[10px] font-bold text-gray-500 ring-1 ring-gray-200"
                      >
                        {{ promo.code }}
                      </span>
                    </div>

                    <p class="mt-0.5 text-[10px] text-gray-400">
                      Promotion pricing
                    </p>
                  </div>
                </div>

                <StatusBadge :status="promo.status" />
              </div>

              <!-- Desktop Table -->
              <div class="hidden overflow-x-auto md:block">
                <table class="min-w-full text-sm">
                  <thead class="bg-gray-50/80">
                    <tr>
                      <th
                        class="px-5 py-3 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-400"
                      >
                        Variant
                      </th>

                      <th
                        class="px-4 py-3 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-400"
                      >
                        Normal
                      </th>

                      <th
                        class="px-4 py-3 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-400"
                      >
                        Campaign
                      </th>

                      <th
                        class="px-4 py-3 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-400"
                      >
                        Discount
                      </th>

                      <th
                        class="px-4 py-3 text-left text-[10px] font-extrabold uppercase tracking-wider text-gray-400"
                      >
                        %
                      </th>

                      <th class="px-5 py-3"></th>
                    </tr>
                  </thead>

                  <tbody class="divide-y divide-gray-100">
                    <tr
                      v-for="v in promo.variants"
                      :key="v.id"
                      class="transition-colors hover:bg-[#D0E7E6]/15"
                    >
                      <td class="px-5 py-3.5">
                        <div class="font-bold text-gray-800">
                          {{ v.name }}
                        </div>

                        <div class="mt-0.5 text-[10px] text-gray-400">
                          {{ v.product?.name }} · {{ v.code }}
                        </div>
                      </td>

                      <td class="px-4 py-3.5 font-medium text-gray-600">
                        {{ formatCurrency(v.promotion_pricing?.normal_price_snapshot) }}
                      </td>

                      <td class="px-4 py-3.5 font-extrabold text-[#293681]">
                        {{ formatCurrency(v.promotion_pricing?.campaign_price) }}
                      </td>

                      <td class="px-4 py-3.5 font-extrabold text-green-600">
                        {{ formatCurrency(v.promotion_pricing?.discount_price) }}
                      </td>

                      <td class="px-4 py-3.5">
                        <span
                          class="inline-flex rounded-full px-2 py-1 text-[10px] font-extrabold"
                          :class="
                            discountPercent(v) > 0
                              ? 'bg-[#D0E7E6] text-[#293681]'
                              : 'bg-gray-100 text-gray-400'
                          "
                        >
                          {{ discountPercent(v) }}%
                        </span>
                      </td>

                      <td class="px-5 py-3.5">
                        <div class="flex items-center justify-end gap-1">
                          <button
                            v-if="$can('promotion.update')"
                            @click="openEditVariantPricing(promo.id, v)"
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-2 text-[10px] font-bold text-[#4274D9] transition-colors hover:bg-[#D0E7E6]/40 hover:text-[#293681]"
                          >
                            <i class="fa-solid fa-pen text-[8px]"></i>
                            Edit
                          </button>

                          <button
                            v-if="$can('promotion.update')"
                            @click="removeVariantFromPromo(promo.id, v.id)"
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-2 text-[10px] font-bold text-red-500 transition-colors hover:bg-red-50 hover:text-red-700"
                          >
                            <i class="fa-solid fa-xmark text-[9px]"></i>
                            Remove
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Mobile Product Cards -->
              <div class="divide-y divide-gray-100 md:hidden">
                <div
                  v-for="v in promo.variants"
                  :key="v.id"
                  class="space-y-4 p-4"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <p class="text-sm font-bold text-gray-800">
                        {{ v.name }}
                      </p>

                      <p class="mt-0.5 text-[10px] text-gray-400">
                        {{ v.product?.name }} · {{ v.code }}
                      </p>
                    </div>

                    <span
                      class="rounded-full px-2 py-1 text-[10px] font-extrabold"
                      :class="
                        discountPercent(v) > 0
                          ? 'bg-[#D0E7E6] text-[#293681]'
                          : 'bg-gray-100 text-gray-400'
                      "
                    >
                      {{ discountPercent(v) }}%
                    </span>
                  </div>

                  <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-gray-50 p-3">
                      <p class="text-[9px] font-bold uppercase text-gray-400">
                        Normal
                      </p>

                      <p class="mt-1 text-xs font-bold text-gray-700">
                        {{ formatCurrency(v.promotion_pricing?.normal_price_snapshot) }}
                      </p>
                    </div>

                    <div class="rounded-xl bg-[#D0E7E6]/40 p-3">
                      <p class="text-[9px] font-bold uppercase text-gray-400">
                        Campaign
                      </p>

                      <p class="mt-1 text-xs font-extrabold text-[#293681]">
                        {{ formatCurrency(v.promotion_pricing?.campaign_price) }}
                      </p>
                    </div>

                    <div class="rounded-xl bg-green-50 p-3">
                      <p class="text-[9px] font-bold uppercase text-gray-400">
                        Discount
                      </p>

                      <p class="mt-1 text-xs font-extrabold text-green-600">
                        {{ formatCurrency(v.promotion_pricing?.discount_price) }}
                      </p>
                    </div>
                  </div>

                  <div class="flex justify-end gap-2">
                    <button
                      v-if="$can('promotion.update')"
                      @click="openEditVariantPricing(promo.id, v)"
                      type="button"
                      class="inline-flex items-center gap-1.5 rounded-lg border border-[#95CCDD] bg-[#D0E7E6]/30 px-3 py-2 text-[10px] font-bold text-[#293681]"
                    >
                      <i class="fa-solid fa-pen text-[8px]"></i>
                      Edit
                    </button>

                    <button
                      v-if="$can('promotion.update')"
                      @click="removeVariantFromPromo(promo.id, v.id)"
                      type="button"
                      class="inline-flex items-center gap-1.5 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-[10px] font-bold text-red-600"
                    >
                      <i class="fa-solid fa-trash text-[8px]"></i>
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- =========================
             SECURE LINK
        ========================== -->
        <div v-else-if="currentTab === 'secure-link'">
          <CampaignSecureLinkPanel
            :campaign-id="campaign.id"
          />
        </div>

        <!-- =========================
             COMMENTS
        ========================== -->
        <div v-else-if="currentTab === 'comments'">
          <CampaignCommentsPanel
            :campaign-id="campaign.id"
          />
        </div>

        <!-- =========================
             PLACEHOLDER
        ========================== -->
        <div
          v-else-if="
            currentTab !== 'overview' &&
            currentTab !== 'tasks' &&
            currentTab !== 'promotions' &&
            currentTab !== 'products' &&
            currentTab !== 'comments'
          "
        >
          <div
            class="flex min-h-[320px] items-center justify-center rounded-2xl border border-dashed border-[#95CCDD] bg-white"
          >
            <EmptyState
              :title="getTabName(currentTab)"
              :description="`The ${getTabName(currentTab)} module is planned for a future sprint.`"
            />
          </div>
        </div>

      </div>
    </div>

    <!-- =========================
         EDIT CAMPAIGN MODAL
    ========================== -->
    <CampaignForm
      :is-open="isModalOpen"
      :campaign="campaign"
      @close="closeModal"
      @saved="handleSaved"
    />

    <!-- =========================
         PROMOTION MODAL
    ========================== -->
    <PromotionForm
      :is-open="isPromotionModalOpen"
      :default-campaign-id="campaign.id"
      @close="isPromotionModalOpen = false"
      @saved="loadLinkedPromotions"
    />

    <!-- =========================
         TASK MODAL
    ========================== -->
    <TaskForm
      :is-open="isTaskModalOpen"
      :task="selectedTask"
      :campaign-id="campaign.id"
      @close="closeTaskModal"
      @saved="fetchCampaignTasks"
    />

    <!-- =========================
         PRODUCT PRICING MODAL
    ========================== -->
    <ModalForm
      :is-open="isProductPricingModalOpen"
      title="Edit Product Pricing"
      @close="isProductPricingModalOpen = false"
    >
      <form
        id="pricing-form"
        @submit.prevent="submitPricingForm"
        class="mt-2 space-y-5"
      >
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

          <div>
            <label class="block text-xs font-bold text-gray-600">
              Campaign Price
            </label>

            <input
              type="number"
              min="0"
              step="1"
              v-model="pricingForm.campaign_price"
              required
              class="mt-1.5 block w-full rounded-xl border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm font-medium shadow-sm outline-none transition-all focus:border-[#4274D9] focus:bg-white focus:ring-4 focus:ring-[#4274D9]/10"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-600">
              Discount Price
            </label>

            <input
              type="number"
              min="0"
              step="1"
              v-model="pricingForm.discount_price"
              required
              class="mt-1.5 block w-full rounded-xl border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm font-medium shadow-sm outline-none transition-all focus:border-[#4274D9] focus:bg-white focus:ring-4 focus:ring-[#4274D9]/10"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-600">
              Bottom Price
            </label>

            <input
              type="number"
              min="0"
              step="1"
              v-model="pricingForm.bottom_price"
              class="mt-1.5 block w-full rounded-xl border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm font-medium shadow-sm outline-none transition-all focus:border-[#4274D9] focus:bg-white focus:ring-4 focus:ring-[#4274D9]/10"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-600">
              Promotion Stock
            </label>

            <input
              type="number"
              min="0"
              v-model="pricingForm.promotion_stock"
              required
              class="mt-1.5 block w-full rounded-xl border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm font-medium shadow-sm outline-none transition-all focus:border-[#4274D9] focus:bg-white focus:ring-4 focus:ring-[#4274D9]/10"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-600">
              Purchase Limit
            </label>

            <input
              type="number"
              min="0"
              v-model="pricingForm.purchase_limit"
              class="mt-1.5 block w-full rounded-xl border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm font-medium shadow-sm outline-none transition-all focus:border-[#4274D9] focus:bg-white focus:ring-4 focus:ring-[#4274D9]/10"
            />
          </div>

        </div>
      </form>

      <template #footer>
        <div class="flex justify-end gap-2">
          <button
            type="button"
            @click="isProductPricingModalOpen = false"
            class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-600 transition-colors hover:bg-gray-50"
          >
            Cancel
          </button>

          <button
            v-if="$can('promotion.update')"
            type="submit"
            form="pricing-form"
            :disabled="pricingSubmitting"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#4274D9] px-4 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all hover:bg-[#293681] disabled:cursor-not-allowed disabled:opacity-50"
          >
            <i
              v-if="pricingSubmitting"
              class="fa-solid fa-spinner fa-spin text-[10px]"
            ></i>

            <i
              v-else
              class="fa-solid fa-check text-[10px]"
            ></i>

            {{ pricingSubmitting ? "Saving..." : "Save Pricing" }}
          </button>
        </div>
      </template>
    </ModalForm>

    <!-- =========================
        ADD PRODUCT MODAL
    ========================== -->
    <ModalForm
      :is-open="isAddProductModalOpen"
      title="Add Product to Campaign"
      @close="isAddProductModalOpen = false"
    >
      <div class="add-product-modal space-y-5">

        <!-- Header Info -->
        <div
          class="flex items-start gap-3 rounded-2xl border border-[#D0E7E6] bg-gradient-to-r from-[#D0E7E6]/60 to-[#95CCDD]/20 p-4"
        >
          <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#293681] text-white shadow-sm"
          >
            <i class="fa-solid fa-box-open text-sm"></i>
          </div>

          <div class="min-w-0">
            <h3 class="text-sm font-extrabold text-[#293681]">
              Add Product
            </h3>

            <p class="mt-1 text-xs leading-relaxed text-gray-500">
              Select the product you want to assign to a promotion in this campaign.
            </p>
          </div>
        </div>

        <!-- Form Container -->
        <div
          class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm"
        >
          <AddProductToCampaignForm
            :campaign-id="campaign.id"
            @close="isAddProductModalOpen = false"
            @saved="loadCampaignProducts"
          />
        </div>

      </div>

      <template #footer>
        <div class="flex w-full items-center justify-end gap-2">

          <button
            type="button"
            @click="isAddProductModalOpen = false"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-600 shadow-sm transition-all duration-200 hover:border-gray-300 hover:bg-gray-50 hover:text-gray-800"
          >
            <i class="fa-solid fa-xmark text-[10px]"></i>
            Cancel
          </button>

          <button
            type="submit"
            form="add-product-form"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#4274D9] px-5 py-2.5 text-xs font-extrabold text-white shadow-sm transition-all duration-200 hover:bg-[#293681] hover:shadow-md focus:outline-none focus:ring-4 focus:ring-[#4274D9]/20"
          >
            <span
              class="flex h-5 w-5 items-center justify-center rounded-md bg-white/15"
            >
              <i class="fa-solid fa-plus text-[9px]"></i>
            </span>

            <span>Add to Promotion</span>
          </button>

        </div>
      </template>
    </ModalForm>

  </div>
</template>

<style scoped>
.add-product-modal {
  width: 100%;
}

/* Form utama */
.add-product-modal :deep(form) {
  width: 100%;
}

/* Semua wrapper field */
.add-product-modal :deep(form > div),
.add-product-modal :deep(.form-group),
.add-product-modal :deep(.field-group) {
  width: 100%;
}

/* Label */
.add-product-modal :deep(label) {
  display: block;
  margin-bottom: 0.5rem;
  color: var(--ui-brand-strong);
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.01em;
}

/* Input, select, textarea */
.add-product-modal :deep(input),
.add-product-modal :deep(select),
.add-product-modal :deep(textarea) {
  width: 100%;
  min-height: 44px;
  border: 1px solid var(--ui-border);
  border-radius: 0.75rem;
  background: var(--ui-surface);
  padding: 0.7rem 0.9rem;
  color: var(--ui-content);
  font-size: 0.8rem;
  font-weight: 600;
  outline: none;
  box-shadow: 0 1px 2px rgba(41, 54, 129, 0.04);
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    background-color 0.2s ease;
}

/* Placeholder */
.add-product-modal :deep(input::placeholder),
.add-product-modal :deep(textarea::placeholder) {
  color: var(--ui-content-muted);
  font-weight: 500;
}

/* Hover */
.add-product-modal :deep(input:hover),
.add-product-modal :deep(select:hover),
.add-product-modal :deep(textarea:hover) {
  border-color: #95ccdd;
}

/* Focus */
.add-product-modal :deep(input:focus),
.add-product-modal :deep(select:focus),
.add-product-modal :deep(textarea:focus) {
  border-color: #4274d9;
  background: var(--ui-surface);
  box-shadow:
    0 0 0 3px rgba(66, 116, 217, 0.12),
    0 2px 6px rgba(41, 54, 129, 0.06);
}

/* Select */
.add-product-modal :deep(select) {
  cursor: pointer;
  appearance: none;
  padding-right: 2.5rem;
}

/* Disabled */
.add-product-modal :deep(input:disabled),
.add-product-modal :deep(select:disabled),
.add-product-modal :deep(textarea:disabled) {
  cursor: not-allowed;
  background: var(--ui-surface-muted);
  color: var(--ui-content-muted);
}

/* Field spacing */
.add-product-modal :deep(form > div + div) {
  margin-top: 1rem;
}

/* Jika form menggunakan grid */
.add-product-modal :deep(.grid) {
  width: 100%;
  gap: 1rem;
}

/* Mobile */
@media (max-width: 640px) {
  .add-product-modal :deep(input),
  .add-product-modal :deep(select),
  .add-product-modal :deep(textarea) {
    min-height: 46px;
  }

  .add-product-modal :deep(.grid) {
    grid-template-columns: 1fr !important;
  }
}
</style>

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

// Task status helpers (Added taskStatusDot for modern UI)
const taskStatusDot = (status) => {
  const map = {
    pending: 'bg-slate-300',
    assigned: 'bg-blue-400',
    in_progress: 'bg-blue-500 animate-pulse',
    waiting_review: 'bg-violet-500',
    revision: 'bg-orange-500',
    completed: 'bg-emerald-500',
    on_hold: 'bg-amber-500',
    cancelled: 'bg-rose-500'
  };
  return map[status] || 'bg-slate-300';
};

const taskStatusClass = (status) => {
  const map = {
    pending: 'bg-slate-100 text-slate-700',
    assigned: 'bg-blue-50 text-blue-700 border border-blue-200',
    in_progress: 'bg-blue-50 text-blue-700 border border-blue-200',
    waiting_review: 'bg-violet-50 text-violet-700 border border-violet-200',
    revision: 'bg-orange-50 text-orange-700 border border-orange-200',
    completed: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
    on_hold: 'bg-amber-50 text-amber-700 border border-amber-200',
    cancelled: 'bg-rose-50 text-rose-700 border border-rose-200'
  };
  return map[status] || 'bg-slate-100 text-slate-700';
};

const taskStatusLabel = (status) => {
  const map = {
    pending: 'Pending',
    assigned: 'Assigned',
    in_progress: 'In Progress',
    waiting_review: 'Waiting Review',
    revision: 'Revision',
    completed: 'Completed',
    on_hold: 'On Hold',
    cancelled: 'Cancelled'
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
