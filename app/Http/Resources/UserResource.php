<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\Authorization\DataScopeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'roles' => $this->roles->pluck('name'),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'direct_permissions' => $this->whenLoaded(
                'permissions',
                fn () => $this->permissions->pluck('name')->values()
            ),
            'company_ids' => $this->whenLoaded(
                'assignedCompanies',
                fn () => $this->assignedCompanies->pluck('id')->values()
            ),
            'brand_ids' => $this->whenLoaded(
                'assignedBrands',
                fn () => $this->assignedBrands->pluck('id')->values()
            ),
            'effective_scope' => $this->when(
                $this->relationLoaded('assignedCompanies') && $this->relationLoaded('assignedBrands'),
                fn () => $this->effectiveScope()
            ),
            'access_history' => $this->whenLoaded(
                'activityLogs',
                fn () => $this->activityLogs->map(fn ($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'actor_name' => $log->actor_name,
                    'properties' => $log->properties,
                    'created_at' => $log->created_at?->toIso8601String(),
                ])->values()
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    private function effectiveScope(): array
    {
        $dataScope = app(DataScopeService::class);
        $global = $dataScope->hasGlobalScope($this->resource);

        return [
            'global' => $global,
            'company_ids' => $global ? [] : $dataScope->effectiveCompanyIds($this->resource)->values(),
            'brand_ids' => $global ? [] : $dataScope->effectiveBrandIds($this->resource)->values(),
        ];
    }
}
