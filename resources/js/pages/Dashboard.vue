<template>
    <div class="space-y-8 pb-12">
        <!-- HERO SECTION & COMMAND CENTER ACTIONS -->
        <div
            class="bg-gradient-to-r from-gray-900 via-blue-950 to-gray-900 rounded-2xl p-6 sm:p-8 text-white shadow-xl border border-gray-800 flex flex-col lg:flex-row lg:items-center justify-between gap-6"
        >
            <div class="space-y-2 max-w-2xl">
                <div class="flex items-center space-x-2.5">
                    <span
                        class="px-2.5 py-0.5 rounded-full bg-blue-500/20 text-blue-300 font-mono text-xs font-bold border border-blue-500/30 uppercase tracking-wider"
                    >
                        Operational Command Center
                    </span>
                    <span class="text-xs text-gray-400 font-mono">
                        {{ formatTime(lastRefreshed) }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Selamat Datang, Admin SunTrack! 👋
                </h1>
                <p class="text-sm text-gray-300 leading-relaxed">
                    Pantau seluruh progres kampanye promosi, persetujuan harga
                    dari Brand eksternal, tenggat waktu kritis, serta unduh
                    laporan operasional secara real-time.
                </p>
            </div>

            <!-- Quick Action Buttons & Refresh Controls (Refinement #2 & #6) -->
            <div
                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3"
            >
                <!-- Export Reports Dropdown -->
                <div class="relative group">
                    <button
                        class="w-full sm:w-auto px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white font-bold text-xs rounded-xl border border-white/10 transition flex items-center justify-center space-x-2"
                    >
                        <span
                            >📊 Unduh Laporan ({{
                                selectedFormat.toUpperCase()
                            }}) ▾</span
                        >
                    </button>
                    <div
                        class="absolute right-0 mt-2 w-64 bg-white rounded-xl shadow-2xl border border-gray-200 p-2 hidden group-hover:block z-50 text-gray-800 text-xs font-medium space-y-1"
                    >
                        <div
                            class="flex items-center justify-between p-1 bg-gray-100 rounded-lg mb-2"
                        >
                            <button
                                @click.stop="selectedFormat = 'csv'"
                                :class="
                                    selectedFormat === 'csv'
                                        ? 'bg-blue-600 text-white shadow'
                                        : 'text-gray-600 hover:text-black'
                                "
                                class="px-2 py-1 rounded-md font-bold text-2xs transition flex-1 text-center"
                            >
                                CSV
                            </button>
                            <button
                                @click.stop="selectedFormat = 'excel'"
                                :class="
                                    selectedFormat === 'excel'
                                        ? 'bg-emerald-600 text-white shadow'
                                        : 'text-gray-600 hover:text-black'
                                "
                                class="px-2 py-1 rounded-md font-bold text-2xs transition flex-1 text-center"
                            >
                                EXCEL
                            </button>
                            <button
                                @click.stop="selectedFormat = 'pdf'"
                                :class="
                                    selectedFormat === 'pdf'
                                        ? 'bg-rose-600 text-white shadow'
                                        : 'text-gray-600 hover:text-black'
                                "
                                class="px-2 py-1 rounded-md font-bold text-2xs transition flex-1 text-center"
                            >
                                PDF
                            </button>
                        </div>
                        <button
                            @click="exportReport('campaign', selectedFormat)"
                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition flex items-center justify-between font-bold"
                        >
                            <span>📋 Laporan Campaign</span>
                            <span class="text-2xs font-mono text-gray-400">{{
                                selectedFormat.toUpperCase()
                            }}</span>
                        </button>
                        <button
                            @click="exportReport('promotion', selectedFormat)"
                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition flex items-center justify-between font-bold"
                        >
                            <span>🏷️ Laporan Promotion</span>
                            <span class="text-2xs font-mono text-gray-400">{{
                                selectedFormat.toUpperCase()
                            }}</span>
                        </button>
                        <button
                            @click="exportReport('approval', selectedFormat)"
                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition flex items-center justify-between font-bold"
                        >
                            <span>✍️ Laporan Brand Approval</span>
                            <span class="text-2xs font-mono text-gray-400">{{
                                selectedFormat.toUpperCase()
                            }}</span>
                        </button>
                        <button
                            @click="exportReport('product', selectedFormat)"
                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition flex items-center justify-between font-bold"
                        >
                            <span>📦 Laporan Master Katalog</span>
                            <span class="text-2xs font-mono text-gray-400">{{
                                selectedFormat.toUpperCase()
                            }}</span>
                        </button>
                        <button
                            @click="exportReport('activity', selectedFormat)"
                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-blue-50 hover:text-blue-600 transition flex items-center justify-between font-bold border-t border-gray-100 pt-2 mt-1"
                        >
                            <span>📜 Laporan Audit Log</span>
                            <span class="text-2xs font-mono text-gray-400">{{
                                selectedFormat.toUpperCase()
                            }}</span>
                        </button>
                    </div>
                </div>

                <button
                    @click="fetchStats"
                    :disabled="loading"
                    class="px-4 py-2.5 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs rounded-xl shadow-lg transition flex items-center justify-center space-x-2"
                >
                    <span :class="{ 'animate-spin': loading }">🔄</span>
                    <span>{{ loading ? "Memuat..." : "Segarkan Data" }}</span>
                </button>

                <router-link
                    to="/campaigns"
                    class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs rounded-xl shadow-lg transition flex items-center justify-center space-x-2 whitespace-nowrap"
                >
                    <span>✨</span>
                    <span>Buat Campaign Baru</span>
                </router-link>
            </div>
        </div>

        <!-- LOADING & ERROR STATES -->
        <div v-if="loading && !stats" class="space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="i in 4"
                    :key="i"
                    class="h-32 bg-gray-200 rounded-2xl animate-pulse"
                ></div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div
                    class="h-96 bg-gray-200 rounded-2xl animate-pulse lg:col-span-2"
                ></div>
                <div class="h-96 bg-gray-200 rounded-2xl animate-pulse"></div>
            </div>
        </div>

        <div
            v-else-if="error"
            class="bg-rose-50 border border-rose-200 p-6 rounded-2xl text-rose-800 flex items-center justify-between"
        >
            <div>
                <h4 class="font-bold">Gagal Memuat Statistik Dashboard</h4>
                <p class="text-sm mt-1">{{ error }}</p>
            </div>
            <button
                @click="fetchStats"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl transition"
            >
                Coba Lagi
            </button>
        </div>

        <div v-else-if="stats" class="space-y-8">
            <!-- 1. PRIMARY CLICKABLE KPI CARDS (Big 4 - Refinement #1) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    @click="navigateTo('/campaigns')"
                    class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs hover:shadow-md hover:border-blue-500/50 cursor-pointer transition group"
                >
                    <div class="flex items-center justify-between mb-3">
                        <span
                            class="text-xs font-extrabold uppercase tracking-wider text-gray-400 group-hover:text-blue-600 transition"
                            >Active Campaigns</span
                        >
                        <span
                            class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm"
                            >🚀</span
                        >
                    </div>
                    <div class="flex items-baseline space-x-2">
                        <span class="text-3xl font-black text-gray-900">{{
                            stats.kpi.campaigns.active
                        }}</span>
                        <span class="text-xs text-gray-400 font-medium"
                            >/ {{ stats.kpi.campaigns.total }} total</span
                        >
                    </div>
                    <p
                        class="text-2xs text-blue-600 font-bold mt-3 flex items-center space-x-1"
                    >
                        <span>Kelola Kampanye Berjalan ➔</span>
                    </p>
                </div>

                <div
                    @click="navigateTo('/promotions')"
                    class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs hover:shadow-md hover:border-emerald-500/50 cursor-pointer transition group"
                >
                    <div class="flex items-center justify-between mb-3">
                        <span
                            class="text-xs font-extrabold uppercase tracking-wider text-gray-400 group-hover:text-emerald-600 transition"
                            >Active Promotions</span
                        >
                        <span
                            class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm"
                            >🏷️</span
                        >
                    </div>
                    <div class="flex items-baseline space-x-2">
                        <span class="text-3xl font-black text-gray-900">{{
                            stats.kpi.promotions.active
                        }}</span>
                        <span class="text-xs text-gray-400 font-medium"
                            >/ {{ stats.kpi.promotions.total }} total</span
                        >
                    </div>
                    <p
                        class="text-2xs text-emerald-600 font-bold mt-3 flex items-center space-x-1"
                    >
                        <span>Lihat Promosi Aktif ➔</span>
                    </p>
                </div>

                <div
                    @click="navigateTo('/promotions')"
                    class="bg-white rounded-2xl border border-amber-200/80 p-6 shadow-xs hover:shadow-md hover:border-amber-500 cursor-pointer transition group bg-gradient-to-br from-white to-amber-50/30"
                >
                    <div class="flex items-center justify-between mb-3">
                        <span
                            class="text-xs font-extrabold uppercase tracking-wider text-amber-600"
                            >Pending Approvals</span
                        >
                        <span
                            class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm animate-bounce"
                            >⏳</span
                        >
                    </div>
                    <div class="flex items-baseline space-x-2">
                        <span class="text-3xl font-black text-amber-900">{{
                            stats.kpi.promotions.pending
                        }}</span>
                        <span class="text-xs text-amber-600 font-medium"
                            >promosi menunggu</span
                        >
                    </div>
                    <p
                        class="text-2xs text-amber-700 font-bold mt-3 flex items-center space-x-1"
                    >
                        <span>Tinjau Respons Brand Sekarang ➔</span>
                    </p>
                </div>

                <div
                    @click="activeDeadlineTab = 'today'"
                    class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs hover:shadow-md hover:border-rose-500/50 cursor-pointer transition group"
                >
                    <div class="flex items-center justify-between mb-3">
                        <span
                            class="text-xs font-extrabold uppercase tracking-wider text-gray-400 group-hover:text-rose-600 transition"
                            >Deadlines Today</span
                        >
                        <span
                            class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm"
                            >⏰</span
                        >
                    </div>
                    <div class="flex items-baseline space-x-2">
                        <span
                            class="text-3xl font-black"
                            :class="
                                (stats.deadlines?.today?.length ?? 0) > 0
                                    ? 'text-rose-600'
                                    : 'text-gray-900'
                            "
                            >{{ stats.deadlines?.today?.length ?? 0 }}</span
                        >
                        <span class="text-xs text-gray-400 font-medium"
                            >item berakhir hari ini</span
                        >
                    </div>
                    <p
                        class="text-2xs text-rose-600 font-bold mt-3 flex items-center space-x-1"
                    >
                        <span>Periksa di Monitor Tenggat ➔</span>
                    </p>
                </div>
            </div>

            <!-- 2. SECONDARY CLICKABLE MINI KPI CARDS & EXTENSIBLE METRICS (Refinement #1 & #3) -->
            <div
                class="bg-gray-50/80 rounded-2xl p-6 border border-gray-200/80"
            >
                <div class="flex items-center justify-between mb-4">
                    <h3
                        class="text-xs font-extrabold uppercase tracking-wider text-gray-500"
                    >
                        📊 Rincian Agregasi Operasional & Kolaborasi Brand
                    </h3>
                    <span
                        class="text-2xs font-bold bg-gray-200/80 px-2.5 py-0.5 rounded-full text-gray-600"
                        >Extensible KPI Architecture</span
                    >
                </div>
                <div
                    class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4"
                >
                    <div
                        @click="navigateTo('/campaigns')"
                        class="bg-white p-3.5 rounded-xl border border-gray-200/80 hover:border-gray-400 cursor-pointer transition text-center"
                    >
                        <span
                            class="block text-xs font-bold uppercase text-gray-400"
                            >Completed Camp.</span
                        >
                        <span
                            class="text-lg font-black text-gray-800 mt-1 block"
                            >{{ stats.kpi?.campaigns?.completed ?? 0 }}</span
                        >
                    </div>
                    <div
                        @click="navigateTo('/promotions')"
                        class="bg-white p-3.5 rounded-xl border border-gray-200/80 hover:border-emerald-400 cursor-pointer transition text-center"
                    >
                        <span
                            class="block text-xs font-bold uppercase text-gray-400"
                            >Approved Promo</span
                        >
                        <span
                            class="text-lg font-black text-emerald-600 mt-1 block"
                            >{{ stats.kpi?.promotions?.approved ?? 0 }}</span
                        >
                    </div>
                    <div
                        @click="navigateTo('/promotions')"
                        class="bg-white p-3.5 rounded-xl border border-gray-200/80 hover:border-amber-400 cursor-pointer transition text-center"
                    >
                        <span
                            class="block text-xs font-bold uppercase text-gray-400"
                            >Partially Approved</span
                        >
                        <span
                            class="text-lg font-black text-amber-600 mt-1 block"
                            >{{
                                stats.kpi?.promotions?.partially_approved ?? 0
                            }}</span
                        >
                    </div>
                    <div
                        @click="navigateTo('/promotions')"
                        class="bg-white p-3.5 rounded-xl border border-gray-200/80 hover:border-rose-400 cursor-pointer transition text-center"
                    >
                        <span
                            class="block text-xs font-bold uppercase text-gray-400"
                            >Rejected Promo</span
                        >
                        <span
                            class="text-lg font-black text-rose-600 mt-1 block"
                            >{{ stats.kpi?.promotions?.rejected ?? 0 }}</span
                        >
                    </div>
                    <div
                        @click="navigateTo('/products')"
                        class="bg-white p-3.5 rounded-xl border border-gray-200/80 hover:border-blue-400 cursor-pointer transition text-center"
                    >
                        <span
                            class="block text-xs font-bold uppercase text-gray-400"
                            >Master Products</span
                        >
                        <span
                            class="text-lg font-black text-gray-800 mt-1 block"
                            >{{ stats.kpi?.catalog?.total_products ?? 0 }}</span
                        >
                    </div>
                    <div
                        @click="navigateTo('/products')"
                        class="bg-white p-3.5 rounded-xl border border-gray-200/80 hover:border-blue-400 cursor-pointer transition text-center"
                    >
                        <span
                            class="block text-xs font-bold uppercase text-gray-400"
                            >Master Variants</span
                        >
                        <span
                            class="text-lg font-black text-gray-800 mt-1 block"
                            >{{ stats.kpi?.catalog?.total_variants ?? 0 }}</span
                        >
                    </div>
                    <div
                        @click="navigateTo('/promotions')"
                        class="bg-white p-3.5 rounded-xl border border-gray-200/80 hover:border-indigo-400 cursor-pointer transition text-center"
                    >
                        <span
                            class="block text-xs font-bold uppercase text-gray-400"
                            >Secure Links</span
                        >
                        <span
                            class="text-lg font-black text-indigo-600 mt-1 block"
                            >{{
                                stats.kpi?.catalog?.total_secure_links ?? 0
                            }}</span
                        >
                    </div>
                    <div
                        class="bg-white p-3.5 rounded-xl border border-gray-200/80 text-center"
                    >
                        <span
                            class="block text-xs font-bold uppercase text-gray-400"
                            title="Brand Review Decisions"
                            >Brand Reviews</span
                        >
                        <span
                            class="text-lg font-black text-purple-600 mt-1 block"
                            >{{ stats.kpi?.catalog?.total_brand_reviews ?? 0 }}
                            <span class="text-3xs font-normal text-gray-400"
                                >({{ stats.kpi?.extended?.approval_rate ?? 0 }}%
                                rate)</span
                            ></span
                        >
                    </div>
                </div>
            </div>

            <!-- 3. WORKSPACE GRID: DEADLINE MONITORING & RECENT ACTIVITIES -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                <!-- LEFT 2 COLUMNS: DEADLINE MONITORING WIDGET (Refinement #4) -->
                <div
                    class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden lg:col-span-2"
                >
                    <div
                        class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gray-50/50"
                    >
                        <div>
                            <h3
                                class="text-lg font-bold text-gray-900 flex items-center space-x-2"
                            >
                                <span>⏰</span>
                                <span
                                    >Pusat Pemantauan Tenggat Waktu (Deadline
                                    Monitoring)</span
                                >
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Pantau kampanye dan tautan promosi yang
                                mendekati batas waktu atau mengalami
                                keterlambatan.
                            </p>
                        </div>
                        <!-- Auto Refresh Toggle (Refinement #2) -->
                        <div
                            class="flex items-center space-x-2 bg-white px-3 py-1.5 rounded-xl border border-gray-200 text-xs"
                        >
                            <input
                                type="checkbox"
                                id="autoRef"
                                v-model="autoRefreshActive"
                                @change="toggleAutoRefresh"
                                class="rounded text-blue-600 focus:ring-blue-500"
                            />
                            <label
                                for="autoRef"
                                class="font-bold text-gray-700 cursor-pointer select-none"
                                >⚡ Auto Refresh (60s)</label
                            >
                        </div>
                    </div>

                    <!-- Interactive Tabs -->
                    <div
                        class="border-b border-gray-200 px-6 bg-white overflow-x-auto"
                    >
                        <nav class="flex space-x-6 -mb-px text-xs font-bold">
                            <button
                                v-for="tab in deadlineTabs"
                                :key="tab.id"
                                @click="activeDeadlineTab = tab.id"
                                :class="[
                                    activeDeadlineTab === tab.id
                                        ? 'border-blue-600 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                    'py-3.5 border-b-2 whitespace-nowrap transition flex items-center space-x-1.5',
                                ]"
                            >
                                <span>{{ tab.name }}</span>
                                <span
                                    class="px-2 py-0.5 rounded-full text-2xs"
                                    :class="
                                        activeDeadlineTab === tab.id
                                            ? 'bg-blue-100 text-blue-800'
                                            : 'bg-gray-100 text-gray-600'
                                    "
                                >
                                    {{ getDeadlineCount(tab.id) }}
                                </span>
                            </button>
                        </nav>
                    </div>

                    <!-- Tab List Items with Visual Indicators & Shortcuts (Refinement #4) -->
                    <div
                        class="p-6 divide-y divide-gray-100 min-h-[340px] max-h-[480px] overflow-y-auto"
                    >
                        <div
                            v-for="(item, index) in currentDeadlineItems"
                            :key="`${item?.type ?? 'item'}-${item?.id ?? index}`"
                            class="py-4 first:pt-0 last:pb-0 flex items-center justify-between gap-4 group hover:bg-gray-50/60 px-2 -mx-2 rounded-xl transition"
                        >
                            <div class="flex items-start space-x-3.5">
                                <!-- Visual Status Indicator Badge (Refinement #4) -->
                                <span
                                    class="mt-1 w-3 h-3 rounded-full flex-shrink-0 shadow-2xs"
                                    :class="getIndicatorClass(item.status_code)"
                                    :title="
                                        item.status_code === 'red'
                                            ? 'Terlambat (Overdue)'
                                            : item.status_code === 'yellow'
                                              ? 'Mendekati Deadline / Hari Ini'
                                              : 'Aman'
                                    "
                                ></span>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span
                                            class="text-2xs font-extrabold uppercase px-2 py-0.5 rounded border"
                                            :class="
                                                String(
                                                    item?.type || '',
                                                ).includes('Secure Link')
                                                    ? 'bg-indigo-50 border-indigo-100 text-indigo-700'
                                                    : item?.type === 'Campaign'
                                                      ? 'bg-blue-50 border-blue-100 text-blue-700'
                                                      : 'bg-emerald-50 border-emerald-100 text-emerald-700'
                                            "
                                        >
                                            {{ item?.type || "Unknown" }}
                                        </span>
                                        <span
                                            class="text-xs font-bold text-gray-800"
                                            >{{ item?.status || "-" }}</span
                                        >
                                    </div>
                                    <h4
                                        class="text-sm font-extrabold text-gray-900 mt-1"
                                    >
                                        {{ item?.title || "-" }}
                                    </h4>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ item?.subtitle || "-" }} •
                                        <span
                                            class="font-mono text-gray-700 font-semibold"
                                            >Deadline:
                                            {{ item?.deadline || "-" }}</span
                                        >
                                    </p>
                                </div>
                            </div>

                            <!-- Shortcut Button (Refinement #4) -->
                            <button
                                @click="navigateTo(item.url)"
                                class="px-3.5 py-1.5 bg-gray-100 hover:bg-gray-900 hover:text-white text-gray-700 font-bold text-xs rounded-lg transition whitespace-nowrap flex items-center space-x-1 shadow-2xs"
                            >
                                <span>Buka Detail</span>
                                <span>↗</span>
                            </button>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-if="currentDeadlineItems.length === 0"
                            class="py-12 text-center text-gray-400 text-sm"
                        >
                            <span class="text-2xl block mb-2">🎉</span>
                            Tidak ada item pada kategori tenggat waktu ini.
                            Seluruh operasional berjalan aman!
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: RECENT ACTIVITIES FEED (Refinement #5) -->
                <div
                    class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden flex flex-col"
                >
                    <div
                        class="p-6 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between"
                    >
                        <div>
                            <h3
                                class="text-lg font-bold text-gray-900 flex items-center space-x-2"
                            >
                                <span>📜</span>
                                <span>Aktivitas Terbaru</span>
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Jejak audit real-time Admin & Brand.
                            </p>
                        </div>
                        <span
                            class="px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 font-extrabold text-xs"
                            >Live Feed</span
                        >
                    </div>

                    <!-- Activity List -->
                    <div
                        class="p-5 divide-y divide-gray-100 max-h-[530px] overflow-y-auto space-y-3.5"
                    >
                        <div
                            v-for="log in stats.recent_activities || []"
                            :key="log.id"
                            class="pt-3.5 first:pt-0 text-xs"
                        >
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center space-x-1.5">
                                    <span
                                        class="px-1.5 py-0.5 rounded text-3xs font-extrabold uppercase text-white"
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
                                    <span class="font-bold text-gray-800">{{
                                        log.actor_name
                                    }}</span>
                                </div>
                                <span
                                    class="text-3xs text-gray-400 font-mono"
                                    >{{ log.time_ago }}</span
                                >
                            </div>
                            <p class="text-gray-700 font-medium leading-normal">
                                {{ log.description }}
                            </p>
                            <div
                                class="mt-1 flex items-center space-x-2 text-3xs text-gray-400"
                            >
                                <span>⚡ {{ log.action }}</span>
                                <span v-if="log.target_type"
                                    >● Target: {{ log.target_type }} #{{
                                        log.target_id?.slice(0, 8)
                                    }}</span
                                >
                            </div>
                        </div>

                        <div
                            v-if="(stats.recent_activities?.length ?? 0) === 0"
                            class="py-12 text-center text-gray-400 text-sm"
                        >
                            Belum ada aktivitas tercatat pada sistem.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
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

const deadlineTabs = [
    { id: "today", name: "Hari Ini" },
    { id: "tomorrow", name: "Besok" },
    { id: "next_7_days", name: "7 Hari ke Depan" },
    { id: "overdue", name: "⚠️ Terlambat (Overdue)" },
    { id: "expiring_links", name: "🔗 Tautan Akan Habis" },
];

onMounted(async () => {
    await fetchStats();
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
