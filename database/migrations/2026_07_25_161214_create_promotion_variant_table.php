<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_variant', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('promotion_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('variant_id')->constrained()->restrictOnDelete();
            $table->decimal('campaign_price', 12, 2);
            $table->decimal('discount_price', 12, 2);
            $table->integer('promotion_stock')->default(0);
            $table->integer('purchase_limit')->default(0);
            $table->string('approval_status', 50)->default('Pending');
            $table->text('rejection_notes')->nullable();
            $table->timestamps();
            
            $table->unique(['promotion_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_variant');
    }
};
