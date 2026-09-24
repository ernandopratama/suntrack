<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\Promotion;
use App\Models\PromotionItem;
use App\Models\SecureLink;
use App\Models\User;
use App\Services\PromotionItemSpreadsheetService;
use App\Support\Rbac\RbacRegistry;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class PromotionItemImportTest extends TestCase
{
    use RefreshDatabase;

    private User $team;

    private Promotion $promotion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $company = Company::create(['name' => 'Import Company']);
        $brand = Brand::create(['company_id' => $company->id, 'name' => 'Import Brand']);

        $admin = User::factory()->create();
        $admin->assignRole(RbacRegistry::ADMIN);

        $this->team = User::factory()->create();
        $this->team->assignRole(RbacRegistry::TEAM);
        $this->team->syncPermissions(RbacRegistry::TEAM_DEFAULT_PERMISSIONS);
        $this->team->assignedBrands()->attach($brand->id, ['assigned_by' => $admin->id]);

        $campaign = Campaign::create([
            'brand_id' => $brand->id,
            'created_by' => $this->team->id,
            'name' => 'Campaign Import',
            'status' => 'draft',
        ]);

        $this->promotion = Promotion::create([
            'brand_id' => $brand->id,
            'campaign_id' => $campaign->id,
            'name' => 'Promosi Import',
            'status' => 'Pending',
        ]);
    }

    public function test_team_can_download_preview_and_import_blank_template_format(): void
    {
        $this->actingAs($this->team)
            ->get("/api/v1/admin/promotions/{$this->promotion->id}/items/template")
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $file = $this->workbook([
            ['Zeta Product', '', 100000, 80000, 20, 2],
            ['Alpha Product', 'Merah', 50000, 45000, 10, 1],
        ]);

        $this->actingAs($this->team)
            ->post("/api/v1/admin/promotions/{$this->promotion->id}/items/import/preview", ['file' => $file])
            ->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.total_rows', 2)
            ->assertJsonPath('data.rows.0.discount_percentage', 20);

        $this->actingAs($this->team)
            ->post("/api/v1/admin/promotions/{$this->promotion->id}/items/import", [
                'file' => $this->workbook([
                    ['Zeta Product', '', 100000, 80000, 20, 2],
                    ['Alpha Product', 'Merah', 50000, 45000, 10, 1],
                ]),
            ])
            ->assertOk()
            ->assertJsonPath('data.imported', 2)
            ->assertJsonPath('data.items.0.product_name', 'Alpha Product')
            ->assertJsonPath('data.items.1.product_name', 'Zeta Product');

        $this->assertDatabaseHas('promotion_items', [
            'promotion_id' => $this->promotion->id,
            'product_name' => 'Alpha Product',
            'discount_amount' => 5000,
        ]);
    }

    public function test_import_replaces_rows_and_items_can_be_edited_and_deleted_individually(): void
    {
        $old = PromotionItem::create([
            'promotion_id' => $this->promotion->id,
            'product_name' => 'Produk Lama',
            'normal_price' => 10000,
            'discount_price' => 9000,
            'discount_amount' => 1000,
            'discount_percentage' => 10,
        ]);

        $this->actingAs($this->team)
            ->post("/api/v1/admin/promotions/{$this->promotion->id}/items/import", [
                'file' => $this->workbook([['Produk Baru', 'Besar', 20000, 15000, 5, 1]]),
            ])
            ->assertOk();

        $this->assertDatabaseMissing('promotion_items', ['id' => $old->id]);
        $item = PromotionItem::where('promotion_id', $this->promotion->id)->firstOrFail();

        $this->actingAs($this->team)
            ->putJson("/api/v1/admin/promotions/{$this->promotion->id}/items/{$item->id}", [
                'product_name' => 'Produk Diperbarui',
                'variant_name' => null,
                'normal_price' => 30000,
                'discount_price' => 24000,
                'promotion_stock' => 8,
                'purchase_limit' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('data.item.discount_percentage', 20);

        $this->actingAs($this->team)
            ->deleteJson("/api/v1/admin/promotions/{$this->promotion->id}/items/{$item->id}")
            ->assertOk();

        $this->assertDatabaseMissing('promotion_items', ['id' => $item->id]);
    }

    public function test_invalid_rows_do_not_replace_existing_products(): void
    {
        $existing = PromotionItem::create([
            'promotion_id' => $this->promotion->id,
            'product_name' => 'Tetap Ada',
            'normal_price' => 10000,
            'discount_price' => 9000,
            'discount_amount' => 1000,
            'discount_percentage' => 10,
        ]);

        $this->actingAs($this->team)
            ->post("/api/v1/admin/promotions/{$this->promotion->id}/items/import", [
                'file' => $this->workbook([['Harga Salah', '', 10000, 12000, 1, 1]]),
            ])
            ->assertUnprocessable();

        $this->assertDatabaseHas('promotion_items', ['id' => $existing->id]);
    }

    public function test_public_secure_link_displays_and_approves_imported_products(): void
    {
        $item = PromotionItem::create([
            'promotion_id' => $this->promotion->id,
            'product_name' => 'Produk Approval',
            'variant_name' => 'Besar',
            'normal_price' => 100000,
            'discount_price' => 80000,
            'discount_amount' => 20000,
            'discount_percentage' => 20,
        ]);
        $link = SecureLink::create([
            'linkable_type' => Promotion::class,
            'linkable_id' => $this->promotion->id,
            'token' => Str::random(64),
            'created_by' => $this->team->id,
        ]);

        $this->getJson("/api/v1/public/review/{$link->token}")
            ->assertOk()
            ->assertJsonPath('data.variants.0.id', $item->id)
            ->assertJsonPath('data.variants.0.product_name', 'Produk Approval')
            ->assertJsonPath('data.variants.0.discount_price', 80000);

        $this->postJson("/api/v1/public/review/{$link->token}/approval", [
            'variant_id' => $item->id,
            'status' => 'Approved',
            'reviewer_name' => 'Client Reviewer',
        ])->assertOk();

        $this->assertDatabaseHas('promotion_items', [
            'id' => $item->id,
            'approval_status' => 'Approved',
        ]);
        $this->assertDatabaseHas('approval_histories', [
            'promotion_item_id' => $item->id,
            'variant_id' => null,
            'new_status' => 'Approved',
        ]);
    }

    /** @param array<int, array<int, string|int|float|null>> $rows */
    private function workbook(array $rows): UploadedFile
    {
        $service = app(PromotionItemSpreadsheetService::class);
        $path = tempnam(sys_get_temp_dir(), 'promotion-test-');
        file_put_contents($path, $service->template());

        $zip = new ZipArchive;
        $zip->open($path);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $xmlRows = '';

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $cells = '';
            foreach ($row as $columnIndex => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $reference = chr(65 + $columnIndex).$rowNumber;
                if (is_numeric($value)) {
                    $cells .= "<c r=\"{$reference}\"><v>{$value}</v></c>";
                } else {
                    $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                    $cells .= "<c r=\"{$reference}\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
                }
            }
            $xmlRows .= "<row r=\"{$rowNumber}\">{$cells}</row>";
        }

        $sheet = str_replace('</sheetData>', $xmlRows.'</sheetData>', $sheet);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        $contents = file_get_contents($path);
        @unlink($path);

        return UploadedFile::fake()->createWithContent('data-promosi.xlsx', $contents);
    }
}
