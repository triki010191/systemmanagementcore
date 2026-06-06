<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otdr_records', function (Blueprint $table) {
            $table->decimal('fault_distance_km', 8, 3)->nullable()->after('distance_km');
            $table->text('notes')->nullable()->after('event_points');
        });
    }

    public function down(): void
    {
        Schema::table('otdr_records', function (Blueprint $table) {
            $table->dropColumn(['fault_distance_km', 'notes']);
        });
    }
};
