<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('odc_splitter_outputs', function (Blueprint $table) {
            $table->enum('route_mode', ['direct', 'via_joint'])->default('direct')->after('target_node_id');
            $table->foreignId('downstream_cable_id')->nullable()->after('outbound_core_number')->constrained('fiber_cables')->nullOnDelete();
            $table->unsignedSmallInteger('downstream_core_number')->nullable()->after('downstream_cable_id');
        });
    }

    public function down(): void
    {
        Schema::table('odc_splitter_outputs', function (Blueprint $table) {
            $table->dropForeign(['downstream_cable_id']);
            $table->dropColumn(['route_mode', 'downstream_cable_id', 'downstream_core_number']);
        });
    }
};
