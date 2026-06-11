<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** @see docs/DATABASE_DESIGN.md §6.1 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('splitter_ports', function (Blueprint $table) {
            $table->foreignId('splitter_id')->nullable()->after('id')
                ->constrained('splitters')->cascadeOnDelete();
            $table->foreignId('connected_core_id')->nullable()->after('label')
                ->constrained('fiber_cores')->nullOnDelete();
        });

        Schema::table('splitter_ports', function (Blueprint $table) {
            $table->dropForeign(['splitter_node_id']);
            $table->dropUnique(['splitter_node_id', 'port_number', 'direction']);
            $table->dropIndex(['splitter_node_id', 'direction', 'status']);
            $table->dropColumn('splitter_node_id');
        });

        Schema::table('splitter_ports', function (Blueprint $table) {
            $table->unique(['splitter_id', 'port_number', 'direction']);
            $table->index(['splitter_id', 'direction', 'status']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE splitter_ports MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'empty'");
        }

        DB::table('splitter_ports')->where('status', 'spare')->update(['status' => 'empty']);
        DB::table('splitter_ports')->where('status', 'fault')->update(['status' => 'broken']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE splitter_ports MODIFY COLUMN status ENUM('empty','active','reserved','broken','maintenance') NOT NULL DEFAULT 'empty'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE splitter_ports MODIFY COLUMN status ENUM('active','spare','fault') NOT NULL DEFAULT 'spare'");
        }

        Schema::table('splitter_ports', function (Blueprint $table) {
            $table->dropForeign(['connected_core_id']);
            $table->dropForeign(['splitter_id']);
            $table->dropUnique(['splitter_id', 'port_number', 'direction']);
            $table->dropIndex(['splitter_id', 'direction', 'status']);
            $table->dropColumn(['splitter_id', 'connected_core_id']);
            $table->foreignId('splitter_node_id')->after('id')->constrained('network_nodes')->cascadeOnDelete();
            $table->unique(['splitter_node_id', 'port_number', 'direction']);
            $table->index(['splitter_node_id', 'direction', 'status']);
        });
    }
};
