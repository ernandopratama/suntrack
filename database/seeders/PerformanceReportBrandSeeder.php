<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerformanceReportBrandSeeder extends Seeder
{
    private const GROUP_COMPANY = 'Suur Lemon Solo dan Timur Raya';

    private const GROUP_BRANDS = [
        'nutripedia',
        'indorganik',
        'hayorganic',
        'suurlemon',
    ];

    private const BRANDS = [
        'Tokopisoerdjo',
        'Sambal Ibu',
        'Suurlemon',
        'Nutripedia',
        'Indorganik',
        'Hayorganic',
        'Pustaka Peluk',
        'Sarmon',
        'Magnolia',
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::BRANDS as $name) {
                $company = $this->companyForBrand($name);
                $brand = Brand::withTrashed()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->first();

                if (! $brand) {
                    Brand::create(['company_id' => $company->id, 'name' => $name]);
                    continue;
                }

                if ($brand->trashed()) {
                    $brand->restore();
                }
                if ($brand->company_id !== $company->id) {
                    $brand->update(['company_id' => $company->id]);
                }
            }

            Brand::query()->each(function (Brand $brand): void {
                $company = $this->companyForBrand($brand->name);
                if ($brand->company_id !== $company->id) {
                    $brand->update(['company_id' => $company->id]);
                }
            });
        });
    }

    private function companyForBrand(string $brandName): Company
    {
        $companyName = in_array(mb_strtolower(trim($brandName)), self::GROUP_BRANDS, true)
            ? self::GROUP_COMPANY
            : trim($brandName);
        $company = Company::withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($companyName)])
            ->first();

        if (! $company) {
            return Company::create(['name' => $companyName]);
        }
        if ($company->trashed()) {
            $company->restore();
        }
        if ($company->name !== $companyName) {
            $company->update(['name' => $companyName]);
        }

        return $company;
    }
}
