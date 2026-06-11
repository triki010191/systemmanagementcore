<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $splitterNodes = DB::table('network_nodes')->where('type', 'splitter')->get();

        foreach ($splitterNodes as $splitter) {
            DB::table('network_nodes')
                ->where('parent_id', $splitter->id)
                ->where('type', 'odp')
                ->update(['parent_id' => $splitter->parent_id]);
        }

        if (Schema::hasColumn('customer_connections', 'splitter_port_id')) {
            Schema::table('customer_connections', function (Blueprint $table) {
                $table->dropForeign(['splitter_port_id']);
                $table->dropIndex(['splitter_port_id']);
                $table->dropColumn('splitter_port_id');
            });
        }

        DB::table('network_links')->where('link_type', 'splitter_connection')->delete();

        if (Schema::hasColumn('network_links', 'source_port_id')) {
            Schema::table('network_links', function (Blueprint $table) {
                $table->dropForeign(['source_port_id']);
                $table->dropForeign(['target_port_id']);
                $table->dropColumn(['source_port_id', 'target_port_id']);
            });
        }

        Schema::dropIfExists('splitter_ports');
        Schema::dropIfExists('splitters');

        DB::table('network_nodes')->where('type', 'splitter')->delete();

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE network_nodes MODIFY COLUMN type ENUM('pop', 'olt', 'otb', 'odc', 'odp', 'customer') NOT NULL");
            DB::statement("ALTER TABLE network_links MODIFY COLUMN link_type ENUM('fiber_cable', 'patch_cord', 'splice', 'drop') NOT NULL");
        }

        Schema::table('odcs', function (Blueprint $table) {
            $table->unsignedSmallInteger('cores_from_otb')->default(0)->after('split_ratio_default');
            $table->unsignedSmallInteger('cores_to_odp')->default(0)->after('cores_from_otb');
        });

        Schema::table('odps', function (Blueprint $table) {
            $table->unsignedSmallInteger('cores_from_odc')->default(0)->after('port_used');
        });
    }

    public function down(): void
    {
        Schema::table('odps', function (Blueprint $table) {
            $table->dropColumn('cores_from_odc');
        });

        Schema::table('odcs', function (Blueprint $table) {
            $table->dropColumn(['cores_from_otb', 'cores_to_odp']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE network_nodes MODIFY COLUMN type ENUM('pop', 'olt', 'otb', 'odc', 'splitter', 'odp', 'customer') NOT NULL");
            DB::statement("ALTER TABLE network_links MODIFY COLUMN link_type ENUM('fiber_cable', 'patch_cord', 'splitter_connection', 'splice', 'drop') NOT NULL");
        }
    }
};
