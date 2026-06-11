<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §4.3 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiber_cores', function (Blueprint $table) {
            $table->foreignId('cable_tube_id')->nullable()->after('cable_id')
                ->constrained('cable_tubes')->nullOnDelete();
            $table->unsignedTinyInteger('core_position_in_tube')->nullable()->after('tube_number');
            $table->decimal('loss_db', 5, 2)->nullable()->after('color_name');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE fiber_cores MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'available'");
        }

        DB::table('fiber_cores')->where('status', 'active')->update(['status' => 'used']);
        DB::table('fiber_cores')->where('status', 'spare')->update(['status' => 'available']);
        DB::table('fiber_cores')->where('status', 'dark')->update(['status' => 'available']);
        DB::table('fiber_cores')->where('status', 'fault')->update(['status' => 'broken']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE fiber_cores MODIFY COLUMN status ENUM('available','used','reserved','broken','maintenance') NOT NULL DEFAULT 'available'");
        }
    }

    public function down(): void
    {
        Schema::table('fiber_cores', function (Blueprint $table) {
            $table->dropForeign(['cable_tube_id']);
            $table->dropColumn(['cable_tube_id', 'core_position_in_tube', 'loss_db']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE fiber_cores MODIFY COLUMN status ENUM('active','spare','dark','fault') NOT NULL DEFAULT 'spare'");
        }
    }
};
