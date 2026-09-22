<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedSmallInteger('recurrence_interval')->default(1)->after('recurrence_type');
            $table->time('recurrence_time')->nullable()->after('recurrence_interval');
            $table->json('recurrence_weekdays')->nullable()->after('recurrence_time');
            $table->string('recurrence_month_day', 10)->nullable()->after('recurrence_weekdays');
            $table->unsignedInteger('recurrence_max_occurrences')->nullable()->after('recurrence_ends_at');
            $table->unsignedInteger('recurrence_generated_count')->default(0)->after('recurrence_max_occurrences');
        });
    }

    public function down(): void
    {
        $hasConfiguredSchedules = DB::table('tasks')
            ->where(function ($query) {
                $query->where('recurrence_interval', '!=', 1)
                    ->orWhereNotNull('recurrence_time')
                    ->orWhereNotNull('recurrence_weekdays')
                    ->orWhereNotNull('recurrence_month_day')
                    ->orWhereNotNull('recurrence_max_occurrences')
                    ->orWhere('recurrence_generated_count', '>', 0);
            })
            ->exists();

        if ($hasConfiguredSchedules) {
            throw new RuntimeException('Rollback dihentikan karena terdapat konfigurasi jadwal pengulangan yang masih digunakan.');
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'recurrence_interval',
                'recurrence_time',
                'recurrence_weekdays',
                'recurrence_month_day',
                'recurrence_max_occurrences',
                'recurrence_generated_count',
            ]);
        });
    }
};
