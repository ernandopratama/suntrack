<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            return;
        }

        $type = Schema::getColumnType('personal_access_tokens', 'tokenable_id');
        if (in_array($type, ['uuid', 'string'], true)) {
            return;
        }

        if (DB::table('personal_access_tokens')->exists()) {
            throw new RuntimeException(
                'Cannot convert personal_access_tokens.tokenable_id to UUID while token rows exist.'
            );
        }

        $this->dropTokenableIndex();

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE personal_access_tokens MODIFY tokenable_id CHAR(36) NOT NULL');
        }

        $this->createTokenableIndex();
    }

    public function down(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            return;
        }

        $type = Schema::getColumnType('personal_access_tokens', 'tokenable_id');
        if (! in_array($type, ['uuid', 'string'], true)) {
            return;
        }

        if (DB::table('personal_access_tokens')->exists()) {
            throw new RuntimeException(
                'Cannot restore bigint tokenable IDs while UUID token rows exist.'
            );
        }

        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $this->dropTokenableIndex();

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE personal_access_tokens MODIFY tokenable_id BIGINT UNSIGNED NOT NULL');
        }

        $this->createTokenableIndex();
    }

    private function dropTokenableIndex(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex(['tokenable_type', 'tokenable_id']);
        });
    }

    private function createTokenableIndex(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }
};
