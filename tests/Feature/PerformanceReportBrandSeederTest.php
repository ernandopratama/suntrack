<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Company;
use Database\Seeders\PerformanceReportBrandSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceReportBrandSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_groups_suurlemon_brands_and_creates_matching_companies_for_other_brands(): void
    {
        $legacyCompany = Company::create(['name' => 'Legacy Company']);
        $customBrand = Brand::create([
            'company_id' => $legacyCompany->id,
            'name' => 'Custom Brand',
        ]);
        $customBrandId = $customBrand->id;

        $this->seed(PerformanceReportBrandSeeder::class);

        $groupCompany = Company::where('name', 'Suur Lemon Solo dan Timur Raya')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            ['Nutripedia', 'Indorganik', 'Hayorganic', 'Suurlemon'],
            $groupCompany->brands()->pluck('name')->all()
        );

        foreach (['Tokopisoerdjo', 'Sambal Ibu', 'Pustaka Peluk', 'Sarmon', 'Magnolia', 'Custom Brand'] as $name) {
            $brand = Brand::where('name', $name)->firstOrFail();
            $this->assertSame($name, $brand->company->name);
        }
        $this->assertSame($customBrandId, Brand::where('name', 'Custom Brand')->value('id'));
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(PerformanceReportBrandSeeder::class);
        $companyCount = Company::count();
        $brandCount = Brand::count();
        $brandOwnership = Brand::orderBy('name')->pluck('company_id', 'name')->all();

        $this->seed(PerformanceReportBrandSeeder::class);

        $this->assertSame($companyCount, Company::count());
        $this->assertSame($brandCount, Brand::count());
        $this->assertSame($brandOwnership, Brand::orderBy('name')->pluck('company_id', 'name')->all());
    }
}
