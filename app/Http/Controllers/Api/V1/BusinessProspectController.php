<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Enums\BusinessProspectStatus;
use App\Enums\ProspectConversionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessProspectRequest;
use App\Http\Requests\UpdateBusinessProspectRequest;
use App\Http\Resources\BusinessProspectResource;
use App\Models\Brand;
use App\Models\BusinessProspect;
use App\Models\Company;
use App\Models\ProspectMarketplaceLink;
use App\Models\User;
use App\Repositories\BusinessProspectRepository;
use App\Services\ActivityLogger;
use App\Services\Authorization\DataScopeService;
use App\Services\BusinessProspectImportService;
use App\Support\Rbac\RbacRegistry;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class BusinessProspectController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly BusinessProspectRepository $repository,
        private readonly BusinessProspectImportService $importer,
        private readonly DataScopeService $dataScope,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BusinessProspect::class);
        $perPage = in_array((int) $request->input('per_page', 10), [10, 25, 50, 100], true)
            ? (int) $request->input('per_page', 10)
            : 10;
        $prospects = $this->repository->getFilteredPaginated(
            $request->user(),
            $request->only(['search', 'category', 'status', 'conversion_status', 'pic_id', 'trashed', 'analysis_date', 'analysis_month', 'analysis_year', 'lead_temperature', 'sort_by', 'sort_dir']),
            $perPage,
        );
        $prospects->setCollection(BusinessProspectResource::collection($prospects->getCollection())->collection);

        return $this->success('Data prospek berhasil dimuat.', ['prospects' => $prospects]);
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BusinessProspect::class);
        $user = $request->user();
        $global = $this->dataScope->hasGlobalScope($user);

        return $this->success('Pilihan prospek berhasil dimuat.', [
            'statuses' => collect(BusinessProspectStatus::cases())->map(fn($status) => ['value' => $status->value, 'label' => $status->label()]),
            'conversion_statuses' => collect(ProspectConversionStatus::cases())->map(fn($status) => ['value' => $status->value, 'label' => $status->label()]),
            'pics' => $global ? User::role([RbacRegistry::BUSINESS_DEVELOPMENT, RbacRegistry::ADMIN, RbacRegistry::SUPER_ADMIN])->orderBy('name')->get(['id', 'name']) : collect([['id' => $user->id, 'name' => $user->name]]),
            'companies' => $global ? Company::orderBy('name')->get(['id', 'name']) : [],
            'brands' => $global ? Brand::with('company:id,name')->orderBy('name')->get(['id', 'company_id', 'name', 'category']) : [],
        ]);
    }

    public function store(StoreBusinessProspectRequest $request): JsonResponse
    {
        $this->authorize('create', BusinessProspect::class);
        $user = $request->user();
        $data = $request->validated();
        $links = $data['marketplace_links'];
        unset($data['marketplace_links'], $data['normalized_name']);
        if (! $user->can('prospect.assign')) {
            $data['pic_id'] = $user->id;
        }
        $data['pic_id'] ??= $user->id;
        $data['created_by'] = $user->id;

        $prospect = DB::transaction(function () use ($data, $links) {
            $prospect = new BusinessProspect;
            $prospect->fill($data);
            $prospect->save();
            $this->replaceLinks($prospect, $links);

            return $prospect;
        });
        $this->log($request, $prospect, ActivityType::Created, "Prospek '{$prospect->name}' dibuat.");

        return $this->success('Prospek berhasil dibuat.', ['prospect' => new BusinessProspectResource($this->load($prospect))], 201);
    }

    public function show(BusinessProspect $businessProspect): JsonResponse
    {
        $this->authorize('view', $businessProspect);

        return $this->success('Prospek berhasil dimuat.', ['prospect' => new BusinessProspectResource($this->load($businessProspect))]);
    }

    public function update(UpdateBusinessProspectRequest $request, BusinessProspect $businessProspect): JsonResponse
    {
        $this->authorize('update', $businessProspect);
        $data = $request->validated();
        $links = $data['marketplace_links'];
        unset($data['marketplace_links'], $data['normalized_name']);
        if (! $request->user()->can('prospect.assign')) {
            unset($data['pic_id']);
        }
        DB::transaction(function () use ($businessProspect, $data, $links) {
            $businessProspect->update($data);
            $this->replaceLinks($businessProspect, $links);
        });
        $this->log($request, $businessProspect, ActivityType::Updated, "Prospek '{$businessProspect->name}' diperbarui.");

        return $this->success('Prospek berhasil diperbarui.', ['prospect' => new BusinessProspectResource($this->load($businessProspect))]);
    }

    public function destroy(Request $request, BusinessProspect $businessProspect): JsonResponse
    {
        $this->authorize('delete', $businessProspect);
        $name = $businessProspect->name;
        $businessProspect->delete();
        $this->log($request, $businessProspect, ActivityType::Deleted, "Prospek '{$name}' dihapus.");

        return $this->success('Prospek berhasil dihapus.');
    }

    public function restore(Request $request, string $id): JsonResponse
    {
        abort_unless($request->user()->can('prospect.restore'), 403);
        $prospect = BusinessProspect::onlyTrashed()->findOrFail($id);
        $prospect->restore();
        $this->log($request, $prospect, ActivityType::Updated, "Prospek '{$prospect->name}' dipulihkan.");

        return $this->success('Prospek berhasil dipulihkan.');
    }

    public function importPreview(Request $request): JsonResponse
    {
        $this->authorize('create', BusinessProspect::class);
        $validated = $request->validate(['file' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:5120']]);
        try {
            return $this->success('File berhasil diperiksa.', ['preview' => $this->importer->inspect($validated['file'])]);
        } catch (Throwable $exception) {
            return $this->error($exception->getMessage(), [], 422);
        }
    }

    public function import(Request $request): JsonResponse
    {
        $this->authorize('create', BusinessProspect::class);
        $data = $request->validate(['rows' => ['required', 'array', 'min:1', 'max:5000'], 'rows.*' => ['array']]);
        $created = 0;
        $merged = 0;
        $skipped = 0;
        foreach ($data['rows'] as $row) {
            if (($row['valid'] ?? false) !== true || empty($row['name']) || empty($row['marketplace_links'])) {
                $skipped++;

                continue;
            }
            $existing = BusinessProspect::withTrashed()->where('normalized_name', BusinessProspect::normalizeName($row['name']))->first();
            if ($existing?->trashed()) {
                $skipped++;

                continue;
            }
            if ($existing) {
                foreach ($row['marketplace_links'] as $link) {
                    $hash = ProspectMarketplaceLink::urlHash($link['url']);
                    if (! ProspectMarketplaceLink::where('normalized_url_hash', $hash)->exists()) {
                        $existing->marketplaceLinks()->create(['marketplace' => $link['marketplace'], 'url' => $link['url'], 'normalized_url_hash' => $hash]);
                    }
                }
                $merged++;

                continue;
            }
            $prospect = BusinessProspect::create([
                'name' => $row['name'],
                'category' => $row['category'] ?? null,
                'city' => $row['city'] ?? null,
                'analysis_summary' => $row['analysis_summary'] ?? null,
                'analysis_link' => $row['analysis_link'] ?? null,
                'potential_reason' => $row['potential_reason'] ?? null,
                'instagram_url' => $row['instagram_url'] ?? null,
                'tiktok_url' => $row['tiktok_url'] ?? null,
                'facebook_or_website_url' => $row['facebook_or_website_url'] ?? null,
                'phone' => $row['phone'] ?? null,
                'analyzed_at' => $row['analyzed_at'] ?? null,
                'status' => $row['status'] ?? BusinessProspectStatus::New->value,
                'notes' => $row['notes'] ?? null,
                'pic_id' => $request->user()->id,
                'created_by' => $request->user()->id,
            ]);
            foreach ($row['marketplace_links'] as $link) {
                $hash = ProspectMarketplaceLink::urlHash($link['url']);
                if (! ProspectMarketplaceLink::where('normalized_url_hash', $hash)->exists()) {
                    $prospect->marketplaceLinks()->create(['marketplace' => $link['marketplace'], 'url' => $link['url'], 'normalized_url_hash' => $hash]);
                }
            }
            $created++;
        }

        return $this->success('Import prospek selesai.', compact('created', 'merged', 'skipped'));
    }

    public function bulkUpdateTemperature(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['uuid', 'exists:business_prospects,id'],
            'lead_temperature' => ['nullable', 'string', 'in:Cold,Warm,Hot'],
        ]);

        BusinessProspect::whereIn('id', $validated['ids'])
            ->update(['lead_temperature' => $validated['lead_temperature']]);

        return $this->success('Kategori prospek berhasil diperbarui.');
    }

    public function requestConversion(Request $request, BusinessProspect $businessProspect): JsonResponse
    {
        abort_unless($request->user()->can('prospect.request-conversion'), 403);
        $this->authorize('update', $businessProspect);
        if ($businessProspect->statusEnum() !== BusinessProspectStatus::Won) {
            return $this->error('Status prospek harus Berhasil sebelum meminta konversi.', [], 422);
        }
        if (! in_array($businessProspect->conversionStatusEnum(), [ProspectConversionStatus::None, ProspectConversionStatus::Rejected], true)) {
            return $this->error('Prospek ini sudah memiliki permintaan konversi aktif atau telah disetujui.', [], 422);
        }
        $businessProspect->forceFill(['conversion_status' => ProspectConversionStatus::Pending, 'conversion_requested_by' => $request->user()->id, 'conversion_requested_at' => now(), 'conversion_rejection_reason' => null])->save();
        $this->log($request, $businessProspect, ActivityType::StatusChanged, "Konversi prospek '{$businessProspect->name}' diajukan.");

        return $this->success('Permintaan konversi dikirim kepada admin.');
    }

    public function approveConversion(Request $request, BusinessProspect $businessProspect): JsonResponse
    {
        abort_unless($request->user()->can('prospect.approve-conversion'), 403);
        if ($businessProspect->conversionStatusEnum() !== ProspectConversionStatus::Pending) {
            return $this->error('Hanya permintaan konversi yang menunggu persetujuan yang dapat diproses.', [], 422);
        }
        $validated = $request->validate([
            'company_id' => ['nullable', 'uuid', 'exists:companies,id'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'brand_id' => ['nullable', 'uuid', 'exists:brands,id'],
            'brand_name' => ['nullable', 'string', 'max:255'],
        ]);
        if (empty($validated['brand_id']) && empty($validated['brand_name'])) {
            return $this->error('Pilih brand atau isi nama brand baru.', [], 422);
        }
        DB::transaction(function () use ($request, $businessProspect, $validated) {
            if (! empty($validated['brand_id'])) {
                $brand = Brand::with('company')->findOrFail($validated['brand_id']);
                $company = $brand->company;
            } else {
                $company = ! empty($validated['company_id'])
                    ? Company::findOrFail($validated['company_id'])
                    : Company::firstOrCreate(['name' => $validated['company_name'] ?: $businessProspect->name]);
                $brand = Brand::firstOrCreate(['company_id' => $company->id, 'name' => $validated['brand_name']]);
            }
            $businessProspect->forceFill([
                'conversion_status' => ProspectConversionStatus::Approved,
                'conversion_reviewed_by' => $request->user()->id,
                'conversion_reviewed_at' => now(),
                'conversion_rejection_reason' => null,
                'converted_company_id' => $company->id,
                'converted_brand_id' => $brand->id,
            ])->save();
        });
        $this->log($request, $businessProspect, ActivityType::StatusChanged, "Konversi prospek '{$businessProspect->name}' disetujui.");

        return $this->success('Konversi disetujui dan data company/brand tersedia.');
    }

    public function rejectConversion(Request $request, BusinessProspect $businessProspect): JsonResponse
    {
        abort_unless($request->user()->can('prospect.approve-conversion'), 403);
        if ($businessProspect->conversionStatusEnum() !== ProspectConversionStatus::Pending) {
            return $this->error('Hanya permintaan konversi yang menunggu persetujuan yang dapat ditolak.', [], 422);
        }
        $validated = $request->validate(['reason' => ['required', 'string', 'max:2000']]);
        $businessProspect->forceFill(['conversion_status' => ProspectConversionStatus::Rejected, 'conversion_reviewed_by' => $request->user()->id, 'conversion_reviewed_at' => now(), 'conversion_rejection_reason' => $validated['reason']])->save();
        $this->log($request, $businessProspect, ActivityType::StatusChanged, "Konversi prospek '{$businessProspect->name}' ditolak.");

        return $this->success('Permintaan konversi ditolak.');
    }

    /** @param array<int, array{marketplace: string, url: string}> $links */
    private function replaceLinks(BusinessProspect $prospect, array $links): void
    {
        $prospect->marketplaceLinks()->delete();
        foreach ($links as $link) {
            $prospect->marketplaceLinks()->create(['marketplace' => $link['marketplace'], 'url' => $link['url'], 'normalized_url_hash' => ProspectMarketplaceLink::urlHash($link['url'])]);
        }
    }

    private function load(BusinessProspect $prospect): BusinessProspect
    {
        return $prospect->load(['marketplaceLinks', 'pic:id,name', 'creator:id,name', 'convertedCompany:id,name', 'convertedBrand:id,name']);
    }

    private function log(Request $request, BusinessProspect $prospect, ActivityType $type, string $description): void
    {
        ActivityLogger::log($type->value, $description, 'Admin', $request->user()->name, $prospect, $request->user()->id);
    }
}
