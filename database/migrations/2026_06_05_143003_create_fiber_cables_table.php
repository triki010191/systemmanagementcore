<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventaris kabel fiber fisik. Mendukung core 12/24/48 dan routing GIS.
 *
 * @see docs/migrations/2026_06_05_143003_create_fiber_cables_table.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiber_cables', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('cable_type', ['backbone', 'distribution', 'drop'])->default('distribution');
            $table->unsignedSmallInteger('core_count')->comment('12, 24, or 48');
            $table->unsignedTinyInteger('tube_count')->comment('Derived from core_count / 12');
            $table->decimal('length_meters', 10, 2)->nullable();
            $table->json('route_geometry')->nullable()->comment('GeoJSON LineString for GIS map');
            $table->foreignId('start_node_id')->nullable()->constrained('network_nodes')->nullOnDelete();
            $table->foreignId('end_node_id')->nullable()->constrained('network_nodes')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->enum('status', ['active', 'inactive', 'damaged', 'planned'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('cable_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiber_cables');
    }
};
