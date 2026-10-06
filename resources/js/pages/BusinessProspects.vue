<template>
    <div class="space-y-5">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700"><i class="fa-solid fa-handshake"></i></div>
                <div><h1 class="text-2xl font-black text-gray-900">Prospek Business Development</h1><p class="mt-1 text-sm text-gray-500">Kelola riset toko, tindak lanjut, dan konversi menjadi client.</p></div>
            </div>
            <div class="flex gap-2">
                <button type="button" class="icon-btn template-trigger" title="Template pesan" aria-label="Buka template pesan" @click="openMessageTemplates"><i class="fa-regular fa-message"></i></button>
                <button v-if="$can('prospect.create')" class="btn-secondary" @click="showImport = true"><i class="fa-solid fa-file-import"></i> Import Excel</button>
                <button v-if="$can('prospect.create')" class="btn-primary" @click="openCreate"><i class="fa-solid fa-plus"></i> Tambah Prospek</button>
            </div>
        </header>

        <section class="overflow-hidden rounded-3xl border border-default bg-surface shadow-sm">
            <header class="flex flex-col gap-3 border-b border-default px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-soft text-brand-strong">
                        <i class="fa-solid fa-sliders"></i>
                    </span>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-black text-content">Filter Prospek</h2>
                            <span v-if="activeFilterCount" class="rounded-full bg-[#4274D9] px-2 py-0.5 text-[10px] font-black text-white">{{ activeFilterCount }} aktif</span>
                        </div>
                        <p class="mt-0.5 text-xs text-content-muted">Temukan prospek berdasarkan status, konversi, dan tanggal analisa.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-auto">
                    <button v-if="hasActiveFilters" type="button" class="inline-flex items-center gap-2 rounded-xl border border-default bg-surface-muted px-3 py-2 text-xs font-bold text-content-soft transition hover:border-[#4274D9]/40 hover:text-brand" @click="resetAllFilters">
                        <i class="fa-solid fa-rotate-left text-[11px]"></i> Reset Filter
                    </button>
                    <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-default bg-surface-muted px-3 py-2 text-xs font-bold transition disabled:opacity-50" :class="selectedProspects.length ? 'bg-[#4274D9] text-white hover:bg-blue-600' : 'text-content-soft hover:border-[#4274D9]/40 hover:text-brand'" :disabled="!selectedProspects.length" @click="openBulkTemperatureModal">
                        <i class="fa-solid fa-layer-group text-[11px]"></i> Ubah Kategori {{ selectedProspects.length > 0 ? `(${selectedProspects.length})` : '' }}
                    </button>
                </div>
            </header>

            <div class="grid gap-4 p-4 sm:p-5 lg:grid-cols-12">
                <label class="filter-label lg:col-span-5">
                    <span>Pencarian</span>
                    <span class="relative mt-1.5 block">
                        <i class="search-icon fa-solid fa-magnifying-glass pointer-events-none absolute left-0 flex items-center pl-4 text-xs text-content-muted"></i>
                        <input v-model="filters.search" class="filter-control search-input" placeholder="Nama toko, kota, atau link marketplace" @input="debouncedLoad" />
                        <button v-if="filters.search" type="button" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-content-muted transition hover:text-content" title="Hapus pencarian" @click="clearSearch"><i class="fa-solid fa-xmark"></i></button>
                    </span>
                </label>

                <label class="filter-label lg:col-span-3">
                    <span>Status Prospek</span>
                    <select v-model="filters.status" class="filter-control mt-1.5" @change="resetAndLoad"><option value="">Semua status</option><option v-for="item in options.statuses" :key="item.value" :value="item.value">{{ item.label }}</option></select>
                </label>

                <label class="filter-label" :class="$can('prospect.restore') ? 'lg:col-span-2' : 'lg:col-span-4'">
                    <span>Status Konversi</span>
                    <select v-model="filters.conversion_status" class="filter-control mt-1.5" @change="resetAndLoad"><option value="">Semua konversi</option><option v-for="item in options.conversion_statuses" :key="item.value" :value="item.value">{{ item.label }}</option></select>
                </label>

                <label v-if="$can('prospect.restore')" class="filter-label lg:col-span-2">
                    <span>Data Prospek</span>
                    <select v-model="filters.trashed" class="filter-control mt-1.5" @change="resetAndLoad"><option value="">Prospek aktif</option><option value="only">Arsip terhapus</option></select>
                </label>

                <label class="filter-label lg:col-span-4">
                    <span>Kategori Toko</span>
                    <input v-model="filters.category" class="filter-control mt-1.5" placeholder="Cari kategori toko" @input="debouncedLoad" />
                </label>

                <label class="filter-label lg:col-span-4">
                    <span>Kategori Prospek</span>
                    <select v-model="filters.lead_temperature" class="filter-control mt-1.5" @change="resetAndLoad">
                        <option value="">Semua kategori</option>
                        <option value="Cold">Cold</option>
                        <option value="Warm">Warm</option>
                        <option value="Hot">Hot</option>
                    </select>
                </label>

                <label class="filter-label lg:col-span-4">
                    <span>Urutkan berdasarkan</span>
                    <select v-model="filters.sort_by" class="filter-control mt-1.5" @change="resetAndLoad">
                        <option value="name">Nama</option>
                        <option value="analysis_summary">Hasil analisa</option>
                        <option value="last_contact_at">Last contact (terbaru)</option>
                    </select>
                </label>

                <div class="rounded-2xl border border-default bg-surface-muted p-3.5 lg:col-span-12">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="mb-2 flex items-center gap-2">
                                <i class="fa-solid fa-calendar-days text-sm text-brand"></i>
                                <p class="text-xs font-black text-content">Tanggal Analisa</p>
                                <span class="text-[11px] text-content-muted">Pilih satu jenis periode</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                                <button v-for="type in dateFilterTypes" :key="type.value" type="button" class="period-button" :class="dateFilterType === type.value ? 'period-button-active' : ''" @click="selectDateFilterType(type.value)">
                                    <i :class="type.icon"></i><span>{{ type.label }}</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="dateFilterType" class="flex w-full flex-col gap-2 sm:flex-row xl:w-auto xl:min-w-[360px]">
                            <input v-if="dateFilterType === 'date'" v-model="filters.analysis_date" type="date" class="filter-control" aria-label="Tanggal analisa" @change="resetAndLoad" />
                            <input v-if="dateFilterType === 'month'" v-model="filters.analysis_month" type="month" class="filter-control" aria-label="Bulan analisa" @change="resetAndLoad" />
                            <select v-if="dateFilterType === 'year'" v-model="filters.analysis_year" class="filter-control" aria-label="Tahun analisa" @change="resetAndLoad"><option value="">Pilih tahun</option><option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option></select>
                            <button type="button" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-default bg-surface px-3 py-2.5 text-xs font-bold text-content-soft transition hover:border-red-300 hover:text-red-500" @click="clearDateFilter"><i class="fa-solid fa-xmark"></i><span>Hapus</span></button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="w-10 p-4 text-center"><input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-[#4274D9]" :checked="selectedProspects.length === prospects.length && prospects.length > 0" @change="toggleSelectAll" /></th><th class="p-4">Toko</th><th class="p-4">Marketplace</th><th class="p-4">PIC</th><th class="p-4">Status</th><th class="p-4">Last Contact</th><th class="p-4">Konversi</th><th class="p-4 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="loading"><td colspan="8" class="p-10 text-center text-gray-500">Memuat data...</td></tr>
                        <tr v-else-if="!prospects.length"><td colspan="8" class="p-10 text-center text-gray-500">Belum ada data prospek.</td></tr>
                        <tr v-for="row in prospects" :key="row.id" class="hover:bg-slate-50/70" :class="selectedProspects.includes(row.id) ? 'bg-blue-50/50' : ''">
                            <td class="p-4 text-center"><input type="checkbox" v-model="selectedProspects" :value="row.id" class="h-4 w-4 rounded border-gray-300 text-[#4274D9]" /></td>
                            <td class="p-4"><div class="flex items-start gap-2"><p class="font-bold text-gray-900">{{ row.name }}</p><span v-if="row.lead_temperature" class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white" :class="{'bg-blue-500': row.lead_temperature==='Cold', 'bg-amber-500': row.lead_temperature==='Warm', 'bg-rose-500': row.lead_temperature==='Hot'}">{{ row.lead_temperature }}</span></div><p class="text-xs text-gray-500">{{ [row.category, row.city].filter(Boolean).join(' · ') || 'Belum dilengkapi' }}</p></td>
                            <td class="p-4"><div class="flex flex-wrap gap-1.5"><a v-for="link in row.marketplace_links" :key="link.id" :href="link.url" target="_blank" class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700 hover:bg-blue-100">{{ link.marketplace }} <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i></a></div></td>
                            <td class="p-4 text-sm text-gray-700">{{ row.pic?.name || '-' }}</td>
                            <td class="p-4"><span class="badge">{{ row.status_label }}</span></td>
                            <td class="p-4 text-sm text-gray-600">{{ formatDate(row.last_contact_at) }}</td>
                            <td class="p-4"><span class="badge" :class="row.conversion_status === 'pending' ? '!bg-amber-50 !text-amber-700' : ''">{{ row.conversion_status_label }}</span></td>
                            <td class="p-4"><div class="flex justify-end gap-1.5">
                                <button class="icon-btn text-indigo-600" title="Lihat detail" @click="openDetail(row)"><i class="fa-solid fa-eye"></i></button>
                                <button v-if="!row.deleted_at && $can('prospect.update')" class="icon-btn text-blue-600" title="Edit" @click="openEdit(row)"><i class="fa-solid fa-pen"></i></button>
                                <button v-if="!row.deleted_at && row.status === 'won' && ['none','rejected'].includes(row.conversion_status) && $can('prospect.request-conversion')" class="icon-btn text-amber-600" title="Ajukan konversi" @click="askConversion(row)"><i class="fa-solid fa-paper-plane"></i></button>
                                <button v-if="!row.deleted_at && row.conversion_status === 'pending' && $can('prospect.approve-conversion')" class="icon-btn text-emerald-600" title="Setujui konversi" @click="openApproval(row)"><i class="fa-solid fa-circle-check"></i></button>
                                <button v-if="!row.deleted_at && row.conversion_status === 'pending' && $can('prospect.approve-conversion')" class="icon-btn text-rose-600" title="Tolak konversi" @click="reject(row)"><i class="fa-solid fa-circle-xmark"></i></button>
                                <button v-if="!row.deleted_at && $can('prospect.delete')" class="icon-btn text-rose-600" title="Hapus" @click="remove(row)"><i class="fa-solid fa-trash"></i></button>
                                <button v-if="row.deleted_at && $can('prospect.restore')" class="icon-btn text-emerald-600" title="Pulihkan" @click="restore(row)"><i class="fa-solid fa-rotate-left"></i></button>
                            </div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <footer class="flex flex-col gap-3 border-t border-gray-100 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2 text-sm text-gray-500"><span>Tampilkan</span><select v-model.number="pagination.per_page" class="rounded-lg border border-gray-200 px-2 py-1.5" @change="resetAndLoad"><option v-for="size in [10,25,50,100]" :key="size" :value="size">{{ size }}</option></select><span>dari {{ pagination.total }} data</span></div>
                <div class="flex items-center gap-2"><button class="pager" :disabled="pagination.current_page <= 1" @click="changePage(-1)"><i class="fa-solid fa-chevron-left"></i></button><span class="text-sm font-bold text-gray-700">{{ pagination.current_page }} / {{ pagination.last_page }}</span><button class="pager" :disabled="pagination.current_page >= pagination.last_page" @click="changePage(1)"><i class="fa-solid fa-chevron-right"></i></button></div>
            </footer>
        </section>

        <div v-if="showMessageTemplates" class="modal-wrap"><div class="modal-backdrop" @click="closeMessageTemplates"></div><section class="modal-card theme-modal max-w-3xl" role="dialog" aria-modal="true" aria-labelledby="message-templates-title">
            <div class="modal-header"><div><p class="text-xs font-black uppercase tracking-widest text-brand">Business Development</p><h2 id="message-templates-title" class="text-xl font-black text-content">Template Pesan</h2></div><button type="button" class="icon-btn" title="Tutup" @click="closeMessageTemplates"><i class="fa-solid fa-xmark"></i></button></div>
            <div class="max-h-[68vh] space-y-4 overflow-y-auto p-5">
                <p v-if="templateNotice" class="rounded-xl border border-default bg-surface-muted px-3 py-2 text-sm font-semibold text-content-soft" role="status">{{ templateNotice }}</p>
                <form class="rounded-xl border border-gray-200 bg-gray-50 p-4" @submit.prevent="saveMessageTemplate">
                    <h3 class="mb-3 text-sm font-black text-content">{{ editingTemplateId ? 'Ubah template' : 'Tambah template' }}</h3>
                    <label class="label">Nama template<input v-model="templateForm.name" required maxlength="80" class="field" placeholder="Contoh: Perkenalan awal" /></label>
                    <label class="label mt-3">Isi pesan<textarea v-model="templateForm.content" required rows="5" class="field" placeholder="Tulis pesan yang ingin digunakan..."></textarea></label>
                    <div class="mt-3 flex justify-end gap-2"><button v-if="editingTemplateId" type="button" class="btn-secondary" @click="resetTemplateForm">Batal edit</button><button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i>{{ editingTemplateId ? 'Simpan perubahan' : 'Tambah template' }}</button></div>
                </form>
                <div v-if="messageTemplates.length" class="divide-y divide-gray-200 rounded-xl border border-gray-200">
                    <article v-for="template in messageTemplates" :key="template.id" class="p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3"><h3 class="font-bold text-gray-900">{{ template.name }}</h3><div class="flex gap-1.5"><button type="button" class="icon-btn text-emerald-700" :title="copiedTemplateId === template.id ? 'Tersalin' : 'Salin pesan'" :aria-label="copiedTemplateId === template.id ? 'Pesan tersalin' : 'Salin pesan'" @click="copyMessageTemplate(template)"><i :class="copiedTemplateId === template.id ? 'fa-solid fa-check' : 'fa-regular fa-copy'"></i></button><button type="button" class="icon-btn text-blue-600" title="Edit template" @click="editMessageTemplate(template)"><i class="fa-solid fa-pen"></i></button><button type="button" class="icon-btn text-rose-600" title="Hapus template" @click="deleteMessageTemplate(template.id)"><i class="fa-solid fa-trash"></i></button></div></div>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{{ template.content }}</p>
                    </article>
                </div>
                <p v-else class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">Belum ada template pesan. Tambahkan template melalui formulir di atas.</p>
            </div>
        </section></div>

        <div v-if="detailProspect" class="modal-wrap"><div class="modal-backdrop" @click="detailProspect=null"></div><section class="modal-card max-w-4xl" aria-modal="true" role="dialog" aria-label="Detail prospek">
            <div class="modal-header bg-gradient-to-r from-indigo-50 via-white to-sky-50"><div class="flex min-w-0 items-center gap-3"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white"><i class="fa-solid fa-store"></i></span><div class="min-w-0"><p class="text-xs font-black uppercase tracking-widest text-indigo-600">Detail Prospek</p><h2 class="truncate text-xl font-black text-gray-900">{{ detailProspect.name }}</h2></div></div><button type="button" class="icon-btn shrink-0" title="Tutup" @click="detailProspect=null"><i class="fa-solid fa-xmark"></i></button></div>
            <div class="max-h-[70vh] overflow-y-auto p-5 sm:p-6">
                <div class="flex flex-wrap gap-2 border-b border-slate-100 pb-5"><span class="badge">{{ detailProspect.status_label }}</span><span class="badge" :class="detailProspect.conversion_status === 'pending' ? '!bg-amber-50 !text-amber-700' : ''">Konversi: {{ detailProspect.conversion_status_label }}</span><span v-if="detailProspect.deleted_at" class="badge !bg-rose-50 !text-rose-700">Diarsipkan</span></div>

                <div v-if="['not_interested', 'not_qualified'].includes(detailProspect.status)" class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4"><p class="text-xs font-black uppercase tracking-widest text-rose-600">Alasan tidak dilanjutkan</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-rose-950">{{ detailProspect.lost_reason || 'Alasan belum diisi.' }}</p></div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="detail-card"><span>PIC</span><strong>{{ detailProspect.pic?.name || '-' }}</strong></div><div class="detail-card"><span>Tanggal Analisa</span><strong>{{ formatDate(detailProspect.analyzed_at) }}</strong></div><div class="detail-card"><span>Terakhir Dihubungi</span><strong>{{ formatDate(detailProspect.last_contact_at) }}</strong></div>
                    <div class="detail-card"><span>Follow-up Berikutnya</span><strong>{{ formatDate(detailProspect.next_follow_up_at) }}</strong></div><div class="detail-card"><span>Kategori</span><strong>{{ detailProspect.category || '-' }}</strong></div><div class="detail-card"><span>Kota</span><strong>{{ detailProspect.city || '-' }}</strong></div>
                    <div class="detail-card"><span>Nomor Kontak</span><strong>{{ detailProspect.phone || '-' }}</strong></div><div class="detail-card"><span>Dibuat Oleh</span><strong>{{ detailProspect.creator?.name || '-' }}</strong></div><div class="detail-card"><span>Terakhir Diperbarui</span><strong>{{ formatDate(detailProspect.updated_at) }}</strong></div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2"><article class="detail-section"><h3>Hasil Analisa</h3><p class="whitespace-pre-line">{{ detailProspect.analysis_summary || 'Belum ada hasil analisa.' }}</p><a v-if="detailProspect.analysis_link" :href="detailProspect.analysis_link" target="_blank" rel="noopener" class="detail-link"><i class="fa-solid fa-arrow-up-right-from-square"></i> Buka link analisa</a></article><article class="detail-section"><h3>Potensi / Alasan Prospek</h3><p class="whitespace-pre-line">{{ detailProspect.potential_reason || 'Belum ada keterangan potensi.' }}</p></article><article class="detail-section lg:col-span-2"><h3>Catatan</h3><p class="whitespace-pre-line">{{ detailProspect.notes || 'Tidak ada catatan.' }}</p></article></div>

                <article class="detail-section mt-4"><h3>Link Marketplace</h3><div v-if="detailProspect.marketplace_links?.length" class="mt-3 flex flex-wrap gap-2"><a v-for="link in detailProspect.marketplace_links" :key="link.id" :href="link.url" target="_blank" rel="noopener" class="detail-link"><i class="fa-solid fa-shop"></i> {{ link.marketplace }}</a></div><p v-else>Tidak ada link marketplace.</p></article>

                <article v-if="detailProspect.instagram_url || detailProspect.tiktok_url || detailProspect.facebook_or_website_url" class="detail-section mt-4"><h3>Link Sosial / Website</h3><div class="mt-3 flex flex-wrap gap-2"><a v-if="detailProspect.instagram_url" :href="detailProspect.instagram_url" target="_blank" rel="noopener" class="detail-link"><i class="fa-brands fa-instagram"></i> Instagram</a><a v-if="detailProspect.tiktok_url" :href="detailProspect.tiktok_url" target="_blank" rel="noopener" class="detail-link"><i class="fa-brands fa-tiktok"></i> TikTok</a><a v-if="detailProspect.facebook_or_website_url" :href="detailProspect.facebook_or_website_url" target="_blank" rel="noopener" class="detail-link"><i class="fa-solid fa-globe"></i> Website / Facebook</a></div></article>
            </div>
            <div class="modal-footer"><button type="button" class="btn-secondary" @click="detailProspect=null">Tutup</button></div>
        </section></div>

        <div v-if="showForm" class="modal-wrap"><div class="modal-backdrop" @click="showForm=false"></div><form class="modal-card theme-modal max-w-4xl" @submit.prevent="save">
            <div class="modal-header"><div><p class="text-xs font-black uppercase tracking-widest text-brand">Data Prospek</p><h2 class="text-xl font-black text-content">{{ editingId ? 'Ubah Prospek' : 'Tambah Prospek' }}</h2></div><button type="button" class="icon-btn" @click="showForm=false"><i class="fa-solid fa-xmark"></i></button></div>
            <div class="grid max-h-[68vh] gap-4 overflow-y-auto p-6 md:grid-cols-2">
                <label class="label">Nama Toko *<input v-model="form.name" required class="field" /></label><label class="label">Kategori<input v-model="form.category" class="field" /></label>
                <label class="label">Kota<input v-model="form.city" class="field" /></label><label class="label">Status<select v-model="form.status" class="field"><option v-for="item in options.statuses" :key="item.value" :value="item.value">{{ item.label }}</option></select></label>
                <label v-if="$can('prospect.assign')" class="label">PIC<select v-model="form.pic_id" class="field"><option v-for="pic in options.pics" :key="pic.id" :value="pic.id">{{ pic.name }}</option></select></label><label v-else class="label">PIC<input :value="authStore.user?.name || '-'" class="field bg-slate-50 text-slate-500" disabled /></label><label class="label">Tanggal Analisa<input v-model="form.analyzed_at" type="date" class="field" /></label>
                <label class="label md:col-span-2">Hasil Analisa<textarea v-model="form.analysis_summary" rows="3" class="field"></textarea></label><label class="label md:col-span-2">Potensi / Alasan Prospek<textarea v-model="form.potential_reason" rows="3" class="field"></textarea></label>
                <label class="label md:col-span-2">Link Hasil Analisa<input v-model="form.analysis_link" type="url" class="field" placeholder="https://..." /></label>
                <label class="label">Nomor WhatsApp / Telepon<input v-model="form.phone" class="field" /></label><label class="label">Terakhir Dihubungi<input v-model="form.last_contact_at" type="datetime-local" class="field" /></label><label class="label">Follow-up Berikutnya<input v-model="form.next_follow_up_at" type="datetime-local" class="field" /></label>
                <label class="label">Instagram URL<input v-model="form.instagram_url" type="url" class="field" /></label><label class="label">TikTok URL<input v-model="form.tiktok_url" type="url" class="field" /></label>
                <label class="label md:col-span-2">Facebook / Website URL<input v-model="form.facebook_or_website_url" type="url" class="field" /></label>
                <div class="md:col-span-2"><div class="mb-2 flex items-center justify-between"><span class="label">Link Marketplace *</span><button type="button" class="text-xs font-bold text-indigo-600" @click="form.marketplace_links.push({marketplace:'Shopee',url:''})">+ Tambah Link</button></div><div v-for="(link,index) in form.marketplace_links" :key="index" class="mb-2 grid grid-cols-[150px_1fr_40px] gap-2"><input v-model="link.marketplace" required class="field" placeholder="Marketplace"/><input v-model="link.url" required type="url" class="field" placeholder="https://..."/><button type="button" class="icon-btn text-rose-600" :disabled="form.marketplace_links.length===1" @click="form.marketplace_links.splice(index,1)"><i class="fa-solid fa-trash"></i></button></div></div>
                <label class="label md:col-span-2">Catatan<textarea v-model="form.notes" rows="3" class="field"></textarea></label><label v-if="['not_interested','not_qualified'].includes(form.status)" class="label md:col-span-2">Alasan tidak dilanjutkan *<textarea v-model="form.lost_reason" required rows="3" class="field"></textarea></label>
            </div><div class="modal-footer"><button type="button" class="btn-secondary" @click="showForm=false">Batal</button><button class="btn-primary" :disabled="saving">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button></div>
        </form></div>

        <div v-if="showImport" class="modal-wrap"><div class="modal-backdrop" @click="closeImport"></div><div class="modal-card max-w-3xl"><div class="modal-header"><div><h2 class="text-xl font-black">Import Prospek</h2><p class="text-sm text-gray-500">Gunakan file XLSX atau CSV. Sistem mencari baris header yang memuat Nama Toko.</p></div><button class="icon-btn" @click="closeImport"><i class="fa-solid fa-xmark"></i></button></div><div class="max-h-[65vh] overflow-auto p-6"><input type="file" accept=".xlsx,.csv" class="field" @change="inspectFile"/><div v-if="importPreview" class="mt-4"><p class="font-bold">{{ importPreview.total_rows }} baris ditemukan</p><ul v-if="importPreview.errors.length" class="mt-2 rounded-xl bg-rose-50 p-3 text-sm text-rose-700"><li v-for="error in importPreview.errors" :key="error">{{ error }}</li></ul><div class="mt-3 max-h-72 overflow-auto rounded-xl border"><table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="p-2 text-left">Nama Toko</th><th class="p-2 text-left">Status</th><th class="p-2 text-left">Hasil</th></tr></thead><tbody><tr v-for="row in importPreview.rows" :key="row.row_number" class="border-t"><td class="p-2">{{ row.name }}</td><td class="p-2">{{ row.status }}</td><td class="p-2">{{ row.existing_trashed ? 'Terhapus—pulihkan dahulu' : row.existing_id ? 'Gabungkan link baru' : 'Data baru' }}</td></tr></tbody></table></div></div></div><div class="modal-footer"><button class="btn-secondary" @click="closeImport">Batal</button><button class="btn-primary" :disabled="!importPreview?.valid || importing" @click="runImport">Konfirmasi Import</button></div></div></div>

        <div v-if="approvalProspect" class="modal-wrap"><div class="modal-backdrop" @click="approvalProspect=null"></div><form class="modal-card max-w-lg" @submit.prevent="approve"><div class="modal-header"><h2 class="text-xl font-black">Setujui Konversi</h2><button type="button" class="icon-btn" @click="approvalProspect=null"><i class="fa-solid fa-xmark"></i></button></div><div class="space-y-4 p-6"><label class="label">Company yang sudah ada<select v-model="approval.company_id" class="field"><option value="">Buat company baru</option><option v-for="company in options.companies" :key="company.id" :value="company.id">{{ company.name }}</option></select></label><label v-if="!approval.company_id" class="label">Nama Company<input v-model="approval.company_name" class="field" :placeholder="approvalProspect.name" /></label><label class="label">Brand yang sudah ada<select v-model="approval.brand_id" class="field"><option value="">Buat brand baru</option><option v-for="brand in options.brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option></select></label><label v-if="!approval.brand_id" class="label">Nama Brand<input v-model="approval.brand_name" required class="field" :placeholder="approvalProspect.name" /></label></div><div class="modal-footer"><button type="button" class="btn-secondary" @click="approvalProspect=null">Batal</button><button class="btn-primary">Setujui</button></div></form></div>

        <div v-if="showBulkTemperatureModal" class="modal-wrap">
            <div class="modal-backdrop" @click="closeBulkTemperatureModal"></div>
            <div class="modal-card theme-modal max-w-md" role="dialog" aria-modal="true" aria-labelledby="bulk-temperature-title">
                <div class="modal-header">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest text-brand">Business Development</p>
                        <h2 id="bulk-temperature-title" class="text-xl font-black text-content">Ubah Kategori Toko</h2>
                    </div>
                    <button type="button" class="icon-btn" title="Tutup" @click="closeBulkTemperatureModal"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="p-5">
                    <form @submit.prevent="saveBulkTemperature">
                        <p class="mb-4 text-sm text-content-soft">
                            Anda akan mengubah kategori (suhu) untuk <strong>{{ selectedProspects.length }}</strong> toko yang dipilih.
                        </p>
                        <label class="label">Kategori Baru
                            <select v-model="bulkTemperatureForm.lead_temperature" class="field mt-1.5">
                                <option value="">Tidak ada kategori (Hapus kategori)</option>
                                <option value="Cold">Cold</option>
                                <option value="Warm">Warm</option>
                                <option value="Hot">Hot</option>
                            </select>
                        </label>
                        <div class="mt-6 flex justify-end gap-2">
                            <button type="button" class="btn-secondary" @click="closeBulkTemperatureModal">Batal</button>
                            <button type="submit" class="btn-primary" :disabled="savingBulkTemperature">
                                <i class="fa-solid fa-floppy-disk"></i> {{ savingBulkTemperature ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useBusinessProspects } from '../composables/useBusinessProspects';
import { useAuthStore } from '../stores/auth';
import api from '../utils/api';

const { prospects, options, loading, pagination, fetchProspects, fetchOptions, createProspect, updateProspect, deleteProspect, restoreProspect, requestConversion, approveConversion, rejectConversion, previewImport, confirmImport } = useBusinessProspects();
const authStore = useAuthStore();
const messageTemplateStorageKey = 'suntrack-business-prospect-message-templates';
const defaultMessageTemplates = [
    { id: 'intro', name: 'Perkenalan awal', content: 'Halo, Kak! Saya [Nama] dari Suntrack. Kami tertarik mengenal bisnis [Nama Toko] lebih jauh dan ingin berbagi informasi tentang layanan kami. Apakah berkenan jika kita berdiskusi sebentar?' },
    { id: 'follow-up', name: 'Tindak lanjut', content: 'Halo, Kak! Saya ingin menindaklanjuti pesan sebelumnya terkait [Topik]. Apakah ada waktu yang nyaman untuk berdiskusi? Terima kasih.' },
];
const readMessageTemplates = () => {
    try {
        const saved = JSON.parse(localStorage.getItem(messageTemplateStorageKey) || 'null');
        return Array.isArray(saved) ? saved : defaultMessageTemplates.map((template) => ({ ...template }));
    } catch {
        return defaultMessageTemplates.map((template) => ({ ...template }));
    }
};
const messageTemplates = ref(readMessageTemplates());
const showMessageTemplates = ref(false);
const templateForm = reactive({ name: '', content: '' });
const editingTemplateId = ref(null);
const copiedTemplateId = ref(null);
const templateNotice = ref('');
let templateNoticeTimer;
const persistMessageTemplates = () => localStorage.setItem(messageTemplateStorageKey, JSON.stringify(messageTemplates.value));
const openMessageTemplates = () => { showMessageTemplates.value = true; templateNotice.value = ''; };
const closeMessageTemplates = () => { showMessageTemplates.value = false; resetTemplateForm(); };
const resetTemplateForm = () => { editingTemplateId.value = null; templateForm.name = ''; templateForm.content = ''; };
const saveMessageTemplate = () => {
    const values = { name: templateForm.name.trim(), content: templateForm.content.trim() };
    if (!values.name || !values.content) return;
    if (editingTemplateId.value) {
        const template = messageTemplates.value.find((item) => item.id === editingTemplateId.value);
        if (template) Object.assign(template, values);
    } else {
        messageTemplates.value.unshift({ id: crypto.randomUUID(), ...values });
    }
    persistMessageTemplates();
    resetTemplateForm();
    templateNotice.value = 'Template berhasil disimpan.';
};
const editMessageTemplate = (template) => {
    editingTemplateId.value = template.id;
    templateForm.name = template.name;
    templateForm.content = template.content;
    templateNotice.value = '';
};
const deleteMessageTemplate = (id) => {
    messageTemplates.value = messageTemplates.value.filter((template) => template.id !== id);
    persistMessageTemplates();
    if (editingTemplateId.value === id) resetTemplateForm();
    templateNotice.value = 'Template berhasil dihapus.';
};
const copyMessageTemplate = async (template) => {
    try {
        await navigator.clipboard.writeText(template.content);
        copiedTemplateId.value = template.id;
        templateNotice.value = `Pesan "${template.name}" berhasil disalin.`;
        clearTimeout(templateNoticeTimer);
        templateNoticeTimer = setTimeout(() => { copiedTemplateId.value = null; }, 1800);
    } catch {
        templateNotice.value = 'Tidak dapat menyalin pesan. Periksa izin clipboard browser.';
    }
};
const filters = reactive({ search: '', category: '', lead_temperature: '', status: '', conversion_status: '', trashed: '', analysis_date: '', analysis_month: '', analysis_year: '', sort_by: 'name' });
const dateFilterType = ref('');
const dateFilterTypes = [
    { value: '', label: 'Semua', icon: 'fa-solid fa-calendar' },
    { value: 'date', label: 'Tanggal', icon: 'fa-solid fa-calendar-day' },
    { value: 'month', label: 'Bulan', icon: 'fa-solid fa-calendar-week' },
    { value: 'year', label: 'Tahun', icon: 'fa-solid fa-calendar-check' },
];
const yearOptions = computed(() => { const current = new Date().getFullYear(); return Array.from({ length: 12 }, (_, index) => current + 2 - index); });
const activeFilterCount = computed(() => [filters.search, filters.category, filters.lead_temperature, filters.status, filters.conversion_status, filters.trashed, dateFilterType.value && (filters.analysis_date || filters.analysis_month || filters.analysis_year)].filter(Boolean).length);
const hasActiveFilters = computed(() => activeFilterCount.value > 0);
const localDate = (value = new Date()) => {
    const pad = (number) => String(number).padStart(2, '0');

    return `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}`;
};
const followUpOneDayLater = (value) => {
    if (! value) return '';

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';

    date.setDate(date.getDate() + 1);
    const pad = (number) => String(number).padStart(2, '0');

    return `${localDate(date)}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};
const blank = () => ({ name:'', category:'', city:'', analysis_summary:'', analysis_link:'', potential_reason:'', phone:'', instagram_url:'', tiktok_url:'', facebook_or_website_url:'', analyzed_at:localDate(), last_contact_at:'', next_follow_up_at:'', status:'new', notes:'', lost_reason:'', pic_id:authStore.user?.id || '', marketplace_links:[{marketplace:'Shopee',url:''}] });
const form = reactive(blank()); const showForm=ref(false); const editingId=ref(null); const saving=ref(false); const showImport=ref(false); const importPreview=ref(null); const importing=ref(false); const detailProspect=ref(null); const approvalProspect=ref(null); const approval=reactive({company_id:'',company_name:'',brand_id:'',brand_name:''});
let timer;

const selectedProspects = ref([]);
const toggleSelectAll = (event) => {
    if (event.target.checked) {
        selectedProspects.value = prospects.value.map(p => p.id);
    } else {
        selectedProspects.value = [];
    }
};

const showBulkTemperatureModal = ref(false);
const bulkTemperatureForm = reactive({ lead_temperature: '' });
const savingBulkTemperature = ref(false);
const openBulkTemperatureModal = () => { showBulkTemperatureModal.value = true; bulkTemperatureForm.lead_temperature = ''; };
const closeBulkTemperatureModal = () => { showBulkTemperatureModal.value = false; };
const saveBulkTemperature = async () => {
    if (!selectedProspects.value.length) return;
    savingBulkTemperature.value = true;
    try {
        await api.post(`/admin/business-prospects/bulk-update-temperature`, {
            ids: selectedProspects.value,
            lead_temperature: bulkTemperatureForm.lead_temperature || null
        });
        closeBulkTemperatureModal();
        selectedProspects.value = [];
        await load();
    } catch (e) {
        alert(e.response?.data?.message || 'Gagal menyimpan kategori prospek.');
    } finally {
        savingBulkTemperature.value = false;
    }
};

const load=()=>fetchProspects(filters); const resetAndLoad=()=>{pagination.current_page=1;load();}; const debouncedLoad=()=>{clearTimeout(timer);timer=setTimeout(resetAndLoad,300);};
const clearDateValues=()=>{filters.analysis_date='';filters.analysis_month='';filters.analysis_year='';};
const selectDateFilterType=(type)=>{dateFilterType.value=type;clearDateValues();resetAndLoad();};
const clearDateFilter=()=>{dateFilterType.value='';clearDateValues();resetAndLoad();};
const clearSearch=()=>{filters.search='';resetAndLoad();};
const resetAllFilters=()=>{filters.search='';filters.category='';filters.lead_temperature='';filters.status='';filters.conversion_status='';filters.trashed='';filters.sort_by='name';dateFilterType.value='';clearDateValues();resetAndLoad();};
const openDetail=(row)=>{detailProspect.value=row;};
const openCreate=()=>{editingId.value=null;Object.assign(form,blank());showForm.value=true;};
const openEdit=(row)=>{editingId.value=row.id;Object.assign(form,blank(),JSON.parse(JSON.stringify(row)));form.marketplace_links=row.marketplace_links?.length?JSON.parse(JSON.stringify(row.marketplace_links)):[{marketplace:'Shopee',url:''}];showForm.value=true;};
const save=async()=>{saving.value=true;try{editingId.value?await updateProspect(editingId.value,form):await createProspect(form);showForm.value=false;await load();}catch(e){alert(e.response?.data?.message||Object.values(e.response?.data?.errors||{}).flat()[0]||'Data tidak dapat disimpan.');}finally{saving.value=false;}};
const remove=async(row)=>{if(confirm(`Hapus prospek ${row.name}?`)){await deleteProspect(row.id);await load();}}; const restore=async(row)=>{await restoreProspect(row.id);await load();};
const askConversion=async(row)=>{if(confirm(`Ajukan ${row.name} menjadi client?`)){await requestConversion(row.id);await load();}};
const openApproval=(row)=>{approvalProspect.value=row;Object.assign(approval,{company_id:'',company_name:row.name,brand_id:'',brand_name:row.name});};
const approve=async()=>{await approveConversion(approvalProspect.value.id,approval);approvalProspect.value=null;await load();};
const reject=async(row)=>{const reason=prompt('Alasan penolakan konversi:');if(reason){await rejectConversion(row.id,reason);await load();}};
const inspectFile=async(event)=>{const file=event.target.files?.[0];if(!file)return;const response=await previewImport(file);importPreview.value=response.data.data.preview;};
const runImport=async()=>{importing.value=true;try{await confirmImport(importPreview.value.rows);closeImport();await load();}finally{importing.value=false;}}; const closeImport=()=>{showImport.value=false;importPreview.value=null;};
const changePage=(step)=>{pagination.current_page+=step;load();}; const formatDate=(value)=>value?new Intl.DateTimeFormat('id-ID',{dateStyle:'medium',timeStyle:'short'}).format(new Date(value)):'-';
watch(() => form.last_contact_at, (value) => {
    if (! editingId.value) form.next_follow_up_at = followUpOneDayLater(value);
});
onMounted(async()=>{await Promise.all([fetchOptions(),load()]);});
</script>

<style scoped>
.filter-label{display:block;font-size:.72rem;font-weight:800;color:var(--ui-content-soft)}.filter-control{width:100%;min-height:44px;border:1px solid var(--ui-border);border-radius:.8rem;background:var(--ui-surface);padding:.68rem .85rem;font-size:.85rem;font-weight:500;color:var(--ui-content);outline:none;transition:border-color .2s,box-shadow .2s,background .2s;color-scheme:inherit}.filter-control::placeholder{color:var(--ui-content-muted)}.filter-control:focus{border-color:var(--ui-brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--ui-brand) 16%,transparent)}.period-button{display:inline-flex;min-height:38px;align-items:center;justify-content:center;gap:.45rem;border:1px solid var(--ui-border);border-radius:.7rem;background:var(--ui-surface);padding:.5rem .8rem;font-size:.72rem;font-weight:800;color:var(--ui-content-soft);transition:all .2s}.period-button:hover{border-color:color-mix(in srgb,var(--ui-brand) 45%,var(--ui-border));color:var(--ui-brand)}.period-button-active{border-color:var(--ui-brand);background:var(--ui-brand);color:#fff;box-shadow:0 5px 14px color-mix(in srgb,var(--ui-brand) 25%,transparent)}.field{width:100%;border:1px solid #dbe1ea;border-radius:.75rem;background:#fff;padding:.7rem .85rem;font-size:.875rem;color:#182033;outline:none;color-scheme:inherit}.field:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}.label{display:block;font-size:.75rem;font-weight:800;color:#475569}.label .field{margin-top:.4rem;font-weight:500}.btn-primary,.btn-secondary{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:.75rem;padding:.7rem 1rem;font-size:.8rem;font-weight:800;transition:.2s}.btn-primary{background:#293681;color:#fff}.btn-primary:hover{background:#202b6b}.btn-secondary{border:1px solid #dbe1ea;background:#fff;color:#334155}.icon-btn{display:inline-flex;height:2.25rem;width:2.25rem;align-items:center;justify-content:center;border-radius:.65rem;border:1px solid #e2e8f0;background:#fff;transition:.2s}.icon-btn:hover{background:#f8fafc}.badge{display:inline-flex;border-radius:999px;background:#eef2ff;padding:.3rem .65rem;font-size:.7rem;font-weight:800;color:#3730a3}.pager{height:2rem;width:2rem;border:1px solid #e2e8f0;border-radius:.55rem}.pager:disabled{opacity:.35}.modal-wrap{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem}.modal-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.65);backdrop-filter:blur(4px)}.modal-card{position:relative;width:100%;overflow:hidden;border-radius:1.25rem;background:#fff;box-shadow:0 25px 70px rgba(15,23,42,.3)}.modal-header,.modal-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.5rem;border-bottom:1px solid #eef2f7}.modal-footer{justify-content:flex-end;border-top:1px solid #eef2f7;border-bottom:0}.detail-card{min-height:82px;border:1px solid #e2e8f0;border-radius:1rem;background:#f8fafc;padding:.85rem 1rem}.detail-card span{display:block;font-size:.68rem;font-weight:800;letter-spacing:.04em;text-transform:uppercase;color:#64748b}.detail-card strong{display:block;margin-top:.38rem;overflow-wrap:anywhere;font-size:.85rem;color:#1e293b}.detail-section{border:1px solid #e2e8f0;border-radius:1rem;background:#fff;padding:1rem}.detail-section h3{font-size:.75rem;font-weight:900;letter-spacing:.04em;text-transform:uppercase;color:#334155}.detail-section p{margin-top:.65rem;font-size:.875rem;line-height:1.6;color:#475569}.detail-link{display:inline-flex;align-items:center;gap:.45rem;border-radius:.65rem;background:#eef2ff;padding:.5rem .7rem;font-size:.75rem;font-weight:800;color:#3730a3;transition:.2s}.detail-link:hover{background:#e0e7ff}
.theme-modal{border-color:var(--ui-border);background:var(--ui-surface);color:var(--ui-content)}.theme-modal .modal-header,.theme-modal .modal-footer{border-color:var(--ui-border)}.theme-modal .field{border-color:var(--ui-border);background:var(--ui-surface);color:var(--ui-content);color-scheme:inherit}.theme-modal .field:focus{border-color:var(--ui-brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--ui-brand) 20%,transparent)}.theme-modal .label{color:var(--ui-content-soft)}.theme-modal .icon-btn,.theme-modal .btn-secondary{border-color:var(--ui-border);background:var(--ui-surface);color:var(--ui-content-soft)}.theme-modal .icon-btn:hover,.theme-modal .btn-secondary:hover{background:var(--ui-surface-muted)}.theme-modal .btn-primary{background:var(--ui-brand-strong);color:var(--ui-page)}.theme-modal .btn-primary:hover{filter:brightness(.92)}
.template-trigger{border-color:var(--ui-border);background:var(--ui-surface);color:var(--ui-brand)}.template-trigger:hover{background:var(--ui-surface-muted)}.search-icon{top:50%;bottom:auto;transform:translateY(-50%)}.filter-control.search-input{padding-left:2.75rem;padding-right:2.75rem}
</style>
