<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('visual_link', 500)->nullable()->after('creative_brief');
            $table->string('visual_file_path', 500)->nullable()->after('visual_link');
            $table->string('visual_file_name', 255)->nullable()->after('visual_file_path');
            $table->string('submitted_by', 255)->nullable()->after('visual_file_name');
            $table->dateTime('submitted_at')->nullable()->after('submitted_by');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['visual_link', 'visual_file_path', 'visual_file_name', 'submitted_by', 'submitted_at']);
        });
    }
};
