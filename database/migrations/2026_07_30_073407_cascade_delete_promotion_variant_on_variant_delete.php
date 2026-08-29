<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('promotion_variant', function (Blueprint $table) {
            $table->dropForeign('promotion_variant_variant_id_foreign');
            $table->foreign('variant_id')->references('id')->on('variants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('promotion_variant', function (Blueprint $table) {
            $table->dropForeign('promotion_variant_variant_id_foreign');
            $table->foreign('variant_id')->references('id')->on('variants')->restrictOnDelete();
        });
    }
};
