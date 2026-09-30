<template>
    <div class="space-y-5">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-700"><i class="fa-solid fa-handshake"></i></div>
                <div><h1 class="text-2xl font-black text-gray-900">Prospek Business Development</h1><p class="mt-1 text-sm text-gray-500">Kelola riset toko, tindak lanjut, dan konversi menjadi client.</p></div>
            </div>
            <div class="flex gap-2">
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
                <button v-if="hasActiveFilters" type="button" class="inline-flex items-center gap-2 self-start rounded-xl border border-default bg-surface-muted px-3 py-2 text-xs font-bold text-content-soft transition hover:border-[#4274D9]/40 hover:text-brand sm:self-auto" @click="resetAllFilters">
                    <i class="fa-solid fa-rotate-left text-[11px]"></i> Reset Filter
                </button>
            </header>

            <div class="grid gap-4 p-4 sm:p-5 lg:grid-cols-12">
                <label class="filter-label lg:col-span-5">
                    <span>Pencarian</span>
                    <span class="relative mt-1.5 block">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-xs text-content-muted"></i>
                        <input v-model="filters.search" class="filter-control pl-10 pr-10" placeholder="Nama toko, kota, atau link marketplace" @input="debouncedLoad" />
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
                    <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="p-4">Toko</th><th class="p-4">Marketplace</th><th class="p-4">PIC</th><th class="p-4">Status</th><th class="p-4">Follow-up</th><th class="p-4">Konversi</th><th class="p-4 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="loading"><td colspan="7" class="p-10 text-center text-gray-500">Memuat data...</td></tr>
                        <tr v-else-if="!prospects.length"><td colspan="7" class="p-10 text-center text-gray-500">Belum ada data prospek.</td></tr>
                        <tr v-for="row in prospects" :key="row.id" class="hover:bg-slate-50/70">
                            <td class="p-4"><p class="font-bold text-gray-900">{{ row.name }}</p><p class="text-xs text-gray-500">{{ [row.category, row.city].filter(Boolean).join(' · ') || 'Belum dilengkapi' }}</p></td>
                            <td class="p-4"><div class="flex flex-wrap gap-1.5"><a v-for="link in row.marketplace_links" :key="link.id" :href="link.url" target="_blank" class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700 hover:bg-blue-100">{{ link.marketplace }} <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i></a></div></td>
                            <td class="p-4 text-sm text-gray-700">{{ row.pic?.name || '-' }}</td>
                            <td class="p-4"><span class="badge">{{ row.status_label }}</span></td>
                            <td class="p-4 text-sm text-gray-600">{{ formatDate(row.next_follow_up_at) }}</td>
                            <td class="p-4"><span class="badge" :class="row.conversion_status === 'pending' ? '!bg-amber-50 !text-amber-700' : ''">{{ row.conversion_status_label }}</span></td>
                            <td class="p-4"><div class="flex justify-end gap-1.5">
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

        <div v-if="showForm" class="modal-wrap"><div class="modal-backdrop" @click="showForm=false"></div><form class="modal-card max-w-4xl" @submit.prevent="save">
            <div class="modal-header"><div><p class="text-xs font-black uppercase tracking-widest text-indigo-600">Data Prospek</p><h2 class="text-xl font-black text-gray-900">{{ editingId ? 'Ubah Prospek' : 'Tambah Prospek' }}</h2></div><button type="button" class="icon-btn" @click="showForm=false"><i class="fa-solid fa-xmark"></i></button></div>
            <div class="grid max-h-[68vh] gap-4 overflow-y-auto p-6 md:grid-cols-2">
                <label class="label">Nama Toko *<input v-model="form.name" required class="field" /></label><label class="label">Kategori<input v-model="form.category" class="field" /></label>
                <label class="label">Kota<input v-model="form.city" class="field" /></label><label class="label">Status<select v-model="form.status" class="field"><option v-for="item in options.statuses" :key="item.value" :value="item.value">{{ item.label }}</option></select></label>
                <label v-if="$can('prospect.assign')" class="label">PIC<select v-model="form.pic_id" class="field"><option v-for="pic in options.pics" :key="pic.id" :value="pic.id">{{ pic.name }}</option></select></label><label class="label">Tanggal Analisa<input v-model="form.analyzed_at" type="date" class="field" /></label>
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
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useBusinessProspects } from '../composables/useBusinessProspects';

const { prospects, options, loading, pagination, fetchProspects, fetchOptions, createProspect, updateProspect, deleteProspect, restoreProspect, requestConversion, approveConversion, rejectConversion, previewImport, confirmImport } = useBusinessProspects();
const filters = reactive({ search: '', status: '', conversion_status: '', trashed: '', analysis_date: '', analysis_month: '', analysis_year: '' });
const dateFilterType = ref('');
const dateFilterTypes = [
    { value: '', label: 'Semua', icon: 'fa-solid fa-calendar' },
    { value: 'date', label: 'Tanggal', icon: 'fa-solid fa-calendar-day' },
    { value: 'month', label: 'Bulan', icon: 'fa-solid fa-calendar-week' },
    { value: 'year', label: 'Tahun', icon: 'fa-solid fa-calendar-check' },
];
const yearOptions = computed(() => { const current = new Date().getFullYear(); return Array.from({ length: 12 }, (_, index) => current + 2 - index); });
const activeFilterCount = computed(() => [filters.search, filters.status, filters.conversion_status, filters.trashed, dateFilterType.value && (filters.analysis_date || filters.analysis_month || filters.analysis_year)].filter(Boolean).length);
const hasActiveFilters = computed(() => activeFilterCount.value > 0);
const blank = () => ({ name:'', category:'', city:'', analysis_summary:'', analysis_link:'', potential_reason:'', phone:'', instagram_url:'', tiktok_url:'', facebook_or_website_url:'', analyzed_at:'', last_contact_at:'', next_follow_up_at:'', status:'new', notes:'', lost_reason:'', pic_id:'', marketplace_links:[{marketplace:'Shopee',url:''}] });
const form = reactive(blank()); const showForm=ref(false); const editingId=ref(null); const saving=ref(false); const showImport=ref(false); const importPreview=ref(null); const importing=ref(false); const approvalProspect=ref(null); const approval=reactive({company_id:'',company_name:'',brand_id:'',brand_name:''});
let timer;
const load=()=>fetchProspects(filters); const resetAndLoad=()=>{pagination.current_page=1;load();}; const debouncedLoad=()=>{clearTimeout(timer);timer=setTimeout(resetAndLoad,300);};
const clearDateValues=()=>{filters.analysis_date='';filters.analysis_month='';filters.analysis_year='';};
const selectDateFilterType=(type)=>{dateFilterType.value=type;clearDateValues();resetAndLoad();};
const clearDateFilter=()=>{dateFilterType.value='';clearDateValues();resetAndLoad();};
const clearSearch=()=>{filters.search='';resetAndLoad();};
const resetAllFilters=()=>{filters.search='';filters.status='';filters.conversion_status='';filters.trashed='';dateFilterType.value='';clearDateValues();resetAndLoad();};
const openCreate=()=>{Object.assign(form,blank());editingId.value=null;showForm.value=true;};
const openEdit=(row)=>{Object.assign(form,blank(),JSON.parse(JSON.stringify(row)));form.marketplace_links=row.marketplace_links?.length?JSON.parse(JSON.stringify(row.marketplace_links)):[{marketplace:'Shopee',url:''}];editingId.value=row.id;showForm.value=true;};
const save=async()=>{saving.value=true;try{editingId.value?await updateProspect(editingId.value,form):await createProspect(form);showForm.value=false;await load();}catch(e){alert(e.response?.data?.message||Object.values(e.response?.data?.errors||{}).flat()[0]||'Data tidak dapat disimpan.');}finally{saving.value=false;}};
const remove=async(row)=>{if(confirm(`Hapus prospek ${row.name}?`)){await deleteProspect(row.id);await load();}}; const restore=async(row)=>{await restoreProspect(row.id);await load();};
const askConversion=async(row)=>{if(confirm(`Ajukan ${row.name} menjadi client?`)){await requestConversion(row.id);await load();}};
const openApproval=(row)=>{approvalProspect.value=row;Object.assign(approval,{company_id:'',company_name:row.name,brand_id:'',brand_name:row.name});};
const approve=async()=>{await approveConversion(approvalProspect.value.id,approval);approvalProspect.value=null;await load();};
const reject=async(row)=>{const reason=prompt('Alasan penolakan konversi:');if(reason){await rejectConversion(row.id,reason);await load();}};
const inspectFile=async(event)=>{const file=event.target.files?.[0];if(!file)return;const response=await previewImport(file);importPreview.value=response.data.data.preview;};
const runImport=async()=>{importing.value=true;try{await confirmImport(importPreview.value.rows);closeImport();await load();}finally{importing.value=false;}}; const closeImport=()=>{showImport.value=false;importPreview.value=null;};
const changePage=(step)=>{pagination.current_page+=step;load();}; const formatDate=(value)=>value?new Intl.DateTimeFormat('id-ID',{dateStyle:'medium',timeStyle:'short'}).format(new Date(value)):'-';
onMounted(async()=>{await Promise.all([fetchOptions(),load()]);});
</script>

<style scoped>
.filter-label{display:block;font-size:.72rem;font-weight:800;color:var(--ui-content-soft)}.filter-control{width:100%;min-height:44px;border:1px solid var(--ui-border);border-radius:.8rem;background:var(--ui-surface);padding:.68rem .85rem;font-size:.85rem;font-weight:500;color:var(--ui-content);outline:none;transition:border-color .2s,box-shadow .2s,background .2s}.filter-control::placeholder{color:var(--ui-content-muted)}.filter-control:focus{border-color:var(--ui-brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--ui-brand) 16%,transparent)}.period-button{display:inline-flex;min-height:38px;align-items:center;justify-content:center;gap:.45rem;border:1px solid var(--ui-border);border-radius:.7rem;background:var(--ui-surface);padding:.5rem .8rem;font-size:.72rem;font-weight:800;color:var(--ui-content-soft);transition:all .2s}.period-button:hover{border-color:color-mix(in srgb,var(--ui-brand) 45%,var(--ui-border));color:var(--ui-brand)}.period-button-active{border-color:var(--ui-brand);background:var(--ui-brand);color:#fff;box-shadow:0 5px 14px color-mix(in srgb,var(--ui-brand) 25%,transparent)}.field{width:100%;border:1px solid #dbe1ea;border-radius:.75rem;background:#fff;padding:.7rem .85rem;font-size:.875rem;color:#182033;outline:none}.field:focus{border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.12)}.label{display:block;font-size:.75rem;font-weight:800;color:#475569}.label .field{margin-top:.4rem;font-weight:500}.btn-primary,.btn-secondary{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:.75rem;padding:.7rem 1rem;font-size:.8rem;font-weight:800;transition:.2s}.btn-primary{background:#293681;color:#fff}.btn-primary:hover{background:#202b6b}.btn-secondary{border:1px solid #dbe1ea;background:#fff;color:#334155}.icon-btn{display:inline-flex;height:2.25rem;width:2.25rem;align-items:center;justify-content:center;border-radius:.65rem;border:1px solid #e2e8f0;background:#fff;transition:.2s}.icon-btn:hover{background:#f8fafc}.badge{display:inline-flex;border-radius:999px;background:#eef2ff;padding:.3rem .65rem;font-size:.7rem;font-weight:800;color:#3730a3}.pager{height:2rem;width:2rem;border:1px solid #e2e8f0;border-radius:.55rem}.pager:disabled{opacity:.35}.modal-wrap{position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem}.modal-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.65);backdrop-filter:blur(4px)}.modal-card{position:relative;width:100%;overflow:hidden;border-radius:1.25rem;background:#fff;box-shadow:0 25px 70px rgba(15,23,42,.3)}.modal-header,.modal-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.5rem;border-bottom:1px solid #eef2f7}.modal-footer{justify-content:flex-end;border-top:1px solid #eef2f7;border-bottom:0}
</style>
