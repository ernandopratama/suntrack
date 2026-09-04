<template>
    <ModalForm
        :is-open="isOpen"
        :title="isEdit ? 'Edit Company' : 'Create Company'"
        @close="closeModal"
    >
        <form
            id="company-form"
            @submit.prevent="submit"
            class="space-y-5"
        >
            <!-- Error Alert -->
            <div
                v-if="typeof error === 'string'"
                class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3.5"
            >
                <div
                    class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0"
                >
                    <i class="fa-solid fa-circle-exclamation text-xs"></i>
                </div>

                <div class="min-w-0">
                    <p class="text-xs font-bold text-rose-800">
                        Gagal menyimpan data
                    </p>

                    <p class="text-xs text-rose-600 mt-0.5 leading-relaxed">
                        {{ error }}
                    </p>
                </div>
            </div>


            <!-- Company Preview -->
            <div
                class="rounded-2xl border border-gray-200 bg-gradient-to-br from-gray-50 to-white p-4"
            >
                <div class="flex items-center gap-3.5">

                    <!-- Company Icon -->
                    <div
                        class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0"
                    >
                        <i class="fa-solid fa-building text-sm"></i>
                    </div>

                    <div class="min-w-0">
                        <p
                            class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400"
                        >
                            Company
                        </p>

                        <p
                            class="text-sm font-bold text-gray-900 mt-0.5 truncate"
                        >
                            {{
                                form.name?.trim()
                                    ? form.name
                                    : "New Company"
                            }}
                        </p>
                    </div>

                    <div class="ml-auto">
                        <span
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-700 text-[10px] font-bold"
                        >
                            <span
                                class="w-1.5 h-1.5 rounded-full bg-emerald-500"
                            ></span>

                            {{ isEdit ? "Editing" : "New" }}
                        </span>
                    </div>
                </div>
            </div>


            <!-- Company Name -->
            <div>
                <label
                    for="company-name"
                    class="flex items-center justify-between text-xs font-bold text-gray-700"
                >
                    <span>
                        Company Name
                        <span class="text-rose-500">*</span>
                    </span>

                    <span
                        class="text-[10px] font-medium text-gray-400"
                    >
                        Required
                    </span>
                </label>

                <div class="relative mt-2">
                    <div
                        class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none"
                    >
                        <i
                            class="fa-solid fa-building text-gray-400 text-xs"
                        ></i>
                    </div>

                    <input
                        id="company-name"
                        type="text"
                        v-model="form.name"
                        required
                        autocomplete="organization"
                        placeholder="Enter company name"
                        class="block w-full rounded-xl border border-gray-200 bg-white py-3 pl-10 pr-4 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm outline-none transition-all duration-200 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 hover:border-gray-300"
                        :class="
                            hasError('name')
                                ? 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/10'
                                : ''
                        "
                    />
                </div>

                <p
                    v-if="hasError('name')"
                    class="flex items-center gap-1.5 mt-2 text-[11px] font-medium text-rose-600"
                >
                    <i
                        class="fa-solid fa-circle-exclamation text-[9px]"
                    ></i>

                    {{ getError("name") }}
                </p>

                <p
                    v-else
                    class="mt-2 text-[10px] text-gray-400"
                >
                    Gunakan nama perusahaan yang akan ditampilkan
                    pada sistem.
                </p>
            </div>
        </form>


        <!-- Footer -->
        <template #footer>
            <div
                class="flex w-full flex-col-reverse gap-2 sm:flex-row sm:justify-end"
            >
                <!-- Cancel -->
                <button
                    type="button"
                    @click="closeModal"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200"
                >
                    <i class="fa-solid fa-xmark text-[10px]"></i>

                    Cancel
                </button>

                <!-- Save -->
                <button
                    v-if="isEdit ? $can('company.update') : $can('company.create')"
                    type="submit"
                    form="company-form"
                    :disabled="loading"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-xs font-extrabold text-white shadow-sm hover:bg-blue-700 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60 transition-all duration-200"
                >
                    <i
                        v-if="loading"
                        class="fa-solid fa-spinner animate-spin text-[10px]"
                    ></i>

                    <i
                        v-else
                        class="fa-solid fa-check text-[10px]"
                    ></i>

                    <span>
                        {{ loading ? "Saving..." : "Save Company" }}
                    </span>
                </button>
            </div>
        </template>
    </ModalForm>
</template>


<script setup>
import { reactive, ref, watch } from "vue";
import ModalForm from "./ModalForm.vue";
import { useCompanies } from "../composables/useCompanies";

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true,
    },

    company: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["close", "saved"]);

const {
    createCompany,
    updateCompany,
    loading,
    error,
} = useCompanies();

const isEdit = ref(false);

const form = reactive({
    name: "",
});

watch(
    () => [props.isOpen, props.company],
    ([open]) => {
        if (!open) return;

        if (props.company) {
            isEdit.value = true;
            form.name = props.company.name;
        } else {
            isEdit.value = false;
            form.name = "";
        }

        error.value = null;
    },
    {
        immediate: true,
    }
);

const hasError = (field) =>
    error.value &&
    typeof error.value === "object" &&
    error.value[field];

const getError = (field) =>
    hasError(field)
        ? Array.isArray(error.value[field])
            ? error.value[field][0]
            : error.value[field]
        : "";

const submit = async () => {
    let success = false;

    if (isEdit.value) {
        success = await updateCompany(props.company.id, {
            name: form.name,
        });
    } else {
        success = await createCompany({
            name: form.name,
        });
    }

    if (success) {
        emit("saved");
        closeModal();
    }
};

const closeModal = () => emit("close");
</script>
