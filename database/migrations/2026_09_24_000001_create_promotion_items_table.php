<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('promotion_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->decimal('normal_price', 15, 2);
            $table->decimal('discount_price', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('discount_percentage', 7, 4)->default(0);
            $table->unsignedInteger('promotion_stock')->nullable();
            $table->unsignedInteger('purchase_limit')->nullable();
            $table->string('approval_status', 50)->default('Pending');
            $table->text('rejection_notes')->nullable();
            $table->timestamps();

            $table->index(['promotion_id', 'product_name']);
        });

        Schema::table('approval_histories', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
        });

        Schema::table('approval_histories', function (Blueprint $table) {
            $table->uuid('variant_id')->nullable()->change();
            $table->foreign('variant_id')->references('id')->on('variants')->restrictOnDelete();
            $table->foreignUuid('promotion_item_id')
                ->nullable()
                ->after('variant_id')
                ->constrained('promotion_items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('approval_histories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_item_id');
        });

        DB::table('approval_histories')->whereNull('variant_id')->delete();

        Schema::table('approval_histories', function (Blueprint $table) {
            $table->dropForeign(['variant_id']);
        });

        Schema::table('approval_histories', function (Blueprint $table) {
            $table->uuid('variant_id')->nullable(false)->change();
            $table->foreign('variant_id')->references('id')->on('variants')->restrictOnDelete();
        });

        Schema::dropIfExists('promotion_items');
    }
};
