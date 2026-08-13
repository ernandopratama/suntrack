```vue
<template>
    <div class="space-y-6">
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
                class="absolute right-28 bottom-[-80px] h-48 w-48 rounded-full opacity-30"
                style="background: #95ccdd"
            ></div>

            <div
                class="relative flex items-center justify-between gap-6 p-8"
            >
                <div class="flex items-start gap-5">
                    <!-- Icon -->
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
                        <div class="flex flex-wrap items-center gap-3">
                            <h1
                                class="text-3xl font-bold tracking-tight"
                                style="color: #293681"
                            >
                                Activity Logs
                            </h1>

                            <span
                                class="rounded-full px-3 py-1 text-xs font-semibold"
                                style="
                                    background: #d0e7e6;
                                    color: #293681;
                                "
                            >
                                System Activity
                            </span>
                        </div>

                        <p
                            class="mt-2 max-w-xl text-sm leading-6 text-slate-500"
                        >
                            Riwayat aktivitas sistem dan pengguna untuk
                            membantu memantau perubahan serta aktivitas
                            operasional.
                        </p>
                    </div>
                </div>

                <!-- Decorative Activity Icon -->
                <div
                    class="hidden h-20 w-20 items-center justify-center rounded-2xl md:flex"
                    style="background: #f1f8f8"
                >
                    <svg
                        class="h-10 w-10"
                        style="color: #4274d9"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        stroke-width="1.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 12h3l2-6 4 12 2-6h5"
                        />
                    </svg>
                </div>
            </div>

            <!-- Accent Line -->
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

        <!-- Placeholder Content -->
        <div
            class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100"
        >
            <div
                class="flex flex-col items-center justify-center px-8 py-14 text-center"
            >
                <!-- Empty State Icon -->
                <div
                    class="flex h-20 w-20 items-center justify-center rounded-3xl"
                    style="background: #d0e7e6"
                >
                    <svg
                        class="h-9 w-9"
                        style="color: #293681"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        stroke-width="1.7"
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

                <h2
                    class="mt-6 text-xl font-bold"
                    style="color: #293681"
                >
                    Activity Log Coming Soon
                </h2>

                <p
                    class="mt-2 max-w-lg text-sm leading-6 text-slate-500"
                >
                    Halaman aktivitas sedang dalam tahap pengembangan.
                    Untuk saat ini, data aktivitas dapat dicari melalui fitur
                    reporting atau daftar aktivitas khusus.
                </p>

                <!-- Information Card -->
                <div
                    class="mt-7 flex max-w-lg items-start gap-3 rounded-2xl p-4 text-left"
                    style="background: #f1f8f8"
                >
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                        style="background: #95ccdd"
                    >
                        <svg
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
                                d="M13 16h-1v-4h-1m1-4h.01"
                            />
                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                            />
                        </svg>
                    </div>

                    <div>
                        <p
                            class="text-sm font-semibold"
                            style="color: #293681"
                        >
                            Activity monitoring
                        </p>

                        <p
                            class="mt-1 text-xs leading-5 text-slate-500"
                        >
                            Activity logs nantinya dapat digunakan untuk
                            melihat riwayat perubahan, aktivitas pengguna,
                            dan kejadian sistem.
                        </p>
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
    </div>
</template>

<script setup></script>