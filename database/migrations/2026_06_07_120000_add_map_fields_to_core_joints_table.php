<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_joints', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('network_node_id');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->foreignId('odc_id')->nullable()->after('longitude')->constrained('odcs')->nullOnDelete();
            $table->foreignId('odp_id')->nullable()->after('odc_id')->constrained('odps')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('core_joints', function (Blueprint $table) {
            $table->dropForeign(['odc_id']);
            $table->dropForeign(['odp_id']);
            $table->dropColumn(['latitude', 'longitude', 'odc_id', 'odp_id']);
        });
    }
};
