<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §4.1 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiber_cables', function (Blueprint $table) {
            $table->string('manufacturer', 100)->nullable()->after('name');
            $table->string('fiber_type', 50)->nullable()->after('manufacturer');
            $table->string('jacket_rating', 50)->nullable()->after('fiber_type');
            $table->date('installed_at')->nullable()->after('length_meters');
            $table->string('qr_code_path')->nullable()->after('installed_at');
        });

        DB::statement('ALTER TABLE fiber_cables MODIFY core_count SMALLINT UNSIGNED NOT NULL COMMENT "12, 24, 48, or 96"');
    }

    public function down(): void
    {
        Schema::table('fiber_cables', function (Blueprint $table) {
            $table->dropColumn(['manufacturer', 'fiber_type', 'jacket_rating', 'installed_at', 'qr_code_path']);
        });
    }
};
