<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add promotion code and description to the promotions table.
     * The code follows the format: PRM-YYYYMM-XXXX (auto-generated on model creation).
     */
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('code', 30)->unique()->nullable()->after('id');
            $table->text('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['code', 'description']);
        });
    }
};
