<?php

namespace Tests\Feature;

use App\Enums\BusinessProspectStatus;
use App\Models\BusinessProspect;
use App\Models\User;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessProspectTest extends TestCase
{
    use RefreshDatabase;

    private User $businessDevelopment;

    private User $otherBusinessDevelopment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->businessDevelopment = User::factory()->create(['type' => 'team']);
        $this->businessDevelopment->assignRole(RbacRegistry::BUSINESS_DEVELOPMENT);
        $this->otherBusinessDevelopment = User::factory()->create(['type' => 'team']);
        $this->otherBusinessDevelopment->assignRole(RbacRegistry::BUSINESS_DEVELOPMENT);
    }

    public function test_business_development_can_create_and_only_view_own_prospects(): void
    {
        $response = $this->actingAs($this->businessDevelopment)->postJson('/api/v1/admin/business-prospects', [
            'name' => '  Toko Contoh  ',
            'status' => BusinessProspectStatus::New->value,
            'marketplace_links' => [['marketplace' => 'Shopee', 'url' => 'https://shopee.co.id/toko-contoh']],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.prospect.name', 'Toko Contoh')
            ->assertJsonPath('data.prospect.pic.id', $this->businessDevelopment->id);

        BusinessProspect::create([
            'name' => 'Milik User Lain',
            'status' => BusinessProspectStatus::New,
            'pic_id' => $this->otherBusinessDevelopment->id,
            'created_by' => $this->otherBusinessDevelopment->id,
        ]);

        $this->actingAs($this->businessDevelopment)
            ->getJson('/api/v1/admin/business-prospects?per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data.prospects.data')
            ->assertJsonPath('data.prospects.per_page', 10);
    }

    public function test_normalized_store_name_stays_unique_after_soft_delete(): void
    {
        $prospect = BusinessProspect::create([
            'name' => 'Toko Unik',
            'status' => BusinessProspectStatus::New,
            'pic_id' => $this->businessDevelopment->id,
            'created_by' => $this->businessDevelopment->id,
        ]);
        $prospect->delete();

        $this->actingAs($this->businessDevelopment)->postJson('/api/v1/admin/business-prospects', [
            'name' => ' toko   unik ',
            'status' => BusinessProspectStatus::New->value,
            'marketplace_links' => [['marketplace' => 'Tokopedia', 'url' => 'https://tokopedia.com/toko-unik']],
        ])->assertUnprocessable()->assertJsonValidationErrors('normalized_name');
    }

    public function test_only_admin_can_restore_deleted_prospect(): void
    {
        $prospect = BusinessProspect::create([
            'name' => 'Prospek Terhapus',
            'status' => BusinessProspectStatus::New,
            'pic_id' => $this->businessDevelopment->id,
            'created_by' => $this->businessDevelopment->id,
        ]);
        $prospect->delete();

        $this->actingAs($this->businessDevelopment)
            ->postJson("/api/v1/admin/business-prospects/{$prospect->id}/restore")
            ->assertForbidden();

        $admin = User::factory()->create(['type' => 'admin']);
        $admin->assignRole(RbacRegistry::ADMIN);
        $this->actingAs($admin)
            ->postJson("/api/v1/admin/business-prospects/{$prospect->id}/restore")
            ->assertOk();

        $this->assertNotSoftDeleted('business_prospects', ['id' => $prospect->id]);
    }

    public function test_business_development_requests_and_admin_approves_conversion(): void
    {
        $prospect = BusinessProspect::create([
            'name' => 'Toko Siap Menjadi Client',
            'status' => BusinessProspectStatus::Won,
            'pic_id' => $this->businessDevelopment->id,
            'created_by' => $this->businessDevelopment->id,
        ]);

        $this->actingAs($this->businessDevelopment)
            ->postJson("/api/v1/admin/business-prospects/{$prospect->id}/request-conversion")
            ->assertOk();

        $admin = User::factory()->create(['type' => 'admin']);
        $admin->assignRole(RbacRegistry::ADMIN);
        $this->actingAs($admin)
            ->postJson("/api/v1/admin/business-prospects/{$prospect->id}/approve-conversion", [
                'company_name' => 'Client Baru',
                'brand_name' => 'Brand Baru',
            ])
            ->assertOk();

        $this->assertDatabaseHas('companies', ['name' => 'Client Baru']);
        $this->assertDatabaseHas('brands', ['name' => 'Brand Baru']);
        $this->assertDatabaseHas('business_prospects', ['id' => $prospect->id, 'conversion_status' => 'approved']);
    }

    public function test_prospects_can_be_filtered_by_analysis_date_month_and_year(): void
    {
        foreach (
            [
                ['name' => 'Prospek Januari', 'analyzed_at' => '2026-01-15'],
                ['name' => 'Prospek September', 'analyzed_at' => '2026-09-28'],
                ['name' => 'Prospek Tahun Lain', 'analyzed_at' => '2025-09-28'],
            ] as $data
        ) {
            BusinessProspect::create($data + [
                'status' => BusinessProspectStatus::New,
                'pic_id' => $this->businessDevelopment->id,
                'created_by' => $this->businessDevelopment->id,
            ]);
        }

        $this->actingAs($this->businessDevelopment)
            ->getJson('/api/v1/admin/business-prospects?analysis_date=2026-09-28')
            ->assertOk()->assertJsonCount(1, 'data.prospects.data')
            ->assertJsonPath('data.prospects.data.0.name', 'Prospek September');

        $this->actingAs($this->businessDevelopment)
            ->getJson('/api/v1/admin/business-prospects?analysis_month=2026-09')
            ->assertOk()->assertJsonCount(1, 'data.prospects.data');

        $this->actingAs($this->businessDevelopment)
            ->getJson('/api/v1/admin/business-prospects?analysis_year=2026')
            ->assertOk()->assertJsonCount(2, 'data.prospects.data');
    }

    public function test_prospects_can_be_filtered_by_category_and_sorted_by_analysis_or_last_contact(): void
    {
        foreach (
            [
                ['name' => 'Toko Zebra', 'category' => 'Fashion Wanita', 'lead_temperature' => 'Cold', 'analysis_summary' => 'Zebra result', 'last_contact_at' => '2026-01-01 10:00:00'],
                ['name' => 'Toko Alpha', 'category' => 'Fashion Pria', 'lead_temperature' => 'Hot', 'analysis_summary' => 'Alpha result', 'last_contact_at' => '2026-03-01 10:00:00'],
                ['name' => 'Toko Beta', 'category' => 'Beauty', 'lead_temperature' => 'Warm', 'analysis_summary' => 'Beta result', 'last_contact_at' => '2026-05-01 10:00:00'],
            ] as $data
        ) {
            BusinessProspect::create($data + [
                'status' => BusinessProspectStatus::New,
                'pic_id' => $this->businessDevelopment->id,
                'created_by' => $this->businessDevelopment->id,
            ]);
        }

        $this->actingAs($this->businessDevelopment)
            ->getJson('/api/v1/admin/business-prospects?category=Fashion')
            ->assertOk()
            ->assertJsonCount(2, 'data.prospects.data');

        $this->actingAs($this->businessDevelopment)
            ->getJson('/api/v1/admin/business-prospects?lead_temperature=Hot')
            ->assertOk()
            ->assertJsonCount(1, 'data.prospects.data')
            ->assertJsonPath('data.prospects.data.0.name', 'Toko Alpha');

        $this->actingAs($this->businessDevelopment)
            ->getJson('/api/v1/admin/business-prospects?sort_by=analysis_summary')
            ->assertOk()
            ->assertJsonPath('data.prospects.data.0.name', 'Toko Alpha')
            ->assertJsonPath('data.prospects.data.2.name', 'Toko Zebra');

        $this->actingAs($this->businessDevelopment)
            ->getJson('/api/v1/admin/business-prospects?sort_by=last_contact_at')
            ->assertOk()
            ->assertJsonPath('data.prospects.data.0.name', 'Toko Beta');
    }
}
