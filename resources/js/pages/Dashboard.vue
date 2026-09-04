<template>
    <div class="space-y-8 pb-12">

        <!-- HERO SECTION & COMMAND CENTER -->
        <div
    class="relative overflow-visible rounded-2xl bg-gray-950 border border-gray-800 shadow-xl"
        >
            <div class="pointer-events-none absolute inset-0 overflow-hidden rounded-2xl">
                <!-- Decorative Background -->
                <div
                    class="absolute -top-32 -right-24 w-80 h-80 rounded-full bg-blue-600/10 blur-3xl"
                ></div>

                <div
                    class="absolute -bottom-32 left-1/3 w-72 h-72 rounded-full bg-indigo-600/10 blur-3xl"
                ></div>

                <!-- Subtle Grid -->
                <div
                    class="absolute inset-0 opacity-[0.035]"
                    style="
                        background-image:
                            linear-gradient(rgba(255,255,255,.8) 1px, transparent 1px),
                            linear-gradient(90deg, rgba(255,255,255,.8) 1px, transparent 1px);
                        background-size: 32px 32px;
                    "
                ></div>
            </div>

            <div
                class="relative z-10 p-5 sm:p-6 lg:p-7"
            >
                <div
                    class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-6"
                >

                    <!-- ================================================= -->
                    <!-- LEFT: COMMAND CENTER INTRO -->
                    <!-- ================================================= -->
                    <div class="min-w-0 max-w-2xl">

                        <!-- Status Row -->
                        <div
                            class="flex flex-wrap items-center gap-2.5 mb-4"
                        >
                            <span
                                class="inline-flex items-center gap-2 px-2.5 py-1 rounded-lg bg-blue-500/10 border border-blue-400/20 text-blue-300 text-[10px] font-extrabold uppercase tracking-wider"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full bg-blue-400 animate-pulse"
                                ></span>

                                Operational Command Center
                            </span>

                            <span
                                class="inline-flex items-center gap-1.5 text-[10px] text-gray-500 font-mono"
                            >
                                <i class="fa-regular fa-clock text-[9px]"></i>

                                {{ formatTime(lastRefreshed) }}
                            </span>
                        </div>

                        <!-- Title -->
                        <h1
                            class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-white leading-tight"
                        >
                            Selamat Datang, Admin SunTrack
                        </h1>

                        <!-- Description -->
                        <p
                            class="text-sm text-gray-400 leading-relaxed mt-3 max-w-xl"
                        >
                            Pantau progres kampanye, persetujuan promosi,
                            tenggat waktu kritis, aktivitas operasional,
                            serta laporan sistem dari satu pusat kendali.
                        </p>

                        <!-- System Status -->
                        <div
                            class="flex flex-wrap items-center gap-3 mt-5"
                        >
                            <div
                                class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400"
                            >
                                <span
                                    class="w-2 h-2 rounded-full bg-emerald-400"
                                ></span>

                                Sistem Operasional
                            </div>

                            <span class="text-gray-700">•</span>

                            <div
                                class="inline-flex items-center gap-2 text-[10px] font-bold text-gray-400"
                            >
                                <i class="fa-solid fa-chart-line text-blue-400"></i>

                                Real-time Monitoring
                            </div>
                        </div>
                    </div>


                    <!-- ================================================= -->
                    <!-- RIGHT: COMMAND ACTIONS -->
                    <!-- ================================================= -->
                    <div
                        class="flex flex-col sm:flex-row xl:flex-col 2xl:flex-row gap-2.5 xl:min-w-[420px] 2xl:min-w-0"
                    >

                        <!-- ============================================= -->
                        <!-- EXPORT REPORT -->
                        <!-- ============================================= -->
                        <div v-if="$can('report.export')" class="relative group flex-1 z-50">

                            <button
                                type="button"
                                @click.stop="exportDropdownOpen = !exportDropdownOpen"
                                class="w-full px-4 py-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 text-white transition flex items-center justify-between gap-3"
                            >
                                <span
                                    class="flex items-center gap-2.5"
                                >
                                    <span
                                        class="w-7 h-7 rounded-lg bg-white/10 flex items-center justify-center"
                                    >
                                        <i
                                            class="fa-solid fa-file-export text-xs text-gray-300"
                                        ></i>
                                    </span>

                                    <span class="text-left">
                                        <span
                                            class="block text-[9px] font-extrabold uppercase tracking-wide text-gray-500"
                                        >
                                            Export Report
                                        </span>

                                        <span
                                            class="block text-xs font-bold text-gray-200 mt-0.5"
                                        >
                                            Format:
                                            {{ selectedFormat.toUpperCase() }}
                                        </span>
                                    </span>
                                </span>

                                <i
                                    class="fa-solid fa-chevron-down text-[9px] text-gray-500 transition"
                                    :class="{ 'rotate-180 text-white': exportDropdownOpen }"
                                ></i>
                            </button>


                            <!-- Export Dropdown -->
                            <div
                                v-if="exportDropdownOpen"
                                @click.stop
                                class="absolute right-0 top-full mt-2 w-full sm:w-72 bg-white rounded-xl shadow-2xl border border-gray-200 p-2 z-[9999] text-gray-800"
                            >

                                <!-- Format Selector -->
                                <div
                                    class="p-1 bg-gray-100 rounded-lg flex items-center gap-1 mb-2"
                                >
                                    <button
                                        @click.stop="selectedFormat = 'csv'"
                                        :class="
                                            selectedFormat === 'csv'
                                                ? 'bg-blue-600 text-white shadow-sm'
                                                : 'text-gray-500 hover:text-gray-800'
                                        "
                                        class="flex-1 px-2 py-1.5 rounded-md text-[10px] font-extrabold transition"
                                    >
                                        CSV
                                    </button>

                                    <button
                                        @click.stop="selectedFormat = 'excel'"
                                        :class="
                                            selectedFormat === 'excel'
                                                ? 'bg-emerald-600 text-white shadow-sm'
                                                : 'text-gray-500 hover:text-gray-800'
                                        "
                                        class="flex-1 px-2 py-1.5 rounded-md text-[10px] font-extrabold transition"
                                    >
                                        EXCEL
                                    </button>

                                    <button
                                        @click.stop="selectedFormat = 'pdf'"
                                        :class="
                                            selectedFormat === 'pdf'
                                                ? 'bg-rose-600 text-white shadow-sm'
                                                : 'text-gray-500 hover:text-gray-800'
                                        "
                                        class="flex-1 px-2 py-1.5 rounded-md text-[10px] font-extrabold transition"
                                    >
                                        PDF
                                    </button>
                                </div>

                                <!-- Dropdown Label -->
                                <div
                                    class="px-2 py-1.5 text-[9px] font-extrabold uppercase tracking-wider text-gray-400"
                                >
                                    Pilih laporan
                                </div>

                                <!-- Campaign -->
                                <button
                                    @click="exportReport('campaign', selectedFormat); exportDropdownOpen = false"
                                    class="w-full px-3 py-2.5 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition flex items-center gap-3 text-left"
                                >
                                    <span
                                        class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0"
                                    >
                                        <i class="fa-solid fa-bullhorn text-[10px]"></i>
                                    </span>

                                    <span class="flex-1">
                                        <span
                                            class="block text-xs font-bold"
                                        >
                                            Laporan Campaign
                                        </span>

                                        <span
                                            class="block text-[9px] text-gray-400 mt-0.5"
                                        >
                                            Data kampanye operasional
                                        </span>
                                    </span>

                                    <span
                                        class="text-[9px] font-mono font-bold text-gray-400"
                                    >
                                        {{ selectedFormat.toUpperCase() }}
                                    </span>
                                </button>

                                <!-- Promotion -->
                                <button
                                    @click="exportReport('promotion', selectedFormat); exportDropdownOpen = false"
                                    class="w-full px-3 py-2.5 rounded-lg hover:bg-emerald-50 hover:text-emerald-600 transition flex items-center gap-3 text-left"
                                >
                                    <span
                                        class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0"
                                    >
                                        <i class="fa-solid fa-tag text-[10px]"></i>
                                    </span>

                                    <span class="flex-1">
                                        <span
                                            class="block text-xs font-bold"
                                        >
                                            Laporan Promotion
                                        </span>

                                        <span
                                            class="block text-[9px] text-gray-400 mt-0.5"
                                        >
                                            Data promosi dan status
                                        </span>
                                    </span>

                                    <span
                                        class="text-[9px] font-mono font-bold text-gray-400"
                                    >
                                        {{ selectedFormat.toUpperCase() }}
                                    </span>
                                </button>

                                <!-- Approval -->
                                <button
                                    @click="exportReport('approval', selectedFormat); exportDropdownOpen = false"
                                    class="w-full px-3 py-2.5 rounded-lg hover:bg-amber-50 hover:text-amber-600 transition flex items-center gap-3 text-left"
                                >
                                    <span
                                        class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0"
                                    >
                                        <i class="fa-solid fa-signature text-[10px]"></i>
                                    </span>

                                    <span class="flex-1">
                                        <span
                                            class="block text-xs font-bold"
                                        >
                                            Brand Approval
                                        </span>

                                        <span
                                            class="block text-[9px] text-gray-400 mt-0.5"
                                        >
                                            Persetujuan dari brand
                                        </span>
                                    </span>

                                    <span
                                        class="text-[9px] font-mono font-bold text-gray-400"
                                    >
                                        {{ selectedFormat.toUpperCase() }}
                                    </span>
                                </button>

                                <!-- Product -->
                                <button
                                    @click="exportReport('product', selectedFormat); exportDropdownOpen = false"
                                    class="w-full px-3 py-2.5 rounded-lg hover:bg-indigo-50 hover:text-indigo-600 transition flex items-center gap-3 text-left"
                                >
                                    <span
                                        class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0"
                                    >
                                        <i class="fa-solid fa-box text-[10px]"></i>
                                    </span>

                                    <span class="flex-1">
                                        <span
                                            class="block text-xs font-bold"
                                        >
                                            Master Katalog
                                        </span>

                                        <span
                                            class="block text-[9px] text-gray-400 mt-0.5"
                                        >
                                            Produk dan varian
                                        </span>
                                    </span>

                                    <span
                                        class="text-[9px] font-mono font-bold text-gray-400"
                                    >
                                        {{ selectedFormat.toUpperCase() }}
                                    </span>
                                </button>

                                <!-- Activity -->
                                <button
                                    @click="exportReport('activity', selectedFormat); exportDropdownOpen = false"
                                    class="w-full px-3 py-2.5 rounded-lg hover:bg-gray-100 transition flex items-center gap-3 text-left border-t border-gray-100 mt-1 pt-3"
                                >
                                    <span
                                        class="w-7 h-7 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center flex-shrink-0"
                                    >
                                        <i class="fa-solid fa-list-check text-[10px]"></i>
                                    </span>

                                    <span class="flex-1">
                                        <span
                                            class="block text-xs font-bold"
                                        >
                                            Audit Log
                                        </span>

                                        <span
                                            class="block text-[9px] text-gray-400 mt-0.5"
                                        >
                                            Aktivitas dan jejak sistem
                                        </span>
                                    </span>

                                    <span
                                        class="text-[9px] font-mono font-bold text-gray-400"
                                    >
                                        {{ selectedFormat.toUpperCase() }}
                                    </span>
                                </button>
                            </div>
                        </div>


                        <!-- ============================================= -->
                        <!-- REFRESH -->
                        <!-- ============================================= -->
                        <button
                            @click="fetchStats"
                            :disabled="loading"
                            class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 disabled:bg-blue-800 disabled:cursor-not-allowed text-white font-bold text-xs shadow-lg shadow-blue-950/30 transition flex items-center justify-center gap-2"
                        >
                            <i
                                class="fa-solid fa-arrows-rotate text-[10px]"
                                :class="{ 'animate-spin': loading }"
                            ></i>

                            <span>
                                {{ loading ? "Memuat..." : "Segarkan Data" }}
                            </span>
                        </button>


                        <!-- ============================================= -->
                        <!-- CREATE CAMPAIGN -->
                        <!-- ============================================= -->
                        <router-link
                            to="/campaigns"
                            class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs shadow-lg shadow-emerald-950/30 transition flex items-center justify-center gap-2 whitespace-nowrap"
                        >
                            <i class="fa-solid fa-plus text-[10px]"></i>

                            <span>Buat Campaign</span>
                        </router-link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- LOADING STATE -->
        <!-- ============================================================= -->
        <div
            v-if="loading && !stats"
            class="space-y-5"
        >
            <!-- KPI Skeleton -->
            <div
                class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4"
            >
                <div
                    v-for="i in 4"
                    :key="i"
                    class="h-36 bg-gray-200 rounded-2xl animate-pulse"
                ></div>
            </div>

            <!-- Workspace Skeleton -->
            <div
                class="grid grid-cols-1 xl:grid-cols-3 gap-5"
            >
                <div
                    class="h-[430px] bg-gray-200 rounded-2xl animate-pulse xl:col-span-2"
                ></div>

                <div
                    class="h-[430px] bg-gray-200 rounded-2xl animate-pulse"
                ></div>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- ERROR STATE -->
        <!-- ============================================================= -->
        <div
            v-else-if="error"
            class="rounded-2xl border border-rose-200 bg-rose-50 overflow-hidden"
        >
            <div
                class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div class="flex items-start gap-3">

                    <div
                        class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center flex-shrink-0"
                    >
                        <i class="fa-solid fa-triangle-exclamation text-sm"></i>
                    </div>

                    <div>
                        <h4
                            class="text-sm font-extrabold text-rose-900"
                        >
                            Gagal Memuat Statistik Dashboard
                        </h4>

                        <p
                            class="text-xs text-rose-700 mt-1"
                        >
                            {{ error }}
                        </p>
                    </div>
                </div>

                <button
                    @click="fetchStats"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition"
                >
                    <i class="fa-solid fa-rotate-right text-[10px]"></i>

                    Coba Lagi
                </button>
            </div>
        </div>

        <div v-else-if="stats" class="space-y-8">
            <!-- 1. PRIMARY KPI CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

                <!-- Active Campaigns -->
                <div
                    @click="navigateTo('/campaigns')"
                    class="group relative overflow-hidden bg-white rounded-2xl border border-gray-200 p-5 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:border-blue-300"
                >
                    <!-- Decorative Background -->
                    <div
                        class="absolute -right-8 -top-8 w-24 h-24 rounded-full bg-blue-50 opacity-60 group-hover:scale-125 transition-transform duration-300"
                    ></div>

                    <div class="relative">

                        <!-- Header -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-rocket text-sm"></i>
                                </div>

                                <div>
                                    <p
                                        class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 group-hover:text-blue-600 transition"
                                    >
                                        Active Campaigns
                                    </p>

                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                        Kampanye berjalan
                                    </p>
                                </div>
                            </div>

                            <i
                                class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-300 group-hover:text-blue-500 transition"
                            ></i>
                        </div>

                        <!-- Value -->
                        <div class="flex items-baseline gap-2 mt-5">
                            <span
                                class="text-3xl font-black tracking-tight text-gray-900"
                            >
                                {{ stats.kpi.campaigns.active }}
                            </span>

                            <span
                                class="text-xs font-medium text-gray-400"
                            >
                                / {{ stats.kpi.campaigns.total }} total
                            </span>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100"
                        >
                            <span
                                class="text-[10px] font-bold text-blue-600"
                            >
                                Kelola kampanye
                            </span>

                            <span
                                class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition"
                            >
                                <i class="fa-solid fa-arrow-right text-[9px]"></i>
                            </span>
                        </div>
                    </div>
                </div>


                <!-- Active Promotions -->
                <div
                    @click="navigateTo('/promotions')"
                    class="group relative overflow-hidden bg-white rounded-2xl border border-gray-200 p-5 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:border-emerald-300"
                >
                    <!-- Decorative Background -->
                    <div
                        class="absolute -right-8 -top-8 w-24 h-24 rounded-full bg-emerald-50 opacity-60 group-hover:scale-125 transition-transform duration-300"
                    ></div>

                    <div class="relative">

                        <!-- Header -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-tag text-sm"></i>
                                </div>

                                <div>
                                    <p
                                        class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 group-hover:text-emerald-600 transition"
                                    >
                                        Active Promotions
                                    </p>

                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                        Promosi sedang aktif
                                    </p>
                                </div>
                            </div>

                            <i
                                class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-300 group-hover:text-emerald-500 transition"
                            ></i>
                        </div>

                        <!-- Value -->
                        <div class="flex items-baseline gap-2 mt-5">
                            <span
                                class="text-3xl font-black tracking-tight text-gray-900"
                            >
                                {{ stats.kpi.promotions.active }}
                            </span>

                            <span
                                class="text-xs font-medium text-gray-400"
                            >
                                / {{ stats.kpi.promotions.total }} total
                            </span>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100"
                        >
                            <span
                                class="text-[10px] font-bold text-emerald-600"
                            >
                                Lihat promosi aktif
                            </span>

                            <span
                                class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:bg-emerald-600 group-hover:text-white transition"
                            >
                                <i class="fa-solid fa-arrow-right text-[9px]"></i>
                            </span>
                        </div>
                    </div>
                </div>


                <!-- Pending Approvals -->
                <div
                    @click="navigateTo('/promotions')"
                    class="group relative overflow-hidden bg-white rounded-2xl border border-amber-200 p-5 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:border-amber-400 bg-gradient-to-br from-white to-amber-50/40"
                >
                    <!-- Decorative Background -->
                    <div
                        class="absolute -right-8 -top-8 w-24 h-24 rounded-full bg-amber-100/70 group-hover:scale-125 transition-transform duration-300"
                    ></div>

                    <div class="relative">

                        <!-- Header -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="relative w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-hourglass-half text-sm"></i>

                                    <span
                                        v-if="stats.kpi.promotions.pending > 0"
                                        class="absolute -top-1 -right-1 w-3 h-3 rounded-full bg-amber-500 border-2 border-white animate-pulse"
                                    ></span>
                                </div>

                                <div>
                                    <p
                                        class="text-[10px] font-extrabold uppercase tracking-wider text-amber-600"
                                    >
                                        Pending Approvals
                                    </p>

                                    <p class="text-[10px] text-amber-600/70 mt-0.5">
                                        Menunggu persetujuan
                                    </p>
                                </div>
                            </div>

                            <i
                                class="fa-solid fa-arrow-up-right-from-square text-[10px] text-amber-300 group-hover:text-amber-600 transition"
                            ></i>
                        </div>

                        <!-- Value -->
                        <div class="flex items-baseline gap-2 mt-5">
                            <span
                                class="text-3xl font-black tracking-tight text-amber-900"
                            >
                                {{ stats.kpi.promotions.pending }}
                            </span>

                            <span
                                class="text-xs font-medium text-amber-600"
                            >
                                promosi menunggu
                            </span>
                        </div>

                        <!-- Footer -->
                        <div
                            class="flex items-center justify-between mt-4 pt-3 border-t border-amber-100"
                        >
                            <span
                                class="text-[10px] font-bold text-amber-700"
                            >
                                Tinjau respons brand
                            </span>

                            <span
                                class="w-6 h-6 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center group-hover:bg-amber-500 group-hover:text-white transition"
                            >
                                <i class="fa-solid fa-arrow-right text-[9px]"></i>
                            </span>
                        </div>
                    </div>
                </div>


                <!-- Deadlines Today -->
                <div
                    @click="activeDeadlineTab = 'today'"
                    class="group relative overflow-hidden bg-white rounded-2xl border border-gray-200 p-5 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:border-rose-300"
                >
                    <!-- Decorative Background -->
                    <div
                        class="absolute -right-8 -top-8 w-24 h-24 rounded-full bg-rose-50 opacity-60 group-hover:scale-125 transition-transform duration-300"
                    ></div>

                    <div class="relative">

                        <!-- Header -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-clock text-sm"></i>
                                </div>

                                <div>
                                    <p
                                        class="text-[10px] font-extrabold uppercase tracking-wider text-gray-400 group-hover:text-rose-600 transition"
                                    >
                                        Deadlines Today
                                    </p>

                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                        Berakhir hari ini
                                    </p>
                                </div>
                            </div>

                            <i
                                class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-300 group-hover:text-rose-500 transition"
                            ></i>
                        </div>

                        <!-- Value -->
                        <div class="flex items-baseline gap-2 mt-5">
                            <span
                                class="text-3xl font-black tracking-tight"
                                :class="
                                    (stats.deadlines?.today?.length ?? 0) > 0
                                        ? 'text-rose-600'
                                        : 'text-gray-900'
                                "
                            >
                                {{ stats.deadlines?.today?.length ?? 0 }}
                            </span>

                            <span
                                class="text-xs font-medium text-gray-400"
                            >
                                item berakhir
                            </span>
                        </div>

                        <!-- Status -->
                        <div
                            class="flex items-center justify-between mt-4 pt-3 border-t border-gray-100"
                        >
                            <span
                                class="inline-flex items-center gap-1.5 text-[10px] font-bold"
                                :class="
                                    (stats.deadlines?.today?.length ?? 0) > 0
                                        ? 'text-rose-600'
                                        : 'text-emerald-600'
                                "
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="
                                        (stats.deadlines?.today?.length ?? 0) > 0
                                            ? 'bg-rose-500'
                                            : 'bg-emerald-500'
                                    "
                                ></span>

                                {{
                                    (stats.deadlines?.today?.length ?? 0) > 0
                                        ? "Perlu perhatian"
                                        : "Tidak ada deadline"
                                }}
                            </span>

                            <span
                                class="w-6 h-6 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center group-hover:bg-rose-600 group-hover:text-white transition"
                            >
                                <i class="fa-solid fa-arrow-right text-[9px]"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. SECONDARY KPI AGGREGATION -->
            <div
                class="rounded-2xl border border-gray-200 bg-white shadow-xs overflow-hidden"
            >
                <!-- Section Header -->
                <div
                    class="px-5 py-4 border-b border-gray-100 bg-gray-50/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0"
                        >
                            <i class="fa-solid fa-chart-column text-sm"></i>
                        </div>

                        <div>
                            <h3 class="text-sm font-extrabold text-gray-900">
                                Rincian Agregasi Operasional & Kolaborasi Brand
                            </h3>
                            <p class="text-2xs text-gray-500 mt-0.5">
                                Ringkasan aktivitas dan data operasional SunTrack
                            </p>
                        </div>
                    </div>

                    <span
                        class="inline-flex items-center self-start sm:self-auto gap-1.5 px-2.5 py-1 rounded-full bg-gray-100 border border-gray-200 text-2xs font-bold text-gray-500"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        KPI Overview
                    </span>
                </div>

                <!-- KPI Grid -->
                <div class="p-4 sm:p-5">
                    <div
                        class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-3"
                    >
                        <!-- Completed Campaign -->
                        <div
                            @click="navigateTo('/campaigns')"
                            class="group rounded-xl border border-gray-200 bg-white p-4 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <div
                                    class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-check text-xs"></i>
                                </div>

                                <i
                                    class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-300 group-hover:text-blue-500 transition"
                                ></i>
                            </div>

                            <p
                                class="text-[10px] font-extrabold uppercase tracking-wide text-gray-400 leading-tight"
                            >
                                Completed Campaign
                            </p>

                            <p
                                class="text-2xl font-black text-gray-900 mt-1"
                            >
                                {{ stats.kpi?.campaigns?.completed ?? 0 }}
                            </p>
                        </div>

                        <!-- Approved Promotion -->
                        <div
                            @click="navigateTo('/promotions')"
                            class="group rounded-xl border border-gray-200 bg-white p-4 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <div
                                    class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-circle-check text-xs"></i>
                                </div>

                                <i
                                    class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-300 group-hover:text-emerald-500 transition"
                                ></i>
                            </div>

                            <p
                                class="text-[10px] font-extrabold uppercase tracking-wide text-gray-400 leading-tight"
                            >
                                Approved Promo
                            </p>

                            <p
                                class="text-2xl font-black text-emerald-600 mt-1"
                            >
                                {{ stats.kpi?.promotions?.approved ?? 0 }}
                            </p>
                        </div>

                        <!-- Partially Approved -->
                        <div
                            @click="navigateTo('/promotions')"
                            class="group rounded-xl border border-gray-200 bg-white p-4 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <div
                                    class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-hourglass-half text-xs"></i>
                                </div>

                                <i
                                    class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-300 group-hover:text-amber-500 transition"
                                ></i>
                            </div>

                            <p
                                class="text-[10px] font-extrabold uppercase tracking-wide text-gray-400 leading-tight"
                            >
                                Partially Approved
                            </p>

                            <p
                                class="text-2xl font-black text-amber-600 mt-1"
                            >
                                {{ stats.kpi?.promotions?.partially_approved ?? 0 }}
                            </p>
                        </div>

                        <!-- Rejected Promotion -->
                        <div
                            @click="navigateTo('/promotions')"
                            class="group rounded-xl border border-gray-200 bg-white p-4 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:border-rose-300 hover:shadow-md"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <div
                                    class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-circle-xmark text-xs"></i>
                                </div>

                                <i
                                    class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-300 group-hover:text-rose-500 transition"
                                ></i>
                            </div>

                            <p
                                class="text-[10px] font-extrabold uppercase tracking-wide text-gray-400 leading-tight"
                            >
                                Rejected Promo
                            </p>

                            <p
                                class="text-2xl font-black text-rose-600 mt-1"
                            >
                                {{ stats.kpi?.promotions?.rejected ?? 0 }}
                            </p>
                        </div>

                        <!-- Master Products -->
                        <div
                            @click="navigateTo('/products')"
                            class="group rounded-xl border border-gray-200 bg-white p-4 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <div
                                    class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-box text-xs"></i>
                                </div>

                                <i
                                    class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-300 group-hover:text-sky-500 transition"
                                ></i>
                            </div>

                            <p
                                class="text-[10px] font-extrabold uppercase tracking-wide text-gray-400 leading-tight"
                            >
                                Master Products
                            </p>

                            <p
                                class="text-2xl font-black text-gray-900 mt-1"
                            >
                                {{ stats.kpi?.catalog?.total_products ?? 0 }}
                            </p>
                        </div>

                        <!-- Master Variants -->
                        <div
                            @click="navigateTo('/products')"
                            class="group rounded-xl border border-gray-200 bg-white p-4 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <div
                                    class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-layer-group text-xs"></i>
                                </div>

                                <i
                                    class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-300 group-hover:text-indigo-500 transition"
                                ></i>
                            </div>

                            <p
                                class="text-[10px] font-extrabold uppercase tracking-wide text-gray-400 leading-tight"
                            >
                                Master Variants
                            </p>

                            <p
                                class="text-2xl font-black text-gray-900 mt-1"
                            >
                                {{ stats.kpi?.catalog?.total_variants ?? 0 }}
                            </p>
                        </div>

                        <!-- Secure Links -->
                        <div
                            @click="navigateTo('/promotions')"
                            class="group rounded-xl border border-gray-200 bg-white p-4 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:border-violet-300 hover:shadow-md"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <div
                                    class="w-8 h-8 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-link text-xs"></i>
                                </div>

                                <i
                                    class="fa-solid fa-arrow-up-right-from-square text-[10px] text-gray-300 group-hover:text-violet-500 transition"
                                ></i>
                            </div>

                            <p
                                class="text-[10px] font-extrabold uppercase tracking-wide text-gray-400 leading-tight"
                            >
                                Secure Links
                            </p>

                            <p
                                class="text-2xl font-black text-violet-600 mt-1"
                            >
                                {{ stats.kpi?.catalog?.total_secure_links ?? 0 }}
                            </p>
                        </div>

                        <!-- Brand Reviews -->
                        <div
                            class="group rounded-xl border border-gray-200 bg-white p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-purple-300 hover:shadow-md"
                        >
                            <div class="flex items-center justify-between mb-4">
                                <div
                                    class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center"
                                >
                                    <i class="fa-solid fa-star text-xs"></i>
                                </div>

                                <span
                                    class="text-[9px] font-bold uppercase tracking-wide text-gray-400"
                                >
                                    Rate
                                </span>
                            </div>

                            <p
                                class="text-[10px] font-extrabold uppercase tracking-wide text-gray-400 leading-tight"
                            >
                                Brand Reviews
                            </p>

                            <div class="flex items-baseline gap-1 mt-1">
                                <p
                                    class="text-2xl font-black text-purple-600"
                                >
                                    {{ stats.kpi?.catalog?.total_brand_reviews ?? 0 }}
                                </p>

                                <span class="text-[10px] font-bold text-gray-400">
                                    ({{ stats.kpi?.extended?.approval_rate ?? 0 }}%)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. WORKSPACE GRID: DEADLINE MONITORING & RECENT ACTIVITIES -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">

                <!-- ========================================================= -->
                <!-- LEFT: DEADLINE MONITORING -->
                <!-- ========================================================= -->
                <div
                    class="xl:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden"
                >

                    <!-- Header -->
                    <div
                        class="px-5 py-4 border-b border-gray-100 bg-gray-50/60"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                        >
                            <!-- Title -->
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0"
                                >
                                    <i class="fa-solid fa-clock text-sm"></i>
                                </div>

                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3
                                            class="text-sm sm:text-base font-extrabold text-gray-900"
                                        >
                                            Pusat Pemantauan Tenggat Waktu
                                        </h3>

                                        <span
                                            class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 border border-gray-200 text-[9px] font-bold text-gray-500 uppercase tracking-wide"
                                        >
                                            Deadline Monitor
                                        </span>
                                    </div>

                                    <p
                                        class="text-xs text-gray-500 mt-1 leading-relaxed"
                                    >
                                        Pantau kampanye dan tautan promosi yang mendekati
                                        deadline atau mengalami keterlambatan.
                                    </p>
                                </div>
                            </div>

                            <!-- Auto Refresh -->
                            <label
                                for="autoRef"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-200 bg-white cursor-pointer hover:border-blue-300 hover:bg-blue-50/50 transition select-none"
                            >
                                <span
                                    class="flex items-center justify-center w-6 h-6 rounded-lg"
                                    :class="
                                        autoRefreshActive
                                            ? 'bg-blue-100 text-blue-600'
                                            : 'bg-gray-100 text-gray-400'
                                    "
                                >
                                    <i
                                        class="fa-solid fa-arrows-rotate text-[10px]"
                                        :class="{ 'animate-spin': loading }"
                                    ></i>
                                </span>

                                <span>
                                    <span
                                        class="block text-[10px] font-extrabold uppercase tracking-wide text-gray-400"
                                    >
                                        Auto Refresh
                                    </span>

                                    <span
                                        class="block text-xs font-bold"
                                        :class="
                                            autoRefreshActive
                                                ? 'text-blue-600'
                                                : 'text-gray-600'
                                        "
                                    >
                                        {{ autoRefreshActive ? "Aktif · 60s" : "Nonaktif" }}
                                    </span>
                                </span>

                                <input
                                    type="checkbox"
                                    id="autoRef"
                                    v-model="autoRefreshActive"
                                    @change="toggleAutoRefresh"
                                    class="sr-only"
                                />

                                <span
                                    class="relative w-9 h-5 rounded-full transition-colors"
                                    :class="
                                        autoRefreshActive
                                            ? 'bg-blue-600'
                                            : 'bg-gray-300'
                                    "
                                >
                                    <span
                                        class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow-sm transition-transform"
                                        :class="
                                            autoRefreshActive
                                                ? 'translate-x-4'
                                                : 'translate-x-0'
                                        "
                                    ></span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Deadline Tabs -->
                    <div
                        class="border-b border-gray-100 bg-white overflow-x-auto"
                    >
                        <nav
                            class="flex items-center gap-1 px-4 sm:px-5 min-w-max"
                        >
                            <button
                                v-for="tab in deadlineTabs"
                                :key="tab.id"
                                @click="activeDeadlineTab = tab.id"
                                :class="[
                                    activeDeadlineTab === tab.id
                                        ? 'text-blue-600 border-blue-600 bg-blue-50/60'
                                        : 'text-gray-500 border-transparent hover:text-gray-700 hover:bg-gray-50',
                                    'relative flex items-center gap-2 px-3 py-3 text-xs font-bold border-b-2 transition whitespace-nowrap',
                                ]"
                            >
                                <span>{{ tab.name }}</span>

                                <span
                                    class="min-w-[20px] h-5 px-1.5 rounded-full flex items-center justify-center text-[10px] font-extrabold"
                                    :class="
                                        activeDeadlineTab === tab.id
                                            ? 'bg-blue-100 text-blue-700'
                                            : 'bg-gray-100 text-gray-500'
                                    "
                                >
                                    {{ getDeadlineCount(tab.id) }}
                                </span>
                            </button>
                        </nav>
                    </div>

                    <!-- Deadline List -->
                    <div
                        class="p-4 sm:p-5 min-h-[360px] max-h-[500px] overflow-y-auto"
                    >
                        <!-- Items -->
                        <div
                            v-for="(item, index) in currentDeadlineItems"
                            :key="`${item?.type ?? 'item'}-${item?.id ?? index}`"
                            class="group relative mb-3 last:mb-0 rounded-xl border border-gray-200 bg-white hover:border-blue-200 hover:bg-blue-50/20 hover:shadow-sm transition-all duration-200"
                        >
                            <div
                                class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                            >

                                <!-- Left Content -->
                                <div class="flex items-start gap-3 min-w-0">

                                    <!-- Status Indicator -->
                                    <div class="pt-1 flex-shrink-0">
                                        <span
                                            class="block w-3 h-3 rounded-full shadow-sm"
                                            :class="getIndicatorClass(item.status_code)"
                                            :title="
                                                item.status_code === 'red'
                                                    ? 'Terlambat (Overdue)'
                                                    : item.status_code === 'yellow'
                                                    ? 'Mendekati Deadline / Hari Ini'
                                                    : 'Aman'
                                            "
                                        ></span>
                                    </div>

                                    <div class="min-w-0 flex-1">

                                        <!-- Type & Status -->
                                        <div class="flex flex-wrap items-center gap-2 mb-1.5">

                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-md border text-[9px] font-extrabold uppercase tracking-wide"
                                                :class="
                                                    String(item?.type || '').includes(
                                                        'Secure Link',
                                                    )
                                                        ? 'bg-indigo-50 border-indigo-100 text-indigo-700'
                                                        : item?.type === 'Campaign'
                                                        ? 'bg-blue-50 border-blue-100 text-blue-700'
                                                        : 'bg-emerald-50 border-emerald-100 text-emerald-700'
                                                "
                                            >
                                                {{ item?.type || "Unknown" }}
                                            </span>

                                            <span
                                                class="inline-flex items-center gap-1 text-[10px] font-bold"
                                                :class="
                                                    item.status_code === 'red'
                                                        ? 'text-rose-600'
                                                        : item.status_code === 'yellow'
                                                        ? 'text-amber-600'
                                                        : 'text-emerald-600'
                                                "
                                            >
                                                <span
                                                    class="w-1.5 h-1.5 rounded-full"
                                                    :class="getIndicatorClass(item.status_code)"
                                                ></span>

                                                {{ item?.status || "-" }}
                                            </span>
                                        </div>

                                        <!-- Title -->
                                        <h4
                                            class="text-sm font-extrabold text-gray-900 truncate group-hover:text-blue-700 transition"
                                        >
                                            {{ item?.title || "-" }}
                                        </h4>

                                        <!-- Subtitle -->
                                        <p
                                            class="text-xs text-gray-500 mt-1 truncate"
                                        >
                                            {{ item?.subtitle || "-" }}
                                        </p>

                                        <!-- Deadline -->
                                        <div
                                            class="flex items-center gap-1.5 mt-2 text-[10px]"
                                        >
                                            <i
                                                class="fa-regular fa-clock text-gray-400"
                                            ></i>

                                            <span class="text-gray-400">
                                                Deadline
                                            </span>

                                            <span
                                                class="font-mono font-bold"
                                                :class="
                                                    item.status_code === 'red'
                                                        ? 'text-rose-600'
                                                        : item.status_code === 'yellow'
                                                        ? 'text-amber-600'
                                                        : 'text-gray-700'
                                                "
                                            >
                                                {{ item?.deadline || "-" }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action -->
                                <button
                                    @click="navigateTo(item.url)"
                                    class="w-full sm:w-auto flex-shrink-0 inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-lg bg-gray-100 hover:bg-gray-900 text-gray-700 hover:text-white text-xs font-bold transition"
                                >
                                    <span>Buka Detail</span>

                                    <i
                                        class="fa-solid fa-arrow-up-right-from-square text-[10px]"
                                    ></i>
                                </button>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-if="currentDeadlineItems.length === 0"
                            class="min-h-[300px] flex flex-col items-center justify-center text-center px-6"
                        >
                            <div
                                class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center mb-4"
                            >
                                <i class="fa-solid fa-circle-check text-xl"></i>
                            </div>

                            <h4
                                class="text-sm font-extrabold text-gray-800"
                            >
                                Tidak Ada Deadline
                            </h4>

                            <p
                                class="text-xs text-gray-400 mt-1 max-w-sm"
                            >
                                Tidak ada item pada kategori tenggat waktu ini.
                                Seluruh operasional berjalan aman.
                            </p>
                        </div>
                    </div>
                </div>


                <!-- ========================================================= -->
                <!-- RIGHT: RECENT ACTIVITIES -->
                <!-- ========================================================= -->
                <div
                    class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden"
                >

                    <!-- Header -->
                    <div
                        class="px-5 py-4 border-b border-gray-100 bg-gray-50/60"
                    >
                        <div class="flex items-center justify-between gap-3">

                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0"
                                >
                                    <i class="fa-solid fa-clock-rotate-left text-sm"></i>
                                </div>

                                <div>
                                    <h3
                                        class="text-sm sm:text-base font-extrabold text-gray-900"
                                    >
                                        Aktivitas Terbaru
                                    </h3>

                                    <p
                                        class="text-xs text-gray-500 mt-0.5"
                                    >
                                        Jejak aktivitas Admin & Brand.
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-100 text-[10px] font-extrabold text-emerald-700"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"
                                ></span>

                                Live
                            </span>
                        </div>
                    </div>

                    <!-- Activity List -->
                    <div
                        class="p-5 max-h-[500px] overflow-y-auto"
                    >
                        <div
                            v-for="(log, index) in stats.recent_activities || []"
                            :key="log.id"
                            class="relative flex gap-3 pb-5 last:pb-0"
                        >

                            <!-- Timeline -->
                            <div
                                class="relative flex flex-col items-center flex-shrink-0"
                            >
                                <div
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-[10px] shadow-sm"
                                    :class="
                                        log.actor_type === 'Admin'
                                            ? 'bg-blue-600'
                                            : log.actor_type === 'Brand'
                                            ? 'bg-amber-500'
                                            : 'bg-gray-500'
                                    "
                                >
                                    <i
                                        :class="
                                            log.actor_type === 'Admin'
                                                ? 'fa-solid fa-user-shield'
                                                : log.actor_type === 'Brand'
                                                ? 'fa-solid fa-store'
                                                : 'fa-solid fa-gear'
                                        "
                                    ></i>
                                </div>

                                <span
                                    v-if="index < (stats.recent_activities?.length ?? 0) - 1"
                                    class="absolute top-9 bottom-0 w-px bg-gray-200"
                                ></span>
                            </div>

                            <!-- Content -->
                            <div class="min-w-0 flex-1">

                                <!-- Actor & Time -->
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <div class="min-w-0">
                                        <div
                                            class="flex flex-wrap items-center gap-1.5"
                                        >
                                            <span
                                                class="text-xs font-extrabold text-gray-800 truncate"
                                            >
                                                {{ log.actor_name || "System" }}
                                            </span>

                                            <span
                                                class="px-1.5 py-0.5 rounded text-[8px] font-extrabold uppercase text-white"
                                                :class="
                                                    log.actor_type === 'Admin'
                                                        ? 'bg-blue-600'
                                                        : log.actor_type === 'Brand'
                                                        ? 'bg-amber-500'
                                                        : 'bg-gray-500'
                                                "
                                            >
                                                {{ log.actor_type || "System" }}
                                            </span>
                                        </div>
                                    </div>

                                    <span
                                        class="text-[9px] text-gray-400 font-mono whitespace-nowrap flex-shrink-0"
                                    >
                                        {{ log.time_ago }}
                                    </span>
                                </div>

                                <!-- Description -->
                                <p
                                    class="text-xs text-gray-600 leading-relaxed mt-1.5"
                                >
                                    {{ log.description }}
                                </p>

                                <!-- Metadata -->
                                <div
                                    class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-2 text-[9px] text-gray-400"
                                >
                                    <span
                                        class="inline-flex items-center gap-1"
                                    >
                                        <i class="fa-solid fa-bolt text-amber-500"></i>
                                        {{ log.action }}
                                    </span>

                                    <span
                                        v-if="log.target_type"
                                        class="inline-flex items-center gap-1"
                                    >
                                        <i class="fa-solid fa-bullseye text-gray-400"></i>
                                        {{ log.target_type }}
                                        #{{ log.target_id?.slice(0, 8) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-if="(stats.recent_activities?.length ?? 0) === 0"
                            class="min-h-[300px] flex flex-col items-center justify-center text-center"
                        >
                            <div
                                class="w-14 h-14 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center mb-4"
                            >
                                <i class="fa-solid fa-clock-rotate-left text-xl"></i>
                            </div>

                            <h4
                                class="text-sm font-extrabold text-gray-800"
                            >
                                Belum Ada Aktivitas
                            </h4>

                            <p
                                class="text-xs text-gray-400 mt-1"
                            >
                                Belum ada aktivitas yang tercatat pada sistem.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import { useDashboard } from "../composables/useDashboard";

const router = useRouter();
const {
    stats,
    loading,
    error,
    lastRefreshed,
    fetchStats,
    startAutoRefresh,
    stopAutoRefresh,
    exportReport,
} = useDashboard();

const activeDeadlineTab = ref("today");
const autoRefreshActive = ref(false);
const selectedFormat = ref("csv");
const exportDropdownOpen = ref(false);

const deadlineTabs = [
    { id: "today", name: "Hari Ini" },
    { id: "tomorrow", name: "Besok" },
    { id: "next_7_days", name: "7 Hari ke Depan" },
    { id: "overdue", name: "⚠️ Terlambat (Overdue)" },
    { id: "expiring_links", name: "🔗 Tautan Akan Habis" },
];

const closeExportDropdown = () => {
    exportDropdownOpen.value = false;
};

onMounted(async () => {
    await fetchStats();
    window.addEventListener("click", closeExportDropdown);
});

onUnmounted(() => {
    window.removeEventListener("click", closeExportDropdown);
});

const toggleAutoRefresh = () => {
    if (autoRefreshActive.value) {
        startAutoRefresh(60);
    } else {
        stopAutoRefresh();
    }
};

const navigateTo = (path) => {
    router.push(path);
};

const getDeadlineCount = (tabId) => {
    if (!stats.value?.deadlines) return 0;
    return stats.value.deadlines[tabId]?.length || 0;
};

const currentDeadlineItems = computed(() => {
    if (!stats.value?.deadlines) return [];
    const rawItems = stats.value.deadlines[activeDeadlineTab.value];
    if (Array.isArray(rawItems)) {
        return rawItems.filter(Boolean);
    }
    if (!rawItems) {
        return [];
    }
    if (typeof rawItems === "object") {
        return Object.values(rawItems).filter(Boolean);
    }
    return [];
});

const getIndicatorClass = (code) => {
    switch (code) {
        case "red":
            return "bg-rose-500 animate-pulse";
        case "yellow":
            return "bg-amber-400";
        default:
            return "bg-emerald-500";
    }
};

const formatTime = (dateObj) => {
    if (!dateObj) return "Memuat...";
    return (
        "Diperbarui pukul " +
        dateObj.toLocaleTimeString("id-ID", {
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit",
        })
    );
};
</script>
