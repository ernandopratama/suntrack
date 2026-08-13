<template>
  <div class="min-h-screen">
    <!-- Page Header -->
    <div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
      <div>
        <div class="flex items-center gap-3">
          <div
            class="flex h-11 w-11 items-center justify-center rounded-xl shadow-sm"
            style="background-color: #d0e7e6;"
          >
            <svg
              class="h-6 w-6"
              style="color: #293681;"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.8"
                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
              />
            </svg>
          </div>

          <div>
            <h1
              class="text-2xl font-bold tracking-tight"
              style="color: #293681;"
            >
              Products
            </h1>
            <p class="mt-0.5 text-sm text-gray-500">
              Manage your product catalog and variants.
            </p>
          </div>
        </div>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row">
        <!-- Import -->
        <button
          @click="openImportModal"
          class="inline-flex items-center justify-center rounded-xl border bg-white px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
          style="border-color: #95ccdd; color: #293681;"
        >
          <svg
            class="-ml-1 mr-2 h-5 w-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
            />
          </svg>
          Import Excel
        </button>

        <!-- New Product -->
        <button
          @click="openCreate"
          class="inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md"
          style="background-color: #293681;"
        >
          <svg
            class="-ml-1 mr-2 h-5 w-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 6v6m0 0v6m0-6h6m-6 0H6"
            />
          </svg>
          New Product
        </button>
      </div>
    </div>

    <!-- Page Error -->
    <div
      v-if="pageError"
      class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm"
    >
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <svg
            class="h-5 w-5 text-red-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </div>

        <div class="ml-3">
          <p class="text-sm font-medium text-red-700">
            {{ pageError }}
          </p>
        </div>
      </div>
    </div>

    <!-- Products Table -->
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
      <DataTable
        :columns="columns"
        :data="products"
        :loading="loading"
        @search="handleSearch"
      >
        <!-- Toolbar -->
        <template #actions>
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <!-- Brand Filter -->
            <div class="relative">
              <select
                v-model="filters.brand_id"
                @change="handleBrandFilter"
                class="block w-full appearance-none rounded-xl border bg-white py-2.5 pl-3 pr-10 text-sm font-medium shadow-sm outline-none transition-all focus:ring-2 sm:w-44"
                style="
                  border-color: #d0e7e6;
                  color: #293681;
                  --tw-ring-color: #95ccdd;
                "
              >
                <option value="">All Brands</option>
                <option
                  v-for="b in brands"
                  :key="b.id"
                  :value="b.id"
                >
                  {{ b.name }}
                </option>
              </select>
            </div>

            <!-- Status Filter -->
            <div class="relative">
              <select
                v-model="filters.status"
                @change="fetchData"
                class="block w-full appearance-none rounded-xl border bg-white py-2.5 pl-3 pr-10 text-sm font-medium shadow-sm outline-none transition-all focus:ring-2 sm:w-40"
                style="
                  border-color: #d0e7e6;
                  color: #293681;
                  --tw-ring-color: #95ccdd;
                "
              >
                <option value="">All Statuses</option>
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
              </select>
            </div>

            <!-- Bulk Delete -->
            <button
              v-if="selectedIds.length > 0"
              @click="confirmBulkDelete"
              class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-3.5 py-2.5 text-sm font-semibold text-red-600 transition-all duration-200 hover:bg-red-100"
            >
              <svg
                class="mr-1.5 h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
              </svg>
              Delete Selected ({{ selectedIds.length }})
            </button>
          </div>
        </template>

        <!-- Checkbox Header -->
        <template #head-checkbox>
          <input
            type="checkbox"
            :checked="
              selectedIds.length === products.length &&
              products.length > 0
            "
            @change="toggleSelectAll"
            class="h-4 w-4 rounded border-gray-300 focus:ring-2"
            style="
              accent-color: #293681;
              --tw-ring-color: #95ccdd;
            "
          />
        </template>

        <!-- Checkbox -->
        <template #cell-checkbox="{ row }">
          <input
            type="checkbox"
            :checked="selectedIds.includes(row.id)"
            @change="toggleSelect(row.id)"
            class="h-4 w-4 rounded border-gray-300 focus:ring-2"
            style="
              accent-color: #293681;
              --tw-ring-color: #95ccdd;
            "
          />
        </template>

        <!-- Number -->
        <template #cell-no="{ idx }">
          <span class="text-sm font-medium text-gray-400">
            {{
              pagination.current_page > 1
                ? (pagination.current_page - 1) *
                    pagination.per_page +
                  idx +
                  1
                : idx + 1
            }}
          </span>
        </template>

        <!-- Code -->
        <template #cell-code="{ row }">
          <span
            class="inline-flex items-center rounded-lg px-2.5 py-1 font-mono text-xs font-semibold"
            style="
              background-color: #d0e7e6;
              color: #293681;
            "
          >
            {{ row.code }}
          </span>
        </template>

        <!-- Product Name -->
        <template #cell-name="{ row }">
          <div class="min-w-0">
            <div class="truncate font-semibold text-gray-800">
              {{ row.name }}
            </div>

            <div class="mt-0.5 flex items-center gap-1.5 text-xs text-gray-400">
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
                  d="M7 7h10M7 11h10M7 15h6"
                />
              </svg>
              {{ row.sku || 'No SKU' }}
            </div>
          </div>
        </template>

        <!-- Brand -->
        <template #cell-brand_name="{ row }">
          <span
            v-if="row.brand?.name"
            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
            style="
              background-color: #eaf5f5;
              color: #293681;
            "
          >
            {{ row.brand.name }}
          </span>

          <span v-else class="text-sm text-gray-400">
            —
          </span>
        </template>

        <!-- Price -->
        <template #cell-current_price="{ row }">
          <span
            class="text-sm font-bold"
            style="color: #293681;"
          >
            {{
              row.current_price
                ? formatCurrency(row.current_price)
                : '—'
            }}
          </span>
        </template>

        <!-- Variants -->
        <template #cell-variants_count="{ row }">
          <span
            class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600"
          >
            <span
              class="flex h-6 w-6 items-center justify-center rounded-lg"
              style="background-color: #d0e7e6; color: #293681;"
            >
              {{ row.variants_count }}
            </span>

            variant{{ row.variants_count !== 1 ? 's' : '' }}
          </span>
        </template>

        <!-- Status -->
        <template #cell-status="{ row }">
          <StatusBadge :status="row.status" />
        </template>

        <!-- Actions -->
        <template #cell-actions="{ row }">
          <div class="flex items-center gap-1">
            <!-- View -->
            <router-link
              :to="`/products/${row.id}`"
              class="group inline-flex h-8 w-8 items-center justify-center rounded-lg transition-colors"
              style="color: #4274d9;"
              title="View Product"
            >
              <svg
                class="h-4 w-4 transition-transform group-hover:scale-110"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.8"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.8"
                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                />
              </svg>
            </router-link>

            <!-- Edit -->
            <button
              @click="openEdit(row)"
              class="group inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700"
              title="Edit Product"
            >
              <svg
                class="h-4 w-4 transition-transform group-hover:scale-110"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.8"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.8"
                  d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"
                />
              </svg>
            </button>

            <!-- Delete -->
            <button
              @click="confirmDelete(row)"
              class="group inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-400 transition-colors hover:bg-red-50 hover:text-red-600"
              title="Delete Product"
            >
              <svg
                class="h-4 w-4 transition-transform group-hover:scale-110"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.8"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
              </svg>
            </button>
          </div>
        </template>

        <!-- Pagination -->
        <template #pagination>
          <div
            class="flex flex-col gap-4 border-t border-gray-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <p class="text-sm text-gray-500">
              Page
              <span class="font-semibold" style="color: #293681;">
                {{ pagination.current_page }}
              </span>
              of
              <span class="font-semibold" style="color: #293681;">
                {{ pagination.last_page }}
              </span>
              <span class="mx-1 text-gray-300">•</span>
              {{ pagination.total }} results
            </p>

            <nav class="flex items-center gap-2">
              <button
                @click="changePage(-1)"
                :disabled="pagination.current_page === 1"
                class="inline-flex items-center rounded-lg border bg-white px-3.5 py-2 text-sm font-semibold transition-all disabled:cursor-not-allowed disabled:opacity-40"
                style="border-color: #d0e7e6; color: #293681;"
              >
                <svg
                  class="mr-1.5 h-4 w-4"
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
                Prev
              </button>

              <button
                @click="changePage(1)"
                :disabled="
                  pagination.current_page === pagination.last_page
                "
                class="inline-flex items-center rounded-lg px-3.5 py-2 text-sm font-semibold text-white transition-all disabled:cursor-not-allowed disabled:opacity-40"
                style="background-color: #293681;"
              >
                Next
                <svg
                  class="ml-1.5 h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 5l7 7-7 7"
                  />
                </svg>
              </button>
            </nav>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create/Edit Modal -->
    <ModalForm
      :is-open="isModalOpen"
      :title="selectedProduct ? 'Edit Product' : 'Create Product'"
      @close="isModalOpen = false"
    >
      <form
        id="product-form"
        @submit.prevent="submitForm"
        class="mt-2 space-y-5"
      >
        <!-- Error -->
        <div
          v-if="formError"
          class="rounded-xl border border-red-200 bg-red-50 p-3"
        >
          <p class="text-sm font-medium text-red-700">
            {{ formError }}
          </p>
        </div>

        <!-- Code & SKU -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-sm font-semibold text-gray-700">
              Product Code
              <span class="text-red-500">*</span>
            </label>

            <input
              type="text"
              v-model="form.code"
              required
              class="mt-1.5 block w-full rounded-xl border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm shadow-sm outline-none transition-all focus:bg-white focus:ring-2"
              style="--tw-ring-color: #95ccdd;"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700">
              SKU
            </label>

            <input
              type="text"
              v-model="form.sku"
              class="mt-1.5 block w-full rounded-xl border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm shadow-sm outline-none transition-all focus:bg-white focus:ring-2"
              style="--tw-ring-color: #95ccdd;"
            />
          </div>
        </div>

        <!-- Name -->
        <div>
          <label class="block text-sm font-semibold text-gray-700">
            Product Name
            <span class="text-red-500">*</span>
          </label>

          <input
            type="text"
            v-model="form.name"
            required
            class="mt-1.5 block w-full rounded-xl border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm shadow-sm outline-none transition-all focus:bg-white focus:ring-2"
            style="--tw-ring-color: #95ccdd;"
          />
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-semibold text-gray-700">
            Description
          </label>

          <textarea
            v-model="form.description"
            rows="3"
            class="mt-1.5 block w-full resize-none rounded-xl border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm shadow-sm outline-none transition-all focus:bg-white focus:ring-2"
            style="--tw-ring-color: #95ccdd;"
          ></textarea>
        </div>

        <!-- Price -->
        <div>
          <label class="block text-sm font-semibold text-gray-700">
            Current Price
            <span class="font-normal text-gray-400">
              (Selling Price)
            </span>
          </label>

          <div class="relative mt-1.5">
            <span
              class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold"
              style="color: #293681;"
            >
              Rp
            </span>

            <input
              type="number"
              min="0"
              step="0.01"
              v-model="form.current_price"
              class="block w-full rounded-xl border-gray-200 bg-gray-50 py-2.5 pl-11 pr-3.5 text-sm shadow-sm outline-none transition-all focus:bg-white focus:ring-2"
              style="--tw-ring-color: #95ccdd;"
              placeholder="150000"
            />
          </div>
        </div>

        <!-- Brand -->
        <div>
          <label class="block text-sm font-semibold text-gray-700">
            Brand
            <span class="text-red-500">*</span>
          </label>

          <select
            v-model="form.brand_id"
            required
            class="mt-1.5 block w-full rounded-xl border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm shadow-sm outline-none transition-all focus:bg-white focus:ring-2"
            style="--tw-ring-color: #95ccdd;"
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

        <!-- Status -->
        <div>
          <label class="block text-sm font-semibold text-gray-700">
            Status
          </label>

          <select
            v-model="form.status"
            class="mt-1.5 block w-full rounded-xl border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm shadow-sm outline-none transition-all focus:bg-white focus:ring-2"
            style="--tw-ring-color: #95ccdd;"
          >
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
      </form>

      <template #footer>
        <div class="mt-5 flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button
            type="button"
            @click="isModalOpen = false"
            class="inline-flex w-full justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 shadow-sm transition-colors hover:bg-gray-50 sm:w-auto"
          >
            Cancel
          </button>

          <button
            type="submit"
            form="product-form"
            :disabled="submitting"
            class="inline-flex w-full justify-center rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
            style="background-color: #293681;"
          >
            {{ submitting
              ? 'Saving...'
              : (selectedProduct ? 'Update Product' : 'Save Product')
            }}
          </button>
        </div>
      </template>
    </ModalForm>

    <!-- Import Modal -->
    <ModalForm
      :is-open="isImportModalOpen"
      title="Import Products from Excel"
      @close="closeImportModal"
    >
      <form
        id="import-form"
        @submit.prevent="submitImport"
        class="mt-2 space-y-5"
      >
        <!-- Error -->
        <div
          v-if="importError"
          class="rounded-xl border border-red-200 bg-red-50 p-3"
        >
          <p class="text-sm font-medium text-red-700">
            {{ importError }}
          </p>
        </div>

        <!-- Result -->
        <div
          v-if="importResult"
          class="rounded-xl border p-4"
          style="
            background-color: #d0e7e6;
            border-color: #95ccdd;
          "
        >
          <div class="mb-3 flex items-center gap-2">
            <div
              class="flex h-8 w-8 items-center justify-center rounded-full bg-white"
            >
              <svg
                class="h-5 w-5"
                style="color: #293681;"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                />
              </svg>
            </div>

            <p
              class="text-sm font-bold"
              style="color: #293681;"
            >
              Import Completed!
            </p>
          </div>

          <div class="space-y-1.5 pl-10">
            <p class="text-sm text-gray-700">
              <span class="font-semibold">
                {{ importResult.imported }}
              </span>
              product(s) imported
            </p>

            <p
              v-if="importResult.updated > 0"
              class="text-sm"
              style="color: #4274d9;"
            >
              <span class="font-semibold">
                {{ importResult.updated }}
              </span>
              product(s) updated
            </p>

            <p
              v-if="importResult.skipped > 0"
              class="text-sm text-amber-700"
            >
              <span class="font-semibold">
                {{ importResult.skipped }}
              </span>
              row(s) skipped
            </p>

            <div
              v-if="
                importResult.errors &&
                importResult.errors.length
              "
              class="mt-3 max-h-32 overflow-y-auto rounded-lg bg-white p-3"
            >
              <p
                v-for="(err, idx) in importResult.errors"
                :key="idx"
                class="text-xs text-red-600"
              >
                ⚠ {{ err }}
              </p>
            </div>
          </div>
        </div>

        <div v-if="!importResult">
          <!-- Brand -->
          <div>
            <label class="block text-sm font-semibold text-gray-700">
              Select Brand
              <span class="text-red-500">*</span>
            </label>

            <select
              v-model="importForm.brand_id"
              required
              class="mt-1.5 block w-full rounded-xl border-gray-200 bg-gray-50 px-3.5 py-2.5 text-sm shadow-sm outline-none transition-all focus:bg-white focus:ring-2"
              style="--tw-ring-color: #95ccdd;"
            >
              <option value="">-- Choose Brand --</option>
              <option
                v-for="b in brands"
                :key="b.id"
                :value="b.id"
              >
                {{ b.name }}
              </option>
            </select>
          </div>

          <!-- File Upload -->
          <div class="mt-5">
            <label class="block text-sm font-semibold text-gray-700">
              Excel File
              <span class="text-red-500">*</span>
            </label>

            <div
              class="mt-2 cursor-pointer rounded-2xl border-2 border-dashed p-7 text-center transition-all duration-200 hover:shadow-sm"
              style="
                border-color: #95ccdd;
                background-color: #f7fbfb;
              "
              @click="triggerFileInput"
            >
              <div
                class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl"
                style="background-color: #d0e7e6;"
              >
                <svg
                  class="h-7 w-7"
                  style="color: #293681;"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                  />
                </svg>
              </div>

              <div class="text-sm">
                <span
                  class="font-semibold"
                  style="color: #4274d9;"
                >
                  {{
                    importForm.file
                      ? importForm.file.name
                      : 'Upload an Excel file'
                  }}
                </span>
              </div>

              <p class="mt-1 text-xs text-gray-400">
                .xlsx or .xls up to 10MB
              </p>

              <input
                ref="fileInputRef"
                type="file"
                accept=".xlsx,.xls"
                class="sr-only"
                @change="onFileChange"
              />
            </div>
          </div>

          <!-- Expected Columns -->
          <div
            class="mt-5 rounded-xl border p-4"
            style="
              background-color: #d0e7e6;
              border-color: #95ccdd;
            "
          >
            <div class="flex items-start gap-3">
              <div
                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-white"
              >
                <svg
                  class="h-4 w-4"
                  style="color: #293681;"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z"
                  />
                </svg>
              </div>

              <div>
                <h4
                  class="text-xs font-bold uppercase tracking-wide"
                  style="color: #293681;"
                >
                  Expected Excel Columns
                </h4>

                <p class="mt-1 text-xs leading-5 text-gray-600">
                  Nama Produk, Kode Produk, Nama Variasi,
                  Kode Variasi, Harga Awal, Harga Saat Ini,
                  Stok Saat Ini
                </p>
              </div>
            </div>
          </div>
        </div>
      </form>

      <template #footer>
        <div class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button
            type="button"
            @click="closeImportModal"
            class="inline-flex w-full justify-center rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 shadow-sm transition-colors hover:bg-gray-50 sm:w-auto"
          >
            {{ importResult ? 'Done' : 'Cancel' }}
          </button>

          <button
            v-if="!importResult"
            type="submit"
            form="import-form"
            :disabled="importing"
            class="inline-flex w-full justify-center rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto"
            style="background-color: #293681;"
          >
            {{ importing ? 'Importing...' : 'Import' }}
          </button>
        </div>
      </template>
    </ModalForm>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from '../components/DataTable.vue';
import StatusBadge from '../components/StatusBadge.vue';
import ModalForm from '../components/ModalForm.vue';
import { useProducts } from '../composables/useProducts';
import api from '../utils/api';

const {
  products, loading, pagination,
  fetchProducts, createProduct, updateProduct, deleteProduct, bulkDeleteProducts
} = useProducts();

const columns = [
  { key: 'checkbox', label: '', sortable: false },
  { key: 'no', label: 'No', sortable: false },
  { key: 'code', label: 'Code', sortable: false },
  { key: 'name', label: 'Name / SKU', sortable: true },
  { key: 'brand_name', label: 'Brand', sortable: false },
  { key: 'current_price', label: 'Price', sortable: false },
  { key: 'variants_count', label: 'Variants', sortable: false },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'actions', label: '', sortable: false },
];

const filters = ref({ search: '', status: '', brand_id: '' });
const selectedIds = ref([]);
const pageError = ref(null);
const isModalOpen = ref(false);
const selectedProduct = ref(null);
const submitting = ref(false);
const formError = ref(null);
const form = ref({ code: '', sku: '', name: '', description: '', current_price: null, status: 'Active' });

// Import state
const isImportModalOpen = ref(false);
const importing = ref(false);
const importError = ref(null);
const importResult = ref(null);
const brands = ref([]);
const importForm = ref({ brand_id: '', file: null });
const fileInputRef = ref(null);


const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id);
  if (idx > -1) {
    selectedIds.value.splice(idx, 1);
  } else {
    selectedIds.value.push(id);
  }
};

const toggleSelectAll = () => {
  if (selectedIds.value.length === products.value.length) {
    selectedIds.value = [];
  } else {
    selectedIds.value = products.value.map(p => p.id);
  }
};

const confirmBulkDelete = async () => {
  if (selectedIds.value.length === 0) return;
  if (!confirm('Delete ' + selectedIds.value.length + ' selected product(s)?')) return;
  const success = await bulkDeleteProducts(selectedIds.value);
  if (success) {
    selectedIds.value = [];
    fetchData();
  }
};

onMounted(() => {
  fetchData();
  fetchBrands();
});

const fetchData = () => {
  pageError.value = null;
  const params = { page: pagination.value.current_page };
  if (filters.value.search) params.search = filters.value.search;
  if (filters.value.status) params.status = filters.value.status;
  if (filters.value.brand_id) params.brand_id = filters.value.brand_id;
  fetchProducts(params);
};

const handleBrandFilter = () => { pagination.value.current_page = 1; fetchData(); };

const handleSearch = (q) => {
  filters.value.search = q;
  pagination.value.current_page = 1;
  fetchData();
};

const changePage = (dir) => {
  pagination.value.current_page += dir;
  fetchData();
};

const openCreate = () => {
  selectedProduct.value = null;
  form.value = { code: '', sku: '', name: '', description: '', current_price: null, status: 'Active', brand_id: '' };
  formError.value = null;
  isModalOpen.value = true;
};

const openEdit = (p) => {
  selectedProduct.value = p;
  form.value = {
    code: p.code,
    sku: p.sku || '',
    name: p.name,
    description: p.description || '',
    current_price: p.current_price ?? null,
    status: p.status,
    brand_id: p.brand?.id || ''
  };
  formError.value = null;
  isModalOpen.value = true;
};

const submitForm = async () => {
  submitting.value = true;
  formError.value = null;
  try {
    const data = { ...form.value };
    if (data.current_price === '' || data.current_price === null) {
      data.current_price = null;
    } else {
      data.current_price = parseFloat(data.current_price);
    }

    const result = selectedProduct.value
      ? await updateProduct(selectedProduct.value.id, data)
      : await createProduct(data);

    if (result) {
      isModalOpen.value = false;
      fetchData();
    } else {
      formError.value = 'Failed to save product. Please check your input.';
    }
  } catch (e) {
    formError.value = e.response?.data?.message || 'An unexpected error occurred.';
  } finally {
    submitting.value = false;
  }
};

const confirmDelete = async (row) => {
  if (!confirm(`Delete product "${row.code} - ${row.name}"?`)) return;
  await deleteProduct(row.id);
  fetchData();
};

const fetchBrands = async () => {
  try {
    const res = await api.get('/admin/brands', { params: { per_page: 100 } });
    if (res.data.success) {
      brands.value = res.data.data.brands.data || res.data.data.brands || [];
    }
  } catch (e) {
    console.error('Failed to load brands', e);
  }
};

const formatCurrency = (val) => {
  if (val == null) return '—';
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
};

// Import handlers
const openImportModal = async () => {
  importError.value = null;
  importResult.value = null;
  importForm.value = { brand_id: '', file: null };
  isImportModalOpen.value = true;
  // Load brands
  try {
    const res = await api.get('/admin/brands');
    if (res.data.success) {
      brands.value = res.data.data.brands.data || res.data.data.brands || [];
    }
  } catch (e) {
    importError.value = 'Failed to load brands.';
  }
};

const triggerFileInput = () => {
  fileInputRef.value?.click();
};

const closeImportModal = () => {
  isImportModalOpen.value = false;
  importResult.value = null;
  importError.value = null;
  // Refresh data if import happened
  fetchData();
};

const onFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    importForm.value.file = file;
  }
};

const submitImport = async () => {
  if (!importForm.value.brand_id) {
    importError.value = 'Please select a brand.';
    return;
  }
  if (!importForm.value.file) {
    importError.value = 'Please select an Excel file.';
    return;
  }

  importing.value = true;
  importError.value = null;
  importResult.value = null;

  try {
    const formData = new FormData();
    formData.append('file', importForm.value.file);
    formData.append('brand_id', importForm.value.brand_id);

    const res = await api.post('/admin/products/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (res.data.success) {
      importResult.value = res.data.data;
    } else {
      importError.value = res.data.message || 'Import failed.';
    }
  } catch (e) {
    importError.value = e.response?.data?.message || 'Import failed. Please check your file.';
  } finally {
    importing.value = false;
  }
};
</script>



