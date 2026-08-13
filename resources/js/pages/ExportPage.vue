```vue
<template>
    <div class="space-y-6">
        <!-- Header -->
        <div
            class="relative overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100"
        >
            <!-- Decorative background -->
            <div
                class="absolute -right-20 -top-24 h-64 w-64 rounded-full opacity-40"
                style="background: #D0E7E6"
            ></div>

            <div
                class="absolute right-20 bottom-[-80px] h-48 w-48 rounded-full opacity-30"
                style="background: #95CCDD"
            ></div>

            <div class="relative p-8">
                <div class="flex items-start justify-between gap-6">
                    <div class="flex items-start gap-5">
                        <!-- Icon -->
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl shadow-sm"
                            style="background: #D0E7E6"
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
                                    d="M12 3v12m0 0 4-4m-4 4-4-4"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 20h14"
                                />
                            </svg>
                        </div>

                        <div>
                            <div class="flex items-center gap-3">
                                <h1
                                    class="text-3xl font-bold tracking-tight"
                                    style="color: #293681"
                                >
                                    Export
                                </h1>

                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold"
                                    style="
                                        background: #d0e7e6;
                                        color: #293681;
                                    "
                                >
                                    Reports
                                </span>
                            </div>

                            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-500">
                                Unduh data dalam format CSV untuk kebutuhan
                                laporan dan operasional.
                            </p>
                        </div>
                    </div>

                    <!-- Export illustration -->
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
                                d="M7 3h7l4 4v14H7V3Z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M14 3v5h5"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M10 13h4M10 17h4"
                            />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Placeholder Card -->
        <div
            class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-slate-100"
        >
            <div class="p-8">
                <div class="flex flex-col items-center justify-center py-10 text-center">
                    <!-- Icon -->
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
                                d="M12 3v12m0 0 4-4m-4 4-4-4"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 20h14"
                            />
                        </svg>
                    </div>

                    <h2
                        class="mt-6 text-xl font-bold"
                        style="color: #293681"
                    >
                        Export Data
                    </h2>

                    <p class="mt-2 max-w-lg text-sm leading-6 text-slate-500">
                        Halaman export ini masih dalam tahap pengembangan.
                        Gunakan tombol unduh laporan pada dashboard untuk
                        melakukan export data saat ini.
                    </p>

                    <!-- Info -->
                    <div
                        class="mt-7 flex max-w-md items-start gap-3 rounded-2xl p-4 text-left"
                        style="background: #f1f8f8"
                    >
                        <div
                            class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl"
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
                                    d="M13 16h-1v-4h-1m1-4h.01M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z"
                                />
                            </svg>
                        </div>

                        <div>
                            <p
                                class="text-sm font-semibold"
                                style="color: #293681"
                            >
                                Export tersedia melalui Dashboard
                            </p>

                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Gunakan fitur export pada dashboard untuk
                                mendapatkan data terbaru dalam format CSV.
                            </p>
                        </div>
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