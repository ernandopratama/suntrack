<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Repositories\CompanyRepository;
use App\Services\ActivityLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CompanyRepository $repository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $search = $request->get('search', '');

        $query = Company::withCount('brands', 'users')->with('brands');

        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%");
        }

        $companies = $query->orderBy('name', 'asc')->paginate($perPage);

        // Transform data
        $companies->getCollection()->transform(function ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'brands_count' => $company->brands_count,
                'brands' => $company->brands->map(function ($brand) {
                    return ['id' => $brand->id, 'name' => $brand->name];
                }),
                'users_count' => $company->users_count,
                'created_at' => $company->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return $this->success('Companies retrieved successfully.', [
            'companies' => $companies
        ]);
    }

    public function store(StoreCompanyRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $company = Company::create($data);

        ActivityLogger::log(
            action: ActivityType::Created->value,
            description: "Company '{$company->name}' was created.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $company,
            actorId: $user->id
        );

        return $this->success('Company created successfully.', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'brands' => [],
                'users_count' => 0,
                'created_at' => $company->created_at->format('Y-m-d H:i:s'),
            ]
        ], 201);
    }

    public function show(Company $company): JsonResponse
    {
        $company->load('brands', 'users');

        return $this->success('Company retrieved successfully.', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'brands' => $company->brands->map(function ($brand) {
                    return ['id' => $brand->id, 'name' => $brand->name];
                }),
                'users_count' => $company->users->count(),
                'created_at' => $company->created_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $user = $request->user();
        $company->update($request->validated());

        ActivityLogger::log(
            action: ActivityType::Updated->value,
            description: "Company '{$company->name}' was updated.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $company,
            actorId: $user->id
        );

        return $this->success('Company updated successfully.', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'created_at' => $company->created_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    public function destroy(Company $company): JsonResponse
    {
        $user = request()->user();
        $companyName = $company->name;
        $company->delete();

        ActivityLogger::log(
            action: ActivityType::Deleted->value,
            description: "Company '{$companyName}' was deleted.",
            actorType: 'Admin',
            actorName: $user->name,
            loggable: $company,
            actorId: $user->id
        );

        return $this->success('Company deleted successfully.');
    }
}
