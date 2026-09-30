<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_prospects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('normalized_name')->unique();
            $table->string('category')->nullable()->index();
            $table->string('city')->nullable()->index();
            $table->text('analysis_summary')->nullable();
            $table->string('analysis_link', 2048)->nullable();
            $table->text('potential_reason')->nullable();
            $table->string('instagram_url', 2048)->nullable();
            $table->string('tiktok_url', 2048)->nullable();
            $table->string('facebook_or_website_url', 2048)->nullable();
            $table->string('phone', 50)->nullable();
            $table->foreignUuid('pic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->date('analyzed_at')->nullable();
            $table->string('status', 40)->default('new')->index();
            $table->dateTime('last_contact_at')->nullable();
            $table->dateTime('next_follow_up_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->text('lost_reason')->nullable();
            $table->string('conversion_status', 20)->default('none')->index();
            $table->foreignUuid('conversion_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('conversion_requested_at')->nullable();
            $table->foreignUuid('conversion_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('conversion_reviewed_at')->nullable();
            $table->text('conversion_rejection_reason')->nullable();
            $table->foreignUuid('converted_company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignUuid('converted_brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pic_id', 'status']);
        });

        Schema::create('prospect_marketplace_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_prospect_id')
                ->constrained('business_prospects')
                ->cascadeOnDelete();
            $table->string('marketplace', 50);
            $table->string('url', 2048);
            $table->char('normalized_url_hash', 64)->unique();
            $table->timestamps();

            $table->index(['business_prospect_id', 'marketplace'], 'prospect_links_prospect_marketplace_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_marketplace_links');
        Schema::dropIfExists('business_prospects');
    }
};
