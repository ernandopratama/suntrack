<template>
    <div class="space-y-5">

        <!-- ========================================================= -->
        <!-- PAGE HEADER -->
        <!-- ========================================================= -->
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
        >
            <div>
                <div class="flex items-center gap-2.5">
                    <div
                        class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center"
                    >
                        <i class="fa-solid fa-tags text-sm"></i>
                    </div>

                    <div>
                        <h1
                            class="text-xl sm:text-2xl font-black tracking-tight text-gray-900"
                        >
                            Brands
                        </h1>

                        <p class="text-xs text-gray-500 mt-0.5">
                            Kelola brand dan identitas produk Anda.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Create Brand -->
            <button
                @click="openCreateModal"
                type="button"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow-sm hover:shadow-md transition-all duration-200"
            >
                <span
                    class="w-5 h-5 rounded-md bg-white/15 flex items-center justify-center"
                >
                    <i class="fa-solid fa-plus text-[9px]"></i>
                </span>

                <span>New Brand</span>
            </button>
        </div>


        <!-- ========================================================= -->
        <!-- BRAND TABLE -->
        <!-- ========================================================= -->
        <div
            class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden"
        >
            <DataTable
                :columns="columns"
                :data="brands"
                :loading="loading"
                @search="handleSearch"
            >

                <!-- NO -->
                <template #cell-no="{ row, idx }">
                    <span
                        class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-gray-50 text-[10px] font-bold text-gray-500"
                    >
                        {{
                            (pagination.current_page - 1) *
                                (pagination.per_page || 15) +
                            idx +
                            1
                        }}
                    </span>
                </template>


                <!-- ID -->
                <template #cell-id="{ row }">
                    <div
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-gray-50 border border-gray-100"
                    >
                        <i
                            class="fa-solid fa-fingerprint text-[8px] text-gray-400"
                        ></i>

                        <code
                            class="text-[10px] font-mono font-semibold text-gray-500"
                        >
                            {{ row.id }}
                        </code>
                    </div>
                </template>


                <!-- BRAND NAME -->
                <template #cell-name="{ row }">
                    <div class="flex items-center gap-3">

                        <!-- Brand Avatar -->
                        <div
                            class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-50 to-blue-50 border border-indigo-100 text-indigo-600 flex items-center justify-center flex-shrink-0"
                        >
                            <span
                                class="text-xs font-black uppercase"
                            >
                                {{
                                    row.name
                                        ? row.name.charAt(0)
                                        : "B"
                                }}
                            </span>
                        </div>

                        <div class="min-w-0">
                            <div
                                class="text-sm font-bold text-gray-900 truncate"
                            >
                                {{ row.name }}
                            </div>

                            <div
                                class="flex items-center gap-1.5 text-[10px] text-gray-400 mt-0.5"
                            >
                                <i
                                    class="fa-solid fa-tag text-[8px]"
                                ></i>

                                Product Brand
                            </div>
                        </div>
                    </div>
                </template>


                <!-- ACTIONS -->
                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-1">

                        <!-- Edit -->
                        <button
                            @click="openEditModal(row)"
                            type="button"
                            title="Edit brand"
                            class="w-8 h-8 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition flex items-center justify-center"
                        >
                            <i
                                class="fa-solid fa-pen-to-square text-xs"
                            ></i>
                        </button>

                        <!-- Delete -->
                        <button
                            @click="confirmDelete(row)"
                            type="button"
                            title="Delete brand"
                            class="w-8 h-8 rounded-lg text-gray-400 hover:text-rose-600 hover:bg-rose-50 transition flex items-center justify-center"
                        >
                            <i
                                class="fa-solid fa-trash-can text-xs"
                            ></i>
                        </button>
                    </div>
                </template>


                <!-- ================================================= -->
                <!-- PAGINATION -->
                <!-- ================================================= -->
                <template #pagination>

                    <!-- Mobile -->
                    <div
                        class="flex items-center justify-between w-full sm:hidden"
                    >
                        <button
                            @click="prevPage"
                            :disabled="pagination.current_page === 1"
                            type="button"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-gray-200 bg-white text-xs font-bold text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        >
                            <i
                                class="fa-solid fa-chevron-left text-[9px]"
                            ></i>

                            Previous
                        </button>

                        <span
                            class="text-[10px] font-bold text-gray-400"
                        >
                            Page
                            <span class="text-gray-700">
                                {{ pagination.current_page }}
                            </span>
                            /
                            <span class="text-gray-700">
                                {{ pagination.last_page }}
                            </span>
                        </span>

                        <button
                            @click="nextPage"
                            :disabled="
                                pagination.current_page ===
                                pagination.last_page
                            "
                            type="button"
                            class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-gray-200 bg-white text-xs font-bold text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        >
                            Next

                            <i
                                class="fa-solid fa-chevron-right text-[9px]"
                            ></i>
                        </button>
                    </div>


                    <!-- Desktop -->
                    <div
                        class="hidden sm:flex sm:items-center sm:justify-between w-full"
                    >
                        <div
                            class="flex items-center gap-2 text-xs text-gray-500"
                        >
                            <i
                                class="fa-solid fa-tags text-[10px] text-gray-400"
                            ></i>

                            <span>
                                Showing page
                                <span class="font-bold text-gray-800">
                                    {{ pagination.current_page }}
                                </span>
                                of
                                <span class="font-bold text-gray-800">
                                    {{ pagination.last_page }}
                                </span>
                            </span>

                            <span class="text-gray-300">•</span>

                            <span>
                                {{ pagination.total }} total brands
                            </span>
                        </div>

                        <div class="flex items-center gap-1.5">

                            <!-- Previous -->
                            <button
                                @click="prevPage"
                                :disabled="
                                    pagination.current_page === 1
                                "
                                type="button"
                                class="w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-900 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center justify-center"
                            >
                                <i
                                    class="fa-solid fa-chevron-left text-[9px]"
                                ></i>
                            </button>

                            <!-- Current Page -->
                            <div
                                class="min-w-8 h-8 px-2 rounded-lg bg-blue-50 text-blue-700 text-xs font-extrabold flex items-center justify-center"
                            >
                                {{ pagination.current_page }}
                            </div>

                            <!-- Next -->
                            <button
                                @click="nextPage"
                                :disabled="
                                    pagination.current_page ===
                                    pagination.last_page
                                "
                                type="button"
                                class="w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-900 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center justify-center"
                            >
                                <i
                                    class="fa-solid fa-chevron-right text-[9px]"
                                ></i>
                            </button>
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>


        <!-- ========================================================= -->
        <!-- DELETE CONFIRMATION MODAL -->
        <!-- ========================================================= -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        >
            <!-- Backdrop -->
            <div
                class="absolute inset-0 bg-gray-950/60 backdrop-blur-sm"
                @click="showDeleteModal = false"
            ></div>

            <!-- Modal -->
            <div
                class="relative w-full max-w-md bg-white rounded-2xl border border-gray-200 shadow-2xl overflow-hidden"
            >

                <!-- Header -->
                <div class="px-6 pt-6 pb-4">
                    <div class="flex items-start gap-4">

                        <div
                            class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0"
                        >
                            <i
                                class="fa-solid fa-tag text-sm"
                            ></i>
                        </div>

                        <div>
                            <h3
                                class="text-base font-black text-gray-900"
                            >
                                Delete Brand
                            </h3>

                            <p
                                class="text-xs text-gray-500 mt-1 leading-relaxed"
                            >
                                Brand yang dihapus tidak dapat
                                digunakan kembali.
                            </p>
                        </div>
                    </div>
                </div>


                <!-- Brand Information -->
                <div class="px-6 pb-5">

                    <div
                        class="rounded-xl bg-gray-50 border border-gray-100 p-3.5"
                    >
                        <div class="flex items-center gap-3">

                            <!-- Avatar -->
                            <div
                                class="w-9 h-9 rounded-lg bg-white border border-gray-200 text-indigo-600 flex items-center justify-center"
                            >
                                <span
                                    class="text-xs font-black uppercase"
                                >
                                    {{
                                        brandToDelete?.name
                                            ? brandToDelete.name.charAt(
                                                  0
                                              )
                                            : "B"
                                    }}
                                </span>
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="text-xs font-bold text-gray-900 truncate"
                                >
                                    {{ brandToDelete?.name }}
                                </p>

                                <p
                                    class="text-[10px] text-gray-400 mt-0.5 font-mono"
                                >
                                    ID:
                                    {{ brandToDelete?.id }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <p
                        class="text-xs text-gray-500 mt-4 leading-relaxed"
                    >
                        Apakah Anda yakin ingin menghapus brand
                        <span class="font-bold text-gray-800">
                            {{ brandToDelete?.name }}
                        </span>
                        ?
                    </p>
                </div>


                <!-- Footer -->
                <div
                    class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-2.5"
                >
                    <!-- Cancel -->
                    <button
                        @click="showDeleteModal = false"
                        type="button"
                        class="px-4 py-2 rounded-lg border border-gray-200 bg-white hover:bg-gray-100 text-xs font-bold text-gray-600 transition"
                    >
                        Cancel
                    </button>

                    <!-- Delete -->
                    <button
                        @click="deleteBrandAction"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold shadow-sm transition"
                    >
                        <i
                            class="fa-solid fa-trash-can text-[9px]"
                        ></i>

                        Delete Brand
                    </button>
                </div>
            </div>
        </div>


        <!-- ========================================================= -->
        <!-- BRAND FORM -->
        <!-- ========================================================= -->
        <BrandForm
            :is-open="isModalOpen"
            :brand="selectedBrand"
            @close="closeModal"
            @saved="fetchData"
        />
    </div>
</template>


<script setup>
import { ref, onMounted } from "vue";
import DataTable from "../components/DataTable.vue";
import BrandForm from "../components/BrandForm.vue";
import { useBrands } from "../composables/useBrands";

const {
    brands,
    loading,
    pagination,
    fetchBrands,
    deleteBrand,
} = useBrands();

const columns = [
    {
        key: "no",
        label: "No",
        sortable: false,
    },
    {
        key: "id",
        label: "ID",
        sortable: false,
    },
    {
        key: "name",
        label: "Brand Name",
        sortable: true,
    },
    {
        key: "actions",
        label: "",
        sortable: false,
    },
];

const searchQuery = ref("");
const isModalOpen = ref(false);
const selectedBrand = ref(null);
const showDeleteModal = ref(false);
const brandToDelete = ref(null);

onMounted(() => {
    fetchData();
});

const fetchData = () => {
    fetchBrands({
        page: pagination.value.current_page,
        search: searchQuery.value,
    });
};

const handleSearch = (q) => {
    searchQuery.value = q;
    pagination.value.current_page = 1;
    fetchData();
};

const prevPage = () => {
    if (pagination.value.current_page > 1) {
        pagination.value.current_page--;
        fetchData();
    }
};

const nextPage = () => {
    if (
        pagination.value.current_page <
        pagination.value.last_page
    ) {
        pagination.value.current_page++;
        fetchData();
    }
};

const openCreateModal = () => {
    selectedBrand.value = null;
    isModalOpen.value = true;
};

const openEditModal = (row) => {
    selectedBrand.value = row;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
};

const confirmDelete = (row) => {
    brandToDelete.value = row;
    showDeleteModal.value = true;
};

const deleteBrandAction = async () => {
    if (brandToDelete.value) {
        const success = await deleteBrand(
            brandToDelete.value.id
        );

        if (success) {
            showDeleteModal.value = false;
            brandToDelete.value = null;
            fetchData();
        }
    }
};
</script>