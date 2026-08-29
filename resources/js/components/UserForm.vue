<template>
    <ModalForm
        :is-open="isOpen"
        :title="isEdit ? 'Edit User' : 'Create User'"
        @close="closeModal"
    >
        <form
            id="user-form"
            @submit.prevent="submit"
            class="space-y-5"
        >

            <!-- ===================================================== -->
            <!-- ERROR ALERT -->
            <!-- ===================================================== -->
            <div
                v-if="typeof error === 'string'"
                class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3"
            >
                <div
                    class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0"
                >
                    <i class="fa-solid fa-circle-exclamation text-xs"></i>
                </div>

                <div>
                    <p class="text-xs font-bold text-rose-800">
                        Unable to save user
                    </p>

                    <p class="text-[11px] text-rose-600 mt-0.5">
                        {{ error }}
                    </p>
                </div>
            </div>


            <!-- ===================================================== -->
            <!-- USER TYPE -->
            <!-- ===================================================== -->
            <div v-if="!isEdit">
                <div class="mb-2.5">
                    <label
                        class="text-xs font-extrabold text-gray-800"
                    >
                        User Type
                        <span class="text-rose-500">*</span>
                    </label>

                    <p class="text-[10px] text-gray-400 mt-0.5">
                        Pilih jenis akun yang akan dibuat.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                    <!-- Admin -->
                    <button
                        type="button"
                        @click="form.type = 'admin'"
                        class="relative flex items-center gap-3 p-3.5 rounded-xl border transition-all duration-200 text-left"
                        :class="
                            form.type === 'admin'
                                ? 'border-blue-500 bg-blue-50/70 shadow-sm'
                                : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'
                        "
                    >
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 transition"
                            :class="
                                form.type === 'admin'
                                    ? 'bg-blue-100 text-blue-600'
                                    : 'bg-gray-100 text-gray-400'
                            "
                        >
                            <i class="fa-solid fa-user-shield text-sm"></i>
                        </div>

                        <div class="min-w-0">
                            <div
                                class="text-xs font-extrabold"
                                :class="
                                    form.type === 'admin'
                                        ? 'text-blue-800'
                                        : 'text-gray-700'
                                "
                            >
                                Admin
                            </div>

                            <div
                                class="text-[10px] text-gray-400 mt-0.5"
                            >
                                Full system access
                            </div>
                        </div>

                        <div
                            v-if="form.type === 'admin'"
                            class="absolute top-3 right-3 w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center"
                        >
                            <i class="fa-solid fa-check text-[8px]"></i>
                        </div>
                    </button>


                    <!-- Company -->
                    <button
                        type="button"
                        @click="form.type = 'company'"
                        class="relative flex items-center gap-3 p-3.5 rounded-xl border transition-all duration-200 text-left"
                        :class="
                            form.type === 'company'
                                ? 'border-emerald-500 bg-emerald-50/70 shadow-sm'
                                : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'
                        "
                    >
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 transition"
                            :class="
                                form.type === 'company'
                                    ? 'bg-emerald-100 text-emerald-600'
                                    : 'bg-gray-100 text-gray-400'
                            "
                        >
                            <i class="fa-solid fa-building text-sm"></i>
                        </div>

                        <div class="min-w-0">
                            <div
                                class="text-xs font-extrabold"
                                :class="
                                    form.type === 'company'
                                        ? 'text-emerald-800'
                                        : 'text-gray-700'
                                "
                            >
                                Company
                            </div>

                            <div
                                class="text-[10px] text-gray-400 mt-0.5"
                            >
                                Brand access
                            </div>
                        </div>

                        <div
                            v-if="form.type === 'company'"
                            class="absolute top-3 right-3 w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center"
                        >
                            <i class="fa-solid fa-check text-[8px]"></i>
                        </div>
                    </button>
                </div>
            </div>


            <!-- ===================================================== -->
            <!-- USER TYPE - EDIT MODE -->
            <!-- ===================================================== -->
            <div v-else>
                <label
                    class="block text-xs font-extrabold text-gray-800 mb-2"
                >
                    User Type
                </label>

                <div
                    class="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-3"
                >
                    <div class="flex items-center gap-3">

                        <div
                            class="w-9 h-9 rounded-lg flex items-center justify-center"
                            :class="
                                form.type === 'admin'
                                    ? 'bg-purple-100 text-purple-600'
                                    : 'bg-emerald-100 text-emerald-600'
                            "
                        >
                            <i
                                :class="
                                    form.type === 'admin'
                                        ? 'fa-solid fa-user-shield'
                                        : 'fa-solid fa-building'
                                "
                                class="text-xs"
                            ></i>
                        </div>

                        <div>
                            <p
                                class="text-xs font-extrabold"
                                :class="
                                    form.type === 'admin'
                                        ? 'text-purple-700'
                                        : 'text-emerald-700'
                                "
                            >
                                {{
                                    form.type === "admin"
                                        ? "Admin"
                                        : "Company"
                                }}
                            </p>

                            <p class="text-[10px] text-gray-400">
                                User type cannot be changed
                            </p>
                        </div>
                    </div>

                    <span
                        class="inline-flex items-center gap-1.5 px-2 py-1 rounded-lg text-[9px] font-extrabold uppercase"
                        :class="
                            form.type === 'admin'
                                ? 'bg-purple-100 text-purple-700'
                                : 'bg-emerald-100 text-emerald-700'
                        "
                    >
                        <span
                            class="w-1.5 h-1.5 rounded-full"
                            :class="
                                form.type === 'admin'
                                    ? 'bg-purple-500'
                                    : 'bg-emerald-500'
                            "
                        ></span>

                        {{ form.type }}
                    </span>
                </div>
            </div>


            <!-- ===================================================== -->
            <!-- NAME -->
            <!-- ===================================================== -->
            <div>
                <label
                    for="user-name"
                    class="block text-xs font-extrabold text-gray-800 mb-1.5"
                >
                    Name
                    <span class="text-rose-500">*</span>
                </label>

                <div class="relative">
                    <div
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                    >
                        <i
                            class="fa-regular fa-user text-[11px] text-gray-400"
                        ></i>
                    </div>

                    <input
                        id="user-name"
                        type="text"
                        v-model="form.name"
                        required
                        placeholder="Enter user name"
                        class="block w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-xs text-gray-900 placeholder-gray-400 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10"
                    />
                </div>

                <p
                    v-if="hasError('name')"
                    class="flex items-center gap-1 mt-1.5 text-[10px] text-rose-600 font-medium"
                >
                    <i class="fa-solid fa-circle-exclamation text-[8px]"></i>
                    {{ getError("name") }}
                </p>
            </div>


            <!-- ===================================================== -->
            <!-- USERNAME -->
            <!-- ===================================================== -->
            <div v-if="form.type === 'admin'">
                <label
                    for="user-username"
                    class="mb-1.5 block text-xs font-extrabold text-gray-800"
                >
                    Username
                    <span class="text-rose-500">*</span>
                </label>

                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fa-solid fa-at text-[11px] text-gray-400"></i>
                    </div>

                    <input
                        id="user-username"
                        v-model="form.username"
                        type="text"
                        required
                        minlength="3"
                        maxlength="50"
                        autocomplete="username"
                        placeholder="contoh: budi.santoso"
                        class="block w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-xs lowercase text-gray-900 placeholder-gray-400 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10"
                    />
                </div>

                <p class="mt-1.5 text-[10px] text-gray-400">
                    Gunakan huruf kecil, angka, titik, garis bawah, atau tanda hubung.
                </p>

                <p
                    v-if="hasError('username')"
                    class="mt-1.5 flex items-center gap-1 text-[10px] font-medium text-rose-600"
                >
                    <i class="fa-solid fa-circle-exclamation text-[8px]"></i>
                    {{ getError("username") }}
                </p>
            </div>


            <!-- ===================================================== -->
            <!-- EMAIL -->
            <!-- ===================================================== -->
            <div v-if="form.type === 'admin'">
                <label
                    for="user-email"
                    class="block text-xs font-extrabold text-gray-800 mb-1.5"
                >
                    Email
                    <span class="text-rose-500">*</span>
                </label>

                <div class="relative">
                    <div
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                    >
                        <i
                            class="fa-regular fa-envelope text-[11px] text-gray-400"
                        ></i>
                    </div>

                    <input
                        id="user-email"
                        type="email"
                        v-model="form.email"
                        required
                        placeholder="admin@example.com"
                        class="block w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-xs text-gray-900 placeholder-gray-400 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10"
                    />
                </div>

                <p
                    v-if="hasError('email')"
                    class="flex items-center gap-1 mt-1.5 text-[10px] text-rose-600 font-medium"
                >
                    <i class="fa-solid fa-circle-exclamation text-[8px]"></i>
                    {{ getError("email") }}
                </p>
            </div>


            <!-- ===================================================== -->
            <!-- PASSWORD -->
            <!-- ===================================================== -->
            <div v-if="form.type === 'admin'">
                <label
                    for="user-password"
                    class="block text-xs font-extrabold text-gray-800 mb-1.5"
                >
                    Password

                    <span
                        v-if="!isEdit"
                        class="text-rose-500"
                    >
                        *
                    </span>
                </label>

                <div class="relative">
                    <div
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                    >
                        <i
                            class="fa-solid fa-lock text-[10px] text-gray-400"
                        ></i>
                    </div>

                    <input
                        id="user-password"
                        type="password"
                        v-model="form.password"
                        :required="!isEdit && form.type === 'admin'"
                        :placeholder="
                            isEdit
                                ? 'Leave blank to keep current password'
                                : 'Enter password'
                        "
                        class="block w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-9 pr-3 text-xs text-gray-900 placeholder-gray-400 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/10"
                    />
                </div>

                <p
                    v-if="isEdit"
                    class="flex items-center gap-1 mt-1.5 text-[10px] text-gray-400"
                >
                    <i class="fa-solid fa-circle-info text-[8px]"></i>
                    Kosongkan jika password tidak ingin diubah.
                </p>

                <p
                    v-if="hasError('password')"
                    class="flex items-center gap-1 mt-1.5 text-[10px] text-rose-600 font-medium"
                >
                    <i class="fa-solid fa-circle-exclamation text-[8px]"></i>
                    {{ getError("password") }}
                </p>
            </div>


            <!-- ===================================================== -->
            <!-- COMPANY INFORMATION -->
            <!-- ===================================================== -->
            <div
                v-if="form.type === 'company' && !isEdit"
                class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-3.5"
            >
                <div
                    class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0"
                >
                    <i class="fa-solid fa-link text-xs"></i>
                </div>

                <div>
                    <p
                        class="text-xs font-extrabold text-blue-800"
                    >
                        Company User
                    </p>

                    <p
                        class="text-[10px] text-blue-700/80 mt-1 leading-relaxed"
                    >
                        Company user tidak memerlukan email dan
                        password. Mereka hanya dapat mengakses halaman
                        public review melalui link yang dibagikan.
                    </p>
                </div>
            </div>
        </form>


        <!-- ========================================================= -->
        <!-- FOOTER -->
        <!-- ========================================================= -->
        <template #footer>
            <div
                class="flex w-full items-center justify-between gap-3"
            >
                <div
                    class="hidden sm:flex items-center gap-1.5 text-[10px] text-gray-400"
                >
                    <i class="fa-solid fa-shield-halved text-[9px]"></i>
                    Secure account
                </div>

                <div
                    class="flex w-full sm:w-auto flex-col-reverse sm:flex-row gap-2"
                >
                    <button
                        type="button"
                        @click="closeModal"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-xs font-bold text-gray-600 transition"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        form="user-form"
                        :disabled="loading"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-extrabold shadow-sm hover:shadow-md disabled:opacity-60 disabled:cursor-not-allowed transition-all"
                    >
                        <i
                            v-if="loading"
                            class="fa-solid fa-spinner animate-spin text-[10px]"
                        ></i>

                        <i
                            v-else
                            class="fa-solid fa-check text-[9px]"
                        ></i>

                        <span>
                            {{
                                loading
                                    ? "Saving..."
                                    : isEdit
                                      ? "Save Changes"
                                      : "Create User"
                            }}
                        </span>
                    </button>
                </div>
            </div>
        </template>
    </ModalForm>
</template>


<script setup>
import { reactive, ref, watch } from "vue";
import ModalForm from "./ModalForm.vue";
import { useUsers } from "../composables/useUsers";
import { useAuthStore } from "../stores/auth";

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true,
    },
    user: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["close", "saved"]);

const {
    createUser,
    updateUser,
    loading,
    error,
} = useUsers();

const authStore = useAuthStore();

const isEdit = ref(false);

const defaultForm = () => ({
    type: "admin",
    name: "",
    username: "",
    email: "",
    password: "",
    company_id: authStore.user?.company_id || "",
});

const form = reactive(defaultForm());

watch(
    () => [props.isOpen, props.user],
    ([open]) => {
        if (!open) return;

        Object.assign(form, defaultForm());

        if (props.user) {
            isEdit.value = true;

            form.type = props.user.type || "admin";
            form.name = props.user.name;
            form.username = props.user.username || "";
            form.email = props.user.email || "";
            form.password = "";
        } else {
            isEdit.value = false;
        }

        error.value = null;
    },
    {
        immediate: true,
    },
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

    const payload = {
        type: form.type,
        name: form.name,
        company_id: form.company_id,
    };

    if (form.type === "admin") {
        payload.username = form.username.trim().toLowerCase();
        payload.email = form.email;

        if (form.password) {
            payload.password = form.password;
        }
    }

    if (isEdit.value) {
        success = await updateUser(
            props.user.id,
            payload,
        );
    } else {
        success = await createUser(payload);
    }

    if (success) {
        emit("saved");
        closeModal();
    }
};

const closeModal = () => emit("close");
</script>
