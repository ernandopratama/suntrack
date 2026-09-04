<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="relative overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100"
    >
      <div
        class="absolute -right-16 -top-20 h-56 w-56 rounded-full opacity-50"
        style="background: #d0e7e6"
      ></div>

      <div
        class="absolute right-24 -bottom-24 h-40 w-40 rounded-full opacity-30"
        style="background: #95ccdd"
      ></div>

      <div
        class="relative flex flex-col gap-5 p-6 lg:flex-row lg:items-center lg:justify-between"
      >
        <div class="min-w-0">
          <div class="mb-3 flex items-center gap-2 text-sm">
            <router-link
              to="/products"
              class="inline-flex items-center gap-2 font-semibold transition-colors"
              style="color: #4274d9"
              @mouseenter="$event.currentTarget.style.color = '#293681'"
              @mouseleave="$event.currentTarget.style.color = '#4274D9'"
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
                  d="M15 19l-7-7 7-7"
                />
              </svg>
              Back to Products
            </router-link>

            <span class="text-slate-300">/</span>

            <span class="truncate text-slate-400">
              Product Detail
            </span>
          </div>

          <div class="flex items-center gap-3">
            <div
              class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
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
                  d="M20 7l-8-4-8 4m16 0v10l-8 4-8-4V7m16 0l-8 4m0 10V11"
                />
              </svg>
            </div>

            <div class="min-w-0">
              <h1
                class="truncate text-2xl font-bold tracking-tight sm:text-3xl"
                style="color: #293681"
              >
                {{ product ? `${product.code} - ${product.name}` : 'Loading...' }}
              </h1>

              <p class="mt-1 text-sm text-slate-500">
                Manage product information, variants, pricing, and inventory.
              </p>
            </div>
          </div>
        </div>

        <div
          v-if="product"
          class="relative flex shrink-0 items-center gap-3"
        >
          <StatusBadge :status="product.status" />

          <button
            v-if="$can('product.update')"
            @click="openEditProduct"
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
            style="background: #4274d9"
            @mouseenter="$event.currentTarget.style.background = '#293681'"
            @mouseleave="$event.currentTarget.style.background = '#4274D9'"
          >
            <svg
              class="h-4 w-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              stroke-width="1.8"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 20h9"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"
              />
            </svg>
            Edit Product
          </button>
        </div>
      </div>

      <div
        class="h-1"
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

    <!-- Loading / Error -->
    <div
      v-if="loading && !product"
      class="rounded-2xl border border-slate-100 bg-white p-6 text-sm text-slate-500 shadow-sm"
    >
      <div class="flex items-center gap-3">
        <div
          class="h-5 w-5 animate-spin rounded-full border-2 border-slate-200 border-t-[#4274D9]"
        ></div>
        Loading product details...
      </div>
    </div>

    <div
      v-else-if="error"
      class="flex items-start gap-3 rounded-2xl border border-red-100 bg-red-50 p-5 text-sm text-red-700"
    >
      <svg
        class="mt-0.5 h-5 w-5 shrink-0"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
        stroke-width="2"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M12 9v4m0 4h.01M10.29 3.86l-7.82 13a2 2 0 001.71 3h15.64a2 2 0 001.71-3l-7.82-13a2 2 0 00-3.42 0z"
        />
      </svg>
      <span>{{ error }}</span>
    </div>

    <template v-else-if="product">
      <!-- Overview Card -->
      <div
        class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100"
      >
        <div class="border-b border-slate-100 px-6 py-5">
          <div class="flex items-center gap-3">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-xl"
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
                  d="M13 16h-1v-4h-1m1-4h.01M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z"
                />
              </svg>
            </div>

            <div>
              <h2 class="text-base font-bold" style="color: #293681">
                Product Overview
              </h2>
              <p class="text-xs text-slate-500">
                Basic information about this product.
              </p>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-px bg-slate-100 md:grid-cols-2 xl:grid-cols-4">
          <div class="bg-white p-6 transition-colors hover:bg-slate-50">
            <span
              class="text-[11px] font-bold uppercase tracking-wider"
              style="color: #4274d9"
            >
              SKU
            </span>
            <span class="mt-2 block text-sm font-bold text-slate-800">
              {{ product.sku || '—' }}
            </span>
          </div>

          <div class="bg-white p-6 transition-colors hover:bg-slate-50">
            <span
              class="text-[11px] font-bold uppercase tracking-wider"
              style="color: #4274d9"
            >
              Brand
            </span>
            <span class="mt-2 block text-sm font-bold text-slate-800">
              {{ product.brand?.name || '—' }}
            </span>
          </div>

          <div class="bg-white p-6 transition-colors hover:bg-slate-50">
            <span
              class="text-[11px] font-bold uppercase tracking-wider"
              style="color: #4274d9"
            >
              Created At
            </span>
            <span class="mt-2 block text-sm font-medium text-slate-600">
              {{ product.created_at }}
            </span>
          </div>

          <div class="bg-white p-6 transition-colors hover:bg-slate-50">
            <span
              class="text-[11px] font-bold uppercase tracking-wider"
              style="color: #4274d9"
            >
              Description
            </span>
            <span
              class="mt-2 block truncate text-sm font-medium text-slate-600"
            >
              {{ product.description || 'No description provided.' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Variants Workspace Section -->
      <div
        class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100"
      >
        <!-- Section Header -->
        <div
          class="border-b border-slate-100 p-6"
          style="background: linear-gradient(135deg, #ffffff 0%, #f6fbfb 100%)"
        >
          <div
            class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
          >
            <div class="flex items-start gap-4">
              <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
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
                    d="M4 7h16M4 12h16M4 17h16"
                  />
                </svg>
              </div>

              <div>
                <div class="flex items-center gap-3">
                  <h2
                    class="text-lg font-bold"
                    style="color: #293681"
                  >
                    Product Variants
                  </h2>

                  <span
                    class="rounded-full px-2.5 py-1 text-[11px] font-bold"
                    style="background: #d0e7e6; color: #293681"
                  >
                    {{ variants.length }}
                  </span>
                </div>

                <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500">
                  Manage master variants, reference retail prices (Normal),
                  minimum floor prices (Bottom), and inventory stock.
                </p>
              </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
              <!-- Search -->
              <div class="relative">
                <svg
                  class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  stroke-width="2"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="m21 21-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"
                  />
                </svg>

                <input
                  type="text"
                  v-model="variantSearch"
                  @input="filterVariants"
                  placeholder="Search variants..."
                  class="w-full rounded-xl border-slate-200 bg-white py-2.5 pl-9 pr-4 text-sm shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:w-64"
                />
              </div>

              <!-- New Variant -->
              <button
                v-if="$can('variant.create')"
                @click="openCreateVariant"
                class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold uppercase tracking-wide text-white shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
                style="background: #293681"
                @mouseenter="$event.currentTarget.style.background = '#4274D9'"
                @mouseleave="$event.currentTarget.style.background = '#293681'"
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
                    d="M12 5v14M5 12h14"
                  />
                </svg>
                New Variant
              </button>
            </div>
          </div>
        </div>

        <!-- Loading -->
        <div
          v-if="variantsLoading"
          class="flex items-center justify-center gap-3 p-10 text-sm text-slate-500"
        >
          <div
            class="h-5 w-5 animate-spin rounded-full border-2 border-slate-200 border-t-[#4274D9]"
          ></div>
          Loading variants...
        </div>

        <!-- Empty -->
        <EmptyState
          v-else-if="!variants.length"
          title="No variants found"
          description="Create master variants for this product to set reference pricing and stock levels."
          :button-text="$can('variant.create') ? '+ Create First Variant' : ''"
          @action="openCreateVariant"
        />

        <!-- Table -->
        <div v-else class="overflow-x-auto">
          <table class="min-w-full">
            <thead
              class="border-b border-slate-100"
              style="background: #f7fbfb"
            >
              <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                <th class="px-6 py-4">Variant Code & Name</th>
                <th class="px-6 py-4">SKU</th>
                <th class="px-6 py-4">Master Normal Price</th>
                <th class="px-6 py-4">Master Bottom Price</th>
                <th class="px-6 py-4">Current Stock</th>
                <th class="px-6 py-4">Status</th>
                <th class="px-6 py-4 text-right">Actions</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-slate-100 bg-white">
              <tr
                v-for="v in variants"
                :key="v.id"
                class="group transition-colors hover:bg-[#f7fbfb]"
              >
                <td class="px-6 py-4">
                  <div class="font-semibold text-slate-800">
                    {{ v.name }}
                  </div>

                  <div
                    class="mt-1 inline-flex rounded-lg px-2 py-1 font-mono text-[11px] font-semibold"
                    style="background: #d0e7e6; color: #293681"
                  >
                    {{ v.code }}
                  </div>
                </td>

                <td class="px-6 py-4 text-sm text-slate-500">
                  {{ v.sku || '—' }}
                </td>

                <td class="px-6 py-4">
                  <span class="font-bold text-slate-800">
                    {{ formatCurrency(v.normal_price) }}
                  </span>
                </td>

                <td class="px-6 py-4">
                  <span
                    class="font-bold"
                    style="color: #4274d9"
                  >
                    {{ formatCurrency(v.bottom_price) }}
                  </span>
                </td>

                <td class="px-6 py-4">
                  <span
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                    :style="
                      v.current_stock > 10
                        ? 'background: #D0E7E6; color: #293681'
                        : 'background: #FEF2F2; color: #B91C1C'
                    "
                  >
                    <span
                      class="h-1.5 w-1.5 rounded-full"
                      :style="
                        v.current_stock > 10
                          ? 'background: #4274D9'
                          : 'background: #DC2626'
                      "
                    ></span>
                    {{ v.current_stock }} units
                  </span>
                </td>

                <td class="px-6 py-4">
                  <StatusBadge :status="v.status" />
                </td>

                <td class="px-6 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      v-if="$can('variant.update')"
                      @click="openEditVariant(v)"
                      class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors"
                      style="color: #4274d9"
                      @mouseenter="$event.currentTarget.style.background = '#D0E7E6'"
                      @mouseleave="$event.currentTarget.style.background = 'transparent'"
                    >
                      Edit
                    </button>

                    <button
                      v-if="$can('variant.delete')"
                      @click="handleDeleteVariant(v)"
                      class="rounded-lg px-3 py-1.5 text-xs font-semibold text-red-500 transition-colors hover:bg-red-50 hover:text-red-700"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>

    <!-- Product Edit Modal -->
    <ModalForm
      :is-open="isProductModalOpen"
      title="Edit Product"
      @close="isProductModalOpen = false"
    >
      <form
        id="edit-product-form"
        @submit.prevent="submitProductEdit"
        class="mt-2 space-y-5"
      >
        <div
          v-if="typeof error === 'string' && error"
          class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
        >
          {{ error }}
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-sm font-semibold text-slate-700">
              Product Code <span class="text-red-500">*</span>
            </label>
            <input
              type="text"
              v-model="productForm.code"
              required
              class="mt-1.5 block w-full rounded-xl border-slate-200 shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-700">
              SKU
            </label>
            <input
              type="text"
              v-model="productForm.sku"
              class="mt-1.5 block w-full rounded-xl border-slate-200 shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-700">
            Product Name <span class="text-red-500">*</span>
          </label>
          <input
            type="text"
            v-model="productForm.name"
            required
            class="mt-1.5 block w-full rounded-xl border-slate-200 shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
          />
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-700">
            Description
          </label>
          <textarea
            v-model="productForm.description"
            rows="3"
            class="mt-1.5 block w-full rounded-xl border-slate-200 shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
          ></textarea>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-700">
            Brand
          </label>
          <select
            v-model="productForm.brand_id"
            class="mt-1.5 block w-full rounded-xl border-slate-200 bg-white shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
          >
            <option value="">-- Select Brand --</option>
            <option
              v-for="b in brands"
              :key="b.id"
              :value="b.id"
            >
              {{ b.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-700">
            Current Price
          </label>
          <input
            type="number"
            min="0"
            step="0.01"
            v-model="productForm.current_price"
            class="mt-1.5 block w-full rounded-xl border-slate-200 shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
            placeholder="e.g. 150000"
          />
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-700">
            Status
          </label>
          <select
            v-model="productForm.status"
            class="mt-1.5 block w-full rounded-xl border-slate-200 bg-white shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
          >
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </form>

      <template #footer>
        <div class="mt-5 flex w-full flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button
            type="button"
            @click="isProductModalOpen = false"
            class="inline-flex w-full justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 sm:w-auto"
          >
            Cancel
          </button>

          <button
            v-if="$can('product.update')"
            type="submit"
            form="edit-product-form"
            :disabled="loading"
            class="inline-flex w-full justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
            style="background: #293681"
          >
            Save Changes
          </button>
        </div>
      </template>
    </ModalForm>

    <!-- Variant Create / Edit Modal -->
    <ModalForm
      :is-open="isVariantModalOpen"
      :title="selectedVariant ? 'Edit Variant' : 'Create Variant'"
      @close="isVariantModalOpen = false"
    >
      <form
        id="variant-form"
        @submit.prevent="submitVariantForm"
        class="mt-2 space-y-5"
      >
        <div
          v-if="typeof error === 'object' && error"
          class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
        >
          <div v-for="(msgs, field) in error" :key="field">
            {{ msgs[0] }}
          </div>
        </div>

        <div
          v-if="typeof error === 'string' && error"
          class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
        >
          {{ error }}
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-sm font-semibold text-slate-700">
              Variant Code <span class="text-red-500">*</span>
            </label>
            <input
              type="text"
              v-model="variantForm.code"
              required
              class="mt-1.5 block w-full rounded-xl border-slate-200 shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-700">
              SKU
            </label>
            <input
              type="text"
              v-model="variantForm.sku"
              class="mt-1.5 block w-full rounded-xl border-slate-200 shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-700">
            Variant Name <span class="text-red-500">*</span>
          </label>
          <input
            type="text"
            v-model="variantForm.name"
            required
            placeholder="e.g. 50ml / SPF 50+"
            class="mt-1.5 block w-full rounded-xl border-slate-200 shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
          />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-sm font-semibold text-slate-700">
              Normal Price (Master)
              <span class="text-red-500">*</span>
            </label>

            <input
              type="number"
              min="0"
              step="1"
              v-model="variantForm.normal_price"
              required
              class="mt-1.5 block w-full rounded-xl border-slate-200 font-semibold text-slate-800 shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
            />

            <span class="mt-1 block text-xs text-slate-400">
              Reference retail price
            </span>
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-700">
              Bottom Price (Master)
              <span class="text-red-500">*</span>
            </label>

            <input
              type="number"
              min="0"
              step="1"
              v-model="variantForm.bottom_price"
              required
              class="mt-1.5 block w-full rounded-xl border-slate-200 font-semibold shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
              style="color: #4274d9"
            />

            <span class="mt-1 block text-xs text-slate-400">
              Minimum floor price
            </span>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-sm font-semibold text-slate-700">
              Current Stock <span class="text-red-500">*</span>
            </label>

            <input
              type="number"
              min="0"
              v-model="variantForm.current_stock"
              required
              class="mt-1.5 block w-full rounded-xl border-slate-200 shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-slate-700">
              Status
            </label>

            <select
              v-model="variantForm.status"
              class="mt-1.5 block w-full rounded-xl border-slate-200 bg-white shadow-sm transition focus:border-[#4274D9] focus:ring-[#4274D9] sm:text-sm"
            >
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
      </form>

      <template #footer>
        <div class="mt-5 flex w-full flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button
            type="button"
            @click="isVariantModalOpen = false"
            class="inline-flex w-full justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:bg-slate-50 sm:w-auto"
          >
            Cancel
          </button>

          <button
            v-if="selectedVariant ? $can('variant.update') : $can('variant.create')"
            type="submit"
            form="variant-form"
            :disabled="loading"
            class="inline-flex w-full justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
            style="background: #293681"
          >
            {{ loading ? 'Saving...' : (selectedVariant ? 'Save Variant' : 'Create Variant') }}
          </button>
        </div>
      </template>
    </ModalForm>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import StatusBadge from '../components/StatusBadge.vue';
import EmptyState from '../components/EmptyState.vue';
import ModalForm from '../components/ModalForm.vue';
import { useProducts } from '../composables/useProducts';
import api from '../utils/api';

const route = useRoute();
const {
  product, variants, loading, error,
  fetchProduct, updateProduct,
  fetchVariants, createVariant, updateVariant, deleteVariant
} = useProducts();

const variantsLoading = ref(false);
const variantSearch = ref('');
const isProductModalOpen = ref(false);
const brands = ref([]);
const isVariantModalOpen = ref(false);
const selectedVariant = ref(null);

const productForm = ref({
  code: '',
  sku: '',
  name: '',
  description: '',
  current_price: null,
  brand_id: '',
  status: 'Active'
});

const variantForm = ref({
  code: '',
  sku: '',
  name: '',
  normal_price: 0,
  bottom_price: 0,
  current_stock: 0,
  status: 'Active'
});

const formatCurrency = (val) => {
  if (val == null) return '—';

  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val);
};

onMounted(async () => {
  await loadData();
  fetchBrands();
});

const fetchBrands = async () => {
  try {
    const res = await api.get('/admin/brands', {
      params: { per_page: 100 }
    });

    if (res.data.success) {
      brands.value =
        res.data.data.brands.data ||
        res.data.data.brands ||
        [];
    }
  } catch (e) {
    console.error('Failed to load brands', e);
  }
};

const loadData = async () => {
  await fetchProduct(route.params.id);

  variantsLoading.value = true;

  await fetchVariants(route.params.id, {
    per_page: 100
  });

  variantsLoading.value = false;
};

const filterVariants = async () => {
  variantsLoading.value = true;

  await fetchVariants(route.params.id, {
    search: variantSearch.value,
    per_page: 100
  });

  variantsLoading.value = false;
};

// Product Edit
const openEditProduct = () => {
  productForm.value = {
    code: product.value.code,
    sku: product.value.sku || '',
    name: product.value.name,
    description: product.value.description || '',
    current_price: product.value.current_price ?? null,
    brand_id: product.value.brand?.id || '',
    status: product.value.status
  };

  isProductModalOpen.value = true;
};

const submitProductEdit = async () => {
  const res = await updateProduct(
    product.value.id,
    productForm.value
  );

  if (res) {
    isProductModalOpen.value = false;
    await fetchProduct(route.params.id);
  }
};

// Variant Management
const openCreateVariant = () => {
  selectedVariant.value = null;

  variantForm.value = {
    code: '',
    sku: '',
    name: '',
    normal_price: 0,
    bottom_price: 0,
    current_stock: 0,
    status: 'Active'
  };

  isVariantModalOpen.value = true;
};

const openEditVariant = (v) => {
  selectedVariant.value = v;

  variantForm.value = {
    code: v.code,
    sku: v.sku || '',
    name: v.name,
    normal_price: v.normal_price,
    bottom_price: v.bottom_price,
    current_stock: v.current_stock,
    status: v.status
  };

  isVariantModalOpen.value = true;
};

const submitVariantForm = async () => {
  const res = selectedVariant.value
    ? await updateVariant(
        product.value.id,
        selectedVariant.value.id,
        variantForm.value
      )
    : await createVariant(
        product.value.id,
        variantForm.value
      );

  if (res) {
    isVariantModalOpen.value = false;
    await loadData();
  }
};

const handleDeleteVariant = async (v) => {
  if (
    !confirm(
      `Are you sure you want to delete variant "${v.code} - ${v.name}"?`
    )
  ) {
    return;
  }

  const res = await deleteVariant(
    product.value.id,
    v.id
  );

  if (res) {
    await loadData();
  }
};
</script>
