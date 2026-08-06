<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('secure_links', function (Blueprint $table) {
            $table->dateTime('last_accessed_at')->nullable()->after('revoked_at');
            $table->unsignedInteger('view_count')->default(0)->after('last_accessed_at');
        });
    }

    public function down(): void
    {
        Schema::table('secure_links', function (Blueprint $table) {
            $table->dropColumn(['last_accessed_at', 'view_count']);
        });
    }
};
